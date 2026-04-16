<?php
/** @var \App\View\AppView $this */
/** @var \App\Model\Entity\User $user */
$this->assign('title', 'Inscription');
?>
<section class="auth">
    <p class="hero__kicker">— nouveau</p>
    <h1 class="auth__title">Créer un<br><em>compte</em>.</h1>

    <?= $this->Form->create($user, ['class' => 'auth__form']) ?>
        <label>
            <span>pseudo</span>
            <?= $this->Form->control('username', ['label' => false, 'required' => true, 'autofocus' => true]) ?>
        </label>
        <label>
            <span>mot de passe</span>
            <?= $this->Form->control('password', ['label' => false, 'required' => true]) ?>
        </label>
        <?= $this->Form->button('S\'inscrire', ['class' => 'btn btn--primary']) ?>
    <?= $this->Form->end() ?>

    <p class="auth__foot">
        Déjà un compte ? <a href="/login">Se connecter</a>
    </p>
</section>
