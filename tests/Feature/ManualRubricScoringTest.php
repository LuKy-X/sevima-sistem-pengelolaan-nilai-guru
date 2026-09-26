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

class ManualRubricScoringTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Helper to set up a standard rubric environment for testing.
     *
     * @return array{user: User, gradebook: Gradebook, column: AssessmentColumn, rubric: Rubric, student: Student, criteria: array<RubricCriterion>}
     */
    protected function setupRubricEnvironment(): array
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

        $column = AssessmentColumn::factory()->create([
            'gradebook_id' => $gradebook->id,
            'name' => 'Proyek Pemrograman Web',
            'max_score' => 100.00,
        ]);

        $rubric = Rubric::factory()->create([
            'assessment_column_id' => $column->id,
            'name' => 'Rubrik Proyek Web',
        ]);

        $c1 = RubricCriterion::create([
            'rubric_id' => $rubric->id,
            'name' => 'Struktur & Kerapian Kode',
            'weight' => 50.00,
        ]);

        $c2 = RubricCriterion::create([
            'rubric_id' => $rubric->id,
            'name' => 'Fungsionalitas & Fitur',
            'weight' => 50.00,
        ]);

        return [
            'user' => $teacher,
            'gradebook' => $gradebook,
            'column' => $column,
            'rubric' => $rubric,
            'student' => $student,
            'criteria' => [$c1, $c2],
        ];
    }

    /**
     * 1. Teacher can input manual numerical scores for rubric criteria.
     */
    public function test_teacher_can_input_manual_numerical_scores(): void
    {
        $env = $this->setupRubricEnvironment();
        [$c1, $c2] = $env['criteria'];

        $response = $this->actingAs($env['user'])->post(
            route('gradebooks.columns.rubric.scores.store', [$env['gradebook'], $env['column'], $env['rubric']]),
            [
                'scores' => [
                    $env['student']->id => [
                        $c1->id => [
                            'score' => 83.5,
                            'level_id' => null,
                        ],
                        $c2->id => [
                            'score' => 91.0,
                            'level_id' => null,
                        ],
                    ],
                ],
            ]
        );

        $response->assertSessionHas('status', 'Nilai rubrik siswa berhasil disimpan dan disinkronkan.');

        $this->assertDatabaseHas('student_rubric_scores', [
            'student_id' => $env['student']->id,
            'rubric_criterion_id' => $c1->id,
            'score' => 83.50,
            'rubric_level_id' => null,
        ]);

        $this->assertDatabaseHas('student_rubric_scores', [
            'student_id' => $env['student']->id,
            'rubric_criterion_id' => $c2->id,
            'score' => 91.00,
            'rubric_level_id' => null,
        ]);
    }

    /**
     * 2. Calculation uses actual score when it differs from selected level reference score.
     * E.g. Level 3 benchmark is 75, but teacher manually specifies 87.5.
     * Calculation MUST use 87.5, not 75.
     */
    public function test_rubric_calculation_uses_actual_score_when_different_from_level_score(): void
    {
        $env = $this->setupRubricEnvironment();
        [$c1, $c2] = $env['criteria'];

        $level1_3 = $c1->levels()->where('level_number', 3)->first(); // Benchmark score: 75.00
        $level2_4 = $c2->levels()->where('level_number', 4)->first(); // Benchmark score: 100.00

        // Teacher selects Level 3 as reference, but inputs manual score 83.00 (differs from 75.00)
        // For criterion 2, teacher inputs 95.00 (differs from 100.00)
        $this->actingAs($env['user'])->post(
            route('gradebooks.columns.rubric.scores.store', [$env['gradebook'], $env['column'], $env['rubric']]),
            [
                'scores' => [
                    $env['student']->id => [
                        $c1->id => [
                            'score' => 83.00,
                            'level_id' => $level1_3->id,
                        ],
                        $c2->id => [
                            'score' => 95.00,
                            'level_id' => $level2_4->id,
                        ],
                    ],
                ],
            ]
        );

        // Expected calculation: (83.00 * 0.50) + (95.00 * 0.50) = 41.5 + 47.5 = 89.00
        // If it incorrectly used level scores, it would be (75 * 0.50) + (100 * 0.50) = 87.50
        $summary = $env['rubric']->getStudentScoreSummary($env['student']->id);
        $this->assertEquals(89.00, $summary['final_score']);
        $this->assertNotEquals(87.50, $summary['final_score']);

        $score = $env['rubric']->calculateStudentScore($env['student']->id);
        $this->assertEquals(89.00, $score);
    }

    /**
     * 3. rubric_level_id can be null on student_rubric_scores.
     */
    public function test_rubric_level_id_can_be_null(): void
    {
        $env = $this->setupRubricEnvironment();
        [$c1] = $env['criteria'];

        $scoreRecord = StudentRubricScore::create([
            'student_id' => $env['student']->id,
            'rubric_criterion_id' => $c1->id,
            'rubric_level_id' => null,
            'score' => 87.50,
        ]);

        $this->assertDatabaseHas('student_rubric_scores', [
            'id' => $scoreRecord->id,
            'student_id' => $env['student']->id,
            'rubric_criterion_id' => $c1->id,
            'rubric_level_id' => null,
            'score' => 87.50,
        ]);

        $this->assertNull($scoreRecord->rubric_level_id);
        $this->assertEquals(87.50, (float) $scoreRecord->score);
    }

    /**
     * 4. Rubric calculation correctly synchronizes to assessment column scores table.
     */
    public function test_rubric_final_score_synchronizes_to_scores_table(): void
    {
        $env = $this->setupRubricEnvironment();
        [$c1, $c2] = $env['criteria'];

        // C1 weight 50% score 80 -> contribution 40
        // C2 weight 50% score 90 -> contribution 45
        // Total expected rubric score = 85.00
        $this->actingAs($env['user'])->post(
            route('gradebooks.columns.rubric.scores.store', [$env['gradebook'], $env['column'], $env['rubric']]),
            [
                'scores' => [
                    $env['student']->id => [
                        $c1->id => [
                            'score' => 80.00,
                            'level_id' => null,
                        ],
                        $c2->id => [
                            'score' => 90.00,
                            'level_id' => null,
                        ],
                    ],
                ],
            ]
        );

        $this->assertDatabaseHas('scores', [
            'assessment_column_id' => $env['column']->id,
            'student_id' => $env['student']->id,
            'score' => 85.00,
        ]);

        $scoreInDb = Score::where('assessment_column_id', $env['column']->id)
            ->where('student_id', $env['student']->id)
            ->first();

        $this->assertNotNull($scoreInDb);
        $this->assertEquals(85.00, (float) $scoreInDb->score);
    }

    /**
     * 5. When level is selected as shortcut without typing score, level benchmark is used.
     */
    public function test_level_selection_without_manual_score_defaults_to_level_score(): void
    {
        $env = $this->setupRubricEnvironment();
        [$c1, $c2] = $env['criteria'];

        $l1 = $c1->levels()->where('level_number', 4)->first(); // 100
        $l2 = $c2->levels()->where('level_number', 3)->first(); // 75

        $this->actingAs($env['user'])->post(
            route('gradebooks.columns.rubric.scores.store', [$env['gradebook'], $env['column'], $env['rubric']]),
            [
                'scores' => [
                    $env['student']->id => [
                        $c1->id => [
                            'level_id' => $l1->id,
                            'score' => '', // Empty manual score
                        ],
                        $c2->id => [
                            'level_id' => $l2->id,
                            'score' => null,
                        ],
                    ],
                ],
            ]
        );

        $this->assertDatabaseHas('student_rubric_scores', [
            'student_id' => $env['student']->id,
            'rubric_criterion_id' => $c1->id,
            'score' => 100.00,
            'rubric_level_id' => $l1->id,
        ]);

        $this->assertDatabaseHas('student_rubric_scores', [
            'student_id' => $env['student']->id,
            'rubric_criterion_id' => $c2->id,
            'score' => 75.00,
            'rubric_level_id' => $l2->id,
        ]);

        // (100 * 0.5) + (75 * 0.5) = 87.5
        $this->assertDatabaseHas('scores', [
            'assessment_column_id' => $env['column']->id,
            'student_id' => $env['student']->id,
            'score' => 87.50,
        ]);
    }

    /**
     * 6. Gradebook show page has column selection action bar and does not show inline header buttons.
     */
    public function test_gradebook_show_displays_column_selection_action_bar_and_removes_inline_header_buttons(): void
    {
        $env = $this->setupRubricEnvironment();

        $response = $this->actingAs($env['user'])->get(route('gradebooks.show', $env['gradebook']));

        $response->assertStatus(200);

        // Has column action bar element
        $response->assertSee('id="column-action-bar"', false);
        $response->assertSee('Kolom Dipilih:');
        $response->assertSee('Edit Kolom');
        $response->assertSee('Kelola Rubrik');
        $response->assertSee('Hapus Kolom');

        // Headers have click-to-select classes and data attributes
        $response->assertSee('assessment-col-header');
        $response->assertSee('data-column-id="'.$env['column']->id.'"', false);
        $response->assertSee('selectAssessmentColumn(this)', false);

        // Contains rubric url link in DOM
        $response->assertSee(route('gradebooks.columns.rubric.show', [$env['gradebook'], $env['column']]));
    }
}
