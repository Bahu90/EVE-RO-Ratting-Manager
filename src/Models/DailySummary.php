<?php

namespace RattingManager\Models;

use Illuminate\Database\Eloquent\Model;

class DailySummary extends Model
{
    protected $table = 'ratting_manager_daily_summaries';

    protected $fillable = [
        'date',
        'character_id',
        'corporation_id',
        'solar_system_id',
        'ticks',
        'minutes',
        'bounty_net',
        'bounty_estimated',
        'bounty_corp_tax',
        'bounty_gross',
        'ess_net',
        'ess_estimated',
        'ess_corp_tax',
        'ess_gross',
        'npc_kills',
    ];

    protected $casts = [
        'date' => 'date',
        'character_id' => 'integer',
        'corporation_id' => 'integer',
        'solar_system_id' => 'integer',
        'ticks' => 'integer',
        'minutes' => 'integer',
        'bounty_net' => 'float',
        'bounty_estimated' => 'float',
        'bounty_corp_tax' => 'float',
        'bounty_gross' => 'float',
        'ess_net' => 'float',
        'ess_estimated' => 'float',
        'ess_corp_tax' => 'float',
        'ess_gross' => 'float',
        'npc_kills' => 'integer',
    ];
}
