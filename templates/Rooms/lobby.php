<?php
/** @var \App\View\AppView $this */
/** @var \App\Game\GameInterface $game */
/** @var array $rooms */
$this->assign('title', $game->getName() . ' · lobby');
$identity = $this->getRequest()->getAttribute('identity');
?>
<section class="game-head">
    <div>
        <p class="game-head__kicker">// <?= h($game->getSlug()) ?> · lobby</p>
        <h1 class="game-head__title">Salles <em><?= h($game->getName()) ?></em></h1>
        <p class="game-head__desc">Crée une salle ou rejoins-en une existante.</p>
    </div>
    <div class="game-head__controls">
        <a href="/games/<?= h($game->getSlug()) ?>" class="btn btn--ghost">Jouer local</a>
        <a href="/" class="btn btn--ghost">← Hub</a>
    </div>
</section>

<div class="lobby">
    <div class="lobby__create">
        <?php if ($identity): ?>
            <?= $this->Form->postLink(
                '+ Créer une salle',
                ['controller' => 'Rooms', 'action' => 'create', $game->getSlug()],
                ['class' => 'btn btn--primary']
            ) ?>
        <?php else: ?>
            <a href="/login" class="btn btn--primary">Connexion pour créer</a>
        <?php endif; ?>

        <form class="lobby__code" onsubmit="event.preventDefault(); const c=this.code.value.trim().toUpperCase(); if(c) location.href='/rooms/'+c;">
            <input type="text" name="code" placeholder="CODE" maxlength="8">
            <button class="btn" type="submit">Rejoindre</button>
        </form>
    </div>

    <div class="lobby__list">
        <p class="mm-label">salles ouvertes</p>
        <?php if (empty($rooms)): ?>
            <p class="lobby__empty">Aucune salle ouverte. Crée la première.</p>
        <?php else: ?>
            <ul class="room-list">
                <?php foreach ($rooms as $r): ?>
                    <li>
                        <a href="/rooms/<?= h($r->code) ?>" class="room-list__item">
                            <span class="room-list__code"><?= h($r->code) ?></span>
                            <span class="room-list__host">
                                <?= h($r->players[0]['name'] ?? '…') ?>
                            </span>
                            <span class="room-list__count">
                                <?= count($r->players) ?>/<?= (int)$game->maxPlayers() ?>
                            </span>
                            <span class="room-list__arrow">↗</span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>
