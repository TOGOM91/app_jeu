<?php
declare(strict_types=1);

namespace App\Game\Mastermind;

use App\Game\GameInterface;
use InvalidArgumentException;

final class MastermindGame implements GameInterface
{
    public const COLORS     = ['red', 'blue', 'green', 'yellow', 'purple', 'orange'];
    public const CODE_LEN   = 4;
    public const MAX_ROUNDS = 10;

    public function getSlug(): string        { return 'mastermind'; }
    public function getName(): string        { return 'Mastermind'; }
    public function getDescription(): string { return 'Déchiffre le code secret en 10 essais.'; }
    public function getIcon(): string        { return '◉'; }
    public function getTemplateDir(): string { return 'Mastermind'; }
    public function isMultiplayer(): bool    { return false; }
    public function minPlayers(): int        { return 1; }
    public function maxPlayers(): int        { return 1; }

    public function newGame(array $options = []): array
    {
        $secret = [];
        for ($i = 0; $i < self::CODE_LEN; $i++) {
            $secret[] = self::COLORS[random_int(0, count(self::COLORS) - 1)];
        }

        return [
            'secret'  => $secret,
            'guesses' => [],
            'status'  => 'playing',
            'round'   => 0,
            'max'     => self::MAX_ROUNDS,
            'codeLen' => self::CODE_LEN,
            'colors'  => self::COLORS,
        ];
    }

    public function play(array $state, array $input, ?int $playerIndex = null): array
    {
        if (($state['status'] ?? 'playing') !== 'playing') {
            return $state;
        }

        $guess = $input['guess'] ?? [];
        $this->validateGuess($guess);

        $feedback = $this->score($state['secret'], $guess);

        $state['guesses'][] = [
            'guess' => $guess,
            'black' => $feedback['black'],
            'white' => $feedback['white'],
        ];
        $state['round']++;

        if ($feedback['black'] === self::CODE_LEN) {
            $state['status'] = 'won';
        } elseif ($state['round'] >= self::MAX_ROUNDS) {
            $state['status'] = 'lost';
        }

        return $state;
    }

    private function score(array $secret, array $guess): array
    {
        $black = 0;
        $secretRemaining = [];
        $guessRemaining  = [];

        foreach ($secret as $i => $color) {
            if ($guess[$i] === $color) {
                $black++;
            } else {
                $secretRemaining[$color] = ($secretRemaining[$color] ?? 0) + 1;
                $guessRemaining[$guess[$i]] = ($guessRemaining[$guess[$i]] ?? 0) + 1;
            }
        }

        $white = 0;
        foreach ($guessRemaining as $color => $count) {
            if (isset($secretRemaining[$color])) {
                $white += min($count, $secretRemaining[$color]);
            }
        }

        return ['black' => $black, 'white' => $white];
    }

    private function validateGuess(array $guess): void
    {
        if (count($guess) !== self::CODE_LEN) {
            throw new InvalidArgumentException('Le code doit contenir ' . self::CODE_LEN . ' pions.');
        }
        foreach ($guess as $color) {
            if (!in_array($color, self::COLORS, true)) {
                throw new InvalidArgumentException("Couleur invalide: {$color}");
            }
        }
    }
}
