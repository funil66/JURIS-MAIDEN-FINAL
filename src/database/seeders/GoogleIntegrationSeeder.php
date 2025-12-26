<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GoogleIntegrationSeeder extends Seeder
{
    /**
     * Seed Google integration placeholders
     */
    public function run(): void
    {
        // Create a global Google Drive settings placeholder (user_id = null)
        DB::table('google_drive_settings')->updateOrInsert(
            ['user_id' => null],
            [
                'root_folder_name' => 'Global Drive',
                'auto_sync' => false,
                'sync_reports' => true,
                'sync_documents' => true,
                'sync_invoices' => false,
                'sync_contracts' => false,
                'is_connected' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}
