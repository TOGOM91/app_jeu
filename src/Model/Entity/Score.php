<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class Score extends Entity
{
    protected array $_accessible = [
        'user_id'   => true,
        'game_slug' => true,
        'score'     => true,
        'won'       => true,
        'meta'      => true,
        'created'   => true,
        'user'      => true,
    ];
}
