<?php

namespace App\Models;

use Database\Factories\AssessmentColumnFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['gradebook_id', 'name', 'type', 'weight', 'max_score', 'order'])]
class AssessmentColumn extends Model
{
    /** @use HasFactory<AssessmentColumnFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'weight' => 'float',
            'max_score' => 'float',
            'order' => 'integer',
        ];
    }

    /**
     * Get the gradebook this assessment column belongs to.
     *
     * @return BelongsTo<Gradebook, $this>
     */
    public function gradebook(): BelongsTo
    {
        return $this->belongsTo(Gradebook::class);
    }

    /**
     * Get all scores recorded under this assessment column.
     *
     * @return HasMany<Score, $this>
     */
    public function scores(): HasMany
    {
        return $this->hasMany(Score::class);
    }
}
