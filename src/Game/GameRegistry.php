<?php
declare(strict_types=1);

namespace App\Game;

use App\Game\Filler\FillerGame;
use App\Game\Labyrinth\LabyrinthGame;
use App\Game\Mastermind\MastermindGame;
use RuntimeException;

final class GameRegistry
{
    private array $games = [];
    private static ?self $instance = null;

    private function __construct()
    {
        $this->register(new MastermindGame());
        $this->register(new FillerGame());
        $this->register(new LabyrinthGame());
    }

    public static function getInstance(): self
    {
        return self::$instance ??= new self();
    }

    private function register(GameInterface $game): void
    {
        $this->games[$game->getSlug()] = $game;
    }

    public function all(): array { return $this->games; }

    public function get(string $slug): GameInterface
    {
        if (!isset($this->games[$slug])) {
            throw new RuntimeException("Jeu inconnu: {$slug}");
        }
        return $this->games[$slug];
    }

    public function has(string $slug): bool { return isset($this->games[$slug]); }
}
