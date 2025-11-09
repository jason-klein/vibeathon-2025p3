<?php

namespace App\Console\Commands;

use App\Models\CommunityEvent;
use App\Models\CommunityPartner;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ImportCommunityEvents extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:events {--fresh : Delete existing events and partners before importing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import community events from CSV file';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $csvPath = storage_path('seed/events.csv');

        if (! file_exists($csvPath)) {
            $this->error("CSV file not found at: {$csvPath}");

            return 1;
        }

        if ($this->option('fresh')) {
            $this->info('Deleting existing events and partners...');
            CommunityEvent::query()->delete();
            CommunityPartner::query()->delete();
        }

        $this->info('Reading CSV file...');
        $csv = array_map('str_getcsv', file($csvPath));
        $header = array_shift($csv);

        $partnersCache = [];
        $eventsCreated = 0;
        $partnersCreated = 0;

        $this->newLine();
        $this->info('Processing events...');
        $progressBar = $this->output->createProgressBar(count($csv));

        foreach ($csv as $row) {
            if (count($row) < 5) {
                continue;
            }

            $data = array_combine($header, $row);

            // Extract partner name from description
            $partnerName = $this->extractPartnerName($data['description']);

            // Get or create partner
            if (! isset($partnersCache[$partnerName])) {
                $partner = CommunityPartner::firstOrCreate(
                    ['name' => $partnerName],
                    [
                        'is_nonprofit' => $this->isNonprofit($partnerName),
                        'is_sponsor' => $this->isSponsor($partnerName),
                    ]
                );

                if ($partner->wasRecentlyCreated) {
                    $partnersCreated++;
                }

                $partnersCache[$partnerName] = $partner;
            }

            $partner = $partnersCache[$partnerName];

            // Parse date and time
            $eventDate = Carbon::parse($data['date']);
            $eventTime = $this->parseTime($data['time']);

            // Create event
            CommunityEvent::create([
                'community_partner_id' => $partner->id,
                'date' => $eventDate,
                'time' => $eventTime,
                'location' => $data['location'],
                'description' => $this->cleanDescription($data['description']),
                'link' => ! empty($data['url']) && filter_var($data['url'], FILTER_VALIDATE_URL) ? $data['url'] : null,
                'is_partner_provided' => true,
            ]);

            $eventsCreated++;
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        $this->info('Import completed successfully!');
        $this->line("Partners created: {$partnersCreated}");
        $this->line("Events created: {$eventsCreated}");

        return 0;
    }

    /**
     * Extract partner name from description (text in parentheses).
     */
    protected function extractPartnerName(string $description): string
    {
        if (preg_match('/\(([^)]+)\)/', $description, $matches)) {
            $name = trim($matches[1]);

            // If the name looks like a description rather than organization, use the main part
            $descriptionKeywords = ['four-season', 'free community', 'special themed', 'monthly', 'near'];
            foreach ($descriptionKeywords as $keyword) {
                if (stripos($name, $keyword) !== false) {
                    // Use the part before parentheses instead
                    $mainPart = trim(preg_replace('/\s*\([^)]+\)\s*/', '', $description));
                    if ($mainPart && strlen($mainPart) > 3) {
                        return $mainPart;
                    }
                }
            }

            return $name;
        }

        // Fallback: clean up the description
        $cleaned = trim($description);

        // Remove common event type suffixes
        $cleaned = preg_replace('/(5K|10K|1 Mile|Half Marathon).*$/i', '', $cleaned);

        return $cleaned ?: 'Community Event';
    }

    /**
     * Clean description by removing partner name in parentheses.
     */
    protected function cleanDescription(string $description): string
    {
        return trim(preg_replace('/\s*\([^)]+\)\s*/', ' ', $description));
    }

    /**
     * Determine if organization is likely a nonprofit.
     */
    protected function isNonprofit(string $name): bool
    {
        $nonprofitKeywords = [
            'Community Blood Center',
            'Walk With A Doc',
            'Joplin Trails Coalition',
            'Bike Walk Joplin',
            'Joplin RoadRunners',
            'American Heart Association',
            'MU Extension',
        ];

        foreach ($nonprofitKeywords as $keyword) {
            if (stripos($name, $keyword) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if organization is a sponsor.
     */
    protected function isSponsor(string $name): bool
    {
        $sponsorKeywords = [
            'Freeman Health',
            'Mercy',
        ];

        foreach ($sponsorKeywords as $keyword) {
            if (stripos($name, $keyword) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Parse time string to H:i:s format.
     */
    protected function parseTime(?string $time): ?string
    {
        if (! $time || trim($time) === '') {
            return null;
        }

        // Handle time ranges like "11:00 AM–6:00 PM"
        if (str_contains($time, '–') || str_contains($time, '-')) {
            $parts = preg_split('/[–-]/', $time);
            $time = trim($parts[0]);
        }

        // Handle special formats like "9:00 AM (opens), 10:00 AM (walk)"
        if (str_contains($time, '(')) {
            preg_match('/\d+:\d+\s*[AP]M/', $time, $matches);
            if (! empty($matches)) {
                $time = $matches[0];
            }
        }

        try {
            return Carbon::parse($time)->format('H:i:s');
        } catch (\Exception $e) {
            return null;
        }
    }
}
