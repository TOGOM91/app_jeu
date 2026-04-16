<?php
declare(strict_types=1);

namespace App\Controller;

use App\Game\GameRegistry;
use Cake\Http\Exception\NotFoundException;

class UsersController extends AppController
{
    public function beforeFilter(\Cake\Event\EventInterface $event): void
    {
        parent::beforeFilter($event);
        $this->Authentication->allowUnauthenticated(['login', 'register', 'view']);
    }

    public function register()
    {
        $user = $this->Users->newEmptyEntity();
        if ($this->request->is('post')) {
            $user = $this->Users->patchEntity($user, (array)$this->request->getData());
            if ($this->Users->save($user)) {
                $this->Flash->success('Compte créé. Connecte-toi.');
                return $this->redirect(['action' => 'login']);
            }
            $this->Flash->error('Impossible de créer le compte.');
        }
        $this->set(compact('user'));
    }

    public function login()
    {
        $this->request->allowMethod(['get', 'post']);
        $result = $this->Authentication->getResult();

        if ($result && $result->isValid()) {
            $target = $this->Authentication->getLoginRedirect() ?? '/';
            return $this->redirect($target);
        }
        if ($this->request->is('post') && $result && !$result->isValid()) {
            $this->Flash->error('Identifiants invalides.');
        }
    }

    public function logout()
    {
        $this->Authentication->logout();
        return $this->redirect(['action' => 'login']);
    }

    /** Page profil : /users/{username} — visible par tout le monde. */
    public function view(string $username)
    {
        $user = $this->Users->find()
            ->where(['username' => $username])
            ->first();

        if (!$user) {
            throw new NotFoundException('Utilisateur introuvable.');
        }

        $scoresTable = $this->fetchTable('Scores');
        $scoresByGame = $scoresTable->findForUser($user->id);
        $stats        = $scoresTable->statsForUser($user->id);

        $identity = $this->Authentication->getIdentity();
        $isMe = $identity && (int)$identity->get('id') === (int)$user->id;

        $activeRooms = [];
        if ($isMe) {
            $roomsTable = $this->fetchTable('GameRooms');
            $rows = $roomsTable->find()
                ->where(['status IN' => ['waiting', 'playing']])
                ->orderBy(['modified' => 'DESC'])
                ->toArray();
            $activeRooms = array_values(array_filter($rows, function ($r) use ($user) {
                foreach ($r->players as $p) {
                    if ((int)$p['id'] === (int)$user->id) return true;
                }
                return false;
            }));
        }

        $this->set([
            'profile'      => $user,
            'scoresByGame' => $scoresByGame,
            'stats'        => $stats,
            'registry'     => GameRegistry::getInstance(),
            'isMe'         => $isMe,
            'activeRooms'  => $activeRooms,
        ]);
    }
}
