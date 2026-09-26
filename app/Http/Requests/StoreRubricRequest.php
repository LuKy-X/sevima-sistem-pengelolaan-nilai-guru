<?php

namespace App\Http\Requests;

use App\Models\AssessmentColumn;
use App\Models\Gradebook;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreRubricRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $column = $this->route('column');
            if (! ($column instanceof AssessmentColumn) && $column) {
                $column = AssessmentColumn::find($column);
            }

            if ($column && $column->rubric()->exists()) {
                $validator->errors()->add('assessment_column', 'Kolom penilaian ini sudah memiliki rubrik.');
            }
        });
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nama rubrik',
            'description' => 'deskripsi rubrik',
        ];
    }
}
