<?php
/** @var \App\View\AppView $this */
/** @var \App\Model\Entity\GameRoom $room */
/** @var \App\Game\GameInterface $game */
/** @var int|null $myIndex */
$this->assign('title', $game->getName() . ' · ' . $room->code);
$state = $room->state;
$equips = \App\Game\Labyrinth\LabyrinthGame::EQUIPMENTS;
?>
<section class="game-head">
    <div>
        <p class="game-head__kicker">// <?= h($game->getSlug()) ?> · en ligne</p>
        <h1 class="game-head__title"><?= h($game->getName()) ?></h1>
        <p class="game-head__desc">Salle <code class="room-code"><?= h($room->code) ?></code></p>
    </div>
    <div class="game-head__controls">
        <a href="/games/labyrinth/rooms" class="btn btn--ghost">Lobby</a>
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

<div id="labyrinth"
     class="labyrinth"
     data-code="<?= h($room->code) ?>"
     data-me="<?= $myIndex === null ? '' : (int)$myIndex ?>"
     data-state='<?= htmlspecialchars(json_encode($state), ENT_QUOTES) ?>'
     data-players='<?= htmlspecialchars(json_encode($room->players), ENT_QUOTES) ?>'
     data-status="<?= h($room->status) ?>"
     data-version="<?= (int)$room->version ?>"
     data-equips='<?= htmlspecialchars(json_encode($equips), ENT_QUOTES) ?>'>

    <div class="lab-hud">
        <div class="lab-hud__player lab-hud__player--0">
            <span class="lab-hud__tag">J1</span>
            <span class="lab-hud__name" id="lab-name-0"><?= h($room->players[0]['name'] ?? '—') ?></span>
            <div class="lab-hud__stats">
                <span class="lab-hud__pa">
                    ⚡<span id="lab-pa-0">0</span><span class="lab-hud__pamax">/<?= \App\Game\Labyrinth\LabyrinthGame::MAX_PA ?></span>
                </span>
                <span class="lab-hud__hp">
                    ♥<span id="lab-hp-0">0</span>
                </span>
            </div>
            <span class="lab-hud__badge" id="lab-badge-0"></span>
            <div class="lab-hud__inv" id="lab-inv-0"></div>
        </div>
        <div class="lab-hud__sep"></div>
        <div class="lab-hud__player lab-hud__player--1">
            <span class="lab-hud__badge" id="lab-badge-1"></span>
            <div class="lab-hud__stats">
                <span class="lab-hud__pa">
                    ⚡<span id="lab-pa-1">0</span><span class="lab-hud__pamax">/<?= \App\Game\Labyrinth\LabyrinthGame::MAX_PA ?></span>
                </span>
                <span class="lab-hud__hp">
                    ♥<span id="lab-hp-1">0</span>
                </span>
            </div>
            <span class="lab-hud__name" id="lab-name-1"><?= h($room->players[1]['name'] ?? '…') ?></span>
            <span class="lab-hud__tag">J2</span>
            <div class="lab-hud__inv" id="lab-inv-1"></div>
        </div>
    </div>

    <div class="lab-board-wrap">
        <div class="lab-board" id="lab-board"></div>
    </div>

    <div class="lab-controls">
        <div class="lab-dpad">
            <button class="lab-dbtn" data-dir="up">↑</button>
            <div class="lab-dpad__mid">
                <button class="lab-dbtn" data-dir="left">←</button>
                <button class="lab-dbtn lab-dbtn--center" disabled>⌬</button>
                <button class="lab-dbtn" data-dir="right">→</button>
            </div>
            <button class="lab-dbtn" data-dir="down">↓</button>
        </div>

        <div class="lab-inventory">
            <p class="mm-label">inventaire (2 max)</p>
            <div class="lab-slots" id="lab-my-slots"></div>
            <p class="lab-legend">Clic = utiliser · Shift+clic = lâcher</p>
        </div>

        <div class="lab-log" id="lab-log"></div>
    </div>
</div>

<dialog id="lab-modal" class="modal">
    <h2 id="lab-modal-title"></h2>
    <p id="lab-modal-msg"></p>
    <a href="/games/labyrinth/rooms" class="btn btn--primary">Retour au lobby</a>
</dialog>

<?php $this->start('script'); ?>
<?= $this->Html->script('labyrinth') ?>
<?php $this->end(); ?>
