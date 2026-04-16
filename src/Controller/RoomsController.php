<?php
declare(strict_types=1);

namespace App\Controller;

use App\Game\GameRegistry;
use Cake\Http\Exception\BadRequestException;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;

class RoomsController extends AppController
{
    public function beforeFilter(\Cake\Event\EventInterface $event): void
    {
        parent::beforeFilter($event);
    }

    public function lobby(string $slug)
    {
        $registry = GameRegistry::getInstance();
        if (!$registry->has($slug)) throw new NotFoundException();
        $game = $registry->get($slug);

        if (!$game->isMultiplayer()) {
            return $this->redirect(['controller' => 'Games', 'action' => 'play', $slug]);
        }

        $rooms = $this->fetchTable('GameRooms')->findOpen($slug);
        $this->set(compact('game', 'rooms'));
    }

    public function create(string $slug): Response
    {
        $this->request->allowMethod(['post']);
        $user = $this->Authentication->getIdentity();
        if (!$user) throw new ForbiddenException();

        $registry = GameRegistry::getInstance();
        if (!$registry->has($slug)) throw new NotFoundException();
        $game = $registry->get($slug);
        if (!$game->isMultiplayer()) throw new BadRequestException();

        $rooms = $this->fetchTable('GameRooms');
        $state = $game->newGame();
        $code = $rooms->generateCode();

        $room = $rooms->newEntity([
            'code'      => $code,
            'game_slug' => $slug,
            'host_id'   => $user->get('id'),
            'state'     => $state,
            'players'   => [['id' => $user->get('id'), 'name' => $user->get('username')]],
            'status'    => 'waiting',
            'version'   => 0,
        ]);
        $rooms->save($room);

        return $this->redirect(['action' => 'view', $code]);
    }

    public function join(string $code): Response
    {
        $this->request->allowMethod(['post']);
        $user = $this->Authentication->getIdentity();
        if (!$user) throw new ForbiddenException();

        $rooms = $this->fetchTable('GameRooms');
        $room = $rooms->findByCode($code);
        if (!$room) throw new NotFoundException();

        $registry = GameRegistry::getInstance();
        $game = $registry->get($room->game_slug);

        $players = $room->players;
        $already = false;
        foreach ($players as $p) {
            if ((int)$p['id'] === (int)$user->get('id')) { $already = true; break; }
        }

        if (!$already) {
            if (count($players) >= $game->maxPlayers()) {
                throw new BadRequestException('Salle pleine.');
            }
            $players[] = ['id' => $user->get('id'), 'name' => $user->get('username')];
            $room->players = $players;
            if (count($players) >= $game->minPlayers()) {
                $room->status = 'playing';
            }
            $room->version = (int)$room->version + 1;
            $rooms->save($room);
        }

        return $this->redirect(['action' => 'view', $code]);
    }

    public function view(string $code): void
    {
        $rooms = $this->fetchTable('GameRooms');
        $room = $rooms->findByCode($code);
        if (!$room) throw new NotFoundException();

        $registry = GameRegistry::getInstance();
        $game = $registry->get($room->game_slug);

        $user = $this->Authentication->getIdentity();
        $myIndex = null;
        if ($user) {
            foreach ($room->players as $i => $p) {
                if ((int)$p['id'] === (int)$user->get('id')) { $myIndex = $i; break; }
            }
        }

        $this->set(compact('room', 'game', 'myIndex'));
        $this->viewBuilder()->setTemplatePath($game->getTemplateDir());
        $this->viewBuilder()->setTemplate('room');
    }

    public function state(string $code): Response
    {
        $rooms = $this->fetchTable('GameRooms');
        $room = $rooms->findByCode($code);
        if (!$room) throw new NotFoundException();

        return $this->json([
            'ok'      => true,
            'code'    => $room->code,
            'state'   => $room->state,
            'players' => $room->players,
            'status'  => $room->status,
            'version' => (int)$room->version,
        ]);
    }

    public function move(string $code): Response
    {
        $this->request->allowMethod(['post']);
        $user = $this->Authentication->getIdentity();
        if (!$user) throw new ForbiddenException();

        $rooms = $this->fetchTable('GameRooms');
        $room = $rooms->findByCode($code);
        if (!$room) throw new NotFoundException();

        $registry = GameRegistry::getInstance();
        $game = $registry->get($room->game_slug);

        $myIndex = null;
        foreach ($room->players as $i => $p) {
            if ((int)$p['id'] === (int)$user->get('id')) { $myIndex = $i; break; }
        }
        if ($myIndex === null) throw new ForbiddenException();

        $input = (array)$this->request->getData();

        try {
            $newState = $game->play($room->state, $input, $myIndex);
        } catch (\Throwable $e) {
            return $this->json(['ok' => false, 'error' => $e->getMessage()], 400);
        }

        $room->state = $newState;
        $room->version = (int)$room->version + 1;
        if (($newState['status'] ?? '') === 'finished') {
            $room->status = 'finished';
            $this->recordMultiScores($room, $game);
        }
        $rooms->save($room);

        return $this->json([
            'ok'      => true,
            'state'   => $room->state,
            'status'  => $room->status,
            'version' => (int)$room->version,
        ]);
    }

    private function recordMultiScores($room, $game): void
    {
        $scores = $this->fetchTable('Scores');
        $winner = $room->state['winner'] ?? null;

        foreach ($room->players as $i => $p) {
            $entity = $scores->newEntity([
                'user_id'   => $p['id'],
                'game_slug' => $room->game_slug,
                'score'     => (int)($room->state['scores'][$i] ?? 0),
                'won'       => ($winner === $i) ? 1 : 0,
                'meta'      => [
                    'mode'    => 'online',
                    'room'    => $room->code,
                    'opponent'=> $room->players[1 - $i]['name'] ?? null,
                    'winner'  => $winner,
                ],
            ]);
            $scores->save($entity);
        }
    }

    private function json(array $data, int $code = 200): Response
    {
        return $this->response
            ->withType('application/json')
            ->withStatus($code)
            ->withStringBody(json_encode($data, JSON_UNESCAPED_UNICODE));
    }
}
