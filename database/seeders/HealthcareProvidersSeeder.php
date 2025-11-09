<?php

namespace Database\Seeders;

use App\Models\HealthcareProvider;
use App\Models\HealthcareSystem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class HealthcareProvidersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $csvPath = storage_path('seed/referrals.csv');

        if (! File::exists($csvPath)) {
            $this->command->error("CSV file not found at: {$csvPath}");

            return;
        }

        // Get healthcare systems
        $healthcareSystems = HealthcareSystem::query()->pluck('id', 'name');

        // Create mapping from CSV provider_system to healthcare_system_id
        $systemMapping = $this->buildSystemMapping($healthcareSystems);

        // Read and parse CSV
        $csv = array_map('str_getcsv', file($csvPath));
        $header = array_shift($csv); // Remove header row

        $providersCreated = 0;
        $providersSkipped = 0;

        foreach ($csv as $row) {
            if (count($row) < 7) {
                continue; // Skip incomplete rows
            }

            [$specialty, $providerSystem, $providerName, $location, $phone, $email, $url] = $row;

            // Skip empty rows
            if (empty($providerName)) {
                continue;
            }

            // Get the healthcare system ID
            $healthcareSystemId = $systemMapping[$providerSystem] ?? null;

            if (! $healthcareSystemId) {
                $this->command->warn("Unknown healthcare system: {$providerSystem}");
                $providersSkipped++;

                continue;
            }

            // Check if provider already exists
            $exists = HealthcareProvider::query()
                ->where('name', $providerName)
                ->where('specialty', $specialty)
                ->where('healthcare_system_id', $healthcareSystemId)
                ->exists();

            if ($exists) {
                $providersSkipped++;

                continue;
            }

            // Create the provider
            HealthcareProvider::create([
                'healthcare_system_id' => $healthcareSystemId,
                'name' => $providerName,
                'specialty' => $specialty,
                'location' => $location ?: null,
                'phone' => $phone ?: null,
                'email' => $email ?: null,
            ]);

            $providersCreated++;
        }

        $this->command->info('Healthcare providers seeded successfully!');
        $this->command->info("Providers created: {$providersCreated}");
        $this->command->info("Providers skipped: {$providersSkipped}");
    }

    /**
     * Build mapping from CSV provider_system to healthcare_system_id
     */
    protected function buildSystemMapping($healthcareSystems): array
    {
        $freemanId = $healthcareSystems['Freeman Health System'] ?? null;
        $mercyId = $healthcareSystems['Mercy Hospital Joplin'] ?? null;
        $independentId = $healthcareSystems['Independent Providers'] ?? null;

        return [
            'Freeman' => $freemanId,
            'Mercy' => $mercyId,
            'U.S. Dermatology Partners (affiliated with Mercy)' => $mercyId,
            'Butler Eye Clinic (affiliated with Mercy)' => $mercyId,
            'Joplin Nephrology Consultants (affiliated with Mercy)' => $mercyId,
            'Regional Eye Center (affiliated with Freeman)' => $freemanId,
            'Freeman (affiliated)' => $freemanId,
            'Missouri Eye Institute' => $independentId,
            'Phelan Dermatology' => $independentId,
            'Joplin ENT (independent)' => $independentId,
        ];
    }
}
