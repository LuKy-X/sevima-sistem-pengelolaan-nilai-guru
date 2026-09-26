<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\AssessmentColumn;
use App\Models\Classroom;
use App\Models\Gradebook;
use App\Models\Rubric;
use App\Models\RubricCriterion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RubricUITest extends TestCase
{
    use RefreshDatabase;

    /**
     * 1. Teacher can access the Rubric management UI page.
     */
    public function test_teacher_can_view_rubric_management_page(): void
    {
        $teacher = User::factory()->create();
        $classroom = Classroom::factory()->create();
        $academicYear = AcademicYear::factory()->create();
        $gradebook = Gradebook::factory()->create([
            'user_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'academic_year_id' => $academicYear->id,
        ]);
        $column = AssessmentColumn::factory()->create([
            'gradebook_id' => $gradebook->id,
            'name' => 'Ulangan Harian 1',
        ]);

        $response = $this->actingAs($teacher)->get(route('gradebooks.columns.rubric.show', [$gradebook, $column]));

        $response->assertStatus(200);
        $response->assertSee('Manajemen Rubrik Nilai');
        $response->assertSee('Kelola Rubrik Nilai');
        $response->assertSee('Rubrik Nilai Ulangan Harian 1');
        $response->assertSee('Kriteria Penilaian');
        $response->assertSee('Nilai / Bobot (%)');
        $response->assertSee('Simpan Rubrik');
    }

    /**
     * 2. Rubric page displays existing criteria and total weight matching reference.
     */
    public function test_rubric_page_displays_criteria_and_total_weight(): void
    {
        $teacher = User::factory()->create();
        $gradebook = Gradebook::factory()->create(['user_id' => $teacher->id]);
        $column = AssessmentColumn::factory()->create(['gradebook_id' => $gradebook->id, 'name' => 'Ulangan Harian 1']);
        $rubric = Rubric::factory()->create(['assessment_column_id' => $column->id, 'name' => 'Rubrik Nilai Ulangan Harian 1']);

        RubricCriterion::create(['rubric_id' => $rubric->id, 'name' => 'Dijabarkan Cara Pengerjaannya', 'weight' => 50.00]);
        RubricCriterion::create(['rubric_id' => $rubric->id, 'name' => 'Jawaban Benar', 'weight' => 40.00]);
        RubricCriterion::create(['rubric_id' => $rubric->id, 'name' => 'Kejujuran', 'weight' => 10.00]);

        $response = $this->actingAs($teacher)->get(route('gradebooks.columns.rubric.show', [$gradebook, $column]));

        $response->assertStatus(200);
        $response->assertSee('Dijabarkan Cara Pengerjaannya');
        $response->assertSee('Jawaban Benar');
        $response->assertSee('Kejujuran');
        $response->assertSee('Total');
        $response->assertSee('100');
    }

    /**
     * 3. Teacher can save/update the rubric and its criteria from the main UI form.
     */
    public function test_teacher_can_update_rubric_and_criteria_from_ui(): void
    {
        $teacher = User::factory()->create();
        $gradebook = Gradebook::factory()->create(['user_id' => $teacher->id]);
        $column = AssessmentColumn::factory()->create(['gradebook_id' => $gradebook->id]);
        $rubric = Rubric::factory()->create(['assessment_column_id' => $column->id]);

        $c1 = RubricCriterion::create(['rubric_id' => $rubric->id, 'name' => 'Kriteria Lama 1', 'weight' => 60.00]);
        $c2 = RubricCriterion::create(['rubric_id' => $rubric->id, 'name' => 'Kriteria Lama 2', 'weight' => 40.00]);

        $response = $this->actingAs($teacher)->put(route('gradebooks.columns.rubric.update', [$gradebook, $column, $rubric]), [
            'name' => 'Rubrik Ulangan Harian 1 Terupdate',
            'description' => 'Deskripsi baru rubrik',
            'criteria' => [
                ['id' => $c1->id, 'name' => 'Kriteria Baru 1', 'weight' => 70.00],
                ['id' => $c2->id, 'name' => 'Kriteria Baru 2', 'weight' => 30.00],
            ],
        ]);

        $response->assertSessionHas('status', 'Rubrik berhasil diperbarui.');
        $this->assertDatabaseHas('rubrics', [
            'id' => $rubric->id,
            'name' => 'Rubrik Ulangan Harian 1 Terupdate',
        ]);
        $this->assertDatabaseHas('rubric_criteria', [
            'id' => $c1->id,
            'name' => 'Kriteria Baru 1',
            'weight' => 70.00,
        ]);
        $this->assertDatabaseHas('rubric_criteria', [
            'id' => $c2->id,
            'name' => 'Kriteria Baru 2',
            'weight' => 30.00,
        ]);
    }

    /**
     * 4. Unauthorized teacher cannot view another teacher's rubric page.
     */
    public function test_unauthorized_teacher_cannot_view_rubric_page(): void
    {
        $teacherA = User::factory()->create();
        $teacherB = User::factory()->create();

        $gradebook = Gradebook::factory()->create(['user_id' => $teacherA->id]);
        $column = AssessmentColumn::factory()->create(['gradebook_id' => $gradebook->id]);

        $response = $this->actingAs($teacherB)->get(route('gradebooks.columns.rubric.show', [$gradebook, $column]));

        $response->assertStatus(403);
    }

    /**
     * 5. Gradebook score matrix contains a direct link to the Rubric management page.
     */
    public function test_gradebook_show_contains_link_to_rubric(): void
    {
        $teacher = User::factory()->create();
        $gradebook = Gradebook::factory()->create(['user_id' => $teacher->id]);
        $column = AssessmentColumn::factory()->create(['gradebook_id' => $gradebook->id, 'name' => 'Ulangan Harian 1']);

        $response = $this->actingAs($teacher)->get(route('gradebooks.show', $gradebook));

        $response->assertStatus(200);
        $response->assertSee(route('gradebooks.columns.rubric.show', [$gradebook, $column]));
    }
}
