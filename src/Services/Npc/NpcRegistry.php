<?php

namespace RattingManager\Services\Npc;

/**
 * What the plugin knows about NPCs, as data only.
 *
 * SeAT's SDE gives every rat a name and a group. Classic belt and anomaly
 * groups say both faction and size ("Asteroid Guristas Battleship"); newer
 * ones do not ("Rogue Drone Entities" mixes frigates and battleships), so
 * those fall back to the type's mass. This is the one place to correct a
 * type or group when CCP adds rats the patterns get wrong.
 */
final class NpcRegistry
{
    public const FACTIONS = [
        'guristas' => 'Guristas',
        'sansha' => "Sansha's Nation",
        'blood_raiders' => 'Blood Raiders',
        'angel' => 'Angel Cartel',
        'serpentis' => 'Serpentis',
        'rogue_drones' => 'Rogue Drones',
        'triglavian' => 'Triglavian',
        'drifter' => 'Drifters',
        'sleeper' => 'Sleepers',
        'other' => 'Other',
    ];

    public const SIZES = ['small', 'medium', 'large', 'capital', 'unknown'];

    public const RANKS = ['regular', 'faction', 'commander', 'officer'];

    /** Ranks and sizes that make a tick worth listing as a special spawn. */
    public const SPECIAL_RANKS = ['faction', 'commander', 'officer'];
    public const SPECIAL_SIZES = ['capital'];

    /**
     * Checked against the group name first, then the type name. The type
     * name patterns are the pirate naming schemes (Pithi, Centum, Corpus,
     * Gistatis, Coreli and so on), which cover rats whose group is generic.
     */
    public const FACTION_PATTERNS = [
        'guristas' => '/Guristas|\bPith(i|um|atis)?\b/i',
        'sansha' => '/Sansha|\bCent(ii|um|us|atis)\b/i',
        'blood_raiders' => '/Blood Raider|Dark Blood|\bCorp(ii|um|us|atis)\b/i',
        'angel' => '/Angel|Domination|\bGist(ii|um|atis)?\b/i',
        'serpentis' => '/Serpentis|\bCore(li|lum|latis)?\b/i',
        'rogue_drones' => '/Rogue Drone|Infesting|\bDrone\b|\bHive\b|\bSwarm\b/i',
        'triglavian' => '/Triglavian|Damavik|Vedmak|Leshak|Kikimora|Drekavac|Rodiva|Zirnitra/i',
        'drifter' => '/Drifter/i',
        'sleeper' => '/Sleeper|Sleepless|Awakened|Emergent/i',
    ];

    /** Order matters: Battlecruiser has to be read before Cruiser would match. */
    public const SIZE_PATTERNS = [
        'capital' => '/Dreadnought|Titan|Supercarrier|Carrier|Mothership|Capital/i',
        'large' => '/Battleship/i',
        'medium' => '/Battlecruiser|Cruiser/i',
        'small' => '/Frigate|Destroyer/i',
    ];

    public const RANK_PATTERNS = [
        'officer' => '/Officer/i',
        'commander' => '/Commander/i',
        'faction' => '/Dread Guristas|True Sansha|Dark Blood|Domination|Shadow Serpentis/i',
    ];

    /** Upper mass bound in kg for each size, used when the names say nothing. */
    public const MASS_BANDS = [
        'small' => 5_000_000,
        'medium' => 50_000_000,
        'large' => 800_000_000,
    ];

    /** Groups whose faction the patterns cannot read. group_id => faction */
    public const GROUP_FACTIONS = [
        4037 => 'rogue_drones', // Rogue Drone Entities ("Infesting ..."), seen on a live install
    ];

    /** Single types the patterns get wrong. type_id => [faction, size, rank] */
    public const TYPES = [];
}
