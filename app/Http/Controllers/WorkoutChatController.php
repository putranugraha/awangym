<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\WorkoutChatMemory;
use App\Services\WorkoutChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class WorkoutChatController extends Controller
{
    public function history(Request $request, WorkoutChatMemory $memory): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        return response()->json(['turns' => $memory->recent($user, 12)])
            ->header('Cache-Control', 'no-store');
    }

    public function __invoke(Request $request, WorkoutChatService $chat, WorkoutChatMemory $memory): JsonResponse
    {
        $validated = $request->validate(['message' => ['required', 'string', 'max:1000', 'not_regex:/^[\s\p{Z}\p{C}]*$/u']]);
        $user = $request->user();
        abort_unless($user instanceof User, 401);
        try {
            $history = $memory->recent($user, 8);
            $result = $chat->reply(trim($validated['message']), $history);
            $user->workoutChatTurns()->create([
                'user_message' => trim($validated['message']),
                'assistant_message' => $result['message'],
                'status' => $result['status'],
            ]);
        } catch (Throwable) {
            // Do not leak credentials, upstream bodies, or user questions into errors/logs.
            return response()->json(['message' => 'Aku belum bisa merespons sekarang. Coba kirim lagi sebentar, ya. 🙂'], 503)->header('Cache-Control', 'no-store');
        }

        return response()->json($result)->header('Cache-Control', 'no-store');
    }
}
