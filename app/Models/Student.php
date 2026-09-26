<?php

namespace App\Models;

use Database\Factories\StudentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['nisn', 'name', 'gender'])]
class Student extends Model
{
    /** @use HasFactory<StudentFactory> */
    use HasFactory;

    /**
     * Get all classrooms this student is enrolled in.
     *
     * @return BelongsToMany<Classroom, $this>
     */
    public function classrooms(): BelongsToMany
    {
        return $this->belongsToMany(Classroom::class, 'classroom_student')
            ->withPivot('academic_year_id')
            ->withTimestamps();
    }

    /**
     * Get all academic years this student has enrollments in.
     *
     * @return BelongsToMany<AcademicYear, $this>
     */
    public function academicYears(): BelongsToMany
    {
        return $this->belongsToMany(AcademicYear::class, 'classroom_student')
            ->withPivot('classroom_id')
            ->withTimestamps();
    }

    /**
     * Get all scores recorded for this student.
     *
     * @return HasMany<Score, $this>
     */
    public function scores(): HasMany
    {
        return $this->hasMany(Score::class);
    }
}
