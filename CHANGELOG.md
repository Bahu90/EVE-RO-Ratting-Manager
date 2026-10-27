# Changelog

All notable changes to Ratting Manager will be documented in this file.

## [1.0.0] (in preparation)

**First release.** There is nothing to update from: installing creates every table and adds the schedules.

### Ratting data

- Bounty ticks and ESS payouts are imported from both wallet journals SeAT pulls, for the corporations picked in Settings. The corporation journal names the ratter on every tick it taxes, so members with no wallet token are seen too.
- A tick seen in both journals is paired: the character journal gives what the character received, the corporation journal the corporation's cut.
- A tick only the corporation journal has gets its income worked out exactly from the cut, at the corporation's tax rate for that day. The rate for each day is read back from that day's paired ticks, falling back to the nearest day that had some and then to the rate SeAT holds.
- ESS payouts are paired and worked out the same way.
- Each tick's kill list is kept and counted once per fleet payout.
- NPCs are placed by faction, size and rank from SeAT's SDE, by group name, or by mass where a group mixes sizes. Officers and commanders come from their SDE groups and clone soldiers from their names; a rat with Commander or Officer only in its own name is a regular rat.
- Ticks are grouped into sessions, and time is counted from the 20 minutes each tick stands for, once per player however many alts were ratting.
- A character's ticks count for the corporation they were in at the time.
- Names and corporations for ratters SeAT does not know come from the journal rows first, then a background lookup: ESI, with EVEWho and zKillboard behind it, the same lookup Mining Manager uses. Pages never wait on it; a character nobody has named yet shows as *Character info in progress*, and the page refreshes itself once the lookup has them.

### Pages

- **My Ratting** for every member: income, ISK per hour, hours, rats, active days, rank by hours and the corporation tax their ratting paid, with charts of income and tax by month, income by day, systems, factions and sizes, a weekday by hour map, each session's ISK per hour against the median, income by character, recent sessions, special spawns and personal records. Every chart says what it shows in a line under it.
- **Insights** on My Ratting: favourite and best-paying system, most active and best-paying day, most active and best-paying time of day, ratting streaks, sessions, rats per hour, favourite target and the ESS share of income. Hours by day of the week and by hour of the day, ISK per hour by system, every system ratted with its hours, share, income, ISK per hour and rats, and the NPCs killed most.
- **Month by character** on My Ratting: what each character earned in a month, bounties, ESS, corporation tax, hours, ISK per hour, ticks and rats, with the month's total across all of them, any of the last 12 months a pick away. Below it, each character's income month by month.
- **Corporation tab** for directors: totals, the corporation's cut, median ISK per hour, coverage, the leaderboard, income by month and day, who earns the income, when the corporation rats, active ratters per month, ratters coming and going, what the corporation kills, untracked ratters, players who have gone quiet, and special spawns.
- **Insights** on the Corporation tab: the same as on My Ratting for the whole corporation, plus the most players ratting at once, players per active day and income per player. Hours by day of the week, players ratting by hour, ISK per hour by system, every system with how many players ratted there and who ratted there most, and the NPCs killed most.
- **Month by month** on the Corporation tab: what the players earned and what landed in the corporation's wallet each month, the tax rate that makes, players, characters, hours, ISK per hour and income per player, with a chart of earned against taxed.
- **Leaderboard**: rank and hours for members, every column for directors. Directors get the top three on a podium, their own place, and every member as cards or as the full table, searchable.
- Long lists on the dashboards (sessions, systems, special spawns, untracked ratters, players gone quiet and the Corporation tab's leaderboard) show their first 10 rows, with **View all** opening the whole list on its own page, searchable and sortable.
- **Ratter Lookup** for directors: every player who ratted in a period (the last 90 days by default), biggest first, with how many have a wallet token on SeAT and a filter for those who do and those who don't.
- The Ratter Lookup, ratter pages, Leaderboard and full lists sit in the same dark body as the dashboard, under a tab naming the page.
- **Settings**, in sections: corporations to track, picked by searching SeAT's corporations and confirming after a look at each one's ticker, alliance, tax rate, members, characters on SeAT and journal; session gap, the Gone quiet lists, the members' leaderboard size and extra NPC types to watch; Webhooks; Notifications; and the Routing Map.
- The dashboard, Leaderboard and Ratter Lookup follow the corporation's ratting page: black ground, the Oxanium typeface, ISK in blue and tax in green, a page head with the period as its title, and EVE portraits. The Corporation view opens on the ISK ratted, the ratting tax split by bounties and ESS, where the corporation ratted, and a daily chart of ISK ratted and tax.
- **Help**, and **Diagnostics** with Health Checks, Master Test, Settings Health, Data Integrity, Tick Trace and Notification Testing.

### Running it for any corporation

- Published as `bahu90/ratting-manager`, Bahu90's edition of Matt Falahe's Ratting Manager.
- **Settings > Access**: directors see their own corporation (the tracked corporations their characters are in now) or every tracked corporation; admins always see all. Optionally, the pages need a character in a tracked corporation now, so players who leave lose them at once.
- **Settings > Display**: EVE-RO's look or standard SeAT's, and the Monthly Report's JSON export button (admins only) on or off.
- Pages name the corporation (its ticker) when they cover one, show its tax rate, and drop the corporation pickers and wording that only matter with several.
- Players who have left are tagged *Left corp* on the leaderboards, the Ratter Lookup and their ratter page.
- The Oxanium font ships with the plugin; no page calls Google Fonts.
- The menu shows to the view tier, so Help is reachable from it.
- Unit tests for ISK ratted, worked-out income, ratting time, ISK formatting and periods.

### Notifications

- Posts to Discord webhooks, and to Slack incoming webhooks as plain text. A webhook is for every tracked corporation or for one, and only gets that corporation's posts.
- Each notification type has a switch and a default role, and is sent to the webhooks ticked for it, each with a role of its own if wanted. The webhook's own role comes first, then the default; *No ping* mentions nobody. Only the role or user named is ever pinged. Roles can be picked by name from SeAT Broadcast or SeAT Connector when either is installed. Nothing is bound on install.
- What a webhook carries is ticked when adding it, and its edit button opens it with its URL and notifications filled in, to change any of them.
- **Special spawn**: an NPC of the chosen rank or above (clone soldier, commander, officer), a capital rat or an NPC on the watch list, one post per spawn however many fleet members shared it.
- **Ratting ship lost**: a tracked pilot lost a ship in the system they had a tick in shortly before, with a zKillboard link.
- **Outsiders in home space**: ESI's hourly NPC kill count for each system of the home space, less what the tracked corporations killed there, once the journals for that hour are in. The home space is the regions, constellations and systems listed on the alert. With none listed it sends nothing even when switched on, and Settings, the Routing Map and Diagnostics say so.
- **Data stalled**: a corporation's wallet journal has stopped coming in, or the import has stopped running, and again when it is back.
- **Untracked ratter** and **Ratter gone quiet**: one post per corporation listing the characters or players new to the list.
- **Director Report**, weekly and monthly: what the players earned, what landed in the corporation's wallet and the tax rate, the change on the period before, players, hours, median ISK per hour, rats and top systems, with players ranked by income (tax paid beside it), hours, ISK per hour and corporation tax paid.
- **Rankings**, weekly and monthly: players ranked by hours only, with no ISK, for members' channels.
- Players are ranked as a main with all their alts.
- Each alert remembers what it has sent and never sends the same thing twice; a post every bound webhook refused is tried again on the next run. Every send is recorded, and Notification Testing shows each webhook's delivery health and sends a sample of any notification to any webhook.
- The **Routing Map** shows where every notification goes and who it mentions, as it will be sent.

### Commands and schedule

- `ratting-manager:initialize` for the first import, of the last 365 days SeAT holds since no page shows more, with a progress bar for each step, `ratting-manager:process` for the scheduled one, `ratting-manager:trace-tick` to follow one journal row, and a command for each step of the import.
- `process` runs twice an hour over the last two days, and once a day over the last 31 so tokens added later pair with the corporation rows already imported.
- `resolve-characters --requested` runs every minute for the characters pages asked for, and the full lookup every 10 minutes.
- `ratting-manager:notify` checks the alerts twice an hour, five minutes after the import; `ratting-manager:poll-system-kills` keeps ESI's kill counts hourly; `ratting-manager:send-reports` sends the reports that are due daily at 11:20 EVE.

### Speed

- Every figure is read from the plugin's own tables, indexed for the way the pages and the import read them. Charts of income, systems and kills come from day by day totals; only time and player counts read the ticks, once per page, as a stream.
- Figures are kept until the next import changes them, and the ones the Corporation tab opens with are worked out straight after each import. A year of a 200-character corporation opens in about two seconds the first time and at once after that.
