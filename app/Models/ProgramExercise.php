<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProgramExercise extends Model
{
    protected $primaryKey = 'program_exercise_id';

    public $timestamps = false;

    protected $fillable = ['program_id', 'exercise_id', 'training_day', 'session_name', 'sequence_order', 'sets', 'repetitions', 'duration_minutes', 'rest_seconds', 'intensity', 'notes', 'link'];

    public static function toEmbedUrl(?string $link): ?string
    {
        if (blank($link)) {
            return null;
        }

        $parts = parse_url($link);
        $scheme = strtolower($parts['scheme'] ?? '');
        $host = strtolower($parts['host'] ?? '');
        $path = trim($parts['path'] ?? '', '/');

        if (! in_array($scheme, ['http', 'https'], true) || blank($host)) {
            return null;
        }

        if (in_array($host, ['youtube.com', 'www.youtube.com', 'm.youtube.com', 'www.youtube-nocookie.com'], true)) {
            parse_str($parts['query'] ?? '', $query);
            $segments = explode('/', $path);
            $videoId = $query['v'] ?? $query['vi'] ?? match ($segments[0] ?? null) {
                'embed', 'shorts', 'live' => $segments[1] ?? null,
                default => null,
            };

            return filled($videoId) ? 'https://www.youtube.com/embed/'.rawurlencode($videoId) : null;
        }

        if ($host === 'youtu.be' && filled($path)) {
            return 'https://www.youtube.com/embed/'.rawurlencode(explode('/', $path)[0]);
        }

        if (in_array($host, ['vimeo.com', 'www.vimeo.com', 'player.vimeo.com'], true)) {
            $videoId = collect(explode('/', $path))->reverse()->first(fn ($segment) => ctype_digit($segment));

            return filled($videoId) && ctype_digit($videoId) ? 'https://player.vimeo.com/video/'.$videoId : null;
        }

        return $link;
    }

    public function embedUrl(): ?string
    {
        return self::toEmbedUrl($this->link);
    }

    public function program()
    {
        return $this->belongsTo(WorkoutProgram::class, 'program_id');
    }

    public function exercise()
    {
        return $this->belongsTo(Exercise::class, 'exercise_id');
    }

    public function checks()
    {
        return $this->hasMany(MemberExerciseCheck::class, 'program_exercise_id');
    }
}
