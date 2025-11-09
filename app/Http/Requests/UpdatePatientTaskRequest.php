<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePatientTaskRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $task = $this->route('task');

        return $this->user()->can('update', $task);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'description' => ['required', 'string', 'max:255'],
            'instructions' => ['nullable', 'string'],
            'patient_appointment_id' => ['nullable', 'exists:patient_appointments,id'],
            'is_scheduling_task' => ['nullable', 'boolean'],
            'provider_specialty_needed' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Get custom error messages for validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'description.required' => 'Please enter a task description.',
            'description.max' => 'Task description must not exceed 255 characters.',
            'patient_appointment_id.exists' => 'The selected appointment is invalid.',
            'provider_specialty_needed.max' => 'Provider specialty must not exceed 255 characters.',
        ];
    }
}
