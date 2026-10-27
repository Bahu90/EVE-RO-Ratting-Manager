<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The alliance tax used to default to 7.5% and the JSON export used to
 * show for everyone. New installs now start at 0% with the export off.
 * An install that already tracks corporations keeps what it had: the old
 * values are written down for it unless an admin has saved their own.
 */
return new class extends Migration
{
    public function up(): void
    {
        $tracked = DB::table('ratting_manager_settings')
            ->whereNull('corporation_id')
            ->where('key', 'general.configured_corporations')
            ->value('value');

        $ids = json_decode((string) $tracked, true);
        if (!is_array($ids) || $ids === []) {
            return;
        }

        foreach (['display.alliance_tax_percent' => '7.5', 'display.website_export' => '1'] as $key => $value) {
            $exists = DB::table('ratting_manager_settings')->whereNull('corporation_id')->where('key', $key)->exists();
            if (!$exists) {
                DB::table('ratting_manager_settings')->insert([
                    'key' => $key,
                    'corporation_id' => null,
                    'value' => $value,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        // The values are the install's own settings now; nothing to undo.
    }
};
