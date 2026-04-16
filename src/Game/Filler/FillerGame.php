<?php
declare(strict_types=1);

namespace App\Game\Filler;

use App\Game\GameInterface;
use InvalidArgumentException;

final class FillerGame implements GameInterface
{
    public const COLORS = ['red', 'blue', 'green', 'yellow', 'purple', 'orange'];
    public const WIDTH  = 8;
    public const HEIGHT = 7;

    public function getSlug(): string        { return 'filler'; }
    public function getName(): string        { return 'Filler'; }
    public function getDescription(): string { return 'Conquiers plus de cases que ton adversaire.'; }
    public function getIcon(): string        { return '▣'; }
    public function getTemplateDir(): string { return 'Filler'; }
    public function isMultiplayer(): bool    { return true; }
    public function minPlayers(): int        { return 2; }
    public function maxPlayers(): int        { return 2; }

    public function newGame(array $options = []): array
    {
        $grid = [];
        for ($y = 0; $y < self::HEIGHT; $y++) {
            for ($x = 0; $x < self::WIDTH; $x++) {
                do {
                    $c = self::COLORS[random_int(0, count(self::COLORS) - 1)];
                    $conflict = false;
                    if ($x > 0 && ($grid[$y][$x - 1] ?? null) === $c) $conflict = true;
                    if ($y > 0 && ($grid[$y - 1][$x] ?? null) === $c) $conflict = true;
                } while ($conflict);
                $grid[$y][$x] = $c;
            }
        }

        $p1Color = $grid[self::HEIGHT - 1][0];
        $p2Color = $grid[0][self::WIDTH - 1];

        $owners = [];
        for ($y = 0; $y < self::HEIGHT; $y++) {
            for ($x = 0; $x < self::WIDTH; $x++) {
                $owners[$y][$x] = null;
            }
        }
        $owners[self::HEIGHT - 1][0] = 0;
        $owners[0][self::WIDTH - 1]  = 1;

        $state = [
            'grid'    => $grid,
            'owners'  => $owners,
            'width'   => self::WIDTH,
            'height'  => self::HEIGHT,
            'colors'  => self::COLORS,
            'turn'    => 0,
            'status'  => 'playing',
            'winner'  => null,
            'scores'  => [1, 1],
            'last'    => [$p1Color, $p2Color],
        ];

        $state = $this->expand($state, 0, $p1Color);
        $state = $this->expand($state, 1, $p2Color);
        $state['last'] = [$p1Color, $p2Color];

        return $state;
    }

    public function play(array $state, array $input, ?int $playerIndex = null): array
    {
        if ($state['status'] !== 'playing') return $state;
        if ($playerIndex === null) {
            throw new InvalidArgumentException('playerIndex requis');
        }
        if ($playerIndex !== $state['turn']) {
            throw new InvalidArgumentException('Ce n\'est pas votre tour.');
        }

        $color = $input['color'] ?? null;
        if (!in_array($color, self::COLORS, true)) {
            throw new InvalidArgumentException('Couleur invalide.');
        }
        if (in_array($color, $state['last'], true)) {
            throw new InvalidArgumentException('Couleur interdite (déjà en jeu).');
        }

        $state = $this->expand($state, $playerIndex, $color);
        $state['last'][$playerIndex] = $color;

        $total = self::WIDTH * self::HEIGHT;
        $filled = $state['scores'][0] + $state['scores'][1];

        if ($filled >= $total) {
            $state['status'] = 'finished';
            if ($state['scores'][0] > $state['scores'][1])      $state['winner'] = 0;
            elseif ($state['scores'][1] > $state['scores'][0])  $state['winner'] = 1;
            else                                                 $state['winner'] = -1;
        } else {
            $state['turn'] = 1 - $playerIndex;
        }

        return $state;
    }

    private function expand(array $state, int $player, string $color): array
    {
        $w = $state['width'];
        $h = $state['height'];

        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                if ($state['owners'][$y][$x] === $player) {
                    $state['grid'][$y][$x] = $color;
                }
            }
        }

        $changed = true;
        while ($changed) {
            $changed = false;
            for ($y = 0; $y < $h; $y++) {
                for ($x = 0; $x < $w; $x++) {
                    if ($state['owners'][$y][$x] !== null) continue;
                    if ($state['grid'][$y][$x] !== $color) continue;

                    $adj = false;
                    foreach ([[0,-1],[0,1],[-1,0],[1,0]] as [$dx,$dy]) {
                        $nx = $x + $dx; $ny = $y + $dy;
                        if ($nx < 0 || $nx >= $w || $ny < 0 || $ny >= $h) continue;
                        if (($state['owners'][$ny][$nx] ?? null) === $player) { $adj = true; break; }
                    }
                    if ($adj) {
                        $state['owners'][$y][$x] = $player;
                        $changed = true;
                    }
                }
            }
        }

        $s0 = 0; $s1 = 0;
        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                if ($state['owners'][$y][$x] === 0) $s0++;
                elseif ($state['owners'][$y][$x] === 1) $s1++;
            }
        }
        $state['scores'] = [$s0, $s1];

        return $state;
    }
}
