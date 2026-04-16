<?php
/** @var \App\View\AppView $this */
/** @var \App\Model\Entity\User $profile */
/** @var array $scoresByGame */
/** @var array $stats */
/** @var \App\Game\GameRegistry $registry */
$this->assign('title', '@' . $profile->username);
?>
<section class="profile">
    <div class="profile__head">
        <p class="hero__kicker">— profil</p>
        <h1 class="profile__name">@<?= h($profile->username) ?></h1>
        <p class="profile__joined">
            Membre depuis <?= $profile->created->i18nFormat('d MMMM yyyy', null, 'fr-FR') ?>
        </p>
    </div>

    <div class="profile__stats">
        <?php if (empty($stats)): ?>
            <p class="profile__empty">Aucune partie jouée pour l'instant.</p>
        <?php else: ?>
            <?php foreach ($stats as $slug => $s):
                if (!$registry->has($slug)) continue;
                $g = $registry->get($slug);
                $winRate = $s['played'] > 0 ? round(($s['won'] / $s['played']) * 100) : 0;
            ?>
                <article class="stat-card">
                    <div class="stat-card__head">
                        <span class="stat-card__icon"><?= h($g->getIcon()) ?></span>
                        <div>
                            <h2><?= h($g->getName()) ?></h2>
                            <a href="/games/<?= h($slug) ?>" class="stat-card__link">jouer ↗</a>
                        </div>
                    </div>
                    <dl class="stat-card__grid">
                        <div>
                            <dt>parties</dt>
                            <dd><?= (int)$s['played'] ?></dd>
                        </div>
                        <div>
                            <dt>victoires</dt>
                            <dd><?= (int)$s['won'] ?></dd>
                        </div>
                        <div>
                            <dt>taux</dt>
                            <dd><?= $winRate ?><span class="unit">%</span></dd>
                        </div>
                        <div>
                            <dt>meilleur</dt>
                            <dd><?= (int)$s['best'] ?><span class="unit">coups</span></dd>
                        </div>
                    </dl>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <?php if (!empty($scoresByGame)): ?>
        <div class="profile__history">
            <h2 class="profile__section-title">Historique</h2>
            <?php foreach ($scoresByGame as $slug => $entries):
                if (!$registry->has($slug)) continue;
                $g = $registry->get($slug);
            ?>
                <section class="history-block">
                    <h3><?= h($g->getIcon()) ?> <?= h($g->getName()) ?></h3>
                    <table class="history">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Résultat</th>
                                <th>Coups</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($entries as $entry): ?>
                                <tr>
                                    <td><?= $entry->created->i18nFormat('d MMM y · HH:mm', null, 'fr-FR') ?></td>
                                    <td>
                                        <?php if ($entry->won): ?>
                                            <span class="tag tag--win">Gagné</span>
                                        <?php else: ?>
                                            <span class="tag tag--lose">Perdu</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= (int)$entry->score ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </section>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
