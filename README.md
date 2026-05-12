# OWC Activity Log

Tracks all WordPress activity such as posts, meta, options, users, taxonomy, comments, plugins, themes and more.

## Requirements

- PHP 8.1 or higher
- WordPress 6.7 or higher

## Installation

### Manual installation

1. Upload the 'owc-activity-log' folder in to the `/wp-content/plugins/` directory.
2. `cd /wp-content/plugins/owc-activity-log`
3. Run composer install, NPM asset build is in version control already.
4. Activate the plugin in via the WordPress admin.

### Composer installation

1. `composer source git@github.com:OpenWebconcept/plugin-owc-activity-log.git`
2. `composer require plugin/owc-activity-log`
3. `cd /wp-content/plugins/owc-activity-log`

## Hooks

### Admin overview page access

By default, the overview page inside the WordPress admin of this plugin is only accessible to administrators.

Some projects may require users with different roles or capabilities to access this page as well.  
This filter allows you to customize the required capability.

```php
add_filter('owc_activity_log_admin_page_overview_cap', function ($cap) {
    return 'superuser'; // Or any other capability.
});
```

## Development

### Install dependencies

```bash
composer install
```

### Run tests

```bash
composer test
```

### Code style

```bash
composer phpcs
composer phpcbf
```

### Prefix vendor dependencies

```bash
composer prefix-dependencies
```
