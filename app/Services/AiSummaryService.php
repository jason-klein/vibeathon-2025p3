<?php

namespace App\Services;

use App\Models\Patient;
use App\Models\PatientAppointment;
use App\Models\PatientAppointmentDocument;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use OpenAI\Laravel\Facades\OpenAI;
use Smalot\PdfParser\Parser as PdfParser;

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

    /**
     * Extract text from a PDF document.
     */
    protected function extractTextFromPdf(string $filePath): string
    {
        try {
            $parser = new PdfParser;
            $pdf = $parser->parseFile($filePath);
            $text = $pdf->getText();

            return trim($text);
        } catch (Exception $e) {
            Log::error('Failed to extract text from PDF', [
                'file' => $filePath,
                'error' => $e->getMessage(),
            ]);

            return '';
        }
    }

    /**
     * Extract text from an image using OCR (placeholder - requires additional setup).
     */
    protected function extractTextFromImage(string $filePath): string
    {
        // For now, return empty string for images
        // In production, integrate with OCR service like AWS Textract, Google Cloud Vision, or Tesseract
        Log::warning('Image OCR not yet implemented', ['file' => $filePath]);

        return 'Image document - text extraction not yet implemented.';
    }

    /**
     * Extract text content from a document file.
     */
    protected function extractTextFromDocument(string $filePath): string
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        return match ($extension) {
            'pdf' => $this->extractTextFromPdf($filePath),
            'jpg', 'jpeg', 'png' => $this->extractTextFromImage($filePath),
            default => 'Unsupported file type.',
        };
    }

    /**
     * Generate a detailed executive summary for an appointment document.
     */
    public function generateDocumentExecutiveSummary(PatientAppointmentDocument $document): string
    {
        $fullPath = Storage::disk('public')->path($document->file_path);

        if (! file_exists($fullPath)) {
            Log::error('Document file not found', ['path' => $fullPath]);

            return 'Document file not found.';
        }

        $extractedText = $this->extractTextFromDocument($fullPath);

        if (empty($extractedText) || $extractedText === 'Unsupported file type.' || str_contains($extractedText, 'not yet implemented')) {
            return $extractedText;
        }

        // Truncate if text is too long
        $maxLength = 10000;
        if (strlen($extractedText) > $maxLength) {
            $extractedText = substr($extractedText, 0, $maxLength).' [... truncated]';
        }

        try {
            $response = OpenAI::chat()->create([
                'model' => 'gpt-4o-mini',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a medical document summarization assistant. Create a detailed, comprehensive executive summary of the provided medical document. Include key findings, diagnoses, treatments, medications, procedures, test results, and any follow-up recommendations. Use clear, accessible language that a patient can understand. Be thorough and capture all important medical information.',
                    ],
                    [
                        'role' => 'user',
                        'content' => "Please provide a detailed executive summary of this medical document:\n\n{$extractedText}",
                    ],
                ],
                'max_tokens' => 1000,
                'temperature' => 0.5,
            ]);

            return $response->choices[0]->message->content;
        } catch (Exception $e) {
            Log::error('Failed to generate document executive summary', [
                'document_id' => $document->id,
                'error' => $e->getMessage(),
            ]);

            return 'Failed to generate summary. Please try again later.';
        }
    }

    /**
     * Generate an executive summary for an appointment including all documents.
     */
    public function generateAppointmentExecutiveSummary(PatientAppointment $appointment): string
    {
        $appointment->load(['documents', 'provider', 'tasks']);

        // Build context about the appointment
        $context = [];
        $context[] = "Date: {$appointment->date->format('M d, Y')}";

        if ($appointment->time) {
            $context[] = "Time: {$appointment->time->format('g:i A')}";
        }

        if ($appointment->provider) {
            $context[] = "Provider: {$appointment->provider->name}";
            if ($appointment->provider->specialty) {
                $context[] = "Specialty: {$appointment->provider->specialty}";
            }
        }

        if ($appointment->location) {
            $context[] = "Location: {$appointment->location}";
        }

        if ($appointment->summary) {
            $context[] = "\nAppointment Summary:\n{$appointment->summary}";
        }

        if ($appointment->patient_notes) {
            $context[] = "\nPatient Notes:\n{$appointment->patient_notes}";
        }

        // Include document summaries if available
        if ($appointment->documents->isNotEmpty()) {
            $context[] = "\n--- Attached Documents ---";
            foreach ($appointment->documents as $index => $document) {
                $docNumber = $index + 1;
                $context[] = "\nDocument {$docNumber}:";
                if ($document->summary) {
                    $context[] = $document->summary;
                } else {
                    $context[] = 'Summary not yet available.';
                }
            }
        }

        // Include related tasks if any
        if ($appointment->tasks->isNotEmpty()) {
            $context[] = "\n--- Related Tasks ---";
            foreach ($appointment->tasks as $task) {
                $status = $task->completed_at ? 'Completed' : 'Pending';
                $context[] = "- [{$status}] {$task->description}";
            }
        }

        $contextString = implode("\n", $context);

        try {
            $response = OpenAI::chat()->create([
                'model' => 'gpt-4o-mini',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a healthcare appointment summarization assistant. Create a comprehensive executive summary that synthesizes all information about this healthcare appointment. Include the purpose of the visit, key findings or discussions, any diagnoses, treatments or medications mentioned, test results if applicable, and follow-up actions. If multiple documents are attached, synthesize their content into a cohesive narrative. Use clear, accessible language for the patient. Be thorough and capture the complete picture of the healthcare encounter.',
                    ],
                    [
                        'role' => 'user',
                        'content' => "Please provide a comprehensive executive summary of this healthcare appointment:\n\n{$contextString}",
                    ],
                ],
                'max_tokens' => 1500,
                'temperature' => 0.5,
            ]);

            return $response->choices[0]->message->content;
        } catch (Exception $e) {
            Log::error('Failed to generate appointment executive summary', [
                'appointment_id' => $appointment->id,
                'error' => $e->getMessage(),
            ]);

            return 'Failed to generate summary. Please try again later.';
        }
    }
}
