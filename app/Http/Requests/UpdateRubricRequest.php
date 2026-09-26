<?php

namespace App\Http\Requests;

use App\Models\Gradebook;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateRubricRequest extends FormRequest
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
            'criteria' => ['nullable', 'array'],
            'criteria.*.id' => ['required_with:criteria', 'integer'],
            'criteria.*.name' => ['required_with:criteria', 'string', 'max:150'],
            'criteria.*.weight' => ['required_with:criteria', 'numeric', 'min:0.01', 'max:100'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $criteria = $this->input('criteria');
            if (is_array($criteria)) {
                $total = 0;
                foreach ($criteria as $criterionData) {
                    $total += (float) ($criterionData['weight'] ?? 0);
                }
                if ($total > 100.001) {
                    $validator->errors()->add('criteria', 'Total bobot kriteria tidak boleh melebihi 100% (saat ini '.round($total, 2).'%).');
                }
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
            'criteria.*.name' => 'nama kriteria',
            'criteria.*.weight' => 'bobot kriteria',
        ];
    }
}
