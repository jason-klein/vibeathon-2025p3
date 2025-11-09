<?php

namespace Database\Seeders;

use App\Models\HealthcareProvider;
use App\Models\HealthcareSystem;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create test user with patient record
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        Patient::factory()->create([
            'user_id' => $user->id,
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

        // Create healthcare providers with Joplin, MO coordinates
        HealthcareProvider::factory()->count(15)->create([
            'healthcare_system_id' => $preferredSystem->id,
        ]);

        HealthcareProvider::factory()->count(10)->create([
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
    }
}
