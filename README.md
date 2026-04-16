# Arcade — Plateforme de jeux CakePHP

Petite plateforme de jeux en CakePHP 5. Un seul jeu pour l'instant (**Mastermind**),
mais l'architecture est pensée pour en ajouter d'autres en quelques minutes.

Les joueurs ont un compte, et **chaque profil public affiche ses scores** —
stats agrégées par jeu + historique complet des parties.

## Installation

```bash
# 1. Squelette CakePHP (si projet vide)
composer create-project --prefer-dist cakephp/app:~5.0 .

# 2. Copier les fichiers de ce repo par-dessus
composer install
composer require cakephp/authentication

# 3. Créer la base et importer le schéma
mysql -u root -p -e "CREATE DATABASE arcade CHARACTER SET utf8mb4;"
mysql -u root -p arcade < config/sql/schema.sql

# 4. Configurer config/app_local.php (Datasources.default)

# 5. Lancer
bin/cake server
# -> http://localhost:8765
```

## Structure

```
src/
├── Application.php              # middleware Authentication
├── Controller/
│   ├── AppController.php
│   ├── PagesController.php      # hub
│   ├── GamesController.php      # contrôleur générique pour TOUS les jeux
│   └── UsersController.php      # login / register / logout / profil
├── Game/
│   ├── GameInterface.php        # contrat de tout nouveau jeu
│   ├── GameRegistry.php         # liste centrale des jeux
│   └── Mastermind/
│       └── MastermindGame.php   # logique pure du Mastermind
└── Model/
    ├── Entity/{User,Score}.php
    └── Table/{UsersTable,ScoresTable}.php

templates/
├── layout/default.php
├── Pages/home.php
├── Users/{login,register,view}.php
└── Mastermind/play.php

config/
├── routes.php
└── sql/schema.sql               # users + scores

webroot/
├── css/app.css
└── js/mastermind.js
```

## Routes

| Méthode | URL                        | Rôle                        |
|---------|----------------------------|-----------------------------|
| GET     | `/`                        | Hub des jeux                |
| GET     | `/login`                   | Connexion                   |
| GET     | `/register`                | Inscription                 |
| GET     | `/logout`                  | Déconnexion                 |
| GET     | `/u/{username}`            | **Profil public + scores**  |
| GET     | `/games/{slug}`            | Lance/affiche le jeu        |
| POST    | `/games/{slug}/new`        | Démarre une nouvelle partie |
| POST    | `/games/{slug}/move`       | Action (JSON)               |

## Comment ajouter un nouveau jeu

**Trois étapes** — aucune route à toucher, les scores sont persistés
automatiquement pour tous les jeux.

### 1. Crée la classe du jeu

`src/Game/Sudoku/SudokuGame.php` — implémente `GameInterface`.

### 2. Enregistre-le dans le registry

`src/Game/GameRegistry.php`, dans le constructeur :

```php
$this->register(new MastermindGame());
$this->register(new SudokuGame());   // <-- une seule ligne
```

### 3. Crée le template

`templates/Sudoku/play.php` — utilise les variables `$game` et `$state`
injectées par `GamesController`.

**C'est tout.** Le jeu :
- apparaît sur le hub,
- est accessible à `/games/sudoku`,
- expose `/games/sudoku/new` + `/games/sudoku/move`,
- **ses scores s'enregistrent automatiquement** dans la table `scores`
  (table générique avec `game_slug`) dès qu'un joueur connecté termine
  une partie,
- **apparaît sur les pages profil** dans les stats et l'historique.

## Philosophie

- **Logique pure séparée du framework** : `MastermindGame` ne dépend pas de
  CakePHP, donc testable en isolation (algo de scoring vérifié sur cas
  vicieux : doublons dans le code et dans la proposition).
- **Table `scores` générique** : une seule table couvre tous les jeux passés
  et futurs, grâce à `game_slug` + un champ `meta` JSON libre.
- **Secret jamais envoyé au client** tant que la partie n'est pas finie
  (`GamesController::sanitize`).
- **Anonyme OK** : on peut jouer sans compte, mais rien n'est persisté tant
  qu'on n'est pas connecté. Pas de friction, scores en option.
