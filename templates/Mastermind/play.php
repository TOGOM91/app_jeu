<?php
/** @var \App\View\AppView $this */
/** @var \App\Game\GameInterface $game */
/** @var array $state */
$this->assign('title', $game->getName());
?>
<section class="game-head">
    <div>
        <p class="game-head__kicker">// <?= h($game->getSlug()) ?></p>
        <h1 class="game-head__title"><?= h($game->getName()) ?></h1>
        <p class="game-head__desc"><?= h($game->getDescription()) ?></p>
    </div>
    <div class="game-head__controls">
        <button id="btn-new" class="btn">Nouvelle partie</button>
        <a href="/" class="btn btn--ghost">← Hub</a>
    </div>
</section>

<div id="mastermind"
     class="mastermind"
     data-state='<?= htmlspecialchars(json_encode($state), ENT_QUOTES) ?>'>
    <div class="mm-board" id="mm-board"></div>

    <aside class="mm-side">
        <div class="mm-current">
            <p class="mm-label">ta proposition</p>
            <div class="mm-slots" id="mm-slots"></div>
            <button id="btn-submit" class="btn btn--primary" disabled>Valider</button>
        </div>

        <div class="mm-palette">
            <p class="mm-label">palette</p>
            <div class="mm-colors" id="mm-colors">
                <?php foreach ($state['colors'] as $c): ?>
                    <button class="peg peg--<?= h($c) ?>" data-color="<?= h($c) ?>" aria-label="<?= h($c) ?>"></button>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="mm-rules">
            <p class="mm-label">règles</p>
            <ul>
                <li><span class="pip pip--black"></span> bonne couleur, bonne place</li>
                <li><span class="pip pip--white"></span> bonne couleur, mauvaise place</li>
                <li>10 essais max.</li>
            </ul>
        </div>
    </aside>
</div>

<dialog id="mm-modal" class="modal">
    <h2 id="mm-modal-title"></h2>
    <p id="mm-modal-msg"></p>
    <div id="mm-modal-secret" class="mm-secret"></div>
    <button id="mm-modal-close" class="btn btn--primary">Rejouer</button>
</dialog>

<?php $this->start('script'); ?>
<?= $this->Html->script('mastermind') ?>
<?php $this->end(); ?>
