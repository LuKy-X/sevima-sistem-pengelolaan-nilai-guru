<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\AssessmentColumn;
use App\Models\Classroom;
use App\Models\Gradebook;
use App\Models\Rubric;
use App\Models\Score;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GradebookExportAndProtectionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 1. Teacher can export gradebook to CSV with standard school gradebook metadata.
     */
    public function test_teacher_can_export_gradebook_to_csv_with_school_metadata(): void
    {
        $teacher = User::factory()->create([
            'name' => 'Dra. Siti Aminah, M.Pd.',
            'nip' => '198501152010012005',
        ]);

        $classroom = Classroom::factory()->create(['name' => 'XII RPL 1']);
        $academicYear = AcademicYear::factory()->create(['name' => '2025/2026']);

        $studentA = Student::factory()->create(['name' => 'Ahmad Faiz', 'nisn' => '0012345678']);
        $studentB = Student::factory()->create(['name' => 'Bintang Pratama', 'nisn' => '0087654321']);

        $classroom->students()->attach([$studentA->id, $studentB->id], ['academic_year_id' => $academicYear->id]);

        $gradebook = Gradebook::factory()->create([
            'user_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'academic_year_id' => $academicYear->id,
            'semester' => 'ganjil',
        ]);

        // Column 1: Non-rubric column
        $col1 = AssessmentColumn::factory()->create([
            'gradebook_id' => $gradebook->id,
            'name' => 'Ulangan Harian 1',
            'weight' => 40.00,
            'max_score' => 100.00,
        ]);

        // Column 2: Rubric-managed column
        $col2 = AssessmentColumn::factory()->create([
            'gradebook_id' => $gradebook->id,
            'name' => 'Proyek Web Portofolio',
            'weight' => 60.00,
            'max_score' => 100.00,
        ]);

        Rubric::factory()->create([
            'assessment_column_id' => $col2->id,
            'name' => 'Rubrik Portofolio',
        ]);

        // Assign some scores
        Score::create(['assessment_column_id' => $col1->id, 'student_id' => $studentA->id, 'score' => 85]);
        Score::create(['assessment_column_id' => $col2->id, 'student_id' => $studentA->id, 'score' => 90]);
        Score::create(['assessment_column_id' => $col1->id, 'student_id' => $studentB->id, 'score' => 75]);
        Score::create(['assessment_column_id' => $col2->id, 'student_id' => $studentB->id, 'score' => 80]);

        $response = $this->actingAs($teacher)->get(route('gradebooks.export', $gradebook));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('attachment; filename="buku-nilai-', $response->headers->get('Content-Disposition'));

        $content = $response->streamedContent();

        $this->assertStringContainsString('BUKU NILAI HASIL BELAJAR SISWA', $content);
        $this->assertStringContainsString('Mata Pelajaran', $content);
        $this->assertStringContainsString('XII RPL 1', $content);
        $this->assertStringContainsString('Dra. Siti Aminah, M.Pd.', $content);
        $this->assertStringContainsString('198501152010012005', $content);
        $this->assertStringContainsString('Ulangan Harian 1', $content);
        $this->assertStringContainsString('Proyek Web Portofolio [Rubrik]', $content);
        $this->assertStringContainsString('Ahmad Faiz', $content);
        $this->assertStringContainsString('Bintang Pratama', $content);
        $this->assertStringContainsString('Rata-rata Kelas', $content);
        $this->assertStringContainsString('Mengetahui,', $content);
        $this->assertStringContainsString('Guru Mata Pelajaran', $content);
    }

    /**
     * 2. Unauthorized teacher or guest cannot export gradebook.
     */
    public function test_unauthorized_teacher_cannot_export_gradebook(): void
    {
        $owner = User::factory()->create();
        $otherTeacher = User::factory()->create();

        $gradebook = Gradebook::factory()->create(['user_id' => $owner->id]);

        // Other teacher receives 403 Forbidden
        $response = $this->actingAs($otherTeacher)->get(route('gradebooks.export', $gradebook));
        $response->assertStatus(403);

        // Guest is redirected to login
        auth()->logout();
        $guestResponse = $this->get(route('gradebooks.export', $gradebook));
        $guestResponse->assertRedirect(route('login'));
    }

    /**
     * 3. Gradebook show page displays back button and export button.
     */
    public function test_gradebook_show_displays_back_and_export_buttons(): void
    {
        $teacher = User::factory()->create();
        $gradebook = Gradebook::factory()->create(['user_id' => $teacher->id]);

        $response = $this->actingAs($teacher)->get(route('gradebooks.show', $gradebook));

        $response->assertStatus(200);
        $response->assertSee(route('gradebooks.index'));
        $response->assertSee('Kembali ke Buku Nilai');
        $response->assertSee(route('gradebooks.export', $gradebook));
        $response->assertSee('Ekspor Buku Nilai');
    }

    /**
     * 4. Rubric-managed column is locked in UI and protected from direct matrix score updates.
     */
    public function test_rubric_managed_column_is_locked_on_matrix_and_protected_from_direct_score_updates(): void
    {
        $teacher = User::factory()->create();
        $classroom = Classroom::factory()->create();
        $academicYear = AcademicYear::factory()->create();

        $student = Student::factory()->create(['name' => 'Dimas Anggara']);
        $classroom->students()->attach($student->id, ['academic_year_id' => $academicYear->id]);

        $gradebook = Gradebook::factory()->create([
            'user_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'academic_year_id' => $academicYear->id,
        ]);

        $regularCol = AssessmentColumn::factory()->create([
            'gradebook_id' => $gradebook->id,
            'name' => 'Tugas Mandiri',
            'weight' => 50.00,
            'max_score' => 100.00,
        ]);

        $rubricCol = AssessmentColumn::factory()->create([
            'gradebook_id' => $gradebook->id,
            'name' => 'Praktikum Robotika',
            'weight' => 50.00,
            'max_score' => 100.00,
        ]);

        Rubric::factory()->create([
            'assessment_column_id' => $rubricCol->id,
            'name' => 'Rubrik Robotika',
        ]);

        // Pre-existing scores (e.g. calculated via rubric)
        Score::create([
            'assessment_column_id' => $rubricCol->id,
            'student_id' => $student->id,
            'score' => 95.00,
        ]);

        // Check show UI:
        $response = $this->actingAs($teacher)->get(route('gradebooks.show', $gradebook));
        $response->assertStatus(200);

        // Regular column input is rendered with name attribute
        $response->assertSee("scores[{$student->id}][{$regularCol->id}]");

        // Rubric column input is NOT rendered with name attribute (preventing direct submit)
        $response->assertDontSee("scores[{$student->id}][{$rubricCol->id}]");

        // Rubric column has locked indicator / badge
        $response->assertSee('Nilai dikelola via Rubrik Penilaian');
        $response->assertSee('rubric-locked-score');

        // Now test direct POST submission to scores.store attempting to tamper or override rubric column
        $postData = [
            'scores' => [
                $student->id => [
                    $regularCol->id => 80.00, // Should be updated
                    $rubricCol->id => 50.00,  // Attempted tamper / direct override: must be IGNORED
                ],
            ],
        ];

        $postResponse = $this->actingAs($teacher)->post(route('gradebooks.scores.store', $gradebook), $postData);
        $postResponse->assertRedirect(route('gradebooks.show', $gradebook));

        // Verify database:
        // Regular column updated to 80.00
        $this->assertDatabaseHas('scores', [
            'assessment_column_id' => $regularCol->id,
            'student_id' => $student->id,
            'score' => 80.00,
        ]);

        // Rubric column score REMAINS 95.00 and is NOT overridden to 50.00!
        $this->assertDatabaseHas('scores', [
            'assessment_column_id' => $rubricCol->id,
            'student_id' => $student->id,
            'score' => 95.00,
        ]);
        $this->assertDatabaseMissing('scores', [
            'assessment_column_id' => $rubricCol->id,
            'student_id' => $student->id,
            'score' => 50.00,
        ]);
    }
}
