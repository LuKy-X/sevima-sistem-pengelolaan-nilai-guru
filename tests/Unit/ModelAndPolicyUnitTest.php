<?php

namespace Tests\Unit;

use App\Models\AcademicYear;
use App\Models\AssessmentColumn;
use App\Models\Classroom;
use App\Models\Gradebook;
use App\Models\Rubric;
use App\Models\Score;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use App\Policies\AssessmentColumnPolicy;
use App\Policies\GradebookPolicy;
use App\Policies\RubricPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModelAndPolicyUnitTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test Subject model relationships.
     */
    public function test_subject_relationships(): void
    {
        $subject = Subject::factory()->create();
        $gradebook = Gradebook::factory()->create(['subject_id' => $subject->id]);

        $this->assertTrue($subject->gradebooks->contains($gradebook));
        $this->assertEquals(1, $subject->gradebooks()->count());
    }

    /**
     * Test Student model relationships.
     */
    public function test_student_relationships(): void
    {
        $classroom = Classroom::factory()->create();
        $academicYear = AcademicYear::factory()->create();
        $student = Student::factory()->create();

        $student->classrooms()->attach($classroom->id, ['academic_year_id' => $academicYear->id]);

        $this->assertTrue($student->classrooms->contains($classroom));
        $this->assertTrue($student->academicYears->contains($academicYear));

        $gradebook = Gradebook::factory()->create([
            'classroom_id' => $classroom->id,
            'academic_year_id' => $academicYear->id,
        ]);
        $column = AssessmentColumn::factory()->create(['gradebook_id' => $gradebook->id]);
        $score = Score::create([
            'assessment_column_id' => $column->id,
            'student_id' => $student->id,
            'score' => 90,
        ]);

        $this->assertTrue($student->scores->contains($score));
    }

    /**
     * Test AcademicYear model relationships.
     */
    public function test_academic_year_relationships(): void
    {
        $academicYear = AcademicYear::factory()->create();
        $classroomA = Classroom::factory()->create();
        $classroomB = Classroom::factory()->create();
        $studentA = Student::factory()->create();
        $studentB = Student::factory()->create();

        $academicYear->students()->attach($studentA->id, ['classroom_id' => $classroomA->id]);
        $academicYear->classrooms()->attach($classroomB->id, ['student_id' => $studentB->id]);

        $this->assertTrue($academicYear->students->contains($studentA));
        $this->assertTrue($academicYear->classrooms->contains($classroomB));

        $gradebook = Gradebook::factory()->create(['academic_year_id' => $academicYear->id]);
        $this->assertTrue($academicYear->gradebooks->contains($gradebook));
    }

    /**
     * Test Classroom model relationships.
     */
    public function test_classroom_relationships(): void
    {
        $classroom = Classroom::factory()->create();
        $academicYear = AcademicYear::factory()->create();
        $student = Student::factory()->create();

        $classroom->students()->attach($student->id, ['academic_year_id' => $academicYear->id]);

        $this->assertTrue($classroom->students->contains($student));
        $this->assertTrue($classroom->academicYears->contains($academicYear));

        $gradebook = Gradebook::factory()->create(['classroom_id' => $classroom->id]);
        $this->assertTrue($classroom->gradebooks->contains($gradebook));
    }

    /**
     * Test RubricPolicy authorization checks.
     */
    public function test_rubric_policy(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $gradebook = Gradebook::factory()->create(['user_id' => $owner->id]);
        $column = AssessmentColumn::factory()->create(['gradebook_id' => $gradebook->id]);
        $rubric = Rubric::factory()->create(['assessment_column_id' => $column->id]);

        $policy = new RubricPolicy;

        // View
        $this->assertTrue($policy->view($owner, $rubric));
        $this->assertFalse($policy->view($otherUser, $rubric));

        // Create
        $this->assertTrue($policy->create($owner, $column));
        $this->assertFalse($policy->create($otherUser, $column));

        // Update
        $this->assertTrue($policy->update($owner, $rubric));
        $this->assertFalse($policy->update($otherUser, $rubric));

        // Delete
        $this->assertTrue($policy->delete($owner, $rubric));
        $this->assertFalse($policy->delete($otherUser, $rubric));
    }

    /**
     * Test AssessmentColumnPolicy authorization checks.
     */
    public function test_assessment_column_policy(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $gradebook = Gradebook::factory()->create(['user_id' => $owner->id]);
        $column = AssessmentColumn::factory()->create(['gradebook_id' => $gradebook->id]);

        $policy = new AssessmentColumnPolicy;

        $this->assertTrue($policy->view($owner, $column));
        $this->assertFalse($policy->view($otherUser, $column));

        $this->assertTrue($policy->create($owner, $gradebook));
        $this->assertFalse($policy->create($otherUser, $gradebook));

        $this->assertTrue($policy->update($owner, $column));
        $this->assertFalse($policy->update($otherUser, $column));

        $this->assertTrue($policy->delete($owner, $column));
        $this->assertFalse($policy->delete($otherUser, $column));
    }

    /**
     * Test GradebookPolicy authorization checks.
     */
    public function test_gradebook_policy(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $gradebook = Gradebook::factory()->create(['user_id' => $owner->id]);

        $policy = new GradebookPolicy;

        $this->assertTrue($policy->viewAny($owner));

        $this->assertTrue($policy->view($owner, $gradebook));
        $this->assertFalse($policy->view($otherUser, $gradebook));

        $this->assertTrue($policy->create($owner));

        $this->assertTrue($policy->update($owner, $gradebook));
        $this->assertFalse($policy->update($otherUser, $gradebook));

        $this->assertTrue($policy->delete($owner, $gradebook));
        $this->assertFalse($policy->delete($otherUser, $gradebook));
    }
}
