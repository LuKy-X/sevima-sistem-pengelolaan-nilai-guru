<?php

namespace App\Http\Requests;

use App\Models\Gradebook;
use App\Models\Rubric;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreRubricCriterionRequest extends FormRequest
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
            'weight' => ['required', 'numeric', 'min:0.01', 'max:100'],
            'order' => ['nullable', 'integer', 'min:0'],
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

            $currentTotal = (float) $rubric->criteria()->sum('weight');
            $incoming = (float) $this->input('weight', 0);

            if (($currentTotal + $incoming) > 100.001) {
                $available = max(0.0, 100.00 - $currentTotal);
                $validator->errors()->add(
                    'weight',
                    'Total bobot kriteria tidak boleh melebihi 100%. Saat ini sudah '.round($currentTotal, 2).'%, sisa bobot tersedia '.round($available, 2).'%.'
                );
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
            'name' => 'nama kriteria',
            'weight' => 'bobot kriteria',
            'description' => 'deskripsi kriteria',
        ];
    }
}
