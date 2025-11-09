<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class CommunityEventSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Importing community events from CSV...');

        Artisan::call('import:events', ['--fresh' => true]);

        $this->command->info(Artisan::output());
    }
}
