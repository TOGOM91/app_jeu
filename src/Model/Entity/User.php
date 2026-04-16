<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Authentication\PasswordHasher\DefaultPasswordHasher;
use Cake\ORM\Entity;

class User extends Entity
{
    protected array $_accessible = [
        'username' => true,
        'password' => true,
        'created'  => true,
        'modified' => true,
        'scores'   => true,
    ];

    protected array $_hidden = ['password'];

    protected function _setPassword(string $password): ?string
    {
        if (strlen($password) === 0) return null;
        return (new DefaultPasswordHasher())->hash($password);
    }
}
