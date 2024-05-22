<?php
/**
 * Configuration for Cache Invalidator
 *
 * This configuration file is used to define the triggers for initiating cache invalidation
 * across various sections of a WordPress site. Each trigger is linked to specific events
 * and targets, determining when and where cache should be invalidated.
 *
 * Targets Explained:
 * - 'template': Refers to specific WordPress template files. Use this type to specify
 *               the template file that should have its cache invalidated when the trigger conditions are met.
 *               Example: 'template-jobs.php' for job listing template pages.
 *
 * - 'gutenberg': Targets specific Gutenberg blocks within the site. This type is used
 *                to indicate that any page containing a specified Gutenberg block should
 *                have its cache invalidated.
 *                Example: 'core/categories' to target pages using the Categories block.
 *
 * - 'home': A special target type used to specify the site's home page. This target
 *           does not require a 'value' field since the home page is uniquely identified by its nature.
 *           Use this type to clear the cache of the home page specifically.
 *
 * - 'layout': Used to invalidate the entire cache, typically for elements that appear across
 *             the whole site such as footers or headers. Use this target type when changes
 *             to a layout element require the entire site cache to be cleared.
 *             This target does not require a 'value' field since it applies site-wide.
 *
 * Structure:
 * - 'postType' (array): Triggers related to specific post type events.
 *        - Each item is an array with the following structure:
 *            - 'type' (string): The post type name (e.g., 'post', 'page').
 *            - 'timeFields' (array, optional): Specific fields names in the post type that hold the date.
 *            - 'targets' (array): Lists targets where cache needs to be invalidated.
 *                - 'type' (string): Type of the target ('template', 'gutenberg', 'home', 'layout').
 *                - 'value' (string, optional): Identifier for the target, such as the template file name or block name.
 *
 * - 'taxonomy' (array): Triggers related to taxonomy events like category or tag updates.
 *        - Each item is an array with the following structure:
 *            - 'type' (string): The taxonomy name (e.g., 'category', 'tag').
 *            - 'targets' (array): Lists targets where cache needs to be invalidated.
 *                - 'type' (string): Type of the target ('template', 'gutenberg', 'home', 'layout').
 *                - 'value' (string, optional): Identifier for the target, such as the template file name or block name.
 *
 * Usage:
 * To utilize this configuration, ensure that the CacheInvalidationManager is properly initialized
 * with this config array. The manager will set up the necessary WordPress hooks based on the
 * defined triggers and manage the cache invalidation logic as configured.
 *
 * Example:
 * For an 'event' custom post type with a 'start_date' and 'end_date' field, to invalidate the cache of the
 * 'template-events.php' and home page whenever an event's start date is today or has passed, configure a
 * 'postType' trigger with 'postType' as 'event', 'fieldName' as 'start_date' and 'end_date',
 * a 'template' target with 'value' as 'template-events.php' and a home target.
 * Here is the PHP code example of how you would set this up in the configuration:
 *
 * return [
 *     'postType' => [
 *         [
 *             'type' => 'event',
 *             'timeFields' => ['start_date', 'end_date'],
 *             'targets' => [
 *                 ['type' => 'template', 'value' => 'template-events.php'],
 *                 ['type' => 'gutenberg', 'value' => 'ix/block-event'],
 *                 ['type' => 'home'],
 *                 ['type' => 'layout']
 *             ]
 *         ]
 *     ],
 *     'taxonomy' => [
 *         [
 *             'type' => 'category',
 *             'targets' => [
 *                 ['type' => 'template', 'value' => 'template-category.php'],
 *                 ['type' => 'gutenberg', 'value' => 'core/categories'],
 *                 ['type' => 'home'],
 *                 ['type' => 'layout']
 *             ]
 *         ]
 *     ]
 * ];
 */

 return [];
