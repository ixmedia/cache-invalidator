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


## Versions and updates

WordPress automatically offers plugin updates in **Plugins**, based on the GitHub Releases of [ixmedia/cache-invalidator](https://github.com/ixmedia/cache-invalidator). This feature uses the [Plugin Update Checker](https://github.com/YahnisElsts/plugin-update-checker) library, included in `lib/`.

**The plugin version is the git tag.** Do not edit the `Version:` header in `init.php` by hand. It is updated automatically after each tag, both on `main` and in the Release zip.

### Creating a new version

1. Commit and push your changes to `main`:

   ```bash
   git push origin main
   ```

2. Choose the version number following [SemVer](https://semver.org/) (`MAJOR.MINOR.PATCH`):
   - `1.0.1`: bug fix
   - `1.1.0`: backward-compatible new feature
   - `2.0.0`: breaking change

   To see the latest tag: `git tag --sort=-v:refname | head -1`

3. Create the tag **on GitLab**, then push it:

   ```bash
   git tag 1.1.0
   git push origin 1.1.0
   ```

   You can also create it in the GitLab interface (**Code › Tags › New tag**). Do not create the tag on GitHub: the GitLab job that updates `main` would not run.

4. Pull the version commit created by GitLab CI:

   ```bash
   git pull
   ```

   A "Bump version to 1.1.0" commit appears on `main`, and `init.php` contains `Version: 1.1.0`.

5. Check that the Release was created: in the [Actions](https://github.com/ixmedia/cache-invalidator/actions) tab on GitHub, the **Release** workflow should be green. Then, in [Releases](https://github.com/ixmedia/cache-invalidator/releases), the version should appear with the `cache-invalidator.zip` file.

That's it. Two automations take care of the rest:

- **GitLab CI** (`.gitlab-ci.yml`) writes the tag number into the `Version:` header of `init.php`, then pushes that commit to `main`.
- **GitHub Actions** (`.github/workflows/release.yml`) builds the `cache-invalidator.zip` file with the correct version, then creates the GitHub Release with automatically generated release notes.

### Initial setup (one time only)

The GitLab job pushes to `main` using the token GitLab CI provides automatically (`CI_JOB_TOKEN`). You only need to allow it to push:

1. In GitLab, go to **Settings › CI/CD › Job token permissions** and check **Allow Git push requests to the repository**.
2. The job pushes with the permissions of the person who created the tag. That person must therefore be allowed to push to `main` (see **Settings › Repository › Protected branches** if `main` is protected).

A GitLab runner must also be available for this project.

### Updating a site

- Sites check for updates every 12 hours.
- To force a check, go to **Plugins** and click **Check for updates** under *Cache Invalidator*.
- When the new version appears, click **Update now**, as with any other plugin.

### Good to know

- The tag can be written as `1.1.0` or `v1.1.0`.
- A version number that has already been used cannot be reused. If a Release fails, create the next tag (for example `1.1.1`).
- If the GitLab `bump-version` job fails because someone pushed to `main` at the same time, simply re-run it.
- If the GitHub workflow does not start after a tag created on GitLab, make sure the GitLab → GitHub mirror uses a GitHub personal access token (PAT).
- The tag points to the commit before the "Bump version" commit. The plugin accounts for this: it relies on the tag number to detect updates.
- In local development, the plugin is a git clone. Do not use the WordPress update button locally: it would replace the folder and delete the clone. Run `git pull` instead.
