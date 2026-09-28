# Cache Invalidator Plugin for WordPress

## Description
The Cache Invalidator plugin is designed to manage cache invalidation efficiently across various sections of a WordPress site. It is configured through a PHP configuration file where specific triggers are defined to determine when and where the cache should be invalidated. This ensures that users always see the most up-to-date content.

## Installation
1. Download the plugin ZIP file.
2. Go to your WordPress dashboard.
3. Navigate to Plugins > Add New > Upload Plugin.
4. Select the downloaded ZIP file and click "Install Now".
5. Once installed, click "Activate Plugin".

## Configuration
The plugin uses a configuration file to set up cache invalidation triggers. Here is an example configuration file (`config.php`) and a detailed explanation of each section:

### Configuration File Structure

```php
<?php
/**
 * Configuration for Cache Invalidator
 *
 * This configuration file is used to define the triggers for initiating cache invalidation
 * across various sections of a WordPress site. Each trigger is linked to specific events
 * and targets, determining when and where cache should be invalidated.
 */

return [
    'postType' => [
        [
            'type' => 'event',
            'timeFields' => ['start_date', 'end_date'],
            'targets' => [
                ['type' => 'template', 'value' => 'template-events.php'],
                ['type' => 'gutenberg', 'value' => 'ix/block-event'],
                ['type' => 'home'],
                ['type' => 'layout']
            ]
        ]
    ],
    'taxonomy' => [
        [
            'type' => 'category',
            'targets' => [
                ['type' => 'template', 'value' => 'template-category.php'],
                ['type' => 'gutenberg', 'value' => 'core/categories'],
                ['type' => 'home'],
                ['type' => 'layout']
            ]
        ]
    ]
];
```

### Targets Explained

- **template**: Refers to specific WordPress template files. Use this type to specify the template file that should have its cache invalidated when the trigger conditions are met.
  **Example**: `template-jobs.php` for job listing template pages.

- **gutenberg**: Targets specific Gutenberg blocks within the site. This type is used to indicate that any page containing a specified Gutenberg block should have its cache invalidated.
  **Example**: `core/categories` to target pages using the Categories block.

- **home**: A special target type used to specify the site's home page. This target does not require a 'value' field since the home page is uniquely identified by its nature. Use this type to clear the cache of the home page specifically.

- **layout**: Used to invalidate the entire cache, typically for elements that appear across the whole site such as footers or headers. Use this target type when changes to a layout element require the entire site cache to be cleared. This target does not require a 'value' field since it applies site-wide.

### Structure

- **postType** (array): Triggers related to specific post type events.
  - Each item is an array with the following structure:
    - **type** (string): The post type name (e.g., 'post', 'page').
    - **timeFields** (array, optional): Specific field names in the post type that hold the date.
    - **targets** (array): Lists targets where cache needs to be invalidated.
      - **type** (string): Type of the target ('template', 'gutenberg', 'home', 'layout').
      - **value** (string, optional): Identifier for the target, such as the template file name or block name.

- **taxonomy** (array): Triggers related to taxonomy events like category or tag updates.
  - Each item is an array with the following structure:
    - **type** (string): The taxonomy name (e.g., 'category', 'tag').
    - **targets** (array): Lists targets where cache needs to be invalidated.
      - **type** (string): Type of the target ('template', 'gutenberg', 'home', 'layout').
      - **value** (string, optional): Identifier for the target, such as the template file name or block name.

### Usage
To utilize this configuration, ensure that the `CacheInvalidationManager` is properly initialized with this config array. The manager will set up the necessary WordPress hooks based on the defined triggers and manage the cache invalidation logic as configured.

### Example
For an 'event' custom post type with a 'start_date' and 'end_date' field, to invalidate the cache of the `template-events.php` and home page whenever an event's start date is today or has passed, configure a `postType` trigger with `postType` as 'event', `fieldName` as 'start_date' and 'end_date', a `template` target with `value` as `template-events.php` and a home target. Here is the PHP code example of how you would set this up in the configuration:

```php
return [
    'postType' => [
        [
            'type' => 'event',
            'timeFields' => ['start_date', 'end_date'],
            'targets' => [
                ['type' => 'template', 'value' => 'template-events.php'],
                ['type' => 'home'],
            ]
        ]
    ],
];
```


## Versions et mises à jour

WordPress propose automatiquement les mises à jour du plugin dans **Extensions** à partir des Releases GitHub de [ixmedia/cache-invalidator](https://github.com/ixmedia/cache-invalidator). Cette fonctionnalité utilise la librairie [Plugin Update Checker](https://github.com/YahnisElsts/plugin-update-checker), incluse dans `lib/`.

**La version du plugin, c'est le tag git.** Ne modifiez pas l'en-tête `Version:` de `init.php` à la main. Il est mis à jour automatiquement après chaque tag, dans `main` et dans le zip de la Release.

### Créer une nouvelle version

1. Commitez et poussez vos changements sur `main` :

   ```bash
   git push origin main
   ```

2. Choisissez le numéro de version en suivant [SemVer](https://semver.org/lang/fr/) (`MAJEUR.MINEUR.CORRECTIF`) :
   - `1.0.1` : correction de bug
   - `1.1.0` : nouvelle fonctionnalité compatible
   - `2.0.0` : changement incompatible

   Pour voir le dernier tag : `git tag --sort=-v:refname | head -1`

3. Créez le tag **sur GitLab**, puis poussez-le :

   ```bash
   git tag 1.1.0
   git push origin 1.1.0
   ```

   Vous pouvez aussi le créer dans l'interface GitLab (**Code › Tags › New tag**). Ne créez pas le tag sur GitHub : le job GitLab qui met à jour `main` ne se lancerait pas.

4. Récupérez le commit de version créé par GitLab CI :

   ```bash
   git pull
   ```

   Un commit « Bump version to 1.1.0 » apparaît sur `main`, et `init.php` contient `Version: 1.1.0`.

5. Vérifiez que la Release a été créée : dans l'onglet [Actions](https://github.com/ixmedia/cache-invalidator/actions) sur GitHub, le workflow **Release** doit être vert. Ensuite, dans [Releases](https://github.com/ixmedia/cache-invalidator/releases), la version doit apparaître avec le fichier `cache-invalidator.zip`.

C'est tout. Deux automatisations s'occupent du reste :

- **GitLab CI** (`.gitlab-ci.yml`) écrit le numéro du tag dans l'en-tête `Version:` de `init.php`, puis pousse ce commit sur `main`.
- **GitHub Actions** (`.github/workflows/release.yml`) crée le fichier `cache-invalidator.zip` avec la bonne version, puis crée la Release GitHub avec des notes de version générées automatiquement.

### Configuration initiale (une seule fois)

Le job GitLab pousse sur `main` avec le token fourni automatiquement par GitLab CI (`CI_JOB_TOKEN`). Il faut seulement l'autoriser à pousser :

1. Dans GitLab, allez dans **Settings › CI/CD › Job token permissions** et cochez **Allow Git push requests to the repository**.
2. Le job pousse avec les droits de la personne qui a créé le tag. Cette personne doit donc pouvoir pousser sur `main` (voir **Settings › Repository › Protected branches** si `main` est protégée).

Il faut aussi qu'un runner GitLab soit disponible pour ce projet.

### Mettre à jour un site

- Les sites vérifient les mises à jour toutes les 12 heures.
- Pour forcer une vérification, allez dans **Extensions** et cliquez sur **Vérifier les mises à jour** sous *Cache Invalidator*.
- Quand la nouvelle version apparaît, cliquez sur **Mettre à jour maintenant**, comme pour n'importe quelle extension.

### Bon à savoir

- Le tag peut s'écrire `1.1.0` ou `v1.1.0`.
- Un numéro de version déjà utilisé ne peut pas être réutilisé. Si une Release est ratée, créez le tag suivant (par exemple `1.1.1`).
- Si le job GitLab `bump-version` échoue parce que quelqu'un a poussé sur `main` au même moment, relancez-le simplement.
- Si le workflow GitHub ne se lance pas après un tag créé sur GitLab, vérifiez que le miroir GitLab → GitHub utilise un token personnel (PAT) GitHub.
- Le tag pointe sur le commit d'avant le « Bump version ». Le plugin en tient compte : il se fie au numéro du tag pour détecter les mises à jour.
- En développement local, le plugin est un clone git. N'utilisez pas le bouton de mise à jour de WordPress en local : il remplacerait le dossier et supprimerait le clone. Faites un `git pull` à la place.
