<?php

namespace Tests\Feature;

use App\Models\AssessmentColumn;
use App\Models\Gradebook;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssessmentColumnManagementTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 1. Guest cannot manage assessment columns.
     */
    public function test_guest_cannot_manage_assessment_columns(): void
    {
        $gradebook = Gradebook::factory()->create();
        $column = AssessmentColumn::factory()->create(['gradebook_id' => $gradebook->id]);

        $this->get(route('gradebooks.columns.create', $gradebook))->assertRedirect('/login');
        $this->post(route('gradebooks.columns.store', $gradebook), [])->assertRedirect('/login');
        $this->get(route('gradebooks.columns.edit', [$gradebook, $column]))->assertRedirect('/login');
        $this->put(route('gradebooks.columns.update', [$gradebook, $column]), [])->assertRedirect('/login');
        $this->delete(route('gradebooks.columns.destroy', [$gradebook, $column]))->assertRedirect('/login');
    }

    /**
     * 2. Teacher can see assessment columns in their own gradebook.
     */
    public function test_teacher_can_see_assessment_columns_in_their_own_gradebook(): void
    {
        $teacher = User::factory()->create();
        $gradebook = Gradebook::factory()->create(['user_id' => $teacher->id]);

        $column1 = AssessmentColumn::factory()->create([
            'gradebook_id' => $gradebook->id,
            'name' => 'Ulangan Harian 1',
            'type' => 'uh',
            'weight' => 20.00,
            'max_score' => 100.00,
            'order' => 1,
        ]);

        $column2 = AssessmentColumn::factory()->create([
            'gradebook_id' => $gradebook->id,
            'name' => 'Tugas Praktik Mandiri',
            'type' => 'praktik',
            'weight' => 30.00,
            'max_score' => 100.00,
            'order' => 2,
        ]);

        $response = $this->actingAs($teacher)->get(route('gradebooks.show', $gradebook));

        $response->assertStatus(200);
        $response->assertSee('Ulangan Harian 1');
        $response->assertSee('Tugas Praktik Mandiri');
        $response->assertSee('20%');
        $response->assertSee('30%');
        $response->assertSee('100');
    }

    /**
     * 3. Teacher can create an assessment column.
     */
    public function test_teacher_can_create_an_assessment_column(): void
    {
        $teacher = User::factory()->create();
        $gradebook = Gradebook::factory()->create(['user_id' => $teacher->id]);

        $data = [
            'name' => 'Ulangan Harian 1',
            'type' => 'uh',
            'weight' => 25.00,
            'max_score' => 100.00,
            'order' => 1,
        ];

        $response = $this->actingAs($teacher)
            ->post(route('gradebooks.columns.store', $gradebook), $data);

        $response->assertRedirect(route('gradebooks.show', $gradebook));
        $response->assertSessionHas('status', 'Kolom penilaian berhasil ditambahkan.');

        $this->assertDatabaseHas('assessment_columns', [
            'gradebook_id' => $gradebook->id,
            'name' => 'Ulangan Harian 1',
            'type' => 'uh',
            'weight' => 25.00,
            'max_score' => 100.00,
            'order' => 1,
        ]);
    }

    /**
     * 4. Teacher can update their own assessment column.
     */
    public function test_teacher_can_update_their_own_assessment_column(): void
    {
        $teacher = User::factory()->create();
        $gradebook = Gradebook::factory()->create(['user_id' => $teacher->id]);
        $column = AssessmentColumn::factory()->create([
            'gradebook_id' => $gradebook->id,
            'name' => 'Tugas Awal',
            'type' => 'tugas',
            'weight' => 15.00,
            'max_score' => 100.00,
            'order' => 1,
        ]);

        $response = $this->actingAs($teacher)
            ->put(route('gradebooks.columns.update', [$gradebook, $column]), [
                'name' => 'Tugas 1 Revisi',
                'type' => 'project',
                'weight' => 20.00,
                'max_score' => 100.00,
                'order' => 2,
            ]);

        $response->assertRedirect(route('gradebooks.show', $gradebook));
        $response->assertSessionHas('status', 'Kolom penilaian berhasil diperbarui.');

        $this->assertDatabaseHas('assessment_columns', [
            'id' => $column->id,
            'name' => 'Tugas 1 Revisi',
            'type' => 'project',
            'weight' => 20.00,
            'order' => 2,
        ]);
    }

    /**
     * 5. Teacher can delete their own assessment column.
     */
    public function test_teacher_can_delete_their_own_assessment_column(): void
    {
        $teacher = User::factory()->create();
        $gradebook = Gradebook::factory()->create(['user_id' => $teacher->id]);
        $column = AssessmentColumn::factory()->create([
            'gradebook_id' => $gradebook->id,
            'name' => 'Kolom Akan Dihapus',
        ]);

        $response = $this->actingAs($teacher)
            ->delete(route('gradebooks.columns.destroy', [$gradebook, $column]));

        $response->assertRedirect(route('gradebooks.show', $gradebook));
        $response->assertSessionHas('status', 'Kolom penilaian berhasil dihapus.');

        $this->assertDatabaseMissing('assessment_columns', [
            'id' => $column->id,
        ]);
    }

    /**
     * 6. Teacher cannot access another teacher's gradebook columns.
     */
    public function test_teacher_cannot_access_another_teachers_gradebook_columns(): void
    {
        $teacherA = User::factory()->create();
        $teacherB = User::factory()->create();

        $gradebookB = Gradebook::factory()->create(['user_id' => $teacherB->id]);
        $columnB = AssessmentColumn::factory()->create(['gradebook_id' => $gradebookB->id]);

        // Teacher A cannot view Teacher B's gradebook details
        $this->actingAs($teacherA)
            ->get(route('gradebooks.show', $gradebookB))
            ->assertStatus(403);

        // Teacher A cannot access Teacher B's column edit page
        $this->actingAs($teacherA)
            ->get(route('gradebooks.columns.edit', [$gradebookB, $columnB]))
            ->assertStatus(403);

        // Teacher A cannot access create column page for Teacher B's gradebook
        $this->actingAs($teacherA)
            ->get(route('gradebooks.columns.create', $gradebookB))
            ->assertStatus(403);
    }

    /**
     * 7. Teacher cannot create a column in another teacher's gradebook.
     */
    public function test_teacher_cannot_create_a_column_in_another_teachers_gradebook(): void
    {
        $teacherA = User::factory()->create();
        $teacherB = User::factory()->create();

        $gradebookB = Gradebook::factory()->create(['user_id' => $teacherB->id]);

        $response = $this->actingAs($teacherA)
            ->post(route('gradebooks.columns.store', $gradebookB), [
                'name' => 'Kolom Liar Teacher A',
                'type' => 'uh',
                'weight' => 20.00,
                'max_score' => 100.00,
                'order' => 1,
            ]);

        $response->assertStatus(403);

        $this->assertDatabaseMissing('assessment_columns', [
            'gradebook_id' => $gradebookB->id,
            'name' => 'Kolom Liar Teacher A',
        ]);
    }

    /**
     * 8. Teacher cannot update another teacher's column.
     */
    public function test_teacher_cannot_update_another_teachers_column(): void
    {
        $teacherA = User::factory()->create();
        $teacherB = User::factory()->create();

        $gradebookB = Gradebook::factory()->create(['user_id' => $teacherB->id]);
        $columnB = AssessmentColumn::factory()->create([
            'gradebook_id' => $gradebookB->id,
            'name' => 'Kolom Asli Teacher B',
            'weight' => 15.00,
        ]);

        $response = $this->actingAs($teacherA)
            ->put(route('gradebooks.columns.update', [$gradebookB, $columnB]), [
                'name' => 'Diretas Teacher A',
                'type' => 'ujian',
                'weight' => 50.00,
                'max_score' => 100.00,
                'order' => 1,
            ]);

        $response->assertStatus(403);

        $this->assertDatabaseHas('assessment_columns', [
            'id' => $columnB->id,
            'name' => 'Kolom Asli Teacher B',
            'weight' => 15.00,
        ]);
    }

    /**
     * 9. Teacher cannot delete another teacher's column.
     */
    public function test_teacher_cannot_delete_another_teachers_column(): void
    {
        $teacherA = User::factory()->create();
        $teacherB = User::factory()->create();

        $gradebookB = Gradebook::factory()->create(['user_id' => $teacherB->id]);
        $columnB = AssessmentColumn::factory()->create([
            'gradebook_id' => $gradebookB->id,
            'name' => 'Kolom Aman Teacher B',
        ]);

        $response = $this->actingAs($teacherA)
            ->delete(route('gradebooks.columns.destroy', [$gradebookB, $columnB]));

        $response->assertStatus(403);

        $this->assertDatabaseHas('assessment_columns', [
            'id' => $columnB->id,
            'name' => 'Kolom Aman Teacher B',
        ]);
    }

    /**
     * 10. Invalid assessment data is rejected.
     */
    public function test_invalid_assessment_data_is_rejected(): void
    {
        $teacher = User::factory()->create();
        $gradebook = Gradebook::factory()->create(['user_id' => $teacher->id]);

        $response = $this->actingAs($teacher)
            ->post(route('gradebooks.columns.store', $gradebook), [
                'name' => '', // Required
                'type' => '', // Required
                'weight' => 150, // Max 100
                'max_score' => -10, // Min 1
            ]);

        $response->assertSessionHasErrors([
            'name',
            'type',
            'weight',
            'max_score',
        ]);

        $this->assertDatabaseCount('assessment_columns', 0);
    }
}
