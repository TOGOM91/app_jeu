<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class GameRoom extends Entity
{
    protected array $_accessible = [
        'code'      => true,
        'game_slug' => true,
        'host_id'   => true,
        'state'     => true,
        'players'   => true,
        'status'    => true,
        'version'   => true,
        'created'   => true,
        'modified'  => true,
    ];
}
