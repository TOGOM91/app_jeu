<?php
/** @var \App\View\AppView $this */
$this->assign('title', 'Connexion');
?>
<section class="auth">
    <p class="hero__kicker">— accès</p>
    <h1 class="auth__title">Connexion<em>.</em></h1>

    <?= $this->Form->create(null, ['class' => 'auth__form']) ?>
        <label>
            <span>pseudo</span>
            <?= $this->Form->control('username', ['label' => false, 'required' => true, 'autofocus' => true]) ?>
        </label>
        <label>
            <span>mot de passe</span>
            <?= $this->Form->control('password', ['label' => false, 'required' => true]) ?>
        </label>
        <?= $this->Form->button('Entrer', ['class' => 'btn btn--primary']) ?>
    <?= $this->Form->end() ?>

    <p class="auth__foot">
        Pas de compte ? <a href="/register">Créer un compte</a>
    </p>
</section>
