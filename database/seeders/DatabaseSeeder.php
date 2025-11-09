<?php

namespace Database\Seeders;

use App\Models\HealthcareProvider;
use App\Models\HealthcareSystem;
use App\Models\Patient;
use App\Models\PatientAppointment;
use App\Models\PatientAppointmentDocument;
use App\Models\PatientTask;
use App\Models\User;
use App\Services\AiSummaryService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create test user (patient record auto-created by UserFactory)
        $user = User::factory()->withoutTwoFactor()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password#1'),
        ]);

        // Create healthcare systems
        $preferredSystem = HealthcareSystem::create([
            'name' => 'Freeman Health System',
            'is_preferred' => true,
        ]);

        HealthcareSystem::create([
            'name' => 'Mercy Hospital Joplin',
            'is_preferred' => false,
        ]);

        HealthcareSystem::create([
            'name' => 'Independent Providers',
            'is_preferred' => false,
        ]);

        // Define all specialties
        $specialties = [
            'Family Medicine',
            'Cardiology',
            'Orthopedics',
            'Endocrinology',
            'Pulmonology',
            'Radiology',
            'Physical Therapy',
            'Dermatology',
            'Neurology',
            'Gastroenterology',
        ];

        // Create at least one provider per specialty in the preferred system
        foreach ($specialties as $specialty) {
            HealthcareProvider::factory()->create([
                'healthcare_system_id' => $preferredSystem->id,
                'specialty' => $specialty,
            ]);
        }

        // Create at least one provider per specialty in a non-preferred system (Mercy)
        foreach ($specialties as $specialty) {
            HealthcareProvider::factory()->create([
                'healthcare_system_id' => 2,
                'specialty' => $specialty,
            ]);
        }

        // Add additional random providers to preferred system
        HealthcareProvider::factory()->count(5)->create([
            'healthcare_system_id' => $preferredSystem->id,
        ]);

        // Add additional random providers to non-preferred systems
        HealthcareProvider::factory()->count(5)->create([
            'healthcare_system_id' => 2,
        ]);

        HealthcareProvider::factory()->count(5)->create([
            'healthcare_system_id' => 3,
        ]);

        // Seed community partners and events
        $this->call([
            CommunityPartnerSeeder::class,
            CommunityEventSeeder::class,
        ]);

        // Generate mock healthcare encounter for test user
        Artisan::call('mock:healthcare-encounter', [
            'patient_id' => $user->patient->id,
        ]);

        // Create Jesse Wolffe demo user with cardiac healthcare journey
        $jesse = User::factory()->withoutTwoFactor()->create([
            'name' => 'Jesse Wolffe',
            'email' => 'jesse@example.com',
            'password' => bcrypt('password#1'),
        ]);

        $this->createJesseHealthcareJourney($jesse->patient);
    }

    /**
     * Create Jesse Wolffe's cardiac monitoring healthcare journey.
     */
    protected function createJesseHealthcareJourney(Patient $patient): void
    {
        // Get providers by specialty
        $familyMedicineProvider = HealthcareProvider::where('specialty', 'Family Medicine')->first();
        $cardiologyProvider = HealthcareProvider::where('specialty', 'Cardiology')->first();
        $endocrinologyProvider = HealthcareProvider::where('specialty', 'Endocrinology')->first();
        $radiologyProvider = HealthcareProvider::where('specialty', 'Radiology')->first();

        // Appointment 1: Annual Physical Exam (~11 months ago) - Initial concern detected
        $appointment1 = PatientAppointment::create([
            'patient_id' => $patient->id,
            'healthcare_provider_id' => $familyMedicineProvider?->id,
            'date' => now()->subMonths(11)->toDateString(),
            'time' => '09:00:00',
            'location' => $familyMedicineProvider?->location ?? 'Freeman Health System, 932 E 34th St, Joplin, MO 64804',
            'summary' => 'Annual physical exam with cardiovascular concerns',
            'patient_notes' => 'Mentioned occasional chest discomfort during exercise. Blood pressure slightly elevated.',
        ]);

        // Appointment 2: Cardiology Consultation (~10 months ago) - Initial evaluation
        $appointment2 = PatientAppointment::create([
            'patient_id' => $patient->id,
            'healthcare_provider_id' => $cardiologyProvider?->id,
            'date' => now()->subMonths(10)->toDateString(),
            'time' => '14:30:00',
            'location' => $cardiologyProvider?->location ?? 'Freeman Heart & Vascular Institute, 932 E 34th St, Joplin, MO 64804',
            'summary' => 'Initial cardiology consultation with EKG',
            'patient_notes' => 'EKG showed occasional PVCs. Stress test scheduled.',
        ]);

        $this->generateVisitSummaryPdf($appointment2, [
            'chief_complaint' => 'Chest discomfort during exercise, elevated blood pressure',
            'assessment' => "Patient referred from primary care for cardiovascular evaluation.\n\nHistory: 45-year-old patient reports occasional chest discomfort with exertion, resolves with rest. Blood pressure readings at home averaging 138/88.\n\nPhysical Exam: Heart sounds regular, no murmurs. Lungs clear.\n\nEKG: Occasional premature ventricular contractions (PVCs), otherwise normal sinus rhythm.\n\nPlan:\n- Schedule stress test\n- Start low-dose beta blocker (Metoprolol 25mg daily)\n- Lifestyle modifications: reduce sodium, increase exercise gradually\n- Follow-up after stress test results",
            'instructions' => 'Take Metoprolol 25mg every morning. Continue monitoring blood pressure at home. Schedule stress test within 2 weeks.',
            'referrals' => [],
        ]);

        // Appointment 3: Follow-up Cardiology (~8 months ago) - Stress test results
        $appointment3 = PatientAppointment::create([
            'patient_id' => $patient->id,
            'healthcare_provider_id' => $cardiologyProvider?->id,
            'date' => now()->subMonths(8)->toDateString(),
            'time' => '10:00:00',
            'location' => $cardiologyProvider?->location ?? 'Freeman Heart & Vascular Institute, 932 E 34th St, Joplin, MO 64804',
            'summary' => 'Stress test results review',
            'patient_notes' => 'Stress test showed good exercise capacity. No significant ischemia detected.',
        ]);

        // Appointment 4: Family Medicine Follow-up (~6 months ago) - Lifestyle check
        $appointment4 = PatientAppointment::create([
            'patient_id' => $patient->id,
            'healthcare_provider_id' => $familyMedicineProvider?->id,
            'date' => now()->subMonths(6)->toDateString(),
            'time' => '11:00:00',
            'location' => $familyMedicineProvider?->location ?? 'Freeman Health System, 932 E 34th St, Joplin, MO 64804',
            'summary' => 'Follow-up visit for blood pressure and lifestyle modifications',
            'patient_notes' => 'Blood pressure improved to 128/82. Lost 8 lbs. Feeling better overall.',
        ]);

        $this->generateVisitSummaryPdf($appointment4, [
            'chief_complaint' => 'Routine follow-up for cardiovascular health',
            'assessment' => "Patient showing excellent progress with lifestyle modifications.\n\nBlood pressure: 128/82 (improved from 138/88)\nWeight loss: 8 lbs over 5 months\nExercise tolerance: Improved\n\nDiscussed sustainable lifestyle changes for long-term cardiovascular health:\n- Recommended participation in community wellness activities\n- Suggested joining local running groups or walking clubs for social exercise (Walk With A Doc, community 5Ks)\n- Encouraged shopping at farmers markets for fresh produce and heart-healthy nutrition\n- Emphasized importance of regular physical activity: 30 minutes daily\n\nPatient expressed interest in exploring local wellness events and community fitness opportunities.\n\nPlan:\n- Continue current medications\n- Maintain healthy lifestyle with community engagement\n- Follow-up in 2 months",
            'instructions' => 'Continue Metoprolol 25mg daily. Explore local community runs, walking groups, and farmers markets. Aim for 30 minutes of moderate exercise daily. Monitor blood pressure weekly.',
            'referrals' => [],
        ]);

        // Appointment 5: Cardiology Check-up (~4 months ago) - Continued monitoring
        PatientAppointment::create([
            'patient_id' => $patient->id,
            'healthcare_provider_id' => $cardiologyProvider?->id,
            'date' => now()->subMonths(4)->toDateString(),
            'time' => '15:00:00',
            'location' => $cardiologyProvider?->location ?? 'Freeman Heart & Vascular Institute, 932 E 34th St, Joplin, MO 64804',
            'summary' => 'Routine cardiology follow-up',
            'patient_notes' => 'Doing well on medications. No recent chest discomfort. Blood pressure stable.',
        ]);

        // Appointment 6: Endocrinology (~3 months ago) - Thyroid check (can affect heart rhythm)
        PatientAppointment::create([
            'patient_id' => $patient->id,
            'healthcare_provider_id' => $endocrinologyProvider?->id,
            'date' => now()->subMonths(3)->toDateString(),
            'time' => '13:30:00',
            'location' => $endocrinologyProvider?->location ?? 'Freeman Health System, 932 E 34th St, Joplin, MO 64804',
            'summary' => 'Thyroid evaluation for cardiac rhythm concerns',
            'patient_notes' => 'Thyroid labs came back normal. Ruled out thyroid contribution to heart rhythm issues.',
        ]);

        // Most Recent: Family Doctor Visit (~2 weeks ago) - New symptoms requiring investigation
        $recentAppointment = PatientAppointment::create([
            'patient_id' => $patient->id,
            'healthcare_provider_id' => $familyMedicineProvider?->id,
            'date' => now()->subWeeks(2)->toDateString(),
            'time' => '10:30:00',
            'location' => $familyMedicineProvider?->location ?? 'Freeman Health System, 932 E 34th St, Joplin, MO 64804',
            'summary' => 'New chest discomfort and shortness of breath on exertion',
            'patient_notes' => 'Experiencing chest tightness when climbing stairs. More fatigued than usual.',
        ]);

        $this->generateVisitSummaryPdf($recentAppointment, [
            'chief_complaint' => 'New onset chest discomfort and shortness of breath with exertion',
            'assessment' => "Patient with history of PVCs and hypertension (well-controlled) presents with new symptoms.\n\nReports: Chest tightness when climbing stairs or brisk walking, started 2 weeks ago. Shortness of breath with moderate exertion. Denies rest symptoms.\n\nPhysical Exam: BP 132/84, HR 78 regular. Heart sounds normal. Lungs clear bilaterally.\n\nConcern: Need to rule out progression of cardiac condition or new ischemia.\n\nPlan:\n- Order chest X-ray to evaluate cardiac silhouette and rule out pulmonary causes\n- Urgent cardiology referral for repeat stress test and possible cardiac catheterization\n- Continue current medications\n- Avoid strenuous activity until further evaluation\n- Follow-up in 1 week or sooner if symptoms worsen",
            'instructions' => 'Schedule chest X-ray within next few days. Schedule urgent cardiology appointment. Continue all current medications. Avoid heavy exercise. Call if chest pain worsens or occurs at rest.',
            'referrals' => [
                ['specialty' => 'Radiology', 'reason' => 'Chest X-ray to evaluate cardiac size and rule out pulmonary pathology'],
                ['specialty' => 'Cardiology', 'reason' => 'Urgent evaluation for new chest symptoms, possible repeat stress test or cardiac catheterization'],
            ],
        ]);

        // Task 1: Schedule X-ray (Radiology) - COMPLETED with scheduled appointment
        $xrayTask = PatientTask::create([
            'patient_id' => $patient->id,
            'patient_appointment_id' => $recentAppointment->id,
            'description' => 'Schedule X-ray (Radiology)',
            'instructions' => 'Chest X-ray to evaluate cardiac size and rule out pulmonary pathology',
            'is_scheduling_task' => true,
            'provider_specialty_needed' => 'Radiology',
            'completed_at' => now()->subDays(3),
        ]);

        // Future X-ray appointment (linked to completed task)
        PatientAppointment::create([
            'patient_id' => $patient->id,
            'healthcare_provider_id' => $radiologyProvider?->id,
            'date' => now()->addWeek()->toDateString(),
            'time' => '08:00:00',
            'location' => $radiologyProvider?->location ?? 'Freeman Imaging Center, 932 E 34th St, Joplin, MO 64804',
            'summary' => 'Chest X-ray',
            'scheduled_from_task_id' => $xrayTask->id,
        ]);

        // Task 2: Schedule Cardiology appointment - OPEN/INCOMPLETE
        PatientTask::create([
            'patient_id' => $patient->id,
            'patient_appointment_id' => $recentAppointment->id,
            'description' => 'Schedule Cardiology appointment',
            'instructions' => 'Urgent evaluation for new chest symptoms, possible repeat stress test or cardiac catheterization',
            'is_scheduling_task' => true,
            'provider_specialty_needed' => 'Cardiology',
            'completed_at' => null,
        ]);

        // Task 3: Research local wellness activities (from appointment 4 recommendations)
        PatientTask::create([
            'patient_id' => $patient->id,
            'patient_appointment_id' => $appointment4->id,
            'description' => 'Research local community wellness activities - farmers markets and running groups',
            'instructions' => 'Explore local farmers markets for fresh produce and community running/walking events (5Ks, Walk With A Doc) for heart-healthy exercise',
            'is_scheduling_task' => false,
            'provider_specialty_needed' => null,
            'completed_at' => null,
        ]);

        // Generate AI summaries for Jesse's patient record and appointments
        $aiSummaryService = app(AiSummaryService::class);
        $aiSummaryService->updatePatientSummaries($patient);
    }

    /**
     * Generate a PDF visit summary document for an appointment.
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
}
