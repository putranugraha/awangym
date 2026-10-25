<?php

namespace App\Services;

use App\Models\WorkoutProgram;
use Illuminate\Support\Str;

class WorkoutCatalogKnowledge
{
    /**
     * @param  list<array{user: string, assistant: string}>  $history
     * @return array{local: bool, matches: list<WorkoutProgram>, sources: list<array<string, mixed>>}
     */
    public function retrieve(string $message, array $history = []): array
    {
        // Only shared workout catalog fields. Never load member, payment or trainer records.
        $programs = WorkoutProgram::query()->where('program_status', 'active')->orderBy('program_id')
            ->limit(50)->get(['program_id', 'program_code', 'program_name', 'difficulty_level', 'target_goal', 'duration_weeks', 'description']);
        $matches = $this->match(array_values($programs->all()), $message);
        $normalized = $this->normalize($message);
        $explicitLocal = (bool) preg_match('/gym ini|gym kami|di gym|di sini|disini|web|aplikasi|awan|katalog/iu', $message);
        $exactName = collect($matches)->contains(fn ($program) => str_contains($normalized, $this->normalize($program->program_name)));
        if ($this->bookQuestion($message) && ! $explicitLocal && ! $exactName) {
            return ['local' => false, 'matches' => [], 'sources' => []];
        }
        if ($matches === []) {
            foreach (array_reverse(array_slice($history, -8)) as $turn) {
                // A new explicitly named program takes precedence over previous conversation.
                if (preg_match('/beginner|pemula|strength|kekuatan|intermediate|menengah|advanced|lanjutan/iu', $message)) {
                    break;
                }
                if ($this->bookQuestion($turn['user'])) {
                    break;
                }
                $matches = $this->match(array_values($programs->all()), $turn['user']);
                if ($matches !== []) {
                    break;
                }
            }
        }
        $local = $explicitLocal || $matches !== [] || (bool) preg_match('/\bprogram\b/iu', $message);
        $sources = [];
        foreach ($matches !== [] ? $matches : $programs->all() as $program) {
            $sources[] = $this->overview($program);
        }
        if (preg_match('/jadwal|gerakan|latihan|set|rep|istirahat|sesi|hari|minggu\s*\d|fase/iu', $message)) {
            $context = $message.' '.implode(' ', array_reverse(array_column($history, 'user')));
            preg_match('/(?:minggu|week)\s*(\d{1,2})(?!\d|\s*(?:hari|days))/iu', $context, $weekMatch);
            $week = max(1, (int) ($weekMatch[1] ?? 1));
            foreach (array_slice($matches, 0, 2) as $program) {
                if ($week > $program->duration_weeks) {
                    continue;
                }
                $items = $program->exercises()->with('exercise')
                    ->whereBetween('training_day', [($week - 1) * 7 + 1, $week * 7])->limit(150)->get();
                if ($items->isEmpty()) {
                    continue;
                }
                $rows = [];
                foreach ($items as $item) {
                    if (! $item->exercise || $item->exercise->exercise_status !== 'active') {
                        continue;
                    }
                    $row = 'Hari '.(($item->training_day - 1) % 7 + 1).' — '.$item->session_name.': '.$item->exercise->exercise_name;
                    foreach (['sets' => 'set', 'repetitions' => 'repetisi', 'duration_minutes' => 'menit', 'rest_seconds' => 'istirahat detik', 'intensity' => 'intensitas', 'notes' => 'catatan'] as $field => $label) {
                        if (filled($item->{$field})) {
                            $row .= '; '.$label.': '.$item->{$field};
                        }
                    }
                    if (filled($item->exercise->instruction)) {
                        $row .= '; instruksi: '.$item->exercise->instruction;
                    }
                    $rows[] = strip_tags($row);
                }
                if ($rows !== []) {
                    $sources[] = [
                        'id' => 'catalog-program-'.$program->program_id.'-week-'.$week,
                        'source_type' => 'database', 'priority' => 100,
                        'document_id' => 'gym-catalog', 'document_title' => 'Katalog resmi Awan Gym',
                        'title' => $program->program_name.' — jadwal minggu '.$week,
                        'excerpt' => implode("\n", $rows),
                        'warnings' => ['This is the stored gym schedule. Do not substitute PDF sets/repetitions, add absent exercises or invent phases.'],
                    ];
                }
            }
        }

        return ['local' => $local, 'matches' => $matches, 'sources' => $sources];
    }

    /**
     * @param  array{local: bool, matches: list<WorkoutProgram>, sources: list<array<string, mixed>>}  $catalog
     * @return array<string, mixed>|null
     */
    public function durationAnswer(string $message, array $catalog): ?array
    {
        if (! $catalog['local'] || ! preg_match('/durasi|berapa\s+(?:minggu|lama|bulan)|berapa\s+lama|lama\s+program|berlangsung/iu', $message)
            || preg_match('/buku|pdf|fittest|ross|banding|beda|jadwal|gerakan|set\b|repetisi|rpe|rekomendasi|saran|cocok|bagaimana|kenapa|mengapa/iu', $message)) {
            return null;
        }
        if (count($catalog['matches']) !== 1) {
            return ['status' => 'clarification', 'mode' => 'catalog', 'message' => 'Kamu maksud program yang mana di gym ini? Sebutkan nama programnya supaya durasinya tepat. 💪'];
        }
        $program = $catalog['matches'][0];
        if ($program->duration_weeks < 1) {
            return ['status' => 'insufficient', 'mode' => 'catalog', 'message' => 'Durasi program itu belum tercatat dengan jelas di katalog gym. Aku belum bisa memastikan jumlah minggunya. 💪'];
        }

        return [
            'status' => 'answered', 'mode' => 'catalog',
            'message' => 'Program **'.strip_tags($program->program_name).'** di gym ini berlangsung **'.$program->duration_weeks.' minggu**. 💪',
        ];
    }

    /** @return array<string, mixed> */
    private function overview(WorkoutProgram $program): array
    {
        return [
            'id' => 'catalog-program-'.$program->program_id,
            'source_type' => 'database', 'priority' => 100,
            'document_id' => 'gym-catalog', 'document_title' => 'Katalog resmi Awan Gym',
            'title' => $program->program_name,
            'facts' => ['program_name' => $program->program_name, 'duration_weeks' => $program->duration_weeks, 'difficulty_level' => $program->difficulty_level],
            'excerpt' => strip_tags($program->program_name."\nKode: ".$program->program_code."\nDurasi resmi: ".$program->duration_weeks." minggu\nTingkat: ".$program->difficulty_level."\nTujuan: ".$program->target_goal."\nDeskripsi: ".$program->description),
            'warnings' => ['Authoritative live database record for this gym program. Structured facts fields override descriptive text, PDF programs and old assistant answers. Missing fields must not be filled with book phases.'],
        ];
    }

    /**
     * @param  list<WorkoutProgram>  $programs
     * @return list<WorkoutProgram>
     */
    private function match(array $programs, string $question): array
    {
        $question = $this->normalize($question);
        $question = str_replace(['pemula', 'kekuatan', 'menengah', 'lanjutan'], ['beginner', 'strength', 'intermediate', 'advanced'], $question);
        $scores = [];
        foreach ($programs as $program) {
            $name = $this->normalize($program->program_name);
            $code = $this->normalize($program->program_code ?? '');
            $score = str_contains($question, $name) ? 100 : 0;
            if ($code !== '' && str_contains($question, $code)) {
                $score += 100;
            }
            foreach (preg_split('/[^a-z0-9]+/', $name, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $term) {
                if (strlen($term) >= 3 && ! in_array($term, ['gym', 'program', 'latihan'], true)
                    && preg_match('/\b'.preg_quote($term, '/').'\b/u', $question)) {
                    $score += 10;
                }
            }
            if (str_contains($question, $program->difficulty_level)) {
                $score += 5;
            }
            if ($score > 0) {
                $scores[] = ['program' => $program, 'score' => $score];
            }
        }
        $max = max(array_column($scores, 'score') ?: [0]);

        return array_values(array_map(fn ($row) => $row['program'], array_filter($scores, fn ($row) => $row['score'] === $max)));
    }

    private function bookQuestion(string $message): bool
    {
        return (bool) preg_match('/\bbuku\b|\bpdf\b|fittest|ross|upper.?lower|4\s*(?:hari|days)|program latihan 12/iu', $message);
    }

    private function normalize(string $value): string
    {
        return mb_strtolower(Str::ascii($value));
    }
}
