<?php

namespace App\Console\Commands;

use App\Models\Patient;
use App\Models\PatientAppointment;
use App\Models\PatientAppointmentDocument;
use App\Models\PatientTask;
use App\Services\AiSummaryService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class MockHealthcareEncounter extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'mock:healthcare-encounter {patient_id} {--update-existing=}';

    /**
     * The console command description.
     */
    protected $description = 'Generate a realistic mock healthcare encounter with AI-powered summaries';

    public function __construct(
        protected AiSummaryService $aiSummaryService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $patient = Patient::find($this->argument('patient_id'));

        if (! $patient) {
            $this->error('Patient not found.');

            return self::FAILURE;
        }

        $updateExistingId = $this->option('update-existing');

        if ($updateExistingId) {
            $this->updateExistingAppointment($patient, $updateExistingId);
        } else {
            $this->createNewEncounter($patient);
        }

        // Update AI summaries
        $this->info('Generating AI summaries...');
        $this->aiSummaryService->updatePatientSummaries($patient);

        $this->info('Healthcare encounter generated successfully!');

        return self::SUCCESS;
    }

    /**
     * Create a new family physician encounter.
     */
    protected function createNewEncounter(Patient $patient): void
    {
        $this->info('Creating new family physician encounter...');

        // Create past appointment (2 weeks ago)
        $visitDate = now()->subWeeks(2);

        $scenarios = $this->getEncounterScenarios();
        $scenario = $scenarios[array_rand($scenarios)];

        // Get a healthcare provider for the appointment
        $provider = \App\Models\HealthcareProvider::inRandomOrder()->first();

        $appointment = PatientAppointment::create([
            'patient_id' => $patient->id,
            'healthcare_provider_id' => $provider?->id,
            'date' => $visitDate->toDateString(),
            'time' => '10:00:00',
            'location' => $provider?->location ?? 'Joplin Family Medicine, 2301 McClelland Blvd, Joplin, MO 64804',
            'summary' => $scenario['summary'],
            'patient_notes' => $scenario['patient_notes'],
        ]);

        // Generate PDF visit summary
        $this->generateVisitSummaryPdf($appointment, $scenario);

        // Create referral tasks
        if (! empty($scenario['referrals'])) {
            foreach ($scenario['referrals'] as $referral) {
                PatientTask::create([
                    'patient_id' => $patient->id,
                    'patient_appointment_id' => $appointment->id,
                    'description' => "Schedule {$referral['specialty']} appointment",
                    'instructions' => $referral['reason'],
                    'is_scheduling_task' => true,
                    'provider_specialty_needed' => $referral['specialty'],
                ]);
            }
        }

        // Create follow-up appointment
        PatientAppointment::create([
            'patient_id' => $patient->id,
            'healthcare_provider_id' => $provider?->id,
            'date' => now()->addWeeks(4)->toDateString(),
            'time' => '14:00:00',
            'location' => $provider?->location ?? 'Joplin Family Medicine, 2301 McClelland Blvd, Joplin, MO 64804',
            'summary' => 'Follow-up visit',
        ]);

        $this->info('Created appointment, documents, and tasks.');
    }

    /**
     * Update an existing future appointment to a past encounter.
     */
    protected function updateExistingAppointment(Patient $patient, int $appointmentId): void
    {
        $appointment = PatientAppointment::where('patient_id', $patient->id)
            ->where('id', $appointmentId)
            ->first();

        if (! $appointment) {
            $this->error('Appointment not found or does not belong to this patient.');

            return;
        }

        $this->info('Updating existing appointment...');

        $scenarios = $this->getEncounterScenarios();
        $scenario = $scenarios[array_rand($scenarios)];

        $appointment->update([
            'date' => now()->subDays(3)->toDateString(),
            'summary' => $scenario['summary'],
            'patient_notes' => $scenario['patient_notes'],
        ]);

        // Generate PDF visit summary
        $this->generateVisitSummaryPdf($appointment, $scenario);

        // Create referral tasks
        if (! empty($scenario['referrals'])) {
            foreach ($scenario['referrals'] as $referral) {
                PatientTask::create([
                    'patient_id' => $patient->id,
                    'patient_appointment_id' => $appointment->id,
                    'description' => "Schedule {$referral['specialty']} appointment",
                    'instructions' => $referral['reason'],
                    'is_scheduling_task' => true,
                    'provider_specialty_needed' => $referral['specialty'],
                ]);
            }
        }

        $this->info('Updated appointment with visit details.');
    }

    /**
     * Generate a PDF visit summary document.
     */
    protected function generateVisitSummaryPdf(PatientAppointment $appointment, array $scenario): void
    {
        $pdf = Pdf::loadView('pdf.visit-summary', [
            'providerName' => $appointment->provider?->name ?? 'Dr. Sarah Mitchell',
            'providerSpecialty' => $appointment->provider?->specialty ?? 'Family Medicine',
            'providerLocation' => $appointment->location,
            'patientName' => $appointment->patient->user->name,
            'visitDate' => $appointment->date->format('F d, Y'),
            'visitTime' => $appointment->time ? \Carbon\Carbon::parse($appointment->time)->format('g:i A') : null,
            'chiefComplaint' => $scenario['chief_complaint'] ?? null,
            'assessment' => $scenario['assessment'],
            'referrals' => $scenario['referrals'] ?? [],
            'instructions' => $scenario['instructions'] ?? null,
        ]);

        $filename = 'visit-summary-'.now()->format('Y-m-d-His').'.pdf';
        $path = 'appointment_docs/'.$filename;

        Storage::disk('public')->put($path, $pdf->output());

        PatientAppointmentDocument::create([
            'patient_appointment_id' => $appointment->id,
            'file_path' => $path,
            'summary' => 'Visit summary document',
        ]);
    }

    /**
     * Get realistic encounter scenarios.
     */
    protected function getEncounterScenarios(): array
    {
        return [
            [
                'chief_complaint' => 'Annual physical examination',
                'summary' => 'Annual physical exam with routine labs',
                'patient_notes' => 'Patient reports feeling well overall. Discussed importance of regular exercise and diet.',
                'assessment' => "Patient presents for annual physical examination. Overall health is good.\n\nVital signs within normal limits. Physical exam unremarkable.\n\nLabs ordered: Complete metabolic panel, lipid panel, HbA1c.\n\nContinue current medications. Encourage 30 minutes of exercise daily.",
                'instructions' => 'Continue current medications. Exercise 30 minutes daily. Follow up in 1 year or as needed.',
                'referrals' => [],
            ],
            [
                'chief_complaint' => 'Elevated blood pressure readings at home',
                'summary' => 'Hypertension management',
                'patient_notes' => 'Patient has been monitoring BP at home, readings averaging 145/92.',
                'assessment' => "Patient reports elevated blood pressure readings at home over the past month.\n\nIn-office BP: 148/94 mmHg. Heart rate regular. No signs of end-organ damage.\n\nDiagnosis: Essential hypertension.\n\nPlan:\n- Start Lisinopril 10mg daily\n- Reduce sodium intake\n- Refer to cardiology for comprehensive evaluation\n- Recheck BP in 2 weeks",
                'instructions' => 'Take Lisinopril 10mg every morning. Monitor blood pressure daily. Limit salt intake. Follow up in 2 weeks.',
                'referrals' => [
                    ['specialty' => 'Cardiology', 'reason' => 'Comprehensive cardiovascular evaluation for newly diagnosed hypertension'],
                ],
            ],
            [
                'chief_complaint' => 'Knee pain and stiffness',
                'summary' => 'Knee osteoarthritis evaluation',
                'patient_notes' => 'Right knee pain worse with activity, better with rest. Duration: 6 months.',
                'assessment' => "Patient presents with chronic right knee pain and stiffness.\n\nPhysical exam reveals decreased range of motion, crepitus, no effusion.\n\nX-ray ordered shows moderate osteoarthritis.\n\nPlan:\n- Trial of NSAIDs (Ibuprofen 400mg TID with food)\n- Physical therapy referral\n- Orthopedic consultation for further management options\n- Weight management counseling",
                'instructions' => 'Take Ibuprofen 400mg three times daily with food. Apply ice after activity. Complete physical therapy exercises. Follow up in 4 weeks.',
                'referrals' => [
                    ['specialty' => 'Orthopedics', 'reason' => 'Evaluation and management of right knee osteoarthritis'],
                    ['specialty' => 'Physical Therapy', 'reason' => 'Knee strengthening and mobility exercises'],
                ],
            ],
            [
                'chief_complaint' => 'Fatigue and difficulty concentrating',
                'summary' => 'Thyroid disorder evaluation',
                'patient_notes' => 'Patient reports fatigue for 3 months, weight gain of 10 lbs, cold intolerance.',
                'assessment' => "Patient presents with fatigue, weight gain, and cold intolerance.\n\nPhysical exam: slightly enlarged thyroid, dry skin, delayed reflexes.\n\nLabs ordered: TSH, Free T4, Complete Metabolic Panel.\n\nPreliminary diagnosis: Hypothyroidism (pending lab confirmation).\n\nPlan:\n- Await lab results\n- Likely start Levothyroxine if TSH elevated\n- Endocrinology referral for ongoing management\n- Recheck labs in 6 weeks",
                'instructions' => 'Lab work scheduled for tomorrow morning (fasting). Will call with results in 3-5 days. Follow up in 2 weeks to discuss treatment plan.',
                'referrals' => [
                    ['specialty' => 'Endocrinology', 'reason' => 'Evaluation and management of suspected hypothyroidism'],
                ],
            ],
            [
                'chief_complaint' => 'Persistent cough and shortness of breath',
                'summary' => 'Respiratory evaluation',
                'patient_notes' => 'Dry cough for 6 weeks, worse at night. Denies fever or chest pain.',
                'assessment' => "Patient presents with persistent dry cough and mild dyspnea on exertion.\n\nLungs: Mild expiratory wheezes bilaterally. No rales or rhonchi.\n\nChest X-ray ordered: Clear, no infiltrates.\n\nDiagnosis: Possible asthma or chronic bronchitis.\n\nPlan:\n- Trial of Albuterol inhaler\n- Pulmonology referral for pulmonary function tests\n- Avoid respiratory irritants\n- Follow up in 3 weeks",
                'instructions' => 'Use Albuterol inhaler 2 puffs every 4-6 hours as needed for shortness of breath. Avoid smoke and strong odors. Follow up in 3 weeks.',
                'referrals' => [
                    ['specialty' => 'Pulmonology', 'reason' => 'Pulmonary function testing and evaluation for chronic respiratory symptoms'],
                ],
            ],
        ];
    }
}
