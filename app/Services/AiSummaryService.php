<?php

namespace App\Services;

use App\Models\Patient;
use OpenAI\Laravel\Facades\OpenAI;

class AiSummaryService
{
    /**
     * Generate a Plain English Patient Record from all past appointments.
     */
    public function generatePlainEnglishRecord(Patient $patient): string
    {
        $pastAppointments = $patient->appointments()
            ->where('date', '<', now()->toDateString())
            ->orderBy('date', 'desc')
            ->get();

        if ($pastAppointments->isEmpty()) {
            return 'No healthcare encounters recorded yet.';
        }

        $encounterSummaries = $pastAppointments->map(function ($appointment) {
            return sprintf(
                "Date: %s\nProvider: %s\nSummary: %s\nPatient Notes: %s",
                $appointment->date->format('M d, Y'),
                $appointment->partner ?? 'Unknown',
                $appointment->summary ?? 'No summary',
                $appointment->patient_notes ?? 'No notes'
            );
        })->join("\n\n---\n\n");

        $response = OpenAI::chat()->create([
            'model' => 'gpt-4o-mini',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are a medical records assistant. Convert the following healthcare encounter records into a cohesive, chronological Plain English Patient Record. Write in third person, focusing on medical history, diagnoses, treatments, and ongoing care. Make it clear and accessible to the patient. Include specific dates and provider names where available.',
                ],
                [
                    'role' => 'user',
                    'content' => $encounterSummaries,
                ],
            ],
            'max_tokens' => 2000,
            'temperature' => 0.7,
        ]);

        return $response->choices[0]->message->content;
    }

    /**
     * Generate an Executive Summary of the patient's current health status.
     */
    public function generateExecutiveSummary(Patient $patient): string
    {
        $recentAppointments = $patient->appointments()
            ->where('date', '<', now()->toDateString())
            ->orderBy('date', 'desc')
            ->limit(5)
            ->get();

        if ($recentAppointments->isEmpty()) {
            return 'No recent healthcare encounters to summarize.';
        }

        $encounterSummaries = $recentAppointments->map(function ($appointment) {
            return sprintf(
                "Date: %s\nProvider: %s\nSummary: %s",
                $appointment->date->format('M d, Y'),
                $appointment->partner ?? 'Unknown',
                $appointment->summary ?? 'No summary'
            );
        })->join("\n\n");

        $response = OpenAI::chat()->create([
            'model' => 'gpt-4o-mini',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are a medical records assistant. Create a concise Executive Summary (2-3 paragraphs) of the patient\'s current health status based on their most recent healthcare encounters. Focus on active conditions, ongoing treatments, and any important follow-up actions. Write in clear, accessible language for the patient.',
                ],
                [
                    'role' => 'user',
                    'content' => $encounterSummaries,
                ],
            ],
            'max_tokens' => 500,
            'temperature' => 0.7,
        ]);

        return $response->choices[0]->message->content;
    }

    /**
     * Update patient summaries if there are new past appointments.
     */
    public function updatePatientSummaries(Patient $patient): void
    {
        $latestPastAppointment = $patient->appointments()
            ->where('date', '<', now()->toDateString())
            ->orderBy('date', 'desc')
            ->first();

        if (! $latestPastAppointment) {
            return;
        }

        // Only regenerate if the latest past appointment is newer than the last summary update
        if ($patient->executive_summary_updated_at &&
            $latestPastAppointment->updated_at <= $patient->executive_summary_updated_at) {
            return;
        }

        $patient->update([
            'plain_english_record' => $this->generatePlainEnglishRecord($patient),
            'executive_summary' => $this->generateExecutiveSummary($patient),
            'executive_summary_updated_at' => now(),
        ]);
    }
}
