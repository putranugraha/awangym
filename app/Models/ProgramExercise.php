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
        $host = strtolower($parts['host'] ?? '');
        $path = trim($parts['path'] ?? '', '/');

        if (in_array($host, ['youtube.com', 'www.youtube.com', 'm.youtube.com'], true)) {
            parse_str($parts['query'] ?? '', $query);
            $videoId = $query['v'] ?? (str_starts_with($path, 'embed/') ? substr($path, 6) : null);

            return filled($videoId) ? 'https://www.youtube.com/embed/'.rawurlencode($videoId) : null;
        }

        if ($host === 'youtu.be' && filled($path)) {
            return 'https://www.youtube.com/embed/'.rawurlencode(explode('/', $path)[0]);
        }

        if (in_array($host, ['vimeo.com', 'www.vimeo.com', 'player.vimeo.com'], true)) {
            $videoId = str_starts_with($path, 'video/') ? substr($path, 6) : explode('/', $path)[0];

            return filled($videoId) && ctype_digit($videoId) ? 'https://player.vimeo.com/video/'.$videoId : null;
        }

        return null;
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
