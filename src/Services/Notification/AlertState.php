<?php

namespace RattingManager\Services\Notification;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * What each alert has already been sent for, so the next run does not send
 * it again. A key names one thing, such as one player gone quiet or one
 * corporation's journal stalling; a new event is a new key, so nothing new
 * is ever held back.
 *
 * A key is open while its condition holds and closed once it has passed, so
 * the same thing happening again later is sent again.
 */
class AlertState
{
    private const TABLE = 'ratting_manager_alert_state';

    public function isOpen(string $key): bool
    {
        return DB::table(self::TABLE)->where('alert_key', $key)->where('state', 'open')->exists();
    }

    /** Marks the key sent and open. */
    public function opened(string $key, array $payload = []): void
    {
        $now = Carbon::now();
        $existing = DB::table(self::TABLE)->where('alert_key', $key)->first();

        if ($existing) {
            DB::table(self::TABLE)->where('id', $existing->id)->update([
                'state' => 'open',
                'payload' => json_encode($payload),
                'first_seen_at' => $existing->state === 'open' ? $existing->first_seen_at : $now,
                'last_sent_at' => $now,
                'closed_at' => null,
                'updated_at' => $now,
            ]);

            return;
        }

        DB::table(self::TABLE)->insert([
            'alert_key' => $key,
            'state' => 'open',
            'payload' => json_encode($payload),
            'first_seen_at' => $now,
            'last_sent_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function close(string $key): void
    {
        DB::table(self::TABLE)->where('alert_key', $key)->where('state', 'open')->update([
            'state' => 'closed',
            'closed_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
    }

    /** @return array<string, array> open keys starting with the prefix, with what was stored when they were sent */
    public function open(string $prefix): array
    {
        return DB::table(self::TABLE)
            ->where('alert_key', 'like', str_replace(['%', '_'], ['\%', '\_'], $prefix) . '%')
            ->where('state', 'open')
            ->get(['alert_key', 'payload'])
            ->mapWithKeys(fn ($r) => [$r->alert_key => (array) json_decode((string) $r->payload, true)])
            ->all();
    }
}
