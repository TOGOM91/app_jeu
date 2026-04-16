<?php
/** @var \App\View\AppView $this */
/** @var \App\Model\Entity\GameRoom $room */
/** @var \App\Game\GameInterface $game */
/** @var int|null $myIndex */
$this->assign('title', $game->getName() . ' · ' . $room->code);
$state = $room->state;
?>
<section class="game-head">
    <div>
        <p class="game-head__kicker">// <?= h($game->getSlug()) ?> · en ligne</p>
        <h1 class="game-head__title"><?= h($game->getName()) ?></h1>
        <p class="game-head__desc">Salle <code class="room-code"><?= h($room->code) ?></code> — partage ce code.</p>
    </div>
    <div class="game-head__controls">
        <a href="/games/filler/rooms" class="btn btn--ghost">Lobby</a>
        <a href="/" class="btn btn--ghost">← Hub</a>
    </div>
</section>

<?php if ($myIndex === null): ?>
    <div class="room-join">
        <p>Rejoindre cette salle ?</p>
        <?= $this->Form->postLink(
            'Rejoindre',
            ['controller' => 'Rooms', 'action' => 'join', $room->code],
            ['class' => 'btn btn--primary']
        ) ?>
    </div>
<?php endif; ?>

<div id="filler"
     class="filler filler--online"
     data-mode="online"
     data-code="<?= h($room->code) ?>"
     data-me="<?= $myIndex === null ? '' : (int)$myIndex ?>"
     data-state='<?= htmlspecialchars(json_encode($state), ENT_QUOTES) ?>'
     data-players='<?= htmlspecialchars(json_encode($room->players), ENT_QUOTES) ?>'
     data-status="<?= h($room->status) ?>"
     data-version="<?= (int)$room->version ?>">

    <div class="fl-players">
        <div class="fl-player fl-player--0" data-player="0">
            <span class="fl-player__tag">J1</span>
            <span class="fl-player__name" id="fl-name-0"><?= h($room->players[0]['name'] ?? '—') ?></span>
            <span class="fl-player__score" id="fl-score-0">1</span>
        </div>
        <div class="fl-turn" id="fl-turn">en attente…</div>
        <div class="fl-player fl-player--1" data-player="1">
            <span class="fl-player__score" id="fl-score-1">1</span>
            <span class="fl-player__name" id="fl-name-1"><?= h($room->players[1]['name'] ?? '…') ?></span>
            <span class="fl-player__tag">J2</span>
        </div>
    </div>

    <div class="fl-board" id="fl-board"></div>

    <div class="fl-palette" id="fl-palette">
        <?php foreach ($state['colors'] as $c): ?>
            <button class="fl-swatch fl-c-<?= h($c) ?>" data-color="<?= h($c) ?>" aria-label="<?= h($c) ?>"></button>
        <?php endforeach; ?>
    </div>
</div>

<dialog id="fl-modal" class="modal">
    <h2 id="fl-modal-title"></h2>
    <p id="fl-modal-msg"></p>
    <a href="/games/filler/rooms" class="btn btn--primary">Retour au lobby</a>
</dialog>

<?php $this->start('script'); ?>
<?= $this->Html->script('filler') ?>
<?php $this->end(); ?>
