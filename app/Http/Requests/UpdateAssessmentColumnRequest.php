<?php

namespace App\Http\Requests;

use App\Models\AssessmentColumn;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAssessmentColumnRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        /** @var AssessmentColumn|null $column */
        $column = $this->route('column');

        return $column !== null && $this->user()?->can('update', $column);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', 'string', 'max:30'],
            'weight' => ['required', 'numeric', 'min:0', 'max:100'],
            'max_score' => ['required', 'numeric', 'min:1', 'max:1000'],
            'order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama kolom penilaian wajib diisi.',
            'type.required' => 'Tipe penilaian wajib dipilih.',
            'weight.required' => 'Bobot penilaian wajib diisi.',
            'weight.min' => 'Bobot penilaian minimal 0%.',
            'weight.max' => 'Bobot penilaian maksimal 100%.',
            'max_score.min' => 'Skor maksimal minimal bernilai 1.',
        ];
    }
}
