<?php
declare(strict_types=1);

namespace App\Controller;

use App\Game\GameRegistry;

class PagesController extends AppController
{
    public function home(): void
    {
        $this->set('games', GameRegistry::getInstance()->all());
    }
}
