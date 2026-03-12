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
