<?php

namespace App\Services;

use App\Models\User;
use App\Models\WorkoutChatTurn;

class WorkoutChatMemory
{
    /** @return list<array{user: string, assistant: string}> */
    public function recent(User $user, int $limit): array
    {
        return array_values($user->workoutChatTurns()->latest('id')->limit($limit)->get()
            ->reverse()->values()->map(fn (WorkoutChatTurn $turn): array => [
                'user' => $turn->user_message,
                'assistant' => $turn->assistant_message,
            ])->all());
    }
}
