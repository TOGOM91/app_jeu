<?php
/** @var \App\View\AppView $this */
/** @var \App\Game\GameInterface $game */
/** @var array $state */
$this->assign('title', $game->getName());
?>
<section class="game-head">
    <div>
        <p class="game-head__kicker">// <?= h($game->getSlug()) ?> · local</p>
        <h1 class="game-head__title"><?= h($game->getName()) ?></h1>
        <p class="game-head__desc"><?= h($game->getDescription()) ?></p>
    </div>
    <div class="game-head__controls">
        <button id="btn-new" class="btn">Nouvelle partie</button>
        <a href="/games/filler/rooms" class="btn btn--ghost">En ligne ↗</a>
        <a href="/" class="btn btn--ghost">← Hub</a>
    </div>
</section>

<div id="filler"
     class="filler filler--local"
     data-mode="local"
     data-state='<?= htmlspecialchars(json_encode($state), ENT_QUOTES) ?>'>

    <div class="fl-players">
        <div class="fl-player fl-player--0" data-player="0">
            <span class="fl-player__tag">J1</span>
            <span class="fl-player__name">Joueur 1</span>
            <span class="fl-player__score" id="fl-score-0">1</span>
        </div>
        <div class="fl-turn" id="fl-turn">au tour de J1</div>
        <div class="fl-player fl-player--1" data-player="1">
            <span class="fl-player__score" id="fl-score-1">1</span>
            <span class="fl-player__name">Joueur 2</span>
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
    <button id="fl-modal-close" class="btn btn--primary">Rejouer</button>
</dialog>

<?php $this->start('script'); ?>
<?= $this->Html->script('filler') ?>
<?php $this->end(); ?>
