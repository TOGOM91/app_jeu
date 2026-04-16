<?php
declare(strict_types=1);

namespace App\Game;

interface GameInterface
{
    public function getSlug(): string;
    public function getName(): string;
    public function getDescription(): string;
    public function getIcon(): string;
    public function getTemplateDir(): string;
    public function isMultiplayer(): bool;
    public function minPlayers(): int;
    public function maxPlayers(): int;
    public function newGame(array $options = []): array;
    public function play(array $state, array $input, ?int $playerIndex = null): array;
}
