<?php

namespace App\Models;

use Database\Factories\AcademicYearFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'is_active'])]
class AcademicYear extends Model
{
    /** @use HasFactory<AcademicYearFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get all gradebooks for this academic year.
     *
     * @return HasMany<Gradebook, $this>
     */
    public function gradebooks(): HasMany
    {
        return $this->hasMany(Gradebook::class);
    }

    /**
     * Get all classrooms active in this academic year.
     *
     * @return BelongsToMany<Classroom, $this>
     */
    public function classrooms(): BelongsToMany
    {
        return $this->belongsToMany(Classroom::class, 'classroom_student')
            ->withPivot('student_id')
            ->withTimestamps();
    }

    /**
     * Get all students enrolled in this academic year.
     *
     * @return BelongsToMany<Student, $this>
     */
    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'classroom_student')
            ->withPivot('classroom_id')
            ->withTimestamps();
    }
}
