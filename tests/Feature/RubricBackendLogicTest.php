<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\AssessmentColumn;
use App\Models\Classroom;
use App\Models\Gradebook;
use App\Models\Rubric;
use App\Models\RubricCriterion;
use App\Models\Score;
use App\Models\Student;
use App\Models\StudentRubricScore;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RubricBackendLogicTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 1. Teacher can create rubric.
     */
    public function test_teacher_can_create_rubric(): void
    {
        $teacher = User::factory()->create();
        $gradebook = Gradebook::factory()->create(['user_id' => $teacher->id]);
        $column = AssessmentColumn::factory()->create(['gradebook_id' => $gradebook->id]);

        $response = $this->actingAs($teacher)->post(route('gradebooks.columns.rubric.store', [$gradebook, $column]), [
            'name' => 'Rubrik Proyek Akhir',
            'description' => 'Rubrik penilaian berbasis kriteria proyek',
        ]);

        $response->assertSessionHas('status', 'Rubrik berhasil dibuat.');
        $this->assertDatabaseHas('rubrics', [
            'assessment_column_id' => $column->id,
            'name' => 'Rubrik Proyek Akhir',
        ]);
    }

    /**
     * 2. Teacher cannot create rubric on another teacher's gradebook.
     */
    public function test_teacher_cannot_create_rubric_on_another_teachers_gradebook(): void
    {
        $teacherA = User::factory()->create();
        $teacherB = User::factory()->create();

        $gradebook = Gradebook::factory()->create(['user_id' => $teacherA->id]);
        $column = AssessmentColumn::factory()->create(['gradebook_id' => $gradebook->id]);

        $response = $this->actingAs($teacherB)->post(route('gradebooks.columns.rubric.store', [$gradebook, $column]), [
            'name' => 'Hacker Rubric',
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('rubrics', ['name' => 'Hacker Rubric']);
    }

    /**
     * 3. Teacher can create criterion.
     */
    public function test_teacher_can_create_criterion(): void
    {
        $teacher = User::factory()->create();
        $gradebook = Gradebook::factory()->create(['user_id' => $teacher->id]);
        $column = AssessmentColumn::factory()->create(['gradebook_id' => $gradebook->id]);
        $rubric = Rubric::factory()->create(['assessment_column_id' => $column->id]);

        $response = $this->actingAs($teacher)->post(route('gradebooks.columns.rubric.criteria.store', [$gradebook, $column, $rubric]), [
            'name' => 'Kualitas Kode',
            'weight' => 30.00,
            'description' => 'Kerapian dan keterbacaan kode program',
            'order' => 1,
        ]);

        $response->assertSessionHas('status', 'Kriteria rubrik berhasil ditambahkan.');
        $this->assertDatabaseHas('rubric_criteria', [
            'rubric_id' => $rubric->id,
            'name' => 'Kualitas Kode',
            'weight' => 30.00,
        ]);
    }

    /**
     * 4. Total criterion weight cannot exceed 100%.
     */
    public function test_total_criterion_weight_cannot_exceed_100_percent(): void
    {
        $teacher = User::factory()->create();
        $gradebook = Gradebook::factory()->create(['user_id' => $teacher->id]);
        $column = AssessmentColumn::factory()->create(['gradebook_id' => $gradebook->id]);
        $rubric = Rubric::factory()->create(['assessment_column_id' => $column->id]);

        RubricCriterion::create([
            'rubric_id' => $rubric->id,
            'name' => 'Kriteria 1',
            'weight' => 70.00,
        ]);

        // Attempt to add 40% (total 110%) -> rejected
        $response = $this->actingAs($teacher)->post(route('gradebooks.columns.rubric.criteria.store', [$gradebook, $column, $rubric]), [
            'name' => 'Kriteria 2',
            'weight' => 40.00,
        ]);

        $response->assertSessionHasErrors(['weight']);
        $this->assertDatabaseMissing('rubric_criteria', ['name' => 'Kriteria 2']);
    }

    /**
     * 5. Rubric cannot be used for scoring when weight != 100%.
     */
    public function test_rubric_cannot_be_used_for_scoring_when_weight_not_100_percent(): void
    {
        $teacher = User::factory()->create();
        $classroom = Classroom::factory()->create();
        $academicYear = AcademicYear::factory()->create();
        $student = Student::factory()->create();
        $classroom->students()->attach($student->id, ['academic_year_id' => $academicYear->id]);

        $gradebook = Gradebook::factory()->create([
            'user_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'academic_year_id' => $academicYear->id,
        ]);
        $column = AssessmentColumn::factory()->create(['gradebook_id' => $gradebook->id]);
        $rubric = Rubric::factory()->create(['assessment_column_id' => $column->id]);

        // Criterion only has 80% weight
        $criterion = RubricCriterion::create([
            'rubric_id' => $rubric->id,
            'name' => 'Kriteria Belum Lengkap',
            'weight' => 80.00,
        ]);
        $level = $criterion->levels()->first();

        $response = $this->actingAs($teacher)->post(route('gradebooks.columns.rubric.scores.store', [$gradebook, $column, $rubric]), [
            'scores' => [
                $student->id => [
                    $criterion->id => $level->id,
                ],
            ],
        ]);

        $response->assertSessionHasErrors(['rubric']);
        $this->assertDatabaseCount('student_rubric_scores', 0);
    }

    /**
     * 6. Criterion automatically has 4 levels upon creation.
     */
    public function test_criterion_automatically_has_4_levels(): void
    {
        $rubric = Rubric::factory()->create();

        $criterion = RubricCriterion::create([
            'rubric_id' => $rubric->id,
            'name' => 'Fungsionalitas',
            'weight' => 25.00,
        ]);

        $this->assertCount(4, $criterion->levels);
        $levelNumbers = $criterion->levels->pluck('level_number')->all();
        $this->assertEquals([1, 2, 3, 4], $levelNumbers);

        $levelNames = $criterion->levels->pluck('name')->all();
        $this->assertEquals(['Perlu Bimbingan', 'Cukup', 'Baik', 'Sangat Baik'], $levelNames);

        $levelScores = $criterion->levels->pluck('score')->all();
        $this->assertEquals([25.00, 50.00, 75.00, 100.00], $levelScores);
    }

    /**
     * 7. Teacher can modify level descriptions and names.
     */
    public function test_teacher_can_modify_level_descriptions(): void
    {
        $teacher = User::factory()->create();
        $gradebook = Gradebook::factory()->create(['user_id' => $teacher->id]);
        $column = AssessmentColumn::factory()->create(['gradebook_id' => $gradebook->id]);
        $rubric = Rubric::factory()->create(['assessment_column_id' => $column->id]);
        $criterion = RubricCriterion::create([
            'rubric_id' => $rubric->id,
            'name' => 'Kualitas Kode',
            'weight' => 50.00,
        ]);
        $level = $criterion->levels()->where('level_number', 1)->first();

        $response = $this->actingAs($teacher)->put(
            route('gradebooks.columns.rubric.criteria.levels.update', [$gradebook, $column, $rubric, $criterion, $level]),
            [
                'name' => 'Perlu Pendampingan Khusus',
                'score' => 20.00,
                'description' => 'Kode program belum dapat dieksekusi dengan benar.',
            ]
        );

        $response->assertSessionHas('status', 'Level rubrik berhasil diperbarui.');
        $this->assertDatabaseHas('rubric_levels', [
            'id' => $level->id,
            'name' => 'Perlu Pendampingan Khusus',
            'score' => 20.00,
            'description' => 'Kode program belum dapat dieksekusi dengan benar.',
        ]);
    }

    /**
     * 8. Teacher can select a level for a student.
     */
    public function test_teacher_can_select_a_level_for_a_student(): void
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
        $column = AssessmentColumn::factory()->create(['gradebook_id' => $gradebook->id]);
        $rubric = Rubric::factory()->create(['assessment_column_id' => $column->id]);

        $criterion = RubricCriterion::create([
            'rubric_id' => $rubric->id,
            'name' => 'Mandiri 100',
            'weight' => 100.00,
        ]);
        $selectedLevel = $criterion->levels()->where('level_number', 3)->first();

        $response = $this->actingAs($teacher)->post(route('gradebooks.columns.rubric.scores.store', [$gradebook, $column, $rubric]), [
            'scores' => [
                $student->id => [
                    $criterion->id => $selectedLevel->id,
                ],
            ],
        ]);

        $response->assertSessionHas('status', 'Nilai rubrik siswa berhasil disimpan dan disinkronkan.');
        $this->assertDatabaseHas('student_rubric_scores', [
            'student_id' => $student->id,
            'rubric_criterion_id' => $criterion->id,
            'rubric_level_id' => $selectedLevel->id,
        ]);
    }

    /**
     * 9. One student cannot have duplicate criterion score.
     */
    public function test_one_student_cannot_have_duplicate_criterion_score(): void
    {
        $teacher = User::factory()->create();
        $gradebook = Gradebook::factory()->create(['user_id' => $teacher->id]);
        $column = AssessmentColumn::factory()->create(['gradebook_id' => $gradebook->id]);
        $rubric = Rubric::factory()->create(['assessment_column_id' => $column->id]);
        $student = Student::factory()->create();

        $criterion = RubricCriterion::create([
            'rubric_id' => $rubric->id,
            'name' => 'Kriteria Tunggal',
            'weight' => 100.00,
        ]);
        $level2 = $criterion->levels()->where('level_number', 2)->first();
        $level4 = $criterion->levels()->where('level_number', 4)->first();

        // First evaluation: Level 2
        $this->actingAs($teacher)->post(route('gradebooks.columns.rubric.scores.store', [$gradebook, $column, $rubric]), [
            'scores' => [
                $student->id => [$criterion->id => $level2->id],
            ],
        ]);
        $this->assertDatabaseCount('student_rubric_scores', 1);

        // Update evaluation: Level 4
        $this->actingAs($teacher)->post(route('gradebooks.columns.rubric.scores.store', [$gradebook, $column, $rubric]), [
            'scores' => [
                $student->id => [$criterion->id => $level4->id],
            ],
        ]);

        // Must still be exactly 1 record with updated level
        $this->assertDatabaseCount('student_rubric_scores', 1);
        $this->assertDatabaseHas('student_rubric_scores', [
            'student_id' => $student->id,
            'rubric_criterion_id' => $criterion->id,
            'rubric_level_id' => $level4->id,
        ]);
    }

    /**
     * 10. Rubric score calculation is correct based on selected level scores and weights.
     * Prompt example:
     * Kualitas Kode = 30%, Level 3 = 75  -> 22.5
     * Fungsionalitas = 30%, Level 4 = 100 -> 30.0
     * UI/UX = 20%, Level 3 = 75           -> 15.0
     * Presentasi = 20%, Level 4 = 100     -> 20.0
     * Total = 87.5
     */
    public function test_rubric_score_calculation_is_correct(): void
    {
        $student = Student::factory()->create();
        $column = AssessmentColumn::factory()->create(['max_score' => 100.00]);
        $rubric = Rubric::factory()->create(['assessment_column_id' => $column->id]);

        $c1 = RubricCriterion::create(['rubric_id' => $rubric->id, 'name' => 'Kualitas Kode', 'weight' => 30.00]);
        $c2 = RubricCriterion::create(['rubric_id' => $rubric->id, 'name' => 'Fungsionalitas', 'weight' => 30.00]);
        $c3 = RubricCriterion::create(['rubric_id' => $rubric->id, 'name' => 'UI/UX', 'weight' => 20.00]);
        $c4 = RubricCriterion::create(['rubric_id' => $rubric->id, 'name' => 'Presentasi', 'weight' => 20.00]);

        $l1_3 = $c1->levels()->where('level_number', 3)->first(); // 75
        $l2_4 = $c2->levels()->where('level_number', 4)->first(); // 100
        $l3_3 = $c3->levels()->where('level_number', 3)->first(); // 75
        $l4_4 = $c4->levels()->where('level_number', 4)->first(); // 100

        StudentRubricScore::create(['student_id' => $student->id, 'rubric_criterion_id' => $c1->id, 'rubric_level_id' => $l1_3->id]);
        StudentRubricScore::create(['student_id' => $student->id, 'rubric_criterion_id' => $c2->id, 'rubric_level_id' => $l2_4->id]);
        StudentRubricScore::create(['student_id' => $student->id, 'rubric_criterion_id' => $c3->id, 'rubric_level_id' => $l3_3->id]);
        StudentRubricScore::create(['student_id' => $student->id, 'rubric_criterion_id' => $c4->id, 'rubric_level_id' => $l4_4->id]);

        $score = $rubric->calculateStudentScore($student->id);
        $this->assertEquals(87.50, $score);

        $summary = $rubric->getStudentScoreSummary($student->id);
        $this->assertEquals(87.50, $summary['final_score']);
        $this->assertEquals(100.00, $summary['completion_percentage']);
        $this->assertTrue($summary['is_complete']);
    }

    /**
     * 11. Partial completion is handled correctly.
     * Evaluated: Kualitas Kode (30%, 75) + Fungsionalitas (30%, 100).
     * Available weight: 60%.
     * Proportional score: (22.5 + 30) / (60/100) = 87.5.
     * Completion: 60%.
     */
    public function test_partial_completion_is_handled_correctly(): void
    {
        $student = Student::factory()->create();
        $column = AssessmentColumn::factory()->create(['max_score' => 100.00]);
        $rubric = Rubric::factory()->create(['assessment_column_id' => $column->id]);

        $c1 = RubricCriterion::create(['rubric_id' => $rubric->id, 'name' => 'Kualitas Kode', 'weight' => 30.00]);
        $c2 = RubricCriterion::create(['rubric_id' => $rubric->id, 'name' => 'Fungsionalitas', 'weight' => 30.00]);
        $c3 = RubricCriterion::create(['rubric_id' => $rubric->id, 'name' => 'UI/UX', 'weight' => 20.00]);
        $c4 = RubricCriterion::create(['rubric_id' => $rubric->id, 'name' => 'Presentasi', 'weight' => 20.00]);

        $l1_3 = $c1->levels()->where('level_number', 3)->first(); // 75
        $l2_4 = $c2->levels()->where('level_number', 4)->first(); // 100

        StudentRubricScore::create(['student_id' => $student->id, 'rubric_criterion_id' => $c1->id, 'rubric_level_id' => $l1_3->id]);
        StudentRubricScore::create(['student_id' => $student->id, 'rubric_criterion_id' => $c2->id, 'rubric_level_id' => $l2_4->id]);

        $summary = $rubric->getStudentScoreSummary($student->id);
        $this->assertEquals(87.50, $summary['final_score']);
        $this->assertEquals(60.00, $summary['completed_weight']);
        $this->assertEquals(100.00, $summary['total_weight']);
        $this->assertEquals(60.0, $summary['completion_percentage']);
        $this->assertFalse($summary['is_complete']);
        $this->assertEquals(2, $summary['evaluated_count']);
        $this->assertEquals(4, $summary['total_criteria']);
    }

    /**
     * 12. No score returns null.
     */
    public function test_no_score_returns_null(): void
    {
        $student = Student::factory()->create();
        $rubric = Rubric::factory()->create();

        RubricCriterion::create(['rubric_id' => $rubric->id, 'name' => 'Kriteria 1', 'weight' => 100.00]);

        $this->assertNull($rubric->calculateStudentScore($student->id));

        $summary = $rubric->getStudentScoreSummary($student->id);
        $this->assertNull($summary['final_score']);
        $this->assertEquals(0.0, $summary['completion_percentage']);
        $this->assertFalse($summary['is_complete']);
    }

    /**
     * 13. Rubric score synchronizes to assessment column Score.
     */
    public function test_rubric_score_synchronizes_to_assessment_column_score(): void
    {
        $teacher = User::factory()->create();
        $classroom = Classroom::factory()->create();
        $academicYear = AcademicYear::factory()->create();
        $student = Student::factory()->create();
        $classroom->students()->attach($student->id, ['academic_year_id' => $academicYear->id]);

        $gradebook = Gradebook::factory()->create([
            'user_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'academic_year_id' => $academicYear->id,
        ]);
        $column = AssessmentColumn::factory()->create(['gradebook_id' => $gradebook->id, 'max_score' => 100.00]);
        $rubric = Rubric::factory()->create(['assessment_column_id' => $column->id]);

        $c1 = RubricCriterion::create(['rubric_id' => $rubric->id, 'name' => 'Kualitas Kode', 'weight' => 50.00]);
        $c2 = RubricCriterion::create(['rubric_id' => $rubric->id, 'name' => 'Fungsionalitas', 'weight' => 50.00]);

        $l1_3 = $c1->levels()->where('level_number', 3)->first(); // 75
        $l2_4 = $c2->levels()->where('level_number', 4)->first(); // 100
        // Score: (75*50 + 100*50)/100 = 87.5

        $this->actingAs($teacher)->post(route('gradebooks.columns.rubric.scores.store', [$gradebook, $column, $rubric]), [
            'scores' => [
                $student->id => [
                    $c1->id => $l1_3->id,
                    $c2->id => $l2_4->id,
                ],
            ],
        ]);

        $this->assertDatabaseHas('scores', [
            'assessment_column_id' => $column->id,
            'student_id' => $student->id,
            'score' => 87.50,
        ]);
    }

    /**
     * 14. Unauthorized teacher cannot modify rubric.
     */
    public function test_unauthorized_teacher_cannot_modify_rubric(): void
    {
        $teacherA = User::factory()->create();
        $teacherB = User::factory()->create();

        $gradebook = Gradebook::factory()->create(['user_id' => $teacherA->id]);
        $column = AssessmentColumn::factory()->create(['gradebook_id' => $gradebook->id]);
        $rubric = Rubric::factory()->create(['assessment_column_id' => $column->id]);
        $criterion = RubricCriterion::create(['rubric_id' => $rubric->id, 'name' => 'C1', 'weight' => 100.00]);
        $level = $criterion->levels()->first();
        $student = Student::factory()->create();

        // Update rubric blocked
        $this->actingAs($teacherB)->put(route('gradebooks.columns.rubric.update', [$gradebook, $column, $rubric]), [
            'name' => 'Hacked',
        ])->assertStatus(403);

        // Delete rubric blocked
        $this->actingAs($teacherB)->delete(route('gradebooks.columns.rubric.destroy', [$gradebook, $column, $rubric]))->assertStatus(403);

        // Create criterion blocked
        $this->actingAs($teacherB)->post(route('gradebooks.columns.rubric.criteria.store', [$gradebook, $column, $rubric]), [
            'name' => 'Hacked',
            'weight' => 10.00,
        ])->assertStatus(403);

        // Update criterion blocked
        $this->actingAs($teacherB)->put(route('gradebooks.columns.rubric.criteria.update', [$gradebook, $column, $rubric, $criterion]), [
            'name' => 'Hacked',
            'weight' => 10.00,
        ])->assertStatus(403);

        // Delete criterion blocked
        $this->actingAs($teacherB)->delete(route('gradebooks.columns.rubric.criteria.destroy', [$gradebook, $column, $rubric, $criterion]))->assertStatus(403);

        // Update level blocked
        $this->actingAs($teacherB)->put(route('gradebooks.columns.rubric.criteria.levels.update', [$gradebook, $column, $rubric, $criterion, $level]), [
            'name' => 'Hacked Level',
            'score' => 0.00,
        ])->assertStatus(403);

        // Submit scores blocked
        $this->actingAs($teacherB)->post(route('gradebooks.columns.rubric.scores.store', [$gradebook, $column, $rubric]), [
            'scores' => [
                $student->id => [$criterion->id => $level->id],
            ],
        ])->assertStatus(403);
    }

    /**
     * 14. Teacher can update and delete rubric criterion.
     */
    public function test_teacher_can_update_and_delete_rubric_criterion(): void
    {
        $teacher = User::factory()->create();
        $gradebook = Gradebook::factory()->create(['user_id' => $teacher->id]);
        $column = AssessmentColumn::factory()->create(['gradebook_id' => $gradebook->id]);
        $rubric = Rubric::factory()->create(['assessment_column_id' => $column->id]);

        $c1 = RubricCriterion::create([
            'rubric_id' => $rubric->id,
            'name' => 'Kerapian Kode',
            'weight' => 40.00,
        ]);

        $c2 = RubricCriterion::create([
            'rubric_id' => $rubric->id,
            'name' => 'Fungsionalitas',
            'weight' => 50.00,
        ]);

        // Update criterion c1 to 45% (total 45 + 50 = 95 <= 100)
        $response = $this->actingAs($teacher)->put(
            route('gradebooks.columns.rubric.criteria.update', [$gradebook, $column, $rubric, $c1]),
            [
                'name' => 'Kerapian & Dokumentasi Kode',
                'weight' => 45.00,
            ]
        );
        $response->assertSessionHas('status', 'Kriteria rubrik berhasil diperbarui.');
        $this->assertDatabaseHas('rubric_criteria', [
            'id' => $c1->id,
            'name' => 'Kerapian & Dokumentasi Kode',
            'weight' => 45.00,
        ]);

        // Attempting to update c1 to 60% (total 60 + 50 = 110 > 100) must fail validation
        $failResponse = $this->actingAs($teacher)->put(
            route('gradebooks.columns.rubric.criteria.update', [$gradebook, $column, $rubric, $c1]),
            [
                'name' => 'Kerapian & Dokumentasi Kode',
                'weight' => 60.00,
            ]
        );
        $failResponse->assertSessionHasErrors(['weight']);

        // Delete criterion c2
        $deleteResponse = $this->actingAs($teacher)->delete(
            route('gradebooks.columns.rubric.criteria.destroy', [$gradebook, $column, $rubric, $c2])
        );
        $deleteResponse->assertSessionHas('status', 'Kriteria rubrik berhasil dihapus.');
        $this->assertDatabaseMissing('rubric_criteria', ['id' => $c2->id]);
    }
}
