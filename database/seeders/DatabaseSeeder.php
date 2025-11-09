<?php

namespace Database\Seeders;

use App\Models\HealthcareProvider;
use App\Models\HealthcareSystem;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

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
    }
}
