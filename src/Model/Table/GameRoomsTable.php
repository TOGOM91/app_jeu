<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;

class GameRoomsTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('game_rooms');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp');

        $this->getSchema()->setColumnType('state', 'json');
        $this->getSchema()->setColumnType('players', 'json');
    }

    public function generateCode(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        do {
            $code = '';
            for ($i = 0; $i < 5; $i++) {
                $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
            $exists = $this->find()->where(['code' => $code])->count() > 0;
        } while ($exists);
        return $code;
    }

    public function findByCode(string $code): ?object
    {
        return $this->find()->where(['code' => $code])->first();
    }

    public function findOpen(string $gameSlug): array
    {
        return $this->find()
            ->where(['game_slug' => $gameSlug, 'status' => 'waiting'])
            ->orderBy(['created' => 'DESC'])
            ->limit(20)
            ->toArray();
    }
}
