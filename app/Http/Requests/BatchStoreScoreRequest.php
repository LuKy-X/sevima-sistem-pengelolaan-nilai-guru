<?php

namespace App\Http\Requests;

use App\Models\Gradebook;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class BatchStoreScoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        /** @var Gradebook|null $gradebook */
        $gradebook = $this->route('gradebook');

        return $gradebook !== null && (bool) $this->user()?->can('update', $gradebook);
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
     * Normalize submitted scores into a consistent list of items:
     * [ ['student_id' => int, 'assessment_column_id' => int, 'score' => float|string|null], ... ]
     *
     * @return array<int, array{student_id: int|string, assessment_column_id: int|string, score: mixed}>
     */
    public function normalizedScores(): array
    {
        // 1. Single score format at root level: ['student_id' => ..., 'assessment_column_id' => ..., 'score' => ...]
        if ($this->has('student_id') && $this->has('assessment_column_id')) {
            return [[
                'student_id' => $this->input('student_id'),
                'assessment_column_id' => $this->input('assessment_column_id'),
                'score' => $this->input('score'),
            ]];
        }

        $rawScores = $this->input('scores');
        if (! is_array($rawScores)) {
            return [];
        }

        $normalized = [];

        // 2. Sequential list of score records: [ ['student_id' => ..., 'assessment_column_id' => ..., 'score' => ...] ]
        if (array_is_list($rawScores)) {
            foreach ($rawScores as $item) {
                if (is_array($item) && isset($item['student_id'], $item['assessment_column_id'])) {
                    $normalized[] = [
                        'student_id' => $item['student_id'],
                        'assessment_column_id' => $item['assessment_column_id'],
                        'score' => $item['score'] ?? null,
                    ];
                }
            }

            return $normalized;
        }

        // 3. Grid format from matrix form: scores[student_id][column_id] = score
        foreach ($rawScores as $studentId => $columnScores) {
            if (is_array($columnScores)) {
                foreach ($columnScores as $columnId => $scoreValue) {
                    $normalized[] = [
                        'student_id' => $studentId,
                        'assessment_column_id' => $columnId,
                        'score' => $scoreValue !== '' ? $scoreValue : null,
                    ];
                }
            }
        }

        return $normalized;
    }

    /**
     * Configure the validator instance.
     *
     * @param  Validator  $validator
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            /** @var Gradebook|null $gradebook */
            $gradebook = $this->route('gradebook');
            if (! $gradebook) {
                return;
            }

            $columns = $gradebook->assessmentColumns->keyBy('id');
            $normalized = $this->normalizedScores();

            foreach ($normalized as $item) {
                $studentId = $item['student_id'];
                $colId = $item['assessment_column_id'];
                $scoreVal = $item['score'];

                // Verify that assessment column belongs to this gradebook
                if (! $columns->has($colId)) {
                    $validator->errors()->add('scores', "Kolom penilaian dengan ID {$colId} tidak terdaftar pada buku nilai ini.");
                    $validator->errors()->add('assessment_column_id', 'Kolom penilaian tidak valid.');

                    continue;
                }

                // Empty / null score is allowed (means unassigned or cleared score)
                if ($scoreVal === null || $scoreVal === '') {
                    continue;
                }

                // Must be numeric
                if (! is_numeric($scoreVal)) {
                    $msg = 'Nilai harus berupa angka valid.';
                    $validator->errors()->add("scores.{$studentId}.{$colId}", $msg);
                    $validator->errors()->add('score', $msg);
                    $validator->errors()->add('scores', $msg);

                    continue;
                }

                $numericScore = (float) $scoreVal;
                $column = $columns->get($colId);

                // Cannot be negative
                if ($numericScore < 0) {
                    $msg = 'Nilai tidak boleh bernilai negatif.';
                    $validator->errors()->add("scores.{$studentId}.{$colId}", $msg);
                    $validator->errors()->add('score', $msg);
                    $validator->errors()->add('scores', $msg);
                }

                // Cannot exceed column max_score
                if ($numericScore > $column->max_score) {
                    $msg = "Nilai tidak boleh melebihi skor maksimal ({$column->max_score}) untuk {$column->name}.";
                    $validator->errors()->add("scores.{$studentId}.{$colId}", $msg);
                    $validator->errors()->add('score', $msg);
                    $validator->errors()->add('scores', $msg);
                }
            }
        });
    }
}
