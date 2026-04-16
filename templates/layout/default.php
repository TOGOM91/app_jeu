<?php
/** @var \App\View\AppView $this */
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $this->fetch('title') ?: 'Arcade' ?> — Arcade</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Major+Mono+Display&family=JetBrains+Mono:wght@400;600&family=Fraunces:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">

    <?= $this->Html->css('app') ?>
    <?= $this->fetch('css') ?>
</head>
<body>
    <div class="grain"></div>

    <header class="topbar">
        <a href="/" class="brand">
            <span class="brand__glyph">▲</span>
            <span class="brand__name">ARCADE</span>
            <span class="brand__sub">// salle de jeux</span>
        </a>
        <nav class="topbar__nav">
            <a href="/">hub</a>
            <?php $identity = $this->getRequest()->getAttribute('identity'); ?>
            <?php if ($identity): ?>
                <a href="/u/<?= h($identity->get('username')) ?>">@<?= h($identity->get('username')) ?></a>
                <a href="/logout">sortir</a>
            <?php else: ?>
                <a href="/login">connexion</a>
                <a href="/register" class="nav-cta">s'inscrire</a>
            <?php endif; ?>
        </nav>
    </header>

    <main class="main">
        <?= $this->Flash->render() ?>
        <?= $this->fetch('content') ?>
    </main>

    <footer class="footer">
        <span>CakePHP · <?= date('Y') ?></span>
        <span class="footer__dots">· · ·</span>
    </footer>

    <?= $this->fetch('script') ?>
</body>
</html>
