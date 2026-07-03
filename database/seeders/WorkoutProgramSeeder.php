<?php

namespace Database\Seeders;

use App\Models\Exercise;
use App\Models\ProgramExercise;
use App\Models\WorkoutProgram;
use Illuminate\Database\Seeder;

class WorkoutProgramSeeder extends Seeder
{
    public function run(): void
    {
        $exercises = Exercise::whereNotNull('exercise_code')->get()->keyBy('exercise_code');

        $beginner = WorkoutProgram::updateOrCreate(
            ['program_code' => 'GYM-BEG-001'],
            [
                'program_name' => 'Gym Beginner',
                'target_goal' => 'Adaptasi latihan dan penguasaan teknik dasar',
                'difficulty_level' => 'beginner',
                'duration_weeks' => 8,
                'description' => 'Program tiga hari per minggu untuk mengenalkan pola squat, push, pull, hip hinge, dan core dengan alat gym umum.',
                'source_name' => 'The World’s Fittest Book',
                'source_reference' => 'Adaptasi program bodyweight dan strength untuk gym pemula',
                'program_status' => 'active',
            ]
        );

        $beginnerSessions = [
            1 => ['Full Body A', [
                ['EX-GOBLET-SQUAT', 3, '10', 75],
                ['EX-INCLINE-PUSHUP', 3, '8–12', 60],
                ['EX-SEATED-CABLE-ROW', 3, '10–12', 60],
                ['EX-PLANK', 3, null, 45, 1],
            ]],
            3 => ['Full Body B', [
                ['EX-DB-RDL', 3, '10', 75],
                ['EX-DB-SHOULDER-PRESS', 3, '10', 60],
                ['EX-LAT-PULLDOWN', 3, '10–12', 60],
                ['EX-STEP-UP', 3, '8 setiap kaki', 60],
            ]],
            5 => ['Full Body A', [
                ['EX-GOBLET-SQUAT', 3, '10', 75],
                ['EX-INCLINE-PUSHUP', 3, '8–12', 60],
                ['EX-SEATED-CABLE-ROW', 3, '10–12', 60],
                ['EX-PLANK', 3, null, 45, 1],
            ]],
        ];
        $this->seedWeeklySchedule($beginner, $beginnerSessions, 8, $exercises);

        $strength = WorkoutProgram::updateOrCreate(
            ['program_code' => 'GYM-STR-001'],
            [
                'program_name' => 'Gym Strength',
                'target_goal' => 'Meningkatkan kekuatan gerakan dasar',
                'difficulty_level' => 'intermediate',
                'duration_weeks' => 12,
                'description' => 'Program bertahap berbasis bench press, squat, deadlift, dan overhead press untuk member yang telah menguasai teknik dasar.',
                'source_name' => 'The World’s Fittest Book',
                'source_reference' => 'Adaptasi Your 12-Week Strength Workout',
                'program_status' => 'active',
            ]
        );

        $strengthSessions = [
            1 => ['Bench Press', [['EX-BENCH-PRESS', 5, '5', 120, null, 'Beban menantang dengan teknik stabil'], ['EX-INCLINE-BENCH', 3, '10', 75]]],
            3 => ['Squat', [['EX-SQUAT', 5, '5', 120, null, 'Beban menantang dengan teknik stabil'], ['EX-FRONT-SQUAT', 3, '8', 90], ['EX-FORWARD-LUNGE', 3, '10 setiap kaki', 60]]],
            5 => ['Deadlift dan Pull', [['EX-DEADLIFT', 3, '5', 120, null, 'Beban menantang dengan teknik stabil'], ['EX-BENT-ROW', 3, '8–10', 75], ['EX-PULLUP', 3, 'Semampunya', 90], ['EX-FARMERS-WALK', 4, '20 meter', 60]]],
        ];
        $this->seedWeeklySchedule($strength, $strengthSessions, 12, $exercises);
    }

    private function seedWeeklySchedule(WorkoutProgram $program, array $sessions, int $weeks, $exercises): void
    {
        for ($week = 1; $week <= $weeks; $week++) {
            foreach ($sessions as $dayOfWeek => [$sessionName, $items]) {
                $trainingDay = (($week - 1) * 7) + $dayOfWeek;
                foreach ($items as $index => $item) {
                    [$code, $sets, $repetitions, $restSeconds, $durationMinutes, $intensity] = array_pad($item, 6, null);
                    ProgramExercise::updateOrCreate(
                        [
                            'program_id' => $program->program_id,
                            'training_day' => $trainingDay,
                            'sequence_order' => $index + 1,
                        ],
                        [
                            'exercise_id' => $exercises->get($code)->exercise_id,
                            'session_name' => $sessionName,
                            'sets' => $sets,
                            'repetitions' => $repetitions,
                            'duration_minutes' => $durationMinutes,
                            'rest_seconds' => $restSeconds,
                            'intensity' => $intensity,
                            'notes' => null,
                        ]
                    );
                }
            }
        }
    }
}
