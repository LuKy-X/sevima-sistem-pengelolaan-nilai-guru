<?php

namespace App\Http\Requests;

use App\Models\Gradebook;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGradebookRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        /** @var Gradebook|null $gradebook */
        $gradebook = $this->route('gradebook');

        return $gradebook !== null && $this->user()?->can('update', $gradebook);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Gradebook|null $gradebook */
        $gradebook = $this->route('gradebook');

        return [
            'classroom_id' => ['required', 'integer', 'exists:classrooms,id'],
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'academic_year_id' => ['required', 'integer', 'exists:academic_years,id'],
            'semester' => [
                'required',
                'string',
                Rule::in(['ganjil', 'genap']),
                Rule::unique('gradebooks')->where(fn ($query) => $query
                    ->where('user_id', $this->user()->id)
                    ->where('classroom_id', $this->input('classroom_id'))
                    ->where('subject_id', $this->input('subject_id'))
                    ->where('academic_year_id', $this->input('academic_year_id'))
                )->ignore($gradebook?->id),
            ],
            'title' => ['nullable', 'string', 'max:255'],
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
            'semester.unique' => 'Buku nilai untuk kelas, mata pelajaran, tahun ajaran, dan semester tersebut sudah ada.',
        ];
    }
}
