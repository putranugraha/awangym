<?php

namespace App\Services;

use Illuminate\Support\Str;
use RuntimeException;

class WorkoutBookKnowledge
{
    /** @var list<array<string, mixed>>|null */
    private ?array $pages = null;

    /**
     * @param  list<array{user: string, assistant: string}>  $history
     * @return list<array<string, mixed>>
     */
    public function retrieve(string $message, array $history = []): array
    {
        $current = $this->terms($message);
        $previous = $this->terms(implode(' ', array_reverse(array_column(array_slice($history, -8), 'user'))));
        $context = $message.' '.implode(' ', array_reverse(array_column($history, 'user')));
        // "Program 12 minggu 4 hari" names a split, not a request for week 4.
        preg_match('/(?:minggu|week)\s*(\d{1,2})(?!\d|\s*(?:hari|days))/iu', $context, $weekMatch);
        $week = isset($weekMatch[1]) ? (int) $weekMatch[1] : null;
        $ranked = [];
        foreach ($this->pages() as $page) {
            if (! $page['eligible']) {
                continue;
            }
            $body = $this->normalize($page['excerpt']);
            $heading = $this->normalize($page['title'].' '.$page['keywords']);
            $score = 0;
            foreach ([[$current, 3], [$previous, 1]] as [$terms, $weight]) {
                foreach ($terms as $term) {
                    $score += $weight * (min(substr_count($body, $term), 3) + (str_contains($heading, $term) ? 4 : 0));
                }
            }
            if ($week !== null && is_array($page['phase'])) {
                $score += $week >= $page['phase'][0] && $week <= $page['phase'][1] ? 18 : -30;
            }
            if ($page['document_id'] === 'worlds-fittest-book' && in_array($page['pdf_page'], [3, 12, 52, 62, 66], true)) {
                $score += 2;
            }
            if ($page['document_id'] === 'worlds-fittest-book' && preg_match('/rekomendasi|cocok|pemula|baru mulai/iu', $context) && $page['pdf_page'] === 61) {
                $score += 12;
            }
            if ($page['document_id'] === 'program-latihan-12-minggu' && preg_match('/4\s*(?:hari|days)|upper.?lower|rpe|rom|program latihan 12/iu', $context)) {
                $score += 35;
            }
            if ($page['document_id'] === 'worlds-fittest-book' && preg_match('/fittest|ross edgley|snatch|marathon|maraton|tree.athlon/iu', $context)) {
                $score += 30;
            }
            $ranked[] = ['page' => $page, 'score' => $score];
        }
        usort($ranked, fn ($a, $b) => $b['score'] <=> $a['score']);
        $selected = array_column(array_slice($ranked, 0, 8), 'page');
        if (preg_match('/rekomendasi|cocok|pemula|baru mulai|tinggi\s*\d|berat\s*\d|waktu|alat/iu', $context)) {
            // Recommendations need the program overview and training-age principles,
            // even when personal numbers or equipment dominate the search terms.
            foreach ([52, 61] as $number) {
                $foundation = collect($this->pages())->where('document_id', 'worlds-fittest-book')->firstWhere('pdf_page', $number);
                if ($foundation && ! in_array($foundation['id'], array_column($selected, 'id'), true)) {
                    $selected[] = $foundation;
                }
            }
        }
        // Read related passages together to preserve context and documented discrepancies.
        $related = [46 => [47], 48 => [49], 49 => [50], 56 => [57, 58], 58 => [59], 60 => [61], 61 => [60], 62 => [65], 65 => [62]];
        foreach (array_slice($selected, 0, 3) as $page) {
            if ($page['document_id'] === 'program-latihan-12-minggu') {
                $other = collect($this->pages())->where('document_id', $page['document_id'])->firstWhere('pdf_page', 5);
                if ($other && ! in_array($other['id'], array_column($selected, 'id'), true)) {
                    $selected[] = $other;
                }

                continue;
            }
            foreach ($related[$page['pdf_page']] ?? [] as $number) {
                $other = collect($this->pages())->where('document_id', $page['document_id'])->firstWhere('pdf_page', $number);
                if ($other && ! in_array($other['id'], array_column($selected, 'id'), true)) {
                    $selected[] = $other;
                }
            }
        }

        return array_map(fn ($page) => [
            'id' => $page['id'], 'title' => $page['title'], 'excerpt' => $page['excerpt'],
            'warnings' => $page['warnings'],
            'document_id' => $page['document_id'], 'document_title' => $page['document_title'],
        ], $selected);
    }

    /** @return list<array<string, mixed>> */
    private function pages(): array
    {
        if ($this->pages !== null) {
            return $this->pages;
        }
        $additional = config('workout-chat.additional_book_indexes', []);
        if (! is_array($additional)) {
            throw new RuntimeException('Invalid workout book configuration.');
        }
        $paths = [config('workout-chat.book_index'), ...$additional];
        $pages = [];
        $seen = [];
        foreach ($paths as $path) {
            if (! is_string($path) || ! is_file($path)) {
                throw new RuntimeException('Workout book index missing.');
            }
            $contents = file_get_contents($path);
            if ($contents === false) {
                throw new RuntimeException('Workout book index unreadable.');
            }
            $index = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
            if (! is_array($index) || ! is_array($index['pages'] ?? null)
                || ! is_array($index['source'] ?? null) || ! is_string($index['source']['title'] ?? null)
                || ! is_string($index['source']['id'] ?? null)) {
                throw new RuntimeException('Invalid workout book index.');
            }
            foreach ($index['pages'] as $page) {
                if (! is_array($page) || ! is_string($page['id'] ?? null) || ! is_string($page['excerpt'] ?? null)
                    || ! is_string($page['title'] ?? null) || ! is_string($page['keywords'] ?? null)
                    || ! is_bool($page['eligible'] ?? null) || ! is_array($page['warnings'] ?? null)
                    || ! is_int($page['pdf_page'] ?? null) || ! array_key_exists('phase', $page)
                    || ($page['document_id'] ?? null) !== $index['source']['id'] || isset($seen[$page['id']])) {
                    throw new RuntimeException('Invalid workout book passage.');
                }
                $seen[$page['id']] = true;
                $page['document_title'] = $index['source']['title'];
                $pages[] = $page;
            }
        }

        return $this->pages = $pages;
    }

    /** @return list<string> */
    private function terms(string $message): array
    {
        $text = $this->normalize($message);
        $aliases = [
            'kekuatan' => 'strength', 'kuat' => 'strength', 'pemula' => 'beginner technique',
            'baru mulai' => 'beginner technique', 'jadwal' => 'routine workout weeks',
            'seminggu' => 'routine week', 'berapa kali' => 'routine week',
            'istirahat' => 'rest recovery', 'pemulihan' => 'recovery', 'daya tahan' => 'endurance',
            'daya ledak' => 'power', 'kecepatan' => 'speed', 'lari' => 'running',
            'maraton' => 'marathon', 'renang' => 'swimming', 'sepeda' => 'cycling',
            'dayung' => 'rowing', 'perut' => 'core ab', 'otot' => 'muscle strength',
            'progresi' => 'progression overload', 'tambah beban' => 'progressive overload',
            'teknik' => 'technique coaching', 'repetisi' => 'reps repetitions',
            'dada' => 'bench press', 'jongkok' => 'squat', 'latihan berat badan' => 'bodyweight',
            'beban tubuh' => 'bodyweight', 'tanpa alat' => 'bodyweight',
            'rekomendasi' => 'beginner technique training age', 'cocok' => 'beginner technique',
            'turun berat' => 'consistency endurance technique', 'menurunkan berat' => 'consistency endurance technique',
            'di rumah' => 'bodyweight', 'dirumah' => 'bodyweight', 'waktu' => 'routine',
            'minggu' => 'weeks', 'gerakan dasar' => 'technique strength',
        ];
        foreach ($aliases as $phrase => $expansion) {
            if (str_contains($text, $phrase)) {
                $text .= ' '.$expansion;
            }
        }
        $tokens = preg_split('/[^a-z0-9]+/', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $stop = ['aku', 'saya', 'kamu', 'yang', 'itu', 'tadi', 'apa', 'untuk', 'dari', 'bisa', 'ingin', 'mau', 'baru', 'mulai', 'latihan', 'program', 'tolong', 'jelaskan', 'berapa', 'bagaimana', 'dengan', 'dan', 'ini', 'buku', 'the', 'world', 'fittest', 'book', 'apakah', 'kenapa', 'supaya', 'kalau', 'juga'];

        return array_values(array_unique(array_filter($tokens, fn ($term) => strlen($term) >= 3 && ! in_array($term, $stop, true))));
    }

    private function normalize(string $text): string
    {
        return mb_strtolower(Str::ascii($text));
    }
}
