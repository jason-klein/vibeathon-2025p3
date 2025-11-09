<?php

namespace App\Console\Commands;

use App\Models\HealthcareProvider;
use App\Models\HealthcareSystem;
use App\Models\Patient;
use App\Models\PatientAppointment;
use App\Models\PatientTask;
use Illuminate\Console\Command;

class SeedPatientData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'seed:patient {patient_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed fake appointments and tasks for a patient';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $patientId = $this->argument('patient_id');
        $patient = Patient::find($patientId);

        if (! $patient) {
            $this->error("Patient {$patientId} not found!");

            return 1;
        }

        $this->info("Patient {$patientId} found: User {$patient->user->name}");
        $this->info('Creating fake records...');
        $this->newLine();

        // Get or create healthcare systems and providers
        $system1 = HealthcareSystem::firstOrCreate(
            ['name' => 'Freeman Health System'],
            ['is_preferred' => true]
        );

        $system2 = HealthcareSystem::firstOrCreate(
            ['name' => 'Mercy Hospital Joplin'],
            ['is_preferred' => false]
        );

        // Create providers with Joplin, MO area coordinates
        $cardiology = HealthcareProvider::firstOrCreate(
            ['name' => 'Dr. Sarah Martinez', 'specialty' => 'Cardiology'],
            [
                'healthcare_system_id' => $system1->id,
                'location' => '1102 W 32nd St, Joplin, MO 64804',
                'latitude' => 37.0612,
                'longitude' => -94.5133,
                'phone' => '(417) 347-8000',
                'email' => 'cardiology@freeman.com',
            ]
        );

        $primaryCare = HealthcareProvider::firstOrCreate(
            ['name' => 'Dr. James Peterson', 'specialty' => 'Family Medicine'],
            [
                'healthcare_system_id' => $system1->id,
                'location' => '932 E 34th St, Joplin, MO 64804',
                'latitude' => 37.0589,
                'longitude' => -94.4998,
                'phone' => '(417) 347-1234',
                'email' => 'familymed@freeman.com',
            ]
        );

        $physical = HealthcareProvider::firstOrCreate(
            ['name' => 'Dr. Michael Thompson', 'specialty' => 'Physical Therapy'],
            [
                'healthcare_system_id' => $system1->id,
                'location' => '3001 McClelland Blvd, Joplin, MO 64804',
                'latitude' => 37.1025,
                'longitude' => -94.5445,
                'phone' => '(417) 347-5678',
                'email' => 'therapy@freeman.com',
            ]
        );

        // Set patient location if not set
        if (! $patient->latitude || ! $patient->longitude) {
            $patient->update([
                'latitude' => 37.0842,
                'longitude' => -94.5133,
            ]);
            $this->info('Updated patient location to Joplin, MO area');
        }

        $this->newLine();
        $this->info('=== Creating Upcoming Appointments ===');

        // Upcoming appointment 1 - Cardiology follow-up
        $appt1 = PatientAppointment::create([
            'patient_id' => $patient->id,
            'healthcare_provider_id' => $cardiology->id,
            'date' => now()->addDays(5),
            'time' => '10:00:00',
            'location' => $cardiology->location,
            'summary' => 'Cardiology follow-up for hypertension management',
        ]);
        $this->line("✓ Cardiology appointment on {$appt1->date->format('M j, Y')} at 10:00 AM");

        // Upcoming appointment 2 - Family Medicine
        $appt2 = PatientAppointment::create([
            'patient_id' => $patient->id,
            'healthcare_provider_id' => $primaryCare->id,
            'date' => now()->addDays(12),
            'time' => '14:30:00',
            'location' => $primaryCare->location,
            'summary' => 'Annual physical examination',
        ]);
        $this->line("✓ Family Medicine appointment on {$appt2->date->format('M j, Y')} at 2:30 PM");

        // Upcoming appointment 3 - Physical Therapy
        $appt3 = PatientAppointment::create([
            'patient_id' => $patient->id,
            'healthcare_provider_id' => $physical->id,
            'date' => now()->addDays(20),
            'time' => '09:00:00',
            'location' => $physical->location,
            'summary' => 'Physical therapy session for lower back pain',
        ]);
        $this->line("✓ Physical Therapy appointment on {$appt3->date->format('M j, Y')} at 9:00 AM");

        $this->newLine();
        $this->info('=== Creating Pending Tasks ===');

        // Regular tasks
        PatientTask::create([
            'patient_id' => $patient->id,
            'description' => 'Monitor blood pressure daily',
            'instructions' => 'Check blood pressure every morning before breakfast. Record readings in health journal.',
            'is_scheduling_task' => false,
        ]);
        $this->line('✓ Task: Monitor blood pressure daily');

        PatientTask::create([
            'patient_id' => $patient->id,
            'description' => 'Pick up new prescription from pharmacy',
            'instructions' => 'Lisinopril 10mg - ready for pickup at Freeman Pharmacy',
            'is_scheduling_task' => false,
        ]);
        $this->line('✓ Task: Pick up new prescription from pharmacy');

        PatientTask::create([
            'patient_id' => $patient->id,
            'description' => 'Complete pre-appointment health questionnaire',
            'instructions' => 'Fill out online form before annual physical. Link sent via email.',
            'is_scheduling_task' => false,
        ]);
        $this->line('✓ Task: Complete pre-appointment health questionnaire');

        // Scheduling tasks
        PatientTask::create([
            'patient_id' => $patient->id,
            'description' => 'Schedule MRI for lower back',
            'instructions' => 'Dr. Peterson ordered MRI to evaluate persistent lower back pain. Schedule with radiology department.',
            'is_scheduling_task' => true,
            'provider_specialty_needed' => 'Radiology',
        ]);
        $this->line('✓ Scheduling Task: Schedule MRI for lower back');

        PatientTask::create([
            'patient_id' => $patient->id,
            'description' => 'Schedule follow-up with endocrinologist',
            'instructions' => 'Need to schedule appointment for diabetes management review within next 2 months.',
            'is_scheduling_task' => true,
            'provider_specialty_needed' => 'Endocrinology',
        ]);
        $this->line('✓ Scheduling Task: Schedule follow-up with endocrinologist');

        PatientTask::create([
            'patient_id' => $patient->id,
            'description' => 'Complete 10 stretching exercises before bed',
            'instructions' => 'Perform the stretching routine provided by physical therapist to help with back pain.',
            'is_scheduling_task' => false,
        ]);
        $this->line('✓ Task: Complete 10 stretching exercises before bed');

        $this->newLine();
        $this->info('=== Summary ===');
        $this->line('Created 3 upcoming appointments');
        $this->line('Created 6 pending tasks (4 regular, 2 scheduling)');
        $this->newLine();
        $this->info("Patient {$patientId} now has realistic healthcare data!");

        return 0;
    }
}
