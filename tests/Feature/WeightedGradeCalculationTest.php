<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\AssessmentColumn;
use App\Models\Classroom;
use App\Models\Gradebook;
use App\Models\Score;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WeightedGradeCalculationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 1. Weighted calculation with all scores present.
     * Example from prompt:
     * Tugas 1 = 15% (score 80)
     * UH 1    = 20% (score 90)
     * Project = 25% (score 80)
     * PAS     = 40% (score 90)
     * Final score = (80*15 + 90*20 + 80*25 + 90*40) / 100 = 86.0
     */
    public function test_weighted_calculation_with_all_scores_present(): void
    {
        $teacher = User::factory()->create();
        $classroom = Classroom::factory()->create();
        $academicYear = AcademicYear::factory()->create();

        $student = Student::factory()->create(['name' => 'Ahmad Dahlan']);
        $classroom->students()->attach($student->id, ['academic_year_id' => $academicYear->id]);

        $gradebook = Gradebook::factory()->create([
            'user_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'academic_year_id' => $academicYear->id,
        ]);

        $colTugas = AssessmentColumn::factory()->create([
            'gradebook_id' => $gradebook->id,
            'name' => 'Tugas 1',
            'weight' => 15.00,
            'max_score' => 100.00,
        ]);
        $colUH = AssessmentColumn::factory()->create([
            'gradebook_id' => $gradebook->id,
            'name' => 'UH 1',
            'weight' => 20.00,
            'max_score' => 100.00,
        ]);
        $colProject = AssessmentColumn::factory()->create([
            'gradebook_id' => $gradebook->id,
            'name' => 'Project',
            'weight' => 25.00,
            'max_score' => 100.00,
        ]);
        $colPAS = AssessmentColumn::factory()->create([
            'gradebook_id' => $gradebook->id,
            'name' => 'PAS',
            'weight' => 40.00,
            'max_score' => 100.00,
        ]);

        Score::create(['assessment_column_id' => $colTugas->id, 'student_id' => $student->id, 'score' => 80.00]);
        Score::create(['assessment_column_id' => $colUH->id, 'student_id' => $student->id, 'score' => 90.00]);
        Score::create(['assessment_column_id' => $colProject->id, 'student_id' => $student->id, 'score' => 80.00]);
        Score::create(['assessment_column_id' => $colPAS->id, 'student_id' => $student->id, 'score' => 90.00]);

        $finalGrade = $gradebook->calculateStudentFinalGrade($student->id);
        $this->assertEquals(86.00, $finalGrade);

        $summary = $gradebook->getStudentGradeSummary($student->id);
        $this->assertEquals(86.00, $summary['final_score']);
        $this->assertTrue($summary['is_complete']);
        $this->assertEquals(100.00, $summary['completed_weight']);
        $this->assertEquals(4, $summary['completed_count']);

        // Verify HTML displays the final score and Lengkap status
        $response = $this->actingAs($teacher)->get(route('gradebooks.show', $gradebook));
        $response->assertStatus(200);
        $response->assertSee('86');
        $response->assertSee('Lengkap');
    }

    /**
     * 2. Weighted calculation when max_score is not 100 (Normalization).
     * Example from prompt:
     * score = 40, max_score = 50 -> normalized = 80
     * Column 1: score 40 / 50 (weight 50%) -> norm 80
     * Column 2: score 90 / 100 (weight 50%) -> norm 90
     * Final score = (80*50 + 90*50) / 100 = 85.0
     */
    public function test_weighted_calculation_when_max_score_is_not_100(): void
    {
        $teacher = User::factory()->create();
        $classroom = Classroom::factory()->create();
        $academicYear = AcademicYear::factory()->create();

        $student = Student::factory()->create(['name' => 'Budi Santoso']);
        $classroom->students()->attach($student->id, ['academic_year_id' => $academicYear->id]);

        $gradebook = Gradebook::factory()->create([
            'user_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'academic_year_id' => $academicYear->id,
        ]);

        $col1 = AssessmentColumn::factory()->create([
            'gradebook_id' => $gradebook->id,
            'name' => 'Kuis Pendek',
            'weight' => 50.00,
            'max_score' => 50.00,
        ]);

        $col2 = AssessmentColumn::factory()->create([
            'gradebook_id' => $gradebook->id,
            'name' => 'Ujian Akhir',
            'weight' => 50.00,
            'max_score' => 100.00,
        ]);

        Score::create(['assessment_column_id' => $col1->id, 'student_id' => $student->id, 'score' => 40.00]);
        Score::create(['assessment_column_id' => $col2->id, 'student_id' => $student->id, 'score' => 90.00]);

        $finalGrade = $gradebook->calculateStudentFinalGrade($student->id);
        $this->assertEquals(85.00, $finalGrade);

        $summary = $gradebook->getStudentGradeSummary($student->id);
        $this->assertEquals(85.00, $summary['final_score']);
        $this->assertTrue($summary['is_complete']);
    }

    /**
     * 3. Missing score handling (empty scores do not become zero, calculated on available weights).
     */
    public function test_missing_score_handling_does_not_count_as_zero_and_uses_available_weights(): void
    {
        $teacher = User::factory()->create();
        $classroom = Classroom::factory()->create();
        $academicYear = AcademicYear::factory()->create();

        $student = Student::factory()->create(['name' => 'Citra Lestari']);
        $classroom->students()->attach($student->id, ['academic_year_id' => $academicYear->id]);

        $gradebook = Gradebook::factory()->create([
            'user_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'academic_year_id' => $academicYear->id,
        ]);

        $col1 = AssessmentColumn::factory()->create([
            'gradebook_id' => $gradebook->id,
            'weight' => 20.00,
            'max_score' => 100.00,
        ]);
        $col2 = AssessmentColumn::factory()->create([
            'gradebook_id' => $gradebook->id,
            'weight' => 30.00,
            'max_score' => 100.00,
        ]);
        $col3 = AssessmentColumn::factory()->create([
            'gradebook_id' => $gradebook->id,
            'weight' => 50.00,
            'max_score' => 100.00,
        ]);

        // Student only has scores for col1 (80) and col2 (90). col3 has NO score.
        Score::create(['assessment_column_id' => $col1->id, 'student_id' => $student->id, 'score' => 80.00]);
        Score::create(['assessment_column_id' => $col2->id, 'student_id' => $student->id, 'score' => 90.00]);

        // Expected calculation: (80*20 + 90*30) / (20 + 30) = (1600 + 2700) / 50 = 4300 / 50 = 86.0
        // If col3 had been counted as zero, it would be 4300 / 100 = 43.0 (which would be misleading!)
        $finalGrade = $gradebook->calculateStudentFinalGrade($student->id);
        $this->assertEquals(86.00, $finalGrade);

        $summary = $gradebook->getStudentGradeSummary($student->id);
        $this->assertFalse($summary['is_complete']);
        $this->assertEquals(50.00, $summary['completed_weight']);
        $this->assertEquals(2, $summary['completed_count']);
        $this->assertEquals(3, $summary['total_columns']);
    }

    /**
     * 4. Partial assessment completion displays provisional badge in UI.
     */
    public function test_partial_assessment_completion_displays_provisional_badge_in_ui(): void
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

        $col1 = AssessmentColumn::factory()->create([
            'gradebook_id' => $gradebook->id,
            'weight' => 40.00,
            'max_score' => 100.00,
        ]);
        $col2 = AssessmentColumn::factory()->create([
            'gradebook_id' => $gradebook->id,
            'weight' => 60.00,
            'max_score' => 100.00,
        ]);

        // Only col1 is filled
        Score::create(['assessment_column_id' => $col1->id, 'student_id' => $student->id, 'score' => 75.00]);

        $response = $this->actingAs($teacher)->get(route('gradebooks.show', $gradebook));
        $response->assertStatus(200);
        $response->assertSee('Sementara (40%)');
        $response->assertSee('75');
    }

    /**
     * 5. Zero score handling (score 0 is a valid score and must participate with its weight).
     */
    public function test_zero_score_handling_participates_in_calculation(): void
    {
        $teacher = User::factory()->create();
        $classroom = Classroom::factory()->create();
        $academicYear = AcademicYear::factory()->create();

        $student = Student::factory()->create(['name' => 'Eka Putri']);
        $classroom->students()->attach($student->id, ['academic_year_id' => $academicYear->id]);

        $gradebook = Gradebook::factory()->create([
            'user_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'academic_year_id' => $academicYear->id,
        ]);

        $col1 = AssessmentColumn::factory()->create([
            'gradebook_id' => $gradebook->id,
            'weight' => 50.00,
            'max_score' => 100.00,
        ]);
        $col2 = AssessmentColumn::factory()->create([
            'gradebook_id' => $gradebook->id,
            'weight' => 50.00,
            'max_score' => 100.00,
        ]);

        // Student scored 0 on col1 and 100 on col2
        Score::create(['assessment_column_id' => $col1->id, 'student_id' => $student->id, 'score' => 0.00]);
        Score::create(['assessment_column_id' => $col2->id, 'student_id' => $student->id, 'score' => 100.00]);

        // Final score: (0*50 + 100*50) / 100 = 50.0
        $finalGrade = $gradebook->calculateStudentFinalGrade($student->id);
        $this->assertEquals(50.00, $finalGrade);

        $summary = $gradebook->getStudentGradeSummary($student->id);
        $this->assertTrue($summary['is_complete']);
        $this->assertEquals(100.00, $summary['completed_weight']);
    }

    /**
     * 6. Multiple assessment weights with different scales.
     */
    public function test_multiple_assessment_weights_with_different_scales(): void
    {
        $teacher = User::factory()->create();
        $classroom = Classroom::factory()->create();
        $academicYear = AcademicYear::factory()->create();

        $student = Student::factory()->create(['name' => 'Fajar Pratama']);
        $classroom->students()->attach($student->id, ['academic_year_id' => $academicYear->id]);

        $gradebook = Gradebook::factory()->create([
            'user_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'academic_year_id' => $academicYear->id,
        ]);

        // Col 1: max 20, score 15 -> normalized 75, weight 10%
        $col1 = AssessmentColumn::factory()->create([
            'gradebook_id' => $gradebook->id,
            'weight' => 10.00,
            'max_score' => 20.00,
        ]);
        // Col 2: max 50, score 45 -> normalized 90, weight 30%
        $col2 = AssessmentColumn::factory()->create([
            'gradebook_id' => $gradebook->id,
            'weight' => 30.00,
            'max_score' => 50.00,
        ]);
        // Col 3: max 100, score 80 -> normalized 80, weight 60%
        $col3 = AssessmentColumn::factory()->create([
            'gradebook_id' => $gradebook->id,
            'weight' => 60.00,
            'max_score' => 100.00,
        ]);

        Score::create(['assessment_column_id' => $col1->id, 'student_id' => $student->id, 'score' => 15.00]);
        Score::create(['assessment_column_id' => $col2->id, 'student_id' => $student->id, 'score' => 45.00]);
        Score::create(['assessment_column_id' => $col3->id, 'student_id' => $student->id, 'score' => 80.00]);

        // Expected: (75*10 + 90*30 + 80*60) / 100 = (750 + 2700 + 4800) / 100 = 8250 / 100 = 82.5
        $finalGrade = $gradebook->calculateStudentFinalGrade($student->id);
        $this->assertEquals(82.50, $finalGrade);
    }

    /**
     * 7. Student with no scores returns null and displays dash.
     */
    public function test_student_with_no_scores_returns_null_and_displays_dash(): void
    {
        $teacher = User::factory()->create();
        $classroom = Classroom::factory()->create();
        $academicYear = AcademicYear::factory()->create();

        $student = Student::factory()->create(['name' => 'Gina Safitri']);
        $classroom->students()->attach($student->id, ['academic_year_id' => $academicYear->id]);

        $gradebook = Gradebook::factory()->create([
            'user_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'academic_year_id' => $academicYear->id,
        ]);

        AssessmentColumn::factory()->create([
            'gradebook_id' => $gradebook->id,
            'weight' => 50.00,
            'max_score' => 100.00,
        ]);

        $this->assertNull($gradebook->calculateStudentFinalGrade($student->id));

        $summary = $gradebook->getStudentGradeSummary($student->id);
        $this->assertNull($summary['final_score']);
        $this->assertFalse($summary['is_complete']);
        $this->assertEquals(0, $summary['completed_weight']);

        $response = $this->actingAs($teacher)->get(route('gradebooks.show', $gradebook));
        $response->assertStatus(200);
        $response->assertSee('Gina Safitri');
    }

    /**
     * 8. Creating an assessment column that causes total weight to exceed 100% is rejected.
     */
    public function test_store_assessment_column_rejects_total_weight_exceeding_100_percent(): void
    {
        $teacher = User::factory()->create();
        $gradebook = Gradebook::factory()->create(['user_id' => $teacher->id]);

        AssessmentColumn::factory()->create([
            'gradebook_id' => $gradebook->id,
            'weight' => 70.00,
        ]);

        // Attempt to create second column with weight 40% (total 110%)
        $response = $this->actingAs($teacher)->post(route('gradebooks.columns.store', $gradebook), [
            'name' => 'Tugas Tambahan',
            'type' => 'tugas',
            'weight' => 40.00,
            'max_score' => 100.00,
            'order' => 2,
        ]);

        $response->assertSessionHasErrors(['weight']);
        $this->assertDatabaseMissing('assessment_columns', ['name' => 'Tugas Tambahan']);

        // Attempt with weight 30% (total exactly 100%) succeeds
        $responseSuccess = $this->actingAs($teacher)->post(route('gradebooks.columns.store', $gradebook), [
            'name' => 'Tugas Pas 100',
            'type' => 'tugas',
            'weight' => 30.00,
            'max_score' => 100.00,
            'order' => 2,
        ]);

        $responseSuccess->assertRedirect(route('gradebooks.show', $gradebook));
        $this->assertDatabaseHas('assessment_columns', ['name' => 'Tugas Pas 100']);
    }

    /**
     * 9. Updating an assessment column that causes total weight to exceed 100% is rejected.
     */
    public function test_update_assessment_column_rejects_total_weight_exceeding_100_percent(): void
    {
        $teacher = User::factory()->create();
        $gradebook = Gradebook::factory()->create(['user_id' => $teacher->id]);

        $col1 = AssessmentColumn::factory()->create([
            'gradebook_id' => $gradebook->id,
            'weight' => 60.00,
        ]);

        $col2 = AssessmentColumn::factory()->create([
            'gradebook_id' => $gradebook->id,
            'weight' => 30.00,
        ]);

        // Updating col2 to 50% would make total 110% -> must be rejected
        $response = $this->actingAs($teacher)->put(route('gradebooks.columns.update', [$gradebook, $col2]), [
            'name' => $col2->name,
            'type' => $col2->type,
            'weight' => 50.00,
            'max_score' => 100.00,
            'order' => 2,
        ]);

        $response->assertSessionHasErrors(['weight']);
        $this->assertEquals(30.00, $col2->fresh()->weight);

        // Updating col2 to 40% (total exactly 100%) succeeds
        $responseSuccess = $this->actingAs($teacher)->put(route('gradebooks.columns.update', [$gradebook, $col2]), [
            'name' => $col2->name,
            'type' => $col2->type,
            'weight' => 40.00,
            'max_score' => 100.00,
            'order' => 2,
        ]);

        $responseSuccess->assertRedirect(route('gradebooks.show', $gradebook));
        $this->assertEquals(40.00, $col2->fresh()->weight);
    }

    /**
     * 10. Unauthorized teacher cannot view another teacher's gradebook calculation.
     */
    public function test_unauthorized_teacher_cannot_view_or_access_gradebook_calculation(): void
    {
        $teacherA = User::factory()->create();
        $teacherB = User::factory()->create();

        $gradebook = Gradebook::factory()->create(['user_id' => $teacherA->id]);

        $response = $this->actingAs($teacherB)->get(route('gradebooks.show', $gradebook));
        $response->assertStatus(403);
    }

    /**
     * 11. Unauthorized teacher cannot store or modify scores.
     */
    public function test_unauthorized_teacher_cannot_store_scores(): void
    {
        $teacherA = User::factory()->create();
        $teacherB = User::factory()->create();

        $gradebook = Gradebook::factory()->create(['user_id' => $teacherA->id]);
        $student = Student::factory()->create();
        $col = AssessmentColumn::factory()->create(['gradebook_id' => $gradebook->id]);

        $response = $this->actingAs($teacherB)->post(route('gradebooks.scores.store', $gradebook), [
            'scores' => [
                $student->id => [$col->id => 90],
            ],
        ]);

        $response->assertStatus(403);
    }
}
