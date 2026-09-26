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

class ScoreMatrixTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 1. Authenticated teacher can view the score matrix.
     */
    public function test_authenticated_teacher_can_view_score_matrix(): void
    {
        $teacher = User::factory()->create();
        $gradebook = Gradebook::factory()->create(['user_id' => $teacher->id]);

        $response = $this->actingAs($teacher)->get(route('gradebooks.show', $gradebook));

        $response->assertStatus(200);
        $response->assertSee('Simpan Nilai');
        $response->assertSee('Tambah Kolom Penilaian');
    }

    /**
     * 2. Students belonging to the gradebook classroom are displayed.
     */
    public function test_students_belonging_to_the_gradebook_classroom_are_displayed(): void
    {
        $teacher = User::factory()->create();
        $classroom = Classroom::factory()->create();
        $academicYear = AcademicYear::factory()->create();

        $studentA = Student::factory()->create(['name' => 'Ahmad Dahlan', 'nisn' => '1234567890']);
        $studentB = Student::factory()->create(['name' => 'Bunga Citra', 'nisn' => '0987654321']);

        $classroom->students()->attach([$studentA->id, $studentB->id], ['academic_year_id' => $academicYear->id]);

        $gradebook = Gradebook::factory()->create([
            'user_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'academic_year_id' => $academicYear->id,
        ]);

        $response = $this->actingAs($teacher)->get(route('gradebooks.show', $gradebook));

        $response->assertStatus(200);
        $response->assertSee('Ahmad Dahlan');
        $response->assertSee('Bunga Citra');
        $response->assertSee('1234567890');
    }

    /**
     * 3. Assessment columns belonging to the gradebook are displayed.
     */
    public function test_assessment_columns_belonging_to_the_gradebook_are_displayed(): void
    {
        $teacher = User::factory()->create();
        $gradebook = Gradebook::factory()->create(['user_id' => $teacher->id]);

        AssessmentColumn::factory()->create([
            'gradebook_id' => $gradebook->id,
            'name' => 'Ulangan Harian Bab 1',
            'type' => 'uh',
            'weight' => 25.00,
            'max_score' => 100.00,
            'order' => 1,
        ]);

        AssessmentColumn::factory()->create([
            'gradebook_id' => $gradebook->id,
            'name' => 'Tugas Praktik CSS',
            'type' => 'tugas',
            'weight' => 15.00,
            'max_score' => 50.00,
            'order' => 2,
        ]);

        $response = $this->actingAs($teacher)->get(route('gradebooks.show', $gradebook));

        $response->assertStatus(200);
        $response->assertSee('Ulangan Harian Bab 1');
        $response->assertSee('Tugas Praktik CSS');
        $response->assertSee('25%');
        $response->assertSee('15%');
        $response->assertSee('50');
    }

    /**
     * 4. Teacher can save a score.
     */
    public function test_teacher_can_save_a_score(): void
    {
        $teacher = User::factory()->create();
        $gradebook = Gradebook::factory()->create(['user_id' => $teacher->id]);
        $student = Student::factory()->create();
        $column = AssessmentColumn::factory()->create([
            'gradebook_id' => $gradebook->id,
            'max_score' => 100.00,
        ]);

        $response = $this->actingAs($teacher)->post(route('gradebooks.scores.store', $gradebook), [
            'scores' => [
                $student->id => [
                    $column->id => 88.5,
                ],
            ],
        ]);

        $response->assertRedirect(route('gradebooks.show', $gradebook));
        $response->assertSessionHas('status', 'Nilai berhasil disimpan.');

        $this->assertDatabaseHas('scores', [
            'assessment_column_id' => $column->id,
            'student_id' => $student->id,
            'score' => 88.50,
        ]);
    }

    /**
     * 5. Teacher can update an existing score.
     */
    public function test_teacher_can_update_an_existing_score(): void
    {
        $teacher = User::factory()->create();
        $gradebook = Gradebook::factory()->create(['user_id' => $teacher->id]);
        $student = Student::factory()->create();
        $column = AssessmentColumn::factory()->create([
            'gradebook_id' => $gradebook->id,
            'max_score' => 100.00,
        ]);

        Score::create([
            'assessment_column_id' => $column->id,
            'student_id' => $student->id,
            'score' => 70.00,
        ]);

        $response = $this->actingAs($teacher)->post(route('gradebooks.scores.store', $gradebook), [
            'scores' => [
                $student->id => [
                    $column->id => 95.00,
                ],
            ],
        ]);

        $response->assertRedirect(route('gradebooks.show', $gradebook));

        $this->assertDatabaseCount('scores', 1);
        $this->assertDatabaseHas('scores', [
            'assessment_column_id' => $column->id,
            'student_id' => $student->id,
            'score' => 95.00,
        ]);
    }

    /**
     * 6. Score belongs to the correct student.
     */
    public function test_score_belongs_to_the_correct_student(): void
    {
        $teacher = User::factory()->create();
        $gradebook = Gradebook::factory()->create(['user_id' => $teacher->id]);
        $studentA = Student::factory()->create();
        $studentB = Student::factory()->create();
        $column = AssessmentColumn::factory()->create(['gradebook_id' => $gradebook->id, 'max_score' => 100]);

        $this->actingAs($teacher)->post(route('gradebooks.scores.store', $gradebook), [
            'scores' => [
                $studentA->id => [$column->id => 85],
                $studentB->id => [$column->id => 92],
            ],
        ]);

        $this->assertDatabaseHas('scores', [
            'student_id' => $studentA->id,
            'assessment_column_id' => $column->id,
            'score' => 85.00,
        ]);

        $this->assertDatabaseHas('scores', [
            'student_id' => $studentB->id,
            'assessment_column_id' => $column->id,
            'score' => 92.00,
        ]);
    }

    /**
     * 7. Score belongs to the correct assessment column.
     */
    public function test_score_belongs_to_the_correct_assessment_column(): void
    {
        $teacher = User::factory()->create();
        $gradebook = Gradebook::factory()->create(['user_id' => $teacher->id]);
        $student = Student::factory()->create();
        $column1 = AssessmentColumn::factory()->create(['gradebook_id' => $gradebook->id, 'max_score' => 100]);
        $column2 = AssessmentColumn::factory()->create(['gradebook_id' => $gradebook->id, 'max_score' => 100]);

        $this->actingAs($teacher)->post(route('gradebooks.scores.store', $gradebook), [
            'scores' => [
                $student->id => [
                    $column1->id => 78,
                    $column2->id => 88,
                ],
            ],
        ]);

        $this->assertDatabaseHas('scores', [
            'student_id' => $student->id,
            'assessment_column_id' => $column1->id,
            'score' => 78.00,
        ]);

        $this->assertDatabaseHas('scores', [
            'student_id' => $student->id,
            'assessment_column_id' => $column2->id,
            'score' => 88.00,
        ]);
    }

    /**
     * 8. Invalid score is rejected.
     */
    public function test_invalid_score_is_rejected(): void
    {
        $teacher = User::factory()->create();
        $gradebook = Gradebook::factory()->create(['user_id' => $teacher->id]);
        $student = Student::factory()->create();
        $column = AssessmentColumn::factory()->create(['gradebook_id' => $gradebook->id, 'max_score' => 100]);

        // Negative score
        $responseNegative = $this->actingAs($teacher)->post(route('gradebooks.scores.store', $gradebook), [
            'scores' => [
                $student->id => [
                    $column->id => -10,
                ],
            ],
        ]);
        $responseNegative->assertSessionHasErrors(['score']);

        // Non numeric score
        $responseString = $this->actingAs($teacher)->post(route('gradebooks.scores.store', $gradebook), [
            'scores' => [
                $student->id => [
                    $column->id => 'not-a-number',
                ],
            ],
        ]);
        $responseString->assertSessionHasErrors(['score']);

        $this->assertDatabaseCount('scores', 0);
    }

    /**
     * 9. Score above max_score is rejected.
     */
    public function test_score_above_max_score_is_rejected(): void
    {
        $teacher = User::factory()->create();
        $gradebook = Gradebook::factory()->create(['user_id' => $teacher->id]);
        $student = Student::factory()->create();
        $column = AssessmentColumn::factory()->create([
            'gradebook_id' => $gradebook->id,
            'max_score' => 50.00,
        ]);

        $response = $this->actingAs($teacher)->post(route('gradebooks.scores.store', $gradebook), [
            'scores' => [
                $student->id => [
                    $column->id => 60, // Above max_score 50
                ],
            ],
        ]);

        $response->assertSessionHasErrors(['score']);
        $this->assertDatabaseCount('scores', 0);
    }

    /**
     * 10. Teacher cannot modify another teacher's gradebook scores.
     */
    public function test_teacher_cannot_modify_another_teachers_gradebook_scores(): void
    {
        $teacherA = User::factory()->create();
        $teacherB = User::factory()->create();

        $gradebookB = Gradebook::factory()->create(['user_id' => $teacherB->id]);
        $student = Student::factory()->create();
        $columnB = AssessmentColumn::factory()->create(['gradebook_id' => $gradebookB->id]);

        $response = $this->actingAs($teacherA)->post(route('gradebooks.scores.store', $gradebookB), [
            'scores' => [
                $student->id => [
                    $columnB->id => 90,
                ],
            ],
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseCount('scores', 0);
    }

    /**
     * 11. Batch score saving works.
     */
    public function test_batch_score_saving_works(): void
    {
        $teacher = User::factory()->create();
        $gradebook = Gradebook::factory()->create(['user_id' => $teacher->id]);

        $students = Student::factory()->count(3)->create();
        $columns = AssessmentColumn::factory()->count(2)->create([
            'gradebook_id' => $gradebook->id,
            'max_score' => 100,
        ]);

        $batchPayload = [];
        foreach ($students as $student) {
            foreach ($columns as $column) {
                $batchPayload[$student->id][$column->id] = rand(70, 95);
            }
        }

        $response = $this->actingAs($teacher)->post(route('gradebooks.scores.store', $gradebook), [
            'scores' => $batchPayload,
        ]);

        $response->assertRedirect(route('gradebooks.show', $gradebook));
        $this->assertDatabaseCount('scores', 6);
    }

    /**
     * 12. Empty scores are handled correctly.
     */
    public function test_empty_scores_are_handled_correctly(): void
    {
        $teacher = User::factory()->create();
        $gradebook = Gradebook::factory()->create(['user_id' => $teacher->id]);
        $studentA = Student::factory()->create();
        $studentB = Student::factory()->create();
        $column = AssessmentColumn::factory()->create(['gradebook_id' => $gradebook->id, 'max_score' => 100]);

        // Pre-existing score for student B
        Score::create([
            'assessment_column_id' => $column->id,
            'student_id' => $studentB->id,
            'score' => 80.00,
        ]);

        // Student A gets score, Student B is emptied/cleared
        $response = $this->actingAs($teacher)->post(route('gradebooks.scores.store', $gradebook), [
            'scores' => [
                $studentA->id => [$column->id => 90],
                $studentB->id => [$column->id => ''],
            ],
        ]);

        $response->assertRedirect(route('gradebooks.show', $gradebook));
        $response->assertSessionHasNoErrors();

        // Student A has score 90
        $this->assertDatabaseHas('scores', [
            'student_id' => $studentA->id,
            'assessment_column_id' => $column->id,
            'score' => 90.00,
        ]);

        // Student B's cleared score is deleted from database
        $this->assertDatabaseMissing('scores', [
            'student_id' => $studentB->id,
            'assessment_column_id' => $column->id,
        ]);
    }

    /**
     * 13. Average calculation is correct.
     */
    public function test_average_calculation_is_correct(): void
    {
        $teacher = User::factory()->create();
        $classroom = Classroom::factory()->create();
        $academicYear = AcademicYear::factory()->create();

        $student = Student::factory()->create(['name' => 'Dewi Sartika']);
        $classroom->students()->attach($student->id, ['academic_year_id' => $academicYear->id]);

        $gradebook = Gradebook::factory()->create([
            'user_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'academic_year_id' => $academicYear->id,
        ]);

        $col1 = AssessmentColumn::factory()->create(['gradebook_id' => $gradebook->id, 'max_score' => 100]);
        $col2 = AssessmentColumn::factory()->create(['gradebook_id' => $gradebook->id, 'max_score' => 100]);
        $col3 = AssessmentColumn::factory()->create(['gradebook_id' => $gradebook->id, 'max_score' => 100]);

        // Student scores: 80, 90, 100 -> Average = 90
        Score::create(['assessment_column_id' => $col1->id, 'student_id' => $student->id, 'score' => 80]);
        Score::create(['assessment_column_id' => $col2->id, 'student_id' => $student->id, 'score' => 90]);
        Score::create(['assessment_column_id' => $col3->id, 'student_id' => $student->id, 'score' => 100]);

        $average = $gradebook->calculateStudentAverage($student->id);
        $this->assertEquals(90.00, $average);

        // Verify HTML displays the average
        $response = $this->actingAs($teacher)->get(route('gradebooks.show', $gradebook));
        $response->assertStatus(200);
        $response->assertSee('Dewi Sartika');
        $response->assertSee('90');

        // Test with missing/empty score: only 80 and 90 -> Average = 85 (empty not counted as 0)
        Score::where('assessment_column_id', $col3->id)->delete();
        $averageWithMissing = $gradebook->calculateStudentAverage($student->id);
        $this->assertEquals(85.00, $averageWithMissing);
    }
}
