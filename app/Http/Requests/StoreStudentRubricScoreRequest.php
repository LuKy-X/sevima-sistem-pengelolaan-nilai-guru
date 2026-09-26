<?php

namespace App\Http\Requests;

use App\Models\Gradebook;
use App\Models\Rubric;
use App\Models\RubricLevel;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreStudentRubricScoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $gradebook = $this->route('gradebook');
        if (! ($gradebook instanceof Gradebook) && $gradebook) {
            $gradebook = Gradebook::find($gradebook);
        }

        return $gradebook !== null && $this->user()?->can('update', $gradebook);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'scores' => ['nullable', 'array'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $rubric = $this->route('rubric');
            if (! ($rubric instanceof Rubric) && $rubric) {
                $rubric = Rubric::find($rubric);
            }

            if (! $rubric) {
                return;
            }

            // Enforce that rubric total weight must be exactly 100% before scoring
            if (! $rubric->isConfigured()) {
                $validator->errors()->add(
                    'rubric',
                    'Total bobot kriteria rubrik harus tepat 100% sebelum penilaian dapat disimpan (saat ini '.$rubric->totalWeight().'%).'
                );

                return;
            }

            $submittedScores = $this->input('scores', []);
            if (! is_array($submittedScores)) {
                return;
            }

            $validLevels = RubricLevel::whereIn('rubric_criterion_id', $rubric->criteria->pluck('id'))
                ->get()
                ->keyBy('id');

            foreach ($submittedScores as $studentId => $criteriaScores) {
                if (! is_array($criteriaScores)) {
                    continue;
                }

                foreach ($criteriaScores as $criterionId => $scoreData) {
                    if ($scoreData === null || $scoreData === '') {
                        continue;
                    }

                    if (is_array($scoreData)) {
                        $actualScore = $scoreData['score'] ?? null;
                        $levelId = $scoreData['level_id'] ?? null;

                        if ($actualScore !== null && $actualScore !== '') {
                            if (! is_numeric($actualScore) || (float) $actualScore < 0 || (float) $actualScore > 1000) {
                                $validator->errors()->add(
                                    "scores.{$studentId}.{$criterionId}.score",
                                    'Nilai aktual harus berupa angka antara 0 dan 1000.'
                                );
                            }
                        }

                        if ($levelId !== null && $levelId !== '') {
                            $level = $validLevels->get($levelId);
                            if (! $level || (int) $level->rubric_criterion_id !== (int) $criterionId) {
                                $validator->errors()->add(
                                    "scores.{$studentId}.{$criterionId}.level_id",
                                    'Level penilaian yang dipilih tidak valid untuk kriteria ini.'
                                );
                            }
                        }
                    } else {
                        // Scalar value (backward-compatible level ID or direct score)
                        $level = $validLevels->get($scoreData);
                        if (! $level && ! is_numeric($scoreData)) {
                            $validator->errors()->add(
                                "scores.{$studentId}.{$criterionId}",
                                'Level penilaian yang dipilih tidak valid untuk kriteria ini.'
                            );
                        }
                    }
                }
            }
        });
    }
}
