<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ISK ratted, the way the corporation counts it: the corporation's cut of
 * bounties and ESS payouts divided by its tax rate that day. A tick the
 * corporation took no cut of adds nothing here, however much the pilot
 * received.
 *
 * Existing days are filled in by the next scheduled run, which rebuilds
 * every summary once (see Pipeline::SUMMARY_VERSION).
 */
class AddRattingManagerGrossToDailySummaries extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('ratting_manager_daily_summaries')) {
            return;
        }

        Schema::table('ratting_manager_daily_summaries', function (Blueprint $table) {
            if (!Schema::hasColumn('ratting_manager_daily_summaries', 'bounty_gross')) {
                $table->decimal('bounty_gross', 20, 2)->default(0)->after('bounty_corp_tax');
            }
            if (!Schema::hasColumn('ratting_manager_daily_summaries', 'ess_gross')) {
                $table->decimal('ess_gross', 20, 2)->default(0)->after('ess_corp_tax');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('ratting_manager_daily_summaries')) {
            return;
        }

        Schema::table('ratting_manager_daily_summaries', function (Blueprint $table) {
            foreach (['bounty_gross', 'ess_gross'] as $column) {
                if (Schema::hasColumn('ratting_manager_daily_summaries', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
}
