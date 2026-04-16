<?php
declare(strict_types=1);

namespace App\Command;

use App\Game\GameRegistry;
use App\Game\Labyrinth\LabyrinthGame;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;

class LabyrinthRegenCommand extends Command
{
    public function execute(Arguments $args, ConsoleIo $io): ?int
    {
        $registry = GameRegistry::getInstance();
        if (!$registry->has('labyrinth')) {
            $io->error('Labyrinth non enregistré.');
            return self::CODE_ERROR;
        }
        $game = $registry->get('labyrinth');
        if (!$game instanceof LabyrinthGame) {
            $io->error('Type inattendu.');
            return self::CODE_ERROR;
        }

        $rooms = $this->fetchTable('GameRooms');
        $active = $rooms->find()
            ->where(['game_slug' => 'labyrinth', 'status' => 'playing'])
            ->toArray();

        $updated = 0;
        foreach ($active as $room) {
            $newState = $game->regenAll($room->state);
            if ($newState !== $room->state) {
                $room->state = $newState;
                $room->version = (int)$room->version + 1;
                $rooms->save($room);
                $updated++;
            }
        }

        $io->out("Regen: {$updated}/" . count($active) . " salles mises à jour.");
        return self::CODE_SUCCESS;
    }
}
