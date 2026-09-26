<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\AssessmentColumn;
use App\Models\Classroom;
use App\Models\Gradebook;
use App\Models\Score;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseFoundationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that DatabaseSeeder successfully populates all required foundation data.
     */
    public function test_seeder_populates_required_foundation_data(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('academic_years', 1);
        $this->assertDatabaseCount('classrooms', 1);
        $this->assertDatabaseCount('subjects', 1);
        $this->assertDatabaseCount('students', 25);
        $this->assertDatabaseCount('classroom_student', 25);
        $this->assertDatabaseCount('gradebooks', 1);
        $this->assertDatabaseCount('assessment_columns', 5);

        $gradebook = Gradebook::first();
        $this->assertNotNull($gradebook);
        $this->assertEquals('ganjil', $gradebook->semester);
        $this->assertEquals('Budi Santoso, S.Kom.', $gradebook->user->name);
        $this->assertCount(5, $gradebook->assessmentColumns);
        $this->assertGreaterThan(0, Score::count());
    }

    /**
     * Test domain relationships across Gradebook, AssessmentColumn, Score, and Student.
     */
    public function test_domain_relationships_work_correctly(): void
    {
        $teacher = User::factory()->create();
        $academicYear = AcademicYear::factory()->create(['name' => '2026/2027']);
        $classroom = Classroom::factory()->create(['name' => 'X RPL 1', 'level' => 10]);
        $subject = Subject::factory()->create(['name' => 'Pemrograman Web', 'code' => 'PW']);

        $studentA = Student::factory()->create(['name' => 'Siswa A']);
        $studentB = Student::factory()->create(['name' => 'Siswa B']);

        $classroom->students()->attach([$studentA->id, $studentB->id], ['academic_year_id' => $academicYear->id]);

        $gradebook = Gradebook::create([
            'user_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'subject_id' => $subject->id,
            'academic_year_id' => $academicYear->id,
            'semester' => 'ganjil',
            'title' => 'Buku Nilai Test',
        ]);

        $column = AssessmentColumn::create([
            'gradebook_id' => $gradebook->id,
            'name' => 'Tugas 1',
            'type' => 'tugas',
            'weight' => 20.00,
            'max_score' => 100.00,
            'order' => 1,
        ]);

        $score = Score::create([
            'assessment_column_id' => $column->id,
            'student_id' => $studentA->id,
            'score' => 95.50,
            'notes' => 'Sangat baik',
        ]);

        // Assert relationships
        $this->assertEquals($teacher->id, $gradebook->user->id);
        $this->assertEquals($classroom->id, $gradebook->classroom->id);
        $this->assertEquals($subject->id, $gradebook->subject->id);
        $this->assertEquals($academicYear->id, $gradebook->academicYear->id);

        $this->assertTrue($gradebook->assessmentColumns->contains($column));
        $this->assertTrue($column->scores->contains($score));
        $this->assertEquals($studentA->id, $score->student->id);
        $this->assertEquals($column->id, $score->assessmentColumn->id);

        $this->assertCount(1, $gradebook->scores);
    }

    /**
     * Test unique constraint on gradebooks: user_id + classroom_id + subject_id + academic_year_id + semester.
     */
    public function test_gradebook_unique_context_constraint(): void
    {
        $teacher = User::factory()->create();
        $academicYear = AcademicYear::factory()->create();
        $classroom = Classroom::factory()->create();
        $subject = Subject::factory()->create();

        Gradebook::create([
            'user_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'subject_id' => $subject->id,
            'academic_year_id' => $academicYear->id,
            'semester' => 'ganjil',
        ]);

        $this->expectException(QueryException::class);

        // Attempting to duplicate the exact same context should throw QueryException
        Gradebook::create([
            'user_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'subject_id' => $subject->id,
            'academic_year_id' => $academicYear->id,
            'semester' => 'ganjil',
        ]);
    }

    /**
     * Test unique constraint on scores: assessment_column_id + student_id.
     */
    public function test_score_unique_constraint_per_student_and_column(): void
    {
        $gradebook = Gradebook::factory()->create();
        $column = AssessmentColumn::factory()->create(['gradebook_id' => $gradebook->id]);
        $student = Student::factory()->create();

        Score::create([
            'assessment_column_id' => $column->id,
            'student_id' => $student->id,
            'score' => 80.00,
        ]);

        $this->expectException(QueryException::class);

        // Attempting to insert duplicate score for same column and student
        Score::create([
            'assessment_column_id' => $column->id,
            'student_id' => $student->id,
            'score' => 90.00,
        ]);
    }

    /**
     * Test dynamic assessment columns order and bulk batch upserting.
     */
    public function test_dynamic_columns_and_batch_upsert_scores(): void
    {
        $gradebook = Gradebook::factory()->create();

        $col1 = AssessmentColumn::create([
            'gradebook_id' => $gradebook->id,
            'name' => 'Kolom 2',
            'type' => 'uh',
            'weight' => 20.00,
            'max_score' => 100.00,
            'order' => 2,
        ]);

        $col2 = AssessmentColumn::create([
            'gradebook_id' => $gradebook->id,
            'name' => 'Kolom 1',
            'type' => 'tugas',
            'weight' => 10.00,
            'max_score' => 100.00,
            'order' => 1,
        ]);

        $orderedColumns = $gradebook->fresh()->assessmentColumns;
        $this->assertEquals('Kolom 1', $orderedColumns->first()->name);
        $this->assertEquals('Kolom 2', $orderedColumns->last()->name);

        $student = Student::factory()->create();

        // Batch upsert scores atomically
        Score::upsert([
            [
                'assessment_column_id' => $col1->id,
                'student_id' => $student->id,
                'score' => 85.00,
                'notes' => 'Awal',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ], ['assessment_column_id', 'student_id'], ['score', 'notes', 'updated_at']);

        $this->assertDatabaseHas('scores', [
            'assessment_column_id' => $col1->id,
            'student_id' => $student->id,
            'score' => 85.00,
        ]);

        // Update via upsert
        Score::upsert([
            [
                'assessment_column_id' => $col1->id,
                'student_id' => $student->id,
                'score' => 92.50,
                'notes' => 'Perbaikan',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ], ['assessment_column_id', 'student_id'], ['score', 'notes', 'updated_at']);

        $this->assertDatabaseCount('scores', 1);
        $this->assertDatabaseHas('scores', [
            'assessment_column_id' => $col1->id,
            'student_id' => $student->id,
            'score' => 92.50,
            'notes' => 'Perbaikan',
        ]);
    }
}
