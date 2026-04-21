# DE WordPress Plugin Utils

Reusable WordPress plugin utilities including upgrader and logger abstracts.

## Installation

```bash
composer require dream-encode/de-wordpress-plugin-utils
```

## Requirements

- PHP >= 8.2
- WordPress
- WooCommerce/Action Scheduler (via `woocommerce/action-scheduler`)

## Provided Abstracts

### Abstract_Plugin_Upgrader
Handles plugin upgrade/install logic with cache-clearing for Redis/object cache environments. Integrates with Action Scheduler for deferred tasks.

### Abstract_REST_API
Base class for registering a REST API namespace and routes.

### Abstract_REST_Controller
Extends `WP_REST_Controller` with a reusable pattern for REST endpoint controllers.

### Abstract_WC_Logger
Logs data through WooCommerce's `WC_Logger`, falling back to `error_log` when WooCommerce is unavailable.

## Development

```bash
# Lint code
composer run lint

# Auto-format code
composer run format

# Static analysis
composer run analyze

# Run tests
composer run test
```

## License

GPL-3.0-or-later
