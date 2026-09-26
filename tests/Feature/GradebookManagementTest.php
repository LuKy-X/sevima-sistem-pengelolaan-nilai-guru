<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Gradebook;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GradebookManagementTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 1. Guest cannot access any gradebook endpoints.
     */
    public function test_guest_cannot_access_gradebooks(): void
    {
        $this->get('/gradebooks')->assertRedirect('/login');
        $this->get('/gradebooks/create')->assertRedirect('/login');
        $this->post('/gradebooks', [])->assertRedirect('/login');
    }

    /**
     * 2. Authenticated teacher can see their own gradebooks and NOT others'.
     */
    public function test_authenticated_teacher_can_see_their_gradebooks(): void
    {
        $teacherA = User::factory()->create();
        $teacherB = User::factory()->create();

        $gradebookA = Gradebook::factory()->create([
            'user_id' => $teacherA->id,
            'title' => 'Buku Nilai Guru A Spesifik',
        ]);

        $gradebookB = Gradebook::factory()->create([
            'user_id' => $teacherB->id,
            'title' => 'Buku Nilai Guru B Rahasia',
        ]);

        $response = $this->actingAs($teacherA)->get('/gradebooks');

        $response->assertStatus(200);
        $response->assertSee('Buku Nilai Guru A Spesifik');
        $response->assertDontSee('Buku Nilai Guru B Rahasia');
    }

    /**
     * 3. Teacher can create a gradebook.
     */
    public function test_teacher_can_create_a_gradebook(): void
    {
        $teacher = User::factory()->create();
        $classroom = Classroom::factory()->create();
        $subject = Subject::factory()->create();
        $academicYear = AcademicYear::factory()->create();

        $data = [
            'classroom_id' => $classroom->id,
            'subject_id' => $subject->id,
            'academic_year_id' => $academicYear->id,
            'semester' => 'ganjil',
            'title' => 'Buku Nilai Web Programming',
        ];

        $response = $this->actingAs($teacher)->post('/gradebooks', $data);

        $gradebook = Gradebook::where('title', 'Buku Nilai Web Programming')->first();
        $this->assertNotNull($gradebook);
        $this->assertEquals($teacher->id, $gradebook->user_id);
        $this->assertEquals('ganjil', $gradebook->semester);

        $response->assertRedirect(route('gradebooks.show', $gradebook));
        $this->assertDatabaseHas('gradebooks', [
            'user_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'title' => 'Buku Nilai Web Programming',
        ]);
    }

    /**
     * 4. Teacher can view their own gradebook.
     */
    public function test_teacher_can_view_their_own_gradebook(): void
    {
        $teacher = User::factory()->create();
        $gradebook = Gradebook::factory()->create([
            'user_id' => $teacher->id,
            'title' => 'Detail Buku Nilai Saya',
        ]);

        $response = $this->actingAs($teacher)->get(route('gradebooks.show', $gradebook));

        $response->assertStatus(200);
        $response->assertSee('Detail Buku Nilai Saya');
        $response->assertSee($gradebook->subject->name);
        $response->assertSee($gradebook->classroom->name);
    }

    /**
     * 5. Teacher cannot view another teacher's gradebook (403 Forbidden).
     */
    public function test_teacher_cannot_view_another_teachers_gradebook(): void
    {
        $teacherA = User::factory()->create();
        $teacherB = User::factory()->create();

        $gradebookB = Gradebook::factory()->create([
            'user_id' => $teacherB->id,
        ]);

        $response = $this->actingAs($teacherA)->get(route('gradebooks.show', $gradebookB));

        $response->assertStatus(403);
    }

    /**
     * 6. Teacher can update their own gradebook.
     */
    public function test_teacher_can_update_their_own_gradebook(): void
    {
        $teacher = User::factory()->create();
        $classroom = Classroom::factory()->create();
        $subject = Subject::factory()->create();
        $academicYear = AcademicYear::factory()->create();

        $gradebook = Gradebook::factory()->create([
            'user_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'subject_id' => $subject->id,
            'academic_year_id' => $academicYear->id,
            'semester' => 'ganjil',
            'title' => 'Judul Lama',
        ]);

        $response = $this->actingAs($teacher)->put(route('gradebooks.update', $gradebook), [
            'classroom_id' => $classroom->id,
            'subject_id' => $subject->id,
            'academic_year_id' => $academicYear->id,
            'semester' => 'genap',
            'title' => 'Judul Baru Diperbarui',
        ]);

        $response->assertRedirect(route('gradebooks.show', $gradebook));
        $this->assertDatabaseHas('gradebooks', [
            'id' => $gradebook->id,
            'semester' => 'genap',
            'title' => 'Judul Baru Diperbarui',
        ]);
    }

    /**
     * 7. Teacher cannot update another teacher's gradebook (403 Forbidden).
     */
    public function test_teacher_cannot_update_another_teachers_gradebook(): void
    {
        $teacherA = User::factory()->create();
        $teacherB = User::factory()->create();

        $gradebookB = Gradebook::factory()->create([
            'user_id' => $teacherB->id,
            'title' => 'Judul Asli Guru B',
        ]);

        $response = $this->actingAs($teacherA)->put(route('gradebooks.update', $gradebookB), [
            'classroom_id' => $gradebookB->classroom_id,
            'subject_id' => $gradebookB->subject_id,
            'academic_year_id' => $gradebookB->academic_year_id,
            'semester' => 'genap',
            'title' => 'Diretas Guru A',
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseHas('gradebooks', [
            'id' => $gradebookB->id,
            'title' => 'Judul Asli Guru B',
        ]);
    }

    /**
     * 8. Teacher can delete their own gradebook.
     */
    public function test_teacher_can_delete_their_own_gradebook(): void
    {
        $teacher = User::factory()->create();
        $gradebook = Gradebook::factory()->create([
            'user_id' => $teacher->id,
        ]);

        $response = $this->actingAs($teacher)->delete(route('gradebooks.destroy', $gradebook));

        $response->assertRedirect(route('gradebooks.index'));
        $this->assertDatabaseMissing('gradebooks', [
            'id' => $gradebook->id,
        ]);
    }

    /**
     * 9. Teacher cannot delete another teacher's gradebook (403 Forbidden).
     */
    public function test_teacher_cannot_delete_another_teachers_gradebook(): void
    {
        $teacherA = User::factory()->create();
        $teacherB = User::factory()->create();

        $gradebookB = Gradebook::factory()->create([
            'user_id' => $teacherB->id,
        ]);

        $response = $this->actingAs($teacherA)->delete(route('gradebooks.destroy', $gradebookB));

        $response->assertStatus(403);
        $this->assertDatabaseHas('gradebooks', [
            'id' => $gradebookB->id,
        ]);
    }

    /**
     * 10. Invalid input is rejected when creating a gradebook.
     */
    public function test_invalid_input_is_rejected(): void
    {
        $teacher = User::factory()->create();

        $response = $this->actingAs($teacher)->post('/gradebooks', [
            'classroom_id' => 999999, // Non-existent foreign key
            'subject_id' => 999999,
            'academic_year_id' => 999999,
            'semester' => 'invalid-semester',
        ]);

        $response->assertSessionHasErrors([
            'classroom_id',
            'subject_id',
            'academic_year_id',
            'semester',
        ]);

        $this->assertDatabaseCount('gradebooks', 0);
    }
}
