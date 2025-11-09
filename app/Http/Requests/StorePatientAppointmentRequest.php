<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePatientAppointmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\PatientAppointment::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $isPastAppointment = $this->date && now()->parse($this->date)->isPast();

        return [
            'date' => ['required', 'date'],
            'time' => ['nullable', 'date_format:H:i'],
            'healthcare_provider_id' => [
                $isPastAppointment ? 'required' : 'nullable',
                'exists:healthcare_providers,id',
            ],
            'location' => ['nullable', 'string', 'max:255'],
            'summary' => ['nullable', 'string'],
            'patient_notes' => [
                $isPastAppointment ? 'required' : 'nullable',
                'string',
            ],
            'documents.*' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
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
            'date.required' => 'Please select an appointment date.',
            'healthcare_provider_id.required' => 'Please select a provider for past appointments.',
            'healthcare_provider_id.exists' => 'The selected provider is invalid.',
            'patient_notes.required' => 'Please add notes for past appointments.',
            'documents.*.mimes' => 'Documents must be PDF, JPG, JPEG, or PNG files.',
            'documents.*.max' => 'Each document must not exceed 10MB.',
        ];
    }
}
