<?php

namespace Tests\Feature;

use App\Models\AssessmentColumn;
use App\Models\Rubric;
use App\Models\RubricCriterion;
use App\Models\RubricLevel;
use App\Models\Student;
use App\Models\StudentRubricScore;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RubricDatabaseTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 1. Rubric table can be created and AssessmentColumn -> Rubric relationship works.
     */
    public function test_rubric_table_and_relationships_can_be_created(): void
    {
        $column = AssessmentColumn::factory()->create(['name' => 'Project Mini Web App']);
        $rubric = Rubric::create([
            'assessment_column_id' => $column->id,
            'name' => 'Rubrik Proyek Akhir',
            'description' => 'Pedoman penilaian proyek web siswa',
        ]);

        $this->assertDatabaseHas('rubrics', [
            'id' => $rubric->id,
            'assessment_column_id' => $column->id,
            'name' => 'Rubrik Proyek Akhir',
        ]);

        $this->assertInstanceOf(Rubric::class, $column->fresh()->rubric);
        $this->assertEquals($rubric->id, $column->fresh()->rubric->id);
        $this->assertInstanceOf(AssessmentColumn::class, $rubric->assessmentColumn);
        $this->assertEquals($column->id, $rubric->assessmentColumn->id);
    }

    /**
     * 2. Rubric -> Criteria relationship works.
     */
    public function test_rubric_to_criteria_relationship(): void
    {
        $rubric = Rubric::factory()->create();

        $c1 = RubricCriterion::create([
            'rubric_id' => $rubric->id,
            'name' => 'Kualitas Kode',
            'description' => 'Kerapian dan modularitas kode',
            'weight' => 40.00,
            'order' => 1,
        ]);

        $c2 = RubricCriterion::create([
            'rubric_id' => $rubric->id,
            'name' => 'Fungsionalitas',
            'description' => 'Kelengkapan fitur aplikasi',
            'weight' => 60.00,
            'order' => 2,
        ]);

        $this->assertCount(2, $rubric->fresh()->criteria);
        $this->assertEquals('Kualitas Kode', $rubric->fresh()->criteria->first()->name);
        $this->assertEquals($rubric->id, $c1->rubric->id);
        $this->assertEquals($rubric->id, $c2->rubric->id);
    }

    /**
     * 3. Criteria -> Levels relationship works.
     */
    public function test_criteria_to_levels_relationship(): void
    {
        $criterion = RubricCriterion::factory()->create(['name' => 'Desain UI/UX']);

        $levels = [
            ['level_number' => 1, 'name' => 'Perlu Bimbingan', 'score' => 25.00, 'description' => 'Tampilan belum terstruktur'],
            ['level_number' => 2, 'name' => 'Cukup', 'score' => 50.00, 'description' => 'Tampilan cukup rapi'],
            ['level_number' => 3, 'name' => 'Baik', 'score' => 75.00, 'description' => 'Tampilan menarik dan konsisten'],
            ['level_number' => 4, 'name' => 'Sangat Baik', 'score' => 100.00, 'description' => 'Tampilan sangat intuitif dan profesional'],
        ];

        foreach ($levels as $lvl) {
            RubricLevel::create(array_merge($lvl, ['rubric_criterion_id' => $criterion->id]));
        }

        $this->assertCount(4, $criterion->fresh()->levels);
        $this->assertEquals('Perlu Bimbingan', $criterion->fresh()->levels->first()->name);
        $this->assertEquals('Sangat Baik', $criterion->fresh()->levels->last()->name);
        $this->assertEquals($criterion->id, $criterion->fresh()->levels->first()->criterion->id);
    }

    /**
     * 4. Student rubric score has correct relationships to Student, Criterion, and Level.
     */
    public function test_student_rubric_score_relationships(): void
    {
        $student = Student::factory()->create();
        $criterion = RubricCriterion::factory()->create();
        $level = RubricLevel::factory()->create([
            'rubric_criterion_id' => $criterion->id,
            'level_number' => 3,
            'name' => 'Baik',
            'score' => 75.00,
        ]);

        $score = StudentRubricScore::create([
            'student_id' => $student->id,
            'rubric_criterion_id' => $criterion->id,
            'rubric_level_id' => $level->id,
        ]);

        $this->assertDatabaseHas('student_rubric_scores', [
            'id' => $score->id,
            'student_id' => $student->id,
            'rubric_criterion_id' => $criterion->id,
            'rubric_level_id' => $level->id,
        ]);

        $this->assertEquals($student->id, $score->student->id);
        $this->assertEquals($criterion->id, $score->criterion->id);
        $this->assertEquals($level->id, $score->level->id);

        $this->assertCount(1, $criterion->fresh()->studentScores);
        $this->assertCount(1, $level->fresh()->studentScores);
    }

    /**
     * 5. Unique constraint: One rubric per assessment column.
     */
    public function test_unique_constraint_one_rubric_per_assessment_column(): void
    {
        $column = AssessmentColumn::factory()->create();
        Rubric::create(['assessment_column_id' => $column->id, 'name' => 'Rubrik Pertama']);

        $this->expectException(QueryException::class);
        Rubric::create(['assessment_column_id' => $column->id, 'name' => 'Rubrik Kedua']);
    }

    /**
     * 6. Unique constraint: rubric_criterion_id + level_number.
     */
    public function test_unique_constraint_criterion_id_and_level_number(): void
    {
        $criterion = RubricCriterion::factory()->create();
        RubricLevel::create([
            'rubric_criterion_id' => $criterion->id,
            'level_number' => 1,
            'name' => 'Level 1',
            'score' => 25.00,
        ]);

        $this->expectException(QueryException::class);
        RubricLevel::create([
            'rubric_criterion_id' => $criterion->id,
            'level_number' => 1,
            'name' => 'Level 1 Duplikat',
            'score' => 30.00,
        ]);
    }

    /**
     * 7. Unique constraint: student_id + rubric_criterion_id.
     */
    public function test_unique_constraint_student_id_and_rubric_criterion_id(): void
    {
        $student = Student::factory()->create();
        $criterion = RubricCriterion::factory()->create();
        $level1 = RubricLevel::factory()->create(['rubric_criterion_id' => $criterion->id, 'level_number' => 1]);
        $level2 = RubricLevel::factory()->create(['rubric_criterion_id' => $criterion->id, 'level_number' => 2]);

        StudentRubricScore::create([
            'student_id' => $student->id,
            'rubric_criterion_id' => $criterion->id,
            'rubric_level_id' => $level1->id,
        ]);

        $this->expectException(QueryException::class);
        StudentRubricScore::create([
            'student_id' => $student->id,
            'rubric_criterion_id' => $criterion->id,
            'rubric_level_id' => $level2->id,
        ]);
    }

    /**
     * 8. Cascade delete works down the hierarchy.
     */
    public function test_cascade_delete_works_down_the_hierarchy(): void
    {
        $column = AssessmentColumn::factory()->create();
        $rubric = Rubric::factory()->create(['assessment_column_id' => $column->id]);
        $criterion = RubricCriterion::factory()->create(['rubric_id' => $rubric->id]);
        $level = RubricLevel::factory()->create(['rubric_criterion_id' => $criterion->id, 'level_number' => 1]);
        $score = StudentRubricScore::factory()->create([
            'rubric_criterion_id' => $criterion->id,
            'rubric_level_id' => $level->id,
        ]);

        // When AssessmentColumn is deleted
        $column->delete();

        $this->assertDatabaseMissing('rubrics', ['id' => $rubric->id]);
        $this->assertDatabaseMissing('rubric_criteria', ['id' => $criterion->id]);
        $this->assertDatabaseMissing('rubric_levels', ['id' => $level->id]);
        $this->assertDatabaseMissing('student_rubric_scores', ['id' => $score->id]);
    }
}
