<?php

namespace Database\Seeders;

use App\Models\Exercise;
use Illuminate\Database\Seeder;

class ExerciseCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $exercises = [
            ['EX-GOBLET-SQUAT', 'Goblet Squat', 'Lower Body', 'Dumbbell atau kettlebell', 'Latihan squat dasar dengan beban di depan dada.', 'Buka kaki selebar bahu, tahan beban di depan dada, turunkan pinggul dengan punggung netral, lalu berdiri melalui tumit.'],
            ['EX-INCLINE-PUSHUP', 'Incline Push-up', 'Push', 'Bench', 'Variasi push-up untuk pemula dengan tangan pada bench.', 'Jaga tubuh membentuk garis lurus, turunkan dada mendekati bench, lalu dorong kembali tanpa mengangkat pinggul.'],
            ['EX-SEATED-CABLE-ROW', 'Seated Cable Row', 'Pull', 'Cable machine', 'Gerakan tarik untuk punggung tengah.', 'Duduk stabil, tarik handle menuju perut sambil merapatkan tulang belikat, lalu kembalikan dengan terkendali.'],
            ['EX-PLANK', 'Plank', 'Core', 'Tanpa alat', 'Latihan stabilitas inti tubuh.', 'Tumpukan siku di bawah bahu, kencangkan perut dan bokong, lalu pertahankan tubuh tetap lurus.'],
            ['EX-DB-RDL', 'Dumbbell Romanian Deadlift', 'Lower Body', 'Dumbbell', 'Latihan pola hip hinge untuk hamstring dan glute.', 'Dorong pinggul ke belakang dengan lutut sedikit menekuk, turunkan dumbbell dekat kaki, lalu berdiri dengan mengencangkan glute.'],
            ['EX-DB-SHOULDER-PRESS', 'Dumbbell Shoulder Press', 'Push', 'Dumbbell', 'Latihan dorong vertikal untuk bahu.', 'Mulai dengan dumbbell di tinggi bahu, kencangkan inti tubuh, dorong ke atas, lalu turunkan perlahan.'],
            ['EX-LAT-PULLDOWN', 'Lat Pulldown', 'Pull', 'Lat pulldown machine', 'Latihan tarik vertikal untuk punggung.', 'Tarik bar ke bagian atas dada tanpa mengayun, rapatkan tulang belikat, lalu luruskan lengan secara terkendali.'],
            ['EX-STEP-UP', 'Step-up', 'Lower Body', 'Bench atau box', 'Latihan kaki unilateral menggunakan pijakan.', 'Letakkan satu kaki penuh di atas pijakan, dorong tubuh naik melalui tumit, kemudian turun perlahan dan berganti sisi.'],
            ['EX-BENCH-PRESS', 'Bench Press', 'Push', 'Barbell dan bench', 'Gerakan utama kekuatan dada dan trisep.', 'Posisikan tubuh stabil di bench, turunkan bar menuju dada dengan kontrol, lalu dorong hingga lengan kembali lurus.'],
            ['EX-SQUAT', 'Barbell Back Squat', 'Lower Body', 'Barbell dan squat rack', 'Gerakan utama kekuatan tubuh bagian bawah.', 'Letakkan bar stabil di punggung atas, turunkan pinggul dengan lutut mengikuti arah kaki, lalu berdiri kuat.'],
            ['EX-DEADLIFT', 'Deadlift', 'Pull', 'Barbell', 'Gerakan hip hinge untuk kekuatan posterior chain.', 'Mulai dengan bar dekat tulang kering, kunci punggung netral, dorong lantai dan berdiri tanpa menarik dengan punggung bawah.'],
            ['EX-INCLINE-BENCH', 'Incline Bench Press', 'Push', 'Barbell dan incline bench', 'Variasi bench press untuk dada bagian atas.', 'Atur bench miring, turunkan bar ke dada atas dengan kontrol, lalu dorong kembali tanpa kehilangan posisi bahu.'],
            ['EX-BENT-ROW', 'Bent-over Row', 'Pull', 'Barbell', 'Latihan tarik horizontal dengan barbell.', 'Lakukan hip hinge dengan punggung netral, tarik bar menuju perut, lalu turunkan tanpa mengubah posisi torso.'],
            ['EX-FRONT-SQUAT', 'Front Squat', 'Lower Body', 'Barbell dan squat rack', 'Variasi squat dengan bar di depan bahu.', 'Jaga siku tinggi dan torso tegak, turun dengan kontrol, kemudian berdiri melalui seluruh telapak kaki.'],
            ['EX-OHP', 'Standing Overhead Press', 'Push', 'Barbell', 'Gerakan dorong vertikal untuk bahu dan trisep.', 'Kencangkan tubuh, dorong bar dari bahu ke atas kepala dalam jalur lurus, kemudian turunkan secara terkendali.'],
            ['EX-PULLUP', 'Pull-up', 'Pull', 'Pull-up bar', 'Latihan tarik vertikal menggunakan berat badan.', 'Mulai dari posisi menggantung aktif, tarik dada menuju bar tanpa mengayun, lalu turun hingga lengan lurus.'],
            ['EX-FORWARD-LUNGE', 'Forward Lunge', 'Lower Body', 'Dumbbell opsional', 'Latihan unilateral untuk kaki dan keseimbangan.', 'Langkahkan satu kaki ke depan, turunkan kedua lutut dengan stabil, lalu dorong kembali ke posisi awal.'],
            ['EX-FARMERS-WALK', 'Farmer’s Walk', 'Full Body', 'Dumbbell atau kettlebell', 'Latihan membawa beban untuk grip dan stabilitas.', 'Berdiri tegak dengan beban di kedua sisi, berjalan dengan langkah terkontrol tanpa memiringkan tubuh.'],
        ];

        foreach ($exercises as [$code, $name, $category, $equipment, $description, $instruction]) {
            Exercise::updateOrCreate(
                ['exercise_code' => $code],
                [
                    'exercise_name' => $name,
                    'category' => $category,
                    'equipment' => $equipment,
                    'description' => $description,
                    'instruction' => $instruction,
                    'exercise_status' => 'active',
                ]
            );
        }
    }
}
