<?php
/** @var \App\View\AppView $this */
/** @var \App\Game\GameInterface $game */
$this->assign('title', $game->getName());
?>
<section class="hero">
    <p class="hero__kicker">— <?= h($game->getSlug()) ?></p>
    <h1 class="hero__title"><?= h($game->getName()) ?><em>.</em></h1>
    <p class="hero__lead">
        Le Labyrinthe se joue <strong>uniquement en ligne</strong>, à deux joueurs.
        Crée une salle ou rejoins-en une existante dans le lobby.
    </p>
    <div style="display:flex; gap:12px; margin-top:32px;">
        <a href="/games/labyrinth/rooms" class="btn btn--primary">Ouvrir le lobby ↗</a>
        <a href="/" class="btn btn--ghost">← Hub</a>
    </div>
</section>
