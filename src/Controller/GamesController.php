<?php
declare(strict_types=1);

namespace App\Controller;

use App\Game\GameRegistry;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;

class GamesController extends AppController
{
    private function game(string $slug)
    {
        $registry = GameRegistry::getInstance();
        if (!$registry->has($slug)) {
            throw new NotFoundException("Jeu '{$slug}' introuvable");
        }
        return $registry->get($slug);
    }

    private function sessionKey(string $slug): string
    {
        return "game.state.{$slug}";
    }

    public function play(string $slug): void
    {
        $game = $this->game($slug);
        $session = $this->request->getSession();

        $state = $session->read($this->sessionKey($slug));
        if (!$state) {
            $state = $game->newGame();
            $session->write($this->sessionKey($slug), $state);
        }

        $this->set([
            'game'  => $game,
            'state' => $this->sanitize($state),
        ]);
        $this->viewBuilder()->setTemplatePath($game->getTemplateDir());
        $this->viewBuilder()->setTemplate('play');
    }

    public function newGame(string $slug): Response
    {
        $this->request->allowMethod(['post']);
        $game = $this->game($slug);
        $state = $game->newGame();
        $this->request->getSession()->write($this->sessionKey($slug), $state);

        return $this->json(['ok' => true, 'state' => $this->sanitize($state)]);
    }

    public function move(string $slug): Response
    {
        $this->request->allowMethod(['post']);
        $game = $this->game($slug);
        $session = $this->request->getSession();

        $state = $session->read($this->sessionKey($slug)) ?: $game->newGame();
        $input = (array)$this->request->getData();
        $playerIndex = isset($input['playerIndex']) ? (int)$input['playerIndex'] : null;

        try {
            $state = $game->play($state, $input, $playerIndex);
        } catch (\Throwable $e) {
            return $this->json(['ok' => false, 'error' => $e->getMessage()], 400);
        }

        $session->write($this->sessionKey($slug), $state);

        $payload = $this->sanitize($state);
        $status = $state['status'] ?? 'playing';
        if ($status !== 'playing') {
            if (isset($state['secret'])) $payload['secret'] = $state['secret'];
            if (!$game->isMultiplayer()) $this->recordSoloScore($slug, $state);
        }

        return $this->json(['ok' => true, 'state' => $payload]);
    }

    private function recordSoloScore(string $slug, array $state): void
    {
        $user = $this->Authentication->getIdentity();
        if (!$user) return;

        $scores = $this->fetchTable('Scores');
        $entity = $scores->newEntity([
            'user_id'   => $user->get('id'),
            'game_slug' => $slug,
            'score'     => (int)($state['round'] ?? 0),
            'won'       => ($state['status'] ?? '') === 'won' ? 1 : 0,
            'meta'      => [
                'status'  => $state['status'] ?? null,
                'guesses' => count($state['guesses'] ?? []),
            ],
        ]);
        $scores->save($entity);
    }

    private function sanitize(array $state): array
    {
        unset($state['secret']);
        return $state;
    }

    private function json(array $data, int $code = 200): Response
    {
        return $this->response
            ->withType('application/json')
            ->withStatus($code)
            ->withStringBody(json_encode($data, JSON_UNESCAPED_UNICODE));
    }
}
