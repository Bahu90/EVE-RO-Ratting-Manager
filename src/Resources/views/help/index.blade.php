@extends('web::layouts.grids.12')

@section('title', trans('ratting-manager::help.title'))
@section('page_header', trans('ratting-manager::help.title'))

@push('head')
<link rel="stylesheet" href="{{ \RattingManager\Support\Asset::url('css/ratting-manager.css') }}">
<style>
    .help-wrapper {
        display: flex;
        gap: 20px;
    }

    .help-sidebar {
        flex: 0 0 280px;
        position: sticky;
        top: 20px;
        max-height: calc(100vh - 120px);
        overflow-y: auto;
    }

    .help-content {
        flex: 1;
        min-width: 0;
        color: #d1d5db !important;
    }

    .help-nav .nav-link {
        color: #e2e8f0 !important;
        border-radius: 5px;
        margin-bottom: 5px;
        padding: 10px 15px;
        transition: all 0.3s;
        font-size: 0.95rem;
    }

    .help-nav .nav-link:hover {
        background: rgba(102, 126, 234, 0.2);
    }

    .help-nav .nav-link.active {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    .help-nav .nav-link i {
        width: 24px;
        text-align: center;
        margin-right: 10px;
    }

    .help-section {
        display: none;
        animation: fadeIn 0.3s;
    }

    .help-section.active {
        display: block;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .help-card {
        background: #2d3748;
        border-radius: 10px;
        padding: 25px;
        margin-bottom: 20px;
        border: 1px solid rgba(102, 126, 234, 0.2);
    }

    .help-card h3 {
        color: #667eea !important;
        margin-bottom: 15px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .help-card h4 {
        color: #9ca3af !important;
        margin-top: 20px;
        margin-bottom: 10px;
        font-size: 1.1rem;
    }

    .help-card h5 {
        color: #e2e8f0 !important;
        margin-top: 15px;
        margin-bottom: 8px;
    }

    .help-card p {
        color: #d1d5db !important;
        line-height: 1.6;
    }

    .help-card ul, .help-card ol {
        color: #d1d5db !important;
        line-height: 1.8;
        margin-left: 20px;
    }

    .help-card strong {
        color: #e2e8f0 !important;
    }

    .help-card li {
        color: #d1d5db !important;
    }

    .help-card code {
        background: rgba(0, 0, 0, 0.3);
        padding: 2px 6px;
        border-radius: 3px;
        color: #fbbf24 !important;
        font-size: 0.9em;
    }

    .help-card pre {
        background: rgba(0, 0, 0, 0.3);
        padding: 15px;
        border-radius: 5px;
        overflow-x: auto;
        color: #d1d5db !important;
    }

    .step-by-step {
        counter-reset: step-counter;
        list-style: none;
        padding-left: 0;
    }

    .step-by-step li {
        counter-increment: step-counter;
        margin-bottom: 20px;
        padding-left: 50px;
        position: relative;
        color: #d1d5db !important;
    }

    .step-by-step li::before {
        content: counter(step-counter);
        position: absolute;
        left: 0;
        top: 0;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        width: 35px;
        height: 35px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 1.1rem;
    }

    .info-box,
    .warning-box,
    .success-box {
        padding: 15px;
        margin: 15px 0;
        border-radius: 5px;
        color: #d1d5db !important;
        display: flex;
        align-items: flex-start;
        gap: 10px;
        flex-wrap: wrap;
    }

    .info-box {
        background: rgba(23, 162, 184, 0.15);
        border-left: 4px solid #17a2b8;
    }

    .warning-box {
        background: rgba(255, 193, 7, 0.15);
        border-left: 4px solid #ffc107;
    }

    .success-box {
        background: rgba(28, 200, 138, 0.15);
        border-left: 4px solid #1cc88a;
    }

    .info-box > i,
    .warning-box > i,
    .success-box > i {
        margin-top: 3px;
        flex-shrink: 0;
    }

    .info-box > i { color: #17a2b8; }
    .warning-box > i { color: #ffc107; }
    .success-box > i { color: #1cc88a; }

    /* Override the inherited .help-card h4/h5/p/ul/ol/small/strong colours
       so text inside a tinted box has high contrast. The default
       .help-card h4 (#9ca3af medium grey) is barely readable on a
       green/blue/yellow 15%-opacity tint background; this lifts every
       text element inside a tinted box to a near-white that pops against
       any of the three background tints uniformly.
       NB: covers BOTH our custom .info-box/.warning-box/.success-box
       classes AND Bootstrap's .alert.alert-info/.alert-secondary/etc.
       (the help blade uses both patterns in practice). */
    .info-box h3, .info-box h4, .info-box h5,
    .warning-box h3, .warning-box h4, .warning-box h5,
    .success-box h3, .success-box h4, .success-box h5,
    .alert h3, .alert h4, .alert h5 {
        color: #f9fafb !important;
    }
    .info-box p, .info-box ul, .info-box ol, .info-box li, .info-box small,
    .warning-box p, .warning-box ul, .warning-box ol, .warning-box li, .warning-box small,
    .success-box p, .success-box ul, .success-box ol, .success-box li, .success-box small,
    .alert p, .alert ul, .alert ol, .alert li, .alert small {
        color: #f3f4f6 !important;
    }
    .info-box strong, .warning-box strong, .success-box strong,
    .alert strong {
        color: #ffffff !important;
    }
    /* Bootstrap's `.text-muted` is `color: #6c757d !important` and ties
       on specificity with the box overrides above, so cascade order
       decides which wins: and Bootstrap usually loads later. Force
       text-muted inside tinted boxes to respect the box's contrast
       baseline by bumping specificity (chained class selector). */
    .info-box .text-muted,
    .warning-box .text-muted,
    .success-box .text-muted,
    .alert .text-muted,
    .info-box small.text-muted,
    .warning-box small.text-muted,
    .success-box small.text-muted,
    .alert small.text-muted {
        color: #d1d5db !important;
        opacity: 0.95;
    }

    .feature-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 15px;
        margin: 20px 0;
    }

    .feature-item {
        background: rgba(102, 126, 234, 0.1);
        padding: 15px;
        border-radius: 8px;
        border: 1px solid rgba(102, 126, 234, 0.3);
    }

    /* Scoped to direct-child icons only (the big feature-card top icon).
       Without the "> i" combinator this selector also hits nested <i>
       elements inside badges, forcing badge icons to render indigo
       regardless of the badge background: barely visible on a green
       badge-success or red badge-danger. Direct-child leaves badge /
       inline icons alone so they inherit the badge's white text. */
    .feature-item > i {
        font-size: 2rem;
        color: #667eea;
        margin-bottom: 10px;
    }

    .feature-item h5 {
        color: #e2e8f0 !important;
        margin-bottom: 8px;
    }

    .feature-item p {
        color: #9ca3af !important;
        font-size: 0.9rem;
        margin: 0;
    }

    .search-box {
        position: relative;
        margin-bottom: 20px;
    }

    .search-box input {
        width: 100%;
        padding: 12px 45px 12px 15px;
        background: #2d3748;
        border: 1px solid rgba(102, 126, 234, 0.3);
        border-radius: 8px;
        color: #e2e8f0 !important;
    }

    .search-box i {
        position: absolute;
        right: 15px;
        top: 50%;
        transform: translateY(-50%);
        color: #9ca3af;
    }

    .quick-links {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 10px;
        margin: 20px 0;
    }

    .quick-link {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        padding: 15px;
        border-radius: 8px;
        text-align: center;
        color: white;
        text-decoration: none;
        transition: transform 0.2s;
    }

    .quick-link:hover {
        transform: translateY(-2px);
        color: white;
        text-decoration: none;
    }

    .quick-link i {
        font-size: 2rem;
        display: block;
        margin-bottom: 8px;
    }

    .faq-item {
        background: rgba(0, 0, 0, 0.2);
        border-radius: 8px;
        margin-bottom: 10px;
        overflow: hidden;
    }

    .faq-question {
        padding: 15px;
        cursor: pointer;
        display: flex;
        justify-content: space-between;
        align-items: center;
        transition: background 0.2s;
        color: #e2e8f0 !important;
    }

    .faq-question:hover {
        background: rgba(102, 126, 234, 0.1);
    }

    .faq-question i {
        transition: transform 0.3s;
    }

    .faq-item.open .faq-question i {
        transform: rotate(180deg);
    }

    .faq-answer {
        padding: 0 15px;
        max-height: 0;
        overflow: hidden;
        transition: all 0.3s;
        color: #d1d5db !important;
    }

    .faq-item.open .faq-answer {
        padding: 15px;
        max-height: 500px;
    }

    .plugin-info-table {
        width: 100%;
        color: #d1d5db !important;
    }

    .plugin-info-table td {
        padding: 8px 12px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    }

    .plugin-info-table td:first-child {
        color: #9ca3af !important;
        width: 120px;
        font-weight: 600;
    }

    .plugin-info-table td:last-child {
        color: #e2e8f0 !important;
    }

    /* Ore classification flow: plain boxes and arrows, so it needs no
       diagram library and still reads on a narrow screen. */
    .ore-flow {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 6px;
        margin: 20px 0;
    }

    .ore-flow-node {
        background: rgba(102, 126, 234, 0.1);
        border: 1px solid rgba(102, 126, 234, 0.35);
        border-radius: 8px;
        padding: 10px 14px;
        text-align: center;
        width: 100%;
        max-width: 420px;
    }

    .ore-flow-node strong {
        display: block;
        color: #e2e8f0 !important;
    }

    .ore-flow-node span {
        display: block;
        margin-top: 2px;
        font-size: 0.85rem;
        color: #9ca3af !important;
    }

    .ore-flow-arrow {
        color: #667eea;
        line-height: 1;
    }

    .ore-flow-row,
    .ore-flow-branches {
        display: grid;
        gap: 10px;
        width: 100%;
    }

    .ore-flow-row {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        max-width: 700px;
    }

    .ore-flow-branches {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    .ore-flow-row .ore-flow-node,
    .ore-flow-branches .ore-flow-node {
        max-width: none;
    }

    .ore-flow-registry {
        background: rgba(28, 200, 138, 0.1);
        border-color: rgba(28, 200, 138, 0.5);
    }

    .ore-flow-ledger {
        background: rgba(102, 126, 234, 0.2);
        border-color: rgba(102, 126, 234, 0.7);
    }

    .ore-flow-skip {
        background: rgba(220, 53, 69, 0.1);
        border: 1px dashed rgba(220, 53, 69, 0.6);
    }

    .ore-flow-unknown {
        background: rgba(255, 193, 7, 0.1);
        border-color: rgba(255, 193, 7, 0.55);
    }

    @media (max-width: 768px) {
        .ore-flow-row,
        .ore-flow-branches {
            grid-template-columns: 1fr;
        }
    }

    /* Earlier updates of the same major version, folded away under the latest
       one in the Recent changes box. */
    .recent-changes-box details {
        margin-top: 18px;
        padding-top: 10px;
        border-top: 1px solid rgba(139, 156, 247, 0.25);
    }

    .recent-changes-box summary {
        color: #8b9cf7 !important;
        font-weight: 600;
        cursor: pointer;
    }

    @media (max-width: 768px) {
        .help-wrapper {
            flex-direction: column;
        }

        .help-sidebar {
            position: relative;
            max-height: none;
        }

        .feature-grid {
            grid-template-columns: 1fr;
        }
    }
</style>@endpush

@section('full')
<div class="ratting-manager-wrapper ratting-manager-dashboard help-page">
    <div class="help-wrapper">
        <div class="help-sidebar">
            <div class="card card-dark">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-compass"></i> {{ trans('ratting-manager::help.navigation') }}</h3>
                </div>
                <div class="card-body p-0">
                    <ul class="nav nav-pills flex-column help-nav">
                        @foreach([
                            'overview' => ['fa-info-circle', 'overview'],
                            'getting-started' => ['fa-rocket', 'getting_started'],
                            'my-ratting' => ['fa-crosshairs', 'my_ratting'],
                            'corporation' => ['fa-building', 'corporation'],
                            'leaderboard' => ['fa-trophy', 'leaderboard'],
                            'figures' => ['fa-calculator', 'figures'],
                            'notifications' => ['fa-bell', 'notifications'],
                            'settings' => ['fa-cog', 'settings'],
                            'commands' => ['fa-terminal', 'commands'],
                            'permissions' => ['fa-user-shield', 'permissions'],
                            'faq' => ['fa-question', 'faq'],
                        ] as $section => [$icon, $label])
                            <li class="nav-item">
                                <a href="#" class="nav-link {{ $loop->first ? 'active' : '' }}" data-section="{{ $section }}">
                                    <i class="fas {{ $icon }}"></i> {{ trans('ratting-manager::help.' . $label) }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>

        <div class="help-content">

            {{-- Overview --}}
            <div id="overview" class="help-section active">
                <div class="help-card">
                    <h3><i class="fas fa-info-circle"></i> Ratting Manager</h3>
                    <p>
                        Ratting Manager tracks one thing: who rats, where, when, how much they earn and what they kill.
                        Every member sees their own ratting, and directors see the same for the whole corporation.
                    </p>
                    <div class="info-box">
                        <i class="fas fa-box-open"></i>
                        <div>Ratting Manager 1.0 is in preparation: there is no release on Packagist yet.</div>
                    </div>
                    <div class="quick-links" style="margin-top: 15px;">
                        <a href="https://github.com/MattFalahe/Ratting-Manager" class="quick-link" target="_blank" rel="noopener" style="padding: 10px;">
                            <i class="fas fa-code-branch" style="font-size: 1rem; margin-bottom: 4px;"></i> GitHub
                        </a>
                        <a href="https://github.com/MattFalahe/Ratting-Manager/blob/main/CHANGELOG.md" class="quick-link" target="_blank" rel="noopener" style="padding: 10px;">
                            <i class="fas fa-list" style="font-size: 1rem; margin-bottom: 4px;"></i> Changelog
                        </a>
                        <a href="https://github.com/MattFalahe/Ratting-Manager/issues" class="quick-link" target="_blank" rel="noopener" style="padding: 10px;">
                            <i class="fas fa-bug" style="font-size: 1rem; margin-bottom: 4px;"></i> Report an issue
                        </a>
                    </div>
                </div>

                {{-- What's New in v1 is the picture of the whole major version and never
                     names a point release. Later updates go in a Recent changes box under
                     it; the changelog keeps the full record. --}}
                <div class="whats-new-box">
                    <h3><i class="fas fa-crosshairs"></i> What's New in v1 <span class="whats-new-tag">first release</span></h3>
                    <p>Version 1 is the first Ratting Manager. It reads the wallet journals SeAT already pulls and turns them into:</p>
                    <ul>
                        <li><strong>My Ratting</strong> for every member: income, ISK per hour, hours, sessions, systems, what they kill and their personal records.</li>
                        <li><strong>A Corporation tab</strong> for directors: totals, the leaderboard with every player's ISK and ISK per hour, efficiency by system, when the corporation rats, who has gone quiet and who rats without a wallet token.</li>
                        <li><strong>A leaderboard</strong> members see too, with rank and hours only.</li>
                        <li><strong>Special spawns</strong>: officers, commanders, faction rats and capitals, listed as they are killed.</li>
                        <li><strong>Members with no wallet token still count.</strong> The corporation journal names the ratter on every tick it taxes, and their income is worked out exactly from the corporation's cut.</li>
                        <li><strong>Insights</strong> for members and directors: favourite and best-paying systems, the most active and best-paying days and times, ISK per hour in every system ratted, and the NPCs killed most.</li>
                        <li><strong>Month by month</strong>: what each of a member's characters earned in a month with the total, and for directors, what the players earned against what landed in the corporation's wallet.</li>
                        <li><strong>Discord and Slack notifications</strong>: alerts for special spawns, ratting ships lost, outsiders in home space, stalled data, untracked ratters and players gone quiet, and weekly and monthly Director Reports and Rankings.</li>
                    </ul>
                </div>
            </div>

            {{-- Getting started --}}
            <div id="getting-started" class="help-section">
                <div class="help-card">
                    <h3><i class="fas fa-rocket"></i> Getting started</h3>
                    <ol class="step-by-step">
                        <li><strong>Add the corporations to track</strong> under Ratting Manager, Settings. Search by name or ticker, or open the list; picking one shows its ticker, alliance, tax rate, members, how many of its characters are on SeAT, and whether SeAT holds its bounty rows, and you confirm from there. Only ticks from tracked corporations are imported.</li>
                        <li><strong>Make sure SeAT has a director token with wallet access</strong> for each one. The corporation journal is what shows members who have not added a wallet token of their own.</li>
                        <li><strong>Run the initial import</strong> once, to bring in everything SeAT already holds:
                            <pre><code>docker exec -it seat-docker-front-1 php artisan ratting-manager:initialize</code></pre>
                            Without it, the schedule brings in the last 31 days within a day.
                        </li>
                        <li><strong>Give out the permissions</strong>: Member for everyone who rats, Director for the people who should see everyone's ISK. See Permissions.</li>
                    </ol>
                    <div class="info-box">
                        <i class="fas fa-clock"></i>
                        <div>A tick reaches the pages about one to two hours after it pays out: ESI caches the wallet journal for an hour, SeAT pulls it about hourly, and the plugin reads it every half hour.</div>
                    </div>
                </div>
            </div>

            {{-- My Ratting --}}
            <div id="my-ratting" class="help-section">
                <div class="help-card">
                    <h3><i class="fas fa-crosshairs"></i> My Ratting</h3>
                    <p>Your own ratting across all your characters, or one of them with the character picker. The period picker covers this month, last month, the last 30 or 90 days and the last 12 months.</p>
                    <ul>
                        <li><strong>Ratting income</strong> is what you received: bounties plus ESS payouts.</li>
                        <li><strong>Corporation tax paid</strong> is the corporation's cut of your bounties and ESS payouts, from the corporation journal, with the share of the payout it took. The income chart shows it month by month, and Month by character shows it per character.</li>
                        <li><strong>ISK per hour</strong> is that income over the time you spent ratting. Alts ratting together count their time once, so three characters for an hour is one hour.</li>
                        <li><strong>Rats killed</strong> counts each tick's kill list once. In a fleet that shares bounties, every member's tick lists the fleet's rats, so a shared payout counts them once and not once per member.</li>
                        <li><strong>Rank by hours</strong> is your place in your corporation by hours ratted.</li>
                        <li><strong>When I rat</strong> shades each hour of the week, in EVE time, by how often you ratted in it. Hover a square for the count.</li>
                        <li><strong>Sessions</strong> are runs of ticks with no gap longer than {{ $sessionGap }} minutes. A session's ISK per hour counts bounties only, because ESS pays out later and is not tied to one session.</li>
                    </ul>
                    <p>Long lists, such as sessions, systems and special spawns, show their first 10 rows. <strong>View all</strong> opens the whole list on its own page, where it can be searched and sorted.</p>
                    <h4>Insights</h4>
                    <ul>
                        <li><strong>Favourite system</strong> is where you spent the most time; <strong>best-paying system</strong> is where your ISK per hour was highest, among systems with at least an hour of ratting.</li>
                        <li><strong>Most active day</strong> and <strong>most active time</strong> are the day of the week and the three hours of the day, in EVE time, with the most ratting. The best-paying ones are where your bounty ISK per hour was highest.</li>
                        <li><strong>Streaks</strong> are days in a row with at least one tick. The current streak counts while you ratted today or yesterday.</li>
                        <li><strong>By day of the week</strong> and <strong>by hour of the day</strong> show your hours; hover a bar for its bounties and bounty ISK per hour. ESS is left out of these, because it pays out hours after the ratting it is for.</li>
                        <li><strong>ISK per hour by system</strong> and the <strong>Systems</strong> table count ESS, since each payout names the system its bank was in.</li>
                    </ul>
                    <h4>Month by character</h4>
                    <p>What each of your characters earned in a month: bounties, ESS, corporation tax, hours, ISK per hour, ticks and rats, with the month's total across all of them. The total's hours count alts ratting together once, so they can be less than the characters' hours added up. Below it, each character's income month by month.</p>
                </div>
            </div>

            {{-- Corporation --}}
            <div id="corporation" class="help-section">
                <div class="help-card">
                    <h3><i class="fas fa-building"></i> The Corporation tab</h3>
                    <p>Directors get a second tab on the Dashboard. It loads when it is first opened, and has its own corporation and period pickers.</p>
                    <ul>
                        <li><strong>Leaderboard</strong>: every player by income, with hours, ISK per hour, how that compares with the median player, rats, active days, the change on the period before, and coverage.</li>
                        <li><strong>Efficiency by system</strong>: where the time pays best.</li>
                        <li><strong>When the corporation rats</strong>: how many players rat in each hour of the week, useful for planning cover.</li>
                        <li><strong>Ratters coming and going</strong>: new players, players back after a gap, players still ratting, and players who stopped since the month before.</li>
                        <li><strong>Untracked ratters</strong>: characters the corporation journal pays bounties to who have no wallet token on SeAT.</li>
                        <li><strong>Gone quiet</strong>: regular ratters who have stopped.</li>
                        <li>Each long list shows its first 10 rows; <strong>View all</strong> opens the whole of it, searchable and sortable, for the same corporation and period. The leaderboard's View all opens the Leaderboard page.</li>
                        <li><strong>Insights</strong>: the same as on My Ratting for the whole corporation, plus the most players ratting in one hour, players per active day and income per player. The Systems table adds how many players ratted in each system and who ratted there most.</li>
                        <li><strong>Month by month</strong>: for the last 12 months, what the players earned, what landed in the corporation's wallet and the tax rate that makes, players, characters, hours, ISK per hour and income per player.</li>
                    </ul>
                    <h4>Coverage</h4>
                    <p>
                        <span class="badge badge-coverage-full"><i class="fas fa-check"></i> Tracked</span> means the ticks the corporation journal saw are also in the character's own journal.
                        A percentage means some are. <span class="badge badge-coverage-none"><i class="fas fa-eye-slash"></i> Untracked</span> means none are, so the player's income is worked out from the corporation's cut.
                    </p>
                </div>
            </div>

            {{-- Leaderboard --}}
            <div id="leaderboard" class="help-section">
                <div class="help-card">
                    <h3><i class="fas fa-trophy"></i> Leaderboard</h3>
                    <p>Members see their corporation ranked by hours ratted, with active days, and their own row picked out. Nobody's ISK is shown to members, their own included; they find their own on My Ratting.</p>
                    <p>Directors see the full board: income, hours, ISK per hour, the median comparison, rats, active days, change and coverage, for any tracked corporation or all of them.</p>
                </div>
            </div>

            {{-- How the figures are worked out --}}
            <div id="figures" class="help-section">
                <div class="help-card">
                    <h3><i class="fas fa-calculator"></i> How the figures are worked out</h3>
                    <h4>Two journals, one tick</h4>
                    <p>
                        Every 20 minutes of ratting pays one tick. The <strong>corporation journal</strong> records the corporation's cut of it and names the ratter.
                        The <strong>character journal</strong> records what the character received, if the character has a wallet token on SeAT.
                        Both carry the same time, the system and the list of rats killed, so the plugin pairs them.
                    </p>
                    <h4>Worked-out income</h4>
                    <p>
                        When only the corporation journal has a tick, the character's income is worked out from the cut:
                        <code>cut x (1 - rate) / rate</code>. At 15% corporation tax, a cut of 1.5M ISK means the character received 8.5M ISK.
                        The rate for each day is read back from that day's paired ticks, so a change of tax rate is followed.
                        Worked-out figures carry a dashed underline and appear striped in the charts.
                    </p>
                    <h4>ESS payouts</h4>
                    <p>The ESS main bank pays out later, as its own journal row, and the corporation takes its cut of that too. ESS payouts are paired and worked out the same way as ticks, and count towards income on the day they pay out.</p>
                    <h4>Time</h4>
                    <p>Each tick stands for the 20 minutes before it. A player's time is every stretch any of their characters was ratting, counted once.</p>
                    <h4>Names</h4>
                    <p>
                        A member who never added a token is not in SeAT at all. Their name comes from the journal row itself
                        (<em>Encounter Surveillance System in ... transferred funds to {name}</em>), and the background lookup adds the
                        corporation they are in now, from ESI, or from EVEWho or zKillboard when ESI is down. Pages only ever read what
                        has been found. A character nobody has named yet shows as <em>Character info in progress</em>, and the page
                        refreshes itself once the lookup has them, usually within a minute.
                    </p>
                    <h4>NPCs</h4>
                    <p>Each rat is placed by faction, size and rank from SeAT's SDE. Groups that mix sizes are sized by the NPC's mass. Anything that cannot be placed still counts, as Other, and Diagnostics lists it.</p>
                </div>
            </div>

            {{-- Notifications --}}
            <div id="notifications" class="help-section">
                <div class="help-card">
                    <h3><i class="fas fa-bell"></i> Notifications</h3>
                    <p>Ratting Manager posts to Discord webhooks, and to Slack incoming webhooks as plain text. Nothing is sent anywhere until an admin adds a webhook and ticks what it should carry.</p>
                    <h4>Setting up</h4>
                    <ol class="step-by-step">
                        <li><strong>Add a webhook</strong> under Settings, Webhooks: a name, the URL from Discord, and whether it is for one corporation or all tracked corporations. A webhook for one corporation only gets that corporation's posts.</li>
                        <li><strong>Pick what it carries</strong> under Settings, Notifications. Each notification has a switch, a default role, and a row per webhook with a tick and a role of its own.</li>
                        <li><strong>Check the Routing Map</strong>: where each notification goes now and who it mentions.</li>
                        <li><strong>Send a sample</strong> of any notification to any webhook from Diagnostics, Notification Testing, to see it as it will look.</li>
                    </ol>
                    <h4>Who gets mentioned</h4>
                    <p>The role set on the webhook's own row comes first. Left blank, the notification's default role is used. <em>No ping</em> mentions nobody, even when a default is set. Only the role or user named is ever pinged; anything else typed in a role field is ignored. With SeAT Broadcast or SeAT Connector installed, roles can be picked by name.</p>
                    <h4>Alerts</h4>
                    <table class="table table-sm">
                        <tbody>
                            <tr><td><strong>Special spawn</strong></td><td>An NPC of the chosen rank or above, a capital rat, or an NPC on the watch list, killed by a tracked ratter. One post per spawn, however many fleet members shared it.</td></tr>
                            <tr><td><strong>Ratting ship lost</strong></td><td>A tracked pilot lost a ship in the system they had a tick in shortly before. Needs SeAT to hold the killmail. Links zKillboard.</td></tr>
                            <tr><td><strong>Outsiders in home space</strong></td><td>ESI's hourly count of NPCs killed in a home system, less what the tracked corporations killed there in that hour. Sent a few hours after the hour, once the journals for it are in.</td></tr>
                            <tr><td><strong>Data stalled</strong></td><td>A corporation's wallet journal has had no new row for the hours set, or the import has not run for three hours. Posts again when it is back.</td></tr>
                            <tr><td><strong>Untracked ratter</strong></td><td>Ratters the corporation journal pays with no wallet token on SeAT, in one post per corporation. Each is posted once.</td></tr>
                            <tr><td><strong>Ratter gone quiet</strong></td><td>Players newly on the Gone quiet list, in one post per corporation. A player who rats again leaves the list.</td></tr>
                        </tbody>
                    </table>
                    <p>Each alert remembers what it has sent and never sends the same thing twice. A post every bound webhook refused is tried again on the next run.</p>
                    <h4>Reports</h4>
                    <ul>
                        <li><strong>Director Report</strong>, weekly on Monday and monthly on the 1st, per corporation: what the players earned, what landed in the corporation's wallet and the tax rate, the change on the period before, players, hours, median ISK per hour, rats, the top systems, and players ranked by income (tax paid beside it), hours, ISK per hour and corporation tax paid. For director channels.</li>
                        <li><strong>Rankings</strong>, on the same days: players ranked by hours ratted, with no ISK figures, so it can go to a members' channel.</li>
                    </ul>
                    <p>Players are ranked as a main with all their alts. A report goes once per period; one that could not be sent, or that had no webhook yet, is sent on the next day's run.</p>
                </div>
            </div>

            {{-- Settings --}}
            <div id="settings" class="help-section">
                <div class="help-card">
                    <h3><i class="fas fa-cog"></i> Settings</h3>
                    <ul>
                        <li><strong>Corporations to track</strong>: only these are imported. Stop tracking keeps what was imported but hides it, and imports nothing new.</li>
                        <li><strong>Session gap</strong>: how long a pause ends a session. The default of {{ $sessionGap }} minutes lets one missed tick pass.</li>
                        <li><strong>Gone quiet after</strong> and <strong>Regular ratter window</strong>: who is listed under Gone quiet.</li>
                        <li><strong>Leaderboard size for members</strong>: how many places members see.</li>
                        <li><strong>Extra NPC type IDs</strong>: rats to list as special spawns on top of officers, commanders, faction rats and capitals.</li>
                        <li><strong>Webhooks</strong>, <strong>Notifications</strong> and the <strong>Routing Map</strong>: see Notifications. Notifications also holds the alert thresholds: the lowest special spawn rank, how old a spawn may be, when a journal counts as stalled, how many NPC kills count as outsiders, the home systems, how close a tick has to be to a loss, and how many players each ranking lists.</li>
                    </ul>
                </div>
            </div>

            {{-- Commands --}}
            <div id="commands" class="help-section">
                <div class="help-card">
                    <h3><i class="fas fa-terminal"></i> Commands</h3>
                    <p>Run them inside SeAT's front container, for example <code>docker exec -it seat-docker-front-1 php artisan ratting-manager:process</code>.</p>
                    <table class="table table-sm">
                        <tbody>
                            <tr><td><code>ratting-manager:initialize</code></td><td>Checks the setup and imports everything SeAT already holds. Run once after picking the corporations.</td></tr>
                            <tr><td><code>ratting-manager:process</code></td><td>The scheduled run: reads the last two days of both journals and updates everything. <code>--days=N</code> or <code>--from=YYYY-MM-DD</code> reads further back.</td></tr>
                            <tr><td><code>ratting-manager:resolve-characters</code></td><td>Looks up names and corporations for ratters SeAT does not know, in the background, so no page waits on ESI. With <code>--requested</code> it takes only the characters pages asked for, which show as <em>Character info in progress</em> until then, and runs every minute; the full run every 10 minutes also takes ratters nobody has looked up yet and answers due another look. <code>--stats</code> shows what it holds. ESI comes first, then EVEWho and zKillboard when ESI is down, and it stops calling ESI while the error budget it shares with SeAT is low.</td></tr>
                            <tr><td><code>ratting-manager:trace-tick {id}</code></td><td>Shows what the plugin did with one journal row, the same as the Tick Trace tab.</td></tr>
                            <tr><td><code>ratting-manager:notify</code></td><td>Checks every alert and sends what is new. Runs twice an hour, five minutes after the import. <code>--only=special_spawn</code> runs one alert.</td></tr>
                            <tr><td><code>ratting-manager:poll-system-kills</code></td><td>Keeps ESI's hourly NPC kill counts for the home systems, for the Outsiders alert. Runs hourly.</td></tr>
                            <tr><td><code>ratting-manager:send-reports</code></td><td>Sends the Director Reports and Rankings that are due. Runs daily at 11:20 EVE; <code>--weekly</code> or <code>--monthly</code> for one kind, <code>--force</code> to send the last period again.</td></tr>
                        </tbody>
                    </table>
                    <p>Each step of <code>process</code> also has its own command (<code>import-corp-journal</code>, <code>import-character-journal</code>, <code>update-tax-rates</code>, <code>snapshot-tax-rates</code>, <code>sync-npc-types</code>, <code>build-sessions</code>, <code>update-daily-summaries</code>). They share one lock, so a manual run never overlaps the scheduled one.</p>
                </div>
            </div>

            {{-- Permissions --}}
            <div id="permissions" class="help-section">
                <div class="help-card">
                    <h3><i class="fas fa-user-shield"></i> Permissions</h3>
                    <p>Each tier includes the ones below it, so a director does not also need Member.</p>
                    <table class="table table-sm">
                        <tbody>
                            <tr><td><code>ratting-manager.view</code></td><td>This Help page.</td></tr>
                            <tr><td><code>ratting-manager.member</code></td><td>My Ratting, and the leaderboard with rank and hours.</td></tr>
                            <tr><td><code>ratting-manager.director</code></td><td>The Corporation tab, the full leaderboard with everyone's ISK, and the Ratter Lookup.</td></tr>
                            <tr><td><code>ratting-manager.admin</code></td><td>Settings and Diagnostics.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- FAQ --}}
            <div id="faq" class="help-section">
                <div class="help-card">
                    <h3><i class="fas fa-question"></i> FAQ</h3>
                    <h5>My ratting does not show up.</h5>
                    <p>Your character has to be in a tracked corporation. With a wallet token on SeAT your ticks come from your own journal; without one they come from the corporation journal, which needs a director token. Either way, allow one to two hours.</p>
                    <h5>Why is some of my income marked as worked out?</h5>
                    <p>Those ticks are only in the corporation journal, usually because your wallet token is missing or new. Adding the token brings in the last 30 days of your journal, and the nightly run pairs them.</p>
                    <h5>Why is my ISK per hour lower in a fleet?</h5>
                    <p>Fleets share bounties, so each member receives part of each payout. Your ISK per hour is what you received.</p>
                    <h5>A ratter shows as Character info in progress.</h5>
                    <p>SeAT does not know them and the journal did not give a name, so they are being looked up in the background. The page refreshes itself when they are in. If it stays that way for more than a few minutes, Diagnostics, Health Checks shows whether the lookup is running.</p>
                    <h5>An alert I expected was not sent.</h5>
                    <p>Check the Routing Map: the notification has to be switched on and ticked for a webhook that is switched on and for that corporation. Diagnostics, Notification Testing shows every send with Discord's answer, and what each alert has already sent for.</p>
                    <h5>Why does the Outsiders alert arrive hours late?</h5>
                    <p>It compares ESI's count for an hour with what the tracked corporations killed in it, and their ticks take one to two hours to reach SeAT. An hour is looked at once it is three hours old, so members' own ratting is never mistaken for outsiders.</p>
                    <h5>A rat shows as Other or Size not known.</h5>
                    <p>It is a type the plugin could not place. It still counts in every total. Diagnostics, Master Test lists it.</p>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection

@push('javascript')
<script>
$(function () {
    $('.help-nav .nav-link').on('click', function (e) {
        e.preventDefault();
        var section = $(this).data('section');
        $('.help-nav .nav-link').removeClass('active');
        $(this).addClass('active');
        $('.help-section').removeClass('active');
        $('#' + section).addClass('active');
        window.location.hash = section;
    });

    if (window.location.hash) {
        $('.help-nav .nav-link[data-section="' + window.location.hash.substring(1) + '"]').click();
    }
});
</script>
@endpush
