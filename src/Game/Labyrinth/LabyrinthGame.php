<?php
declare(strict_types=1);

namespace App\Game\Labyrinth;

use App\Game\GameInterface;
use InvalidArgumentException;
use RuntimeException;

final class LabyrinthGame implements GameInterface
{
    public const START_PA     = 10;
    public const MAX_PA       = 15;
    public const REGEN_PA     = 5;
    public const REGEN_SEC    = 60;
    public const TREASURE_PA  = 10;
    public const MOVE_COST    = 1;
    public const MAX_EQUIP    = 2;

    public const EQUIPMENTS = [
        'sword'  => ['name' => 'Épée',    'icon' => '⚔', 'attack' => 3, 'defense' => 0, 'bonus_pa' => 0],
        'armor'  => ['name' => 'Armure',  'icon' => '⛊', 'attack' => 0, 'defense' => 2, 'bonus_pa' => 0],
        'potion' => ['name' => 'Potion',  'icon' => '✚', 'attack' => 0, 'defense' => 0, 'bonus_pa' => 8],
        'torch'  => ['name' => 'Torche',  'icon' => '✦', 'attack' => 1, 'defense' => 1, 'bonus_pa' => 0],
    ];

    public const MONSTER_HP      = 3;
    public const MONSTER_ATK     = 2;
    public const MONSTER_COUNT   = 3;
    public const EQUIP_COUNT     = 4;
    public const MONSTER_MOVE_SEC = 15;

    public function getSlug(): string        { return 'labyrinth'; }
    public function getName(): string        { return 'Labyrinthe'; }
    public function getDescription(): string { return 'Survis, équipe-toi, terrasse ton rival.'; }
    public function getIcon(): string        { return '⌬'; }
    public function getTemplateDir(): string { return 'Labyrinth'; }
    public function isMultiplayer(): bool    { return true; }
    public function minPlayers(): int        { return 2; }
    public function maxPlayers(): int        { return 2; }

    public function newGame(array $options = []): array
    {
        $mapFile = $options['map'] ?? CONFIG . 'maps/default.txt';
        $raw = @file_get_contents($mapFile);
        if ($raw === false) throw new RuntimeException("Map introuvable: {$mapFile}");

        $lines = array_values(array_filter(
            array_map('rtrim', explode("\n", $raw)),
            fn($l) => $l !== ''
        ));

        $h = count($lines);
        $w = max(array_map('strlen', $lines));

        $grid = [];
        $treasure = null;
        $floors = [];
        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                $ch = $lines[$y][$x] ?? '#';
                if ($ch === '#') {
                    $grid[$y][$x] = 'wall';
                } elseif ($ch === '$') {
                    $grid[$y][$x] = 'floor';
                    $treasure = [$x, $y];
                    $floors[] = [$x, $y];
                } else {
                    $grid[$y][$x] = 'floor';
                    $floors[] = [$x, $y];
                }
            }
        }

        if (!$treasure) {
            $treasure = $floors[array_rand($floors)];
        }

        [$p1, $p2] = $this->pickStartPositions($grid, $w, $h, $treasure);

        $reserved = [
            $treasure,
            $p1, $p2,
        ];
        $reservedKey = fn($x, $y) => "$x,$y";
        $reservedSet = [];
        foreach ($reserved as $r) $reservedSet[$reservedKey($r[0], $r[1])] = true;

        $availableFloors = array_values(array_filter($floors,
            fn($f) => !isset($reservedSet[$reservedKey($f[0], $f[1])])
        ));
        shuffle($availableFloors);

        $equipments = [];
        $types = array_keys(self::EQUIPMENTS);
        for ($i = 0; $i < self::EQUIP_COUNT && !empty($availableFloors); $i++) {
            [$x, $y] = array_shift($availableFloors);
            $equipments[] = [
                'x' => $x, 'y' => $y,
                'type' => $types[$i % count($types)],
            ];
        }

        $monsters = [];
        for ($i = 0; $i < self::MONSTER_COUNT && !empty($availableFloors); $i++) {
            [$x, $y] = array_shift($availableFloors);
            $monsters[] = [
                'id' => $i,
                'x'  => $x, 'y' => $y,
                'hp' => self::MONSTER_HP,
                'atk' => self::MONSTER_ATK,
                'alive' => true,
                'lastMove' => time(),
            ];
        }

        $now = time();

        return [
            'width'    => $w,
            'height'   => $h,
            'grid'     => $grid,
            'treasure' => ['x' => $treasure[0], 'y' => $treasure[1], 'taken' => false],
            'players'  => [
                ['x' => $p1[0], 'y' => $p1[1], 'pa' => self::START_PA, 'lastRegen' => $now,
                 'hasTreasure' => false, 'alive' => true, 'equipment' => [], 'hp' => 10],
                ['x' => $p2[0], 'y' => $p2[1], 'pa' => self::START_PA, 'lastRegen' => $now,
                 'hasTreasure' => false, 'alive' => true, 'equipment' => [], 'hp' => 10],
            ],
            'equipments' => $equipments,
            'monsters'   => $monsters,
            'status'   => 'playing',
            'winner'   => null,
            'log'      => [],
        ];
    }

    private function pickStartPositions(array $grid, int $w, int $h, array $treasure): array
    {
        $candidates = [];
        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                if (($grid[$y][$x] ?? 'wall') !== 'floor') continue;
                if ($x === $treasure[0] && $y === $treasure[1]) continue;
                foreach ([[0,-1],[0,1],[-1,0],[1,0]] as [$dx,$dy]) {
                    $nx = $x + $dx; $ny = $y + $dy;
                    if (($grid[$ny][$nx] ?? 'wall') === 'floor'
                        && !($nx === $treasure[0] && $ny === $treasure[1])) {
                        $candidates[] = [[$x, $y], [$nx, $ny]];
                    }
                }
            }
        }
        if (!$candidates) throw new RuntimeException('Map invalide: pas de paire adjacente.');

        $best = null;
        $bestDist = -1;
        foreach ($candidates as $pair) {
            $d = abs($pair[0][0] - $treasure[0]) + abs($pair[0][1] - $treasure[1]);
            if ($d > $bestDist) { $bestDist = $d; $best = $pair; }
        }
        return $best;
    }

    public function play(array $state, array $input, ?int $playerIndex = null): array
    {
        if ($state['status'] !== 'playing') return $state;
        if ($playerIndex === null || !isset($state['players'][$playerIndex])) {
            throw new InvalidArgumentException('playerIndex invalide');
        }

        $state = $this->regenAll($state);
        $state = $this->tickMonsters($state);

        $me = &$state['players'][$playerIndex];
        if (!$me['alive']) throw new InvalidArgumentException('Vous êtes éliminé.');

        $action = $input['action'] ?? 'move';

        if ($action === 'use') {
            return $this->useItem($state, $playerIndex, (int)($input['slot'] ?? -1));
        }
        if ($action === 'drop') {
            return $this->dropItem($state, $playerIndex, (int)($input['slot'] ?? -1));
        }
        if ($action !== 'move') throw new InvalidArgumentException('Action inconnue.');

        $dir = $input['dir'] ?? null;
        $deltas = ['up' => [0,-1], 'down' => [0,1], 'left' => [-1,0], 'right' => [1,0]];
        if (!isset($deltas[$dir])) throw new InvalidArgumentException('Direction invalide.');

        if ($me['pa'] < self::MOVE_COST) throw new InvalidArgumentException('Pas assez de PA.');

        [$dx, $dy] = $deltas[$dir];
        $nx = $me['x'] + $dx;
        $ny = $me['y'] + $dy;

        if ($nx < 0 || $ny < 0 || $nx >= $state['width'] || $ny >= $state['height']) {
            throw new InvalidArgumentException('Hors carte.');
        }
        if (($state['grid'][$ny][$nx] ?? 'wall') !== 'floor') {
            throw new InvalidArgumentException('Mur.');
        }

        $me['pa'] -= self::MOVE_COST;

        $monsterIdx = null;
        foreach ($state['monsters'] as $i => $m) {
            if ($m['alive'] && $m['x'] === $nx && $m['y'] === $ny) { $monsterIdx = $i; break; }
        }

        if ($monsterIdx !== null) {
            $state = $this->fightMonster($state, $playerIndex, $monsterIdx);
            $me = &$state['players'][$playerIndex];
            if (!$me['alive']) {
                $state = $this->checkEnd($state);
                return $state;
            }
            if ($state['monsters'][$monsterIdx]['alive']) {
                return $state;
            }
        }

        $me['x'] = $nx;
        $me['y'] = $ny;

        foreach ($state['equipments'] as $i => $eq) {
            if ($eq['x'] === $nx && $eq['y'] === $ny) {
                if (count($me['equipment']) < self::MAX_EQUIP) {
                    $me['equipment'][] = $eq['type'];
                    $state['log'][] = "J" . ($playerIndex + 1) . " ramasse " . self::EQUIPMENTS[$eq['type']]['name'] . ".";
                    array_splice($state['equipments'], $i, 1);
                }
                break;
            }
        }

        if (!$state['treasure']['taken']
            && $nx === $state['treasure']['x']
            && $ny === $state['treasure']['y']) {
            $state['treasure']['taken'] = true;
            $me['hasTreasure'] = true;
            $me['pa'] = min(self::MAX_PA, $me['pa'] + self::TREASURE_PA);
            $state['log'][] = "J" . ($playerIndex + 1) . " s'empare du trésor (+" . self::TREASURE_PA . " PA).";
        }

        foreach ($state['players'] as $i => $other) {
            if ($i === $playerIndex) continue;
            if (!$other['alive']) continue;
            if ($other['x'] === $nx && $other['y'] === $ny) {
                $state = $this->resolveCombat($state, $playerIndex, $i);
                break;
            }
        }

        return $this->checkEnd($state);
    }

    private function fightMonster(array $state, int $playerIndex, int $monsterIdx): array
    {
        $p = &$state['players'][$playerIndex];
        $m = &$state['monsters'][$monsterIdx];

        $attack = 1 + $this->sumStat($p['equipment'], 'attack');
        $defense = $this->sumStat($p['equipment'], 'defense');

        $m['hp'] -= $attack;
        if ($m['hp'] <= 0) {
            $m['alive'] = false;
            $state['log'][] = "J" . ($playerIndex + 1) . " tue un monstre.";
        } else {
            $dmg = max(0, $m['atk'] - $defense);
            $p['hp'] -= $dmg;
            $state['log'][] = "J" . ($playerIndex + 1) . " blesse un monstre (−{$attack} PV) et subit {$dmg}.";
            if ($p['hp'] <= 0) {
                $p['alive'] = false;
                $state['log'][] = "J" . ($playerIndex + 1) . " succombe à un monstre.";
            }
        }
        return $state;
    }

    private function resolveCombat(array $state, int $attacker, int $defender): array
    {
        $a = &$state['players'][$attacker];
        $d = &$state['players'][$defender];

        $aPower = $a['pa'] + $this->sumStat($a['equipment'], 'attack') - $this->sumStat($d['equipment'], 'defense');
        $dPower = $d['pa'] + $this->sumStat($d['equipment'], 'attack') - $this->sumStat($a['equipment'], 'defense');

        if ($aPower >= $dPower) {
            $d['alive'] = false;
            if ($d['hasTreasure']) { $d['hasTreasure'] = false; $a['hasTreasure'] = true; }
            $state['log'][] = "J" . ($attacker + 1) . " élimine J" . ($defender + 1) . " (" . $aPower . " vs " . $dPower . ").";
        } else {
            $a['alive'] = false;
            if ($a['hasTreasure']) { $a['hasTreasure'] = false; $d['hasTreasure'] = true; }
            $state['log'][] = "J" . ($defender + 1) . " repousse et élimine J" . ($attacker + 1) . " (" . $dPower . " vs " . $aPower . ").";
        }
        return $state;
    }

    private function useItem(array $state, int $playerIndex, int $slot): array
    {
        $p = &$state['players'][$playerIndex];
        if (!$p['alive']) throw new InvalidArgumentException('Éliminé.');
        if (!isset($p['equipment'][$slot])) throw new InvalidArgumentException('Slot invalide.');

        $type = $p['equipment'][$slot];
        $def = self::EQUIPMENTS[$type] ?? null;
        if (!$def) throw new InvalidArgumentException('Objet inconnu.');

        if ($def['bonus_pa'] > 0) {
            $p['pa'] = min(self::MAX_PA, $p['pa'] + $def['bonus_pa']);
            array_splice($p['equipment'], $slot, 1);
            $state['log'][] = "J" . ($playerIndex + 1) . " utilise " . $def['name'] . " (+" . $def['bonus_pa'] . " PA).";
        } else {
            throw new InvalidArgumentException('Objet non consommable (effet passif).');
        }
        return $state;
    }

    private function dropItem(array $state, int $playerIndex, int $slot): array
    {
        $p = &$state['players'][$playerIndex];
        if (!isset($p['equipment'][$slot])) throw new InvalidArgumentException('Slot invalide.');

        $type = $p['equipment'][$slot];
        array_splice($p['equipment'], $slot, 1);
        $state['equipments'][] = ['x' => $p['x'], 'y' => $p['y'], 'type' => $type];
        $state['log'][] = "J" . ($playerIndex + 1) . " lâche " . self::EQUIPMENTS[$type]['name'] . ".";
        return $state;
    }

    private function sumStat(array $equipment, string $stat): int
    {
        $sum = 0;
        foreach ($equipment as $type) {
            $sum += self::EQUIPMENTS[$type][$stat] ?? 0;
        }
        return $sum;
    }

    private function checkEnd(array $state): array
    {
        $alive = array_keys(array_filter($state['players'], fn($p) => $p['alive']));
        if (count($alive) === 1) {
            $state['status'] = 'finished';
            $state['winner'] = $alive[0];
        } elseif (count($alive) === 0) {
            $state['status'] = 'finished';
            $state['winner'] = -1;
        }
        return $state;
    }

    public function regenAll(array $state): array
    {
        $now = time();
        foreach ($state['players'] as $i => $p) {
            if (!$p['alive']) continue;
            $elapsed = $now - (int)$p['lastRegen'];
            $ticks = intdiv($elapsed, self::REGEN_SEC);
            if ($ticks > 0) {
                $state['players'][$i]['pa'] = min(self::MAX_PA, $p['pa'] + $ticks * self::REGEN_PA);
                $state['players'][$i]['lastRegen'] = $p['lastRegen'] + $ticks * self::REGEN_SEC;
            }
        }
        return $state;
    }

    public function tickMonsters(array $state): array
    {
        $now = time();
        foreach ($state['monsters'] as $i => $m) {
            if (!$m['alive']) continue;
            $elapsed = $now - (int)$m['lastMove'];
            if ($elapsed < self::MONSTER_MOVE_SEC) continue;

            $ticks = min(2, intdiv($elapsed, self::MONSTER_MOVE_SEC));
            for ($t = 0; $t < $ticks; $t++) {
                $dirs = [[0,-1],[0,1],[-1,0],[1,0]];
                shuffle($dirs);
                foreach ($dirs as [$dx, $dy]) {
                    $nx = $m['x'] + $dx; $ny = $m['y'] + $dy;
                    if ($nx < 0 || $ny < 0 || $nx >= $state['width'] || $ny >= $state['height']) continue;
                    if (($state['grid'][$ny][$nx] ?? 'wall') !== 'floor') continue;

                    $occupied = false;
                    foreach ($state['monsters'] as $j => $m2) {
                        if ($j === $i || !$m2['alive']) continue;
                        if ($m2['x'] === $nx && $m2['y'] === $ny) { $occupied = true; break; }
                    }
                    foreach ($state['players'] as $p) {
                        if ($p['alive'] && $p['x'] === $nx && $p['y'] === $ny) { $occupied = true; break; }
                    }
                    if ($occupied) continue;

                    $m['x'] = $nx; $m['y'] = $ny;
                    break;
                }
            }
            $m['lastMove'] = $now;
            $state['monsters'][$i] = $m;
        }
        return $state;
    }
}
