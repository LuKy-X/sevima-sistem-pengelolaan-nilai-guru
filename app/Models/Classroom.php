<?php

namespace App\Models;

use Database\Factories\ClassroomFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'level'])]
class Classroom extends Model
{
    /** @use HasFactory<ClassroomFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'level' => 'integer',
        ];
    }

    /**
     * Get all students enrolled in this classroom.
     *
     * @return BelongsToMany<Student, $this>
     */
    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'classroom_student')
            ->withPivot('academic_year_id')
            ->withTimestamps();
    }

    /**
     * Get all academic years this classroom has students in.
     *
     * @return BelongsToMany<AcademicYear, $this>
     */
    public function academicYears(): BelongsToMany
    {
        return $this->belongsToMany(AcademicYear::class, 'classroom_student')
            ->withPivot('student_id')
            ->withTimestamps();
    }

    /**
     * Get all gradebooks for this classroom.
     *
     * @return HasMany<Gradebook, $this>
     */
    public function gradebooks(): HasMany
    {
        return $this->hasMany(Gradebook::class);
    }
}
