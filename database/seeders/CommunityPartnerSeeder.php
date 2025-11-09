<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class CommunityPartnerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Note: Community partners are automatically created when running
     * the CommunityEventSeeder or 'php artisan import:events' command.
     * This seeder is available for adding additional standalone partners
     * that may not have events yet.
     */
    public function run(): void
    {
        $this->command->info('Community partners are created via CommunityEventSeeder');
        $this->command->info('Run: php artisan db:seed --class=CommunityEventSeeder');
    }
}
