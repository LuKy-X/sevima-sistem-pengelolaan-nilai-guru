<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\AssessmentColumn;
use App\Models\Classroom;
use App\Models\Gradebook;
use App\Models\Score;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Minimal 1 Guru (User)
        $teacher = User::factory()->create([
            'name' => 'Budi Santoso, S.Kom.',
            'email' => 'guru@sekolah.id',
            'nip' => '198507152010011012',
            'password' => Hash::make('password'),
        ]);

        // 2. 1 Academic Year
        $academicYear = AcademicYear::create([
            'name' => '2026/2027',
            'is_active' => true,
        ]);

        // 3. 1 Classroom
        $classroom = Classroom::create([
            'name' => 'X RPL 1',
            'level' => 10,
        ]);

        // 4. 1 Subject
        $subject = Subject::create([
            'name' => 'Pemrograman Web dan Perangkat Bergerak',
            'code' => 'PWPB',
        ]);

        // 5. 25 Siswa terdaftar pada kelas dan tahun ajaran tersebut
        $students = Student::factory()->count(25)->create();
        $classroom->students()->attach(
            $students->pluck('id'),
            ['academic_year_id' => $academicYear->id]
        );

        // 6. 1 Contoh Gradebook
        $gradebook = Gradebook::create([
            'user_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'subject_id' => $subject->id,
            'academic_year_id' => $academicYear->id,
            'semester' => 'ganjil',
            'title' => 'Buku Nilai PWPB Kelas X RPL 1 - Semester Ganjil',
        ]);

        // 7. Beberapa Assessment Columns (Dynamic Columns)
        $columnTugas1 = AssessmentColumn::create([
            'gradebook_id' => $gradebook->id,
            'name' => 'Tugas 1: Dasar HTML & CSS',
            'type' => 'tugas',
            'weight' => 15.00,
            'max_score' => 100.00,
            'order' => 1,
        ]);

        $columnUH1 = AssessmentColumn::create([
            'gradebook_id' => $gradebook->id,
            'name' => 'UH 1: Sintaks PHP & Web Server',
            'type' => 'uh',
            'weight' => 20.00,
            'max_score' => 100.00,
            'order' => 2,
        ]);

        $columnTugas2 = AssessmentColumn::create([
            'gradebook_id' => $gradebook->id,
            'name' => 'Tugas 2: Desain Tailwind CSS',
            'type' => 'tugas',
            'weight' => 15.00,
            'max_score' => 100.00,
            'order' => 3,
        ]);

        $columnProject = AssessmentColumn::create([
            'gradebook_id' => $gradebook->id,
            'name' => 'Project: Mini Web App',
            'type' => 'project',
            'weight' => 25.00,
            'max_score' => 100.00,
            'order' => 4,
        ]);

        $columnPAS = AssessmentColumn::create([
            'gradebook_id' => $gradebook->id,
            'name' => 'PAS (Penilaian Akhir Semester)',
            'type' => 'ujian',
            'weight' => 25.00,
            'max_score' => 100.00,
            'order' => 5,
        ]);

        // 8. Beberapa Sample Scores (Nilai Siswa)
        $scoreEntries = [];
        $columns = [$columnTugas1, $columnUH1, $columnTugas2, $columnProject, $columnPAS];

        foreach ($students as $index => $student) {
            // Berikan nilai untuk Tugas 1 dan UH 1 pada semua siswa
            $scoreEntries[] = [
                'assessment_column_id' => $columnTugas1->id,
                'student_id' => $student->id,
                'score' => rand(75, 95),
                'notes' => 'Pengerjaan tepat waktu',
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $scoreEntries[] = [
                'assessment_column_id' => $columnUH1->id,
                'student_id' => $student->id,
                'score' => rand(70, 98),
                'notes' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            // Sebagian siswa sudah mengumpulkan Tugas 2 & Project
            if ($index < 20) {
                $scoreEntries[] = [
                    'assessment_column_id' => $columnTugas2->id,
                    'student_id' => $student->id,
                    'score' => rand(80, 100),
                    'notes' => 'Desain rapi dan responsif',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            if ($index < 15) {
                $scoreEntries[] = [
                    'assessment_column_id' => $columnProject->id,
                    'student_id' => $student->id,
                    'score' => rand(85, 98),
                    'notes' => 'Fitur lengkap dan fungsional',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        Score::insert($scoreEntries);
    }
}
