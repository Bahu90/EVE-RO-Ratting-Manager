<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

/**
 * Webhook URLs are stored encrypted (Models\Casts\EncryptedUrl). The column
 * becomes TEXT, since an encrypted URL is longer than 500 characters, and
 * the URLs already saved are encrypted in place.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE ratting_manager_webhooks MODIFY webhook_url TEXT NOT NULL');

        foreach (DB::table('ratting_manager_webhooks')->get(['id', 'webhook_url']) as $row) {
            if (str_starts_with((string) $row->webhook_url, 'https://')) {
                DB::table('ratting_manager_webhooks')
                    ->where('id', $row->id)
                    ->update(['webhook_url' => Crypt::encryptString((string) $row->webhook_url)]);
            }
        }
    }

    public function down(): void
    {
        foreach (DB::table('ratting_manager_webhooks')->get(['id', 'webhook_url']) as $row) {
            if (!str_starts_with((string) $row->webhook_url, 'https://')) {
                try {
                    $plain = Crypt::decryptString((string) $row->webhook_url);
                } catch (\Throwable $e) {
                    continue;
                }
                DB::table('ratting_manager_webhooks')->where('id', $row->id)->update(['webhook_url' => $plain]);
            }
        }

        DB::statement('ALTER TABLE ratting_manager_webhooks MODIFY webhook_url VARCHAR(500) NOT NULL');
    }
};
