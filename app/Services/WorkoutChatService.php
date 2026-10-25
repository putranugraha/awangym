<?php

namespace App\Services;

use Illuminate\Support\Str;
use RuntimeException;

class WorkoutChatService
{
    public function __construct(private WorkoutChatResponder $responder, private WorkoutBookKnowledge $book, private WorkoutCatalogKnowledge $catalog) {}

    /**
     * @param  list<array{user: string, assistant: string}>  $history
     * @return array<string, mixed>
     */
    public function reply(string $message, array $history = []): array
    {
        $normalized = mb_strtolower(Str::ascii($message));
        if (preg_match('/^(halo|hai|hi|hello|pagi|siang|sore|malam|terima kasih|makasih)[!.\s]*$/u', $normalized)) {
            return ['status' => 'greeting', 'mode' => 'ai', 'message' => 'Hai! Mau bahas tujuan latihan, jadwal, atau teknik gerakan? Ceritakan yang ingin kamu pahami, ya. 💪'];
        }
        // Personal measurements, weight-loss goals and limitations are valid workout context.
        // Leave their interpretation to the conversational scope check instead of word bans.
        if (preg_match('/\b(politik|presiden|pemilu|agama|judi|saham|crypto|kripto|bitcoin|masakan|cuaca|coding|javascript|python|password|kata sandi|pembayaran|invoice|gaji|salary|steroid|diagnosis|diagnosa)\b|dosis\s+obat|(?:buat(?:kan)?|susun|hitung).{0,30}(?:diet|kalori|menu makanan)|(?:abaikan|lupakan|ignore|bypass).{0,60}(?:aturan|instruksi|batasan|instructions|rules)|system\s*prompt|developer\s*message|berperan\s+sebagai|act\s+as|https?:\/\/|www\./u', $normalized)) {
            return ['status' => 'out_of_scope', 'mode' => 'ai', 'message' => 'Aku fokus membantu soal program latihan, ya. Mau bahas jadwal, teknik gerakan, atau progresi latihanmu? 💪'];
        }
        $catalog = $this->catalog->retrieve($message, $history);
        $durationAnswer = $this->catalog->durationAnswer($message, $catalog);
        if ($durationAnswer !== null) {
            return $durationAnswer;
        }
        $key = match (config('workout-chat.provider')) {
            'openai' => config('workout-chat.api_key'),
            'gemini' => config('workout-chat.gemini_api_key'),
            default => throw new RuntimeException('Unsupported workout chat provider.'),
        };
        if (blank($key)) {
            throw new RuntimeException('Workout chat API key missing.');
        }

        return $this->responder->reply($message, $history, [...$catalog['sources'], ...$this->book->retrieve($message, $history)]);
    }
}
