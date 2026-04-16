<?php
/** @var \App\View\AppView $this */
/** @var array<string, \App\Game\GameInterface> $games */
?>
<section class="hero">
    <p class="hero__kicker">— bienvenue</p>
    <h1 class="hero__title">
        Une salle<br>
        <em>d'arcade</em><br>
        minimaliste.
    </h1>
    <p class="hero__lead">
        Choisis une machine. D'autres jeux arrivent bientôt.
    </p>
</section>

<section class="games-grid">
    <?php foreach ($games as $g): ?>
        <div class="game-card-wrap">
            <a href="/games/<?= h($g->getSlug()) ?>" class="game-card">
                <div class="game-card__icon"><?= h($g->getIcon()) ?></div>
                <div class="game-card__body">
                    <h2><?= h($g->getName()) ?></h2>
                    <p><?= h($g->getDescription()) ?></p>
                </div>
                <div class="game-card__arrow">↗</div>
            </a>
            <?php if ($g->isMultiplayer()): ?>
                <a href="/games/<?= h($g->getSlug()) ?>/rooms" class="game-card__multi">
                    ◎ jouer en ligne
                </a>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>

    <div class="game-card game-card--ghost">
        <div class="game-card__icon">+</div>
        <div class="game-card__body">
            <h2>Bientôt</h2>
            <p>Nouvelle cabine en cours d'assemblage.</p>
        </div>
    </div>
</section>
