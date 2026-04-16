<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class UsersTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('users');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp');

        $this->hasMany('Scores', [
            'foreignKey' => 'user_id',
            'sort' => ['Scores.created' => 'DESC'],
        ]);
    }

    public function validationDefault(Validator $validator): Validator
    {
        return $validator
            ->notEmptyString('username', 'Le pseudo est requis.')
            ->minLength('username', 3, 'Minimum 3 caractères.')
            ->maxLength('username', 40)
            ->add('username', 'alphanum', [
                'rule' => ['custom', '/^[a-zA-Z0-9_\-]+$/'],
                'message' => 'Lettres, chiffres, - et _ uniquement.',
            ])
            ->notEmptyString('password', 'Le mot de passe est requis.')
            ->minLength('password', 6, 'Minimum 6 caractères.');
    }

    public function buildRules(\Cake\ORM\RulesChecker $rules): \Cake\ORM\RulesChecker
    {
        $rules->add($rules->isUnique(['username'], 'Pseudo déjà pris.'));
        return $rules;
    }
}
