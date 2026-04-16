<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\Database\Expression\QueryExpression;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\Table;

class ScoresTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('scores');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp', [
            'events' => ['Model.beforeSave' => ['created' => 'new']],
        ]);
        $this->belongsTo('Users', ['foreignKey' => 'user_id']);

        // JSON auto-décodé en array
        $this->getSchema()->setColumnType('meta', 'json');
    }

    /** Scores d'un user groupés par jeu (pour la page profil). */
    public function findForUser(int $userId): array
    {
        return $this->find()
            ->where(['user_id' => $userId])
            ->orderBy(['created' => 'DESC'])
            ->all()
            ->groupBy('game_slug')
            ->toArray();
    }

    /** Stats agrégées par jeu (parties, victoires, meilleur score). */
    public function statsForUser(int $userId): array
    {
        $rows = $this->find()
            ->select([
                'game_slug',
                'played' => $this->find()->func()->count('*'),
                'won'    => $this->find()->func()->sum('won'),
                'best'   => $this->find()->func()->min('score'),
            ])
            ->where(['user_id' => $userId])
            ->groupBy('game_slug')
            ->disableHydration()
            ->toArray();

        $out = [];
        foreach ($rows as $r) $out[$r['game_slug']] = $r;
        return $out;
    }
}
