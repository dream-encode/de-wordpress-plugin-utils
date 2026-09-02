# DE WordPress Plugin Utils

Reusable WordPress plugin utilities including abstracts for plugin bootstrap, lifecycle, settings, REST API, logging, background processing, and data migrations.

## Installation

```bash
composer require dream-encode/de-wordpress-plugin-utils
```

## Requirements

- PHP >= 8.2
- WordPress
- WooCommerce/Action Scheduler (via `woocommerce/action-scheduler`)

## Provided Abstracts

### Abstract_Plugin
`Dream_Encode\WordPress_Plugin_Utils\Abstracts\Abstract_Plugin`

Main plugin bootstrap template. Constructs the loader, loads dependencies, sets locale, and wires admin/public hooks.

**Must implement:**
- `get_plugin_slug(): string` — returns the plugin slug used as `$plugin_name`.
- `get_version_constant(): string` — returns the PHP constant name that holds the plugin version.

**Optional overrides:**
- `get_default_version(): string` — fallback version string when the constant is not yet defined (default `'1.0.0'`).
- `create_loader(): Plugin_Loader` — returns the loader instance; defaults to `new Plugin_Loader()`.
- `load_dependencies(): void` — require additional files/classes.
- `set_locale(): void` — instantiate and register the i18n loader.
- `define_admin_hooks(): void` — register wp-admin actions and filters.
- `define_public_hooks(): void` — register front-end actions and filters.

**Public API:**
- `run(): void` — fires all registered hooks via the loader.
- `get_plugin_name(): string`
- `get_version(): string`
- `get_loader(): Plugin_Loader`

---

### Plugin_Loader
`Dream_Encode\WordPress_Plugin_Utils\Loader\Plugin_Loader`

Concrete class. Stores and bulk-registers WordPress actions and filters. Can be used directly or subclassed for custom hook-registration behaviour.

**Public API:**
- `add_action( $hook, $component, $callback, $priority, $accepted_args ): void`
- `add_filter( $hook, $component, $callback, $priority, $accepted_args ): void`
- `run(): void` — calls `add_action`/`add_filter` for every registered hook.

---

### Abstract_Plugin_Activator
`Dream_Encode\WordPress_Plugin_Utils\Abstracts\Abstract_Plugin_Activator`

Consistent entry point for `register_activation_hook`.

**Static entry point:** `activate(): void`

**Optional overrides (all no-ops by default):**
- `before_activate(): void`
- `run_install(): void`
- `after_activate(): void`

---

### Abstract_Plugin_Deactivator
`Dream_Encode\WordPress_Plugin_Utils\Abstracts\Abstract_Plugin_Deactivator`

Consistent entry point for `register_deactivation_hook`.

**Static entry point:** `deactivate(): void`

**Optional overrides (all no-ops by default):**
- `before_deactivate(): void`
- `delete_options(): void`
- `after_deactivate(): void`

---

### Abstract_Plugin_I18n
`Dream_Encode\WordPress_Plugin_Utils\Abstracts\Abstract_Plugin_I18n`

Loads plugin translations via `load_plugin_textdomain`.

**Must implement:**
- `get_text_domain(): string`
- `get_languages_path(): string`

**Public API:**
- `load_plugin_textdomain(): bool`

---

### Abstract_Plugin_Upgrader
`Dream_Encode\WordPress_Plugin_Utils\Abstracts\Abstract_Plugin_Upgrader`

Handles plugin upgrade/install logic with cache-clearing for Redis/object cache environments. Integrates with Action Scheduler for deferred tasks.

---

### Asset_Manager
`Dream_Encode\WordPress_Plugin_Utils\Assets\Asset_Manager`

Concrete class. Screen-based stylesheet and script enqueuer for both admin and front-end contexts. All configuration is passed via constructor — no subclassing required for common usage.

**Constructor** (all params after `$plugin_version` are optional):
```php
new Asset_Manager(
    handle_prefix: 'my-plugin-',
    plugin_path:   MY_PLUGIN_PATH,
    plugin_url:    MY_PLUGIN_URL,
    plugin_version: MY_PLUGIN_VERSION,
    screens_to_assets: [ 'edit-post' => [ ['name' => 'editor'] ] ],
    localization_global: 'MY_PLUGIN',
    screens_localization_data: [ 'edit-post' => [ 'REST_URL' => '...' ] ],
    asset_subdir: 'admin/',        // default ''
    asset_file_prefix: 'admin-',   // default ''
);
```

**Public API:**
- `add_screens( array $screens ): void` — merge additional screen entries at runtime.
- `add_screens_localization_data( array $data ): void` — merge additional localization entries.
- `enqueue_styles(): void`
- `enqueue_scripts(): void`
- `current_screen_assets(): array` — asset definitions for the current screen.
- `current_screen_has_assets(): int`
- `screen_assets( WP_Screen $screen ): array`
- `screen_has_assets( WP_Screen $screen ): int`
- `screen_get_localized_data( WP_Screen $screen ): array`

---

### Abstract_REST_API
`Dream_Encode\WordPress_Plugin_Utils\Abstracts\Abstract_REST_API`

Base class for registering a REST API namespace and routes.

---

### Abstract_REST_Controller
`Dream_Encode\WordPress_Plugin_Utils\Abstracts\Abstract_REST_Controller`

Extends `WP_REST_Controller` with a reusable pattern for REST endpoint controllers.

**Must implement:**
- `check_user_permission(): bool`

---

### REST_Authentication
`Dream_Encode\WordPress_Plugin_Utils\RestApi\REST_Authentication`

Handles WP REST API authentication, including cookie auth error checking, app password authentication, and permission helpers. Auto-registers the `rest_authentication_errors` filter when the file is loaded.

**Static permission helpers:**
- `check_logged_in_permission(): bool`
- `check_admin_permission(): bool`
- `check_editor_permission(): bool`
- `check_user_permission(): bool`
- `check_woocommerce_shop_manager_permission(): bool`
- `check_post_permissions( $post_type, $permission, $user_id ): bool`

**Static accessors:**
- `get_wp_rest_nonce(): string`
- `get_wp_user_id(): int`

---

### REST_Response
`Dream_Encode\WordPress_Plugin_Utils\RestApi\REST_Response`

Simple value object for REST API responses.

**Properties:**
- `$status` — `string`, defaults to `'error'`.
- `$message` — `string`, defaults to `''`.
- `$data` — `mixed`, initialised to a fresh `stdClass`.
- `$success` — `bool|null`, defaults to `null`.

---

### Object_Data
`Dream_Encode\WordPress_Plugin_Utils\Data\Object_Data`

Concrete class. WooCommerce-style data object. Tracks a `$data` array, a `$changes` diff, and optional `$extra_data`. Can be used directly or subclassed to declare typed `$data`/`$extra_data` defaults and domain-specific getters/setters.

**Public API:**
- `get_prop( $prop ): mixed` — reads from `$changes` first, then `$data`.
- `set_prop( $prop, $value ): void`
- `set_props( $props ): void`
- `apply_changes(): void` — merges `$changes` into `$data`.
- `get_data(): array`
- `get_changes(): array`
- `get_extra_data(): array`
- `get_no_cache(): bool`
- `set_no_cache( $no_cache ): void`

---

### Abstract_WC_Logger
`Dream_Encode\WordPress_Plugin_Utils\Abstracts\Abstract_WC_Logger`

Logs data through WooCommerce's `WC_Logger`, falling back to `error_log` when WooCommerce is unavailable.

---

### Abstract_Data_Migrator
`Dream_Encode\WordPress_Plugin_Utils\Abstracts\Abstract_Data_Migrator`

Template-method base for multi-run data migrations driven by Action Scheduler. Each migration is broken into individually-scheduled runs; progress, status, and results are persisted via plugin-supplied storage hooks.

**Must implement (persistence — decoupled from any specific storage):**
- `create_new_migration( array $migration ): false|array`
- `create_new_migration_run( array $migration ): false|int`
- `get_migration_option( $migration_id ): false|mixed`
- `update_migration_option( $migration_id, $data ): void`
- `update_migration( array $progress ): void`
- `update_migration_run( array $migration_run ): void`
- `get_migration_run_option( $migration_run_id ): false|mixed`
- `update_migration_run_option( $migration_run_id, $data ): void`
- `save_completed_migration_run( array $migration_run ): void`
- `save_completed_migration( array $progress ): void`
- `create_migration_message( array $args ): void`

**Must implement (metadata):**
- `get_migrator_label( $migrator ): string`
- `get_plugin_setting( $key ): mixed`
- `get_migrator_setting( $migrator, $key, $default ): mixed`
- `get_all_migration_param_keys(): string[]`
- `get_migration_progress_keys(): string[]`
- `get_action_scheduler_hook(): string`
- `get_action_scheduler_group(): string`

**Public API:**
- `queue_migrator( $init_params ): void` — queues the first migration run via Action Scheduler.
- `run( $migration_run_id ): void` — invoked by Action Scheduler to execute one run.
- `process_migration(): void` — override to perform the actual row-level work.
- `get_progress(): array` — current progress snapshot.
- `update_percent_complete(): void`
- `get_migration_id(): false|int`
- `get_migration_run_id(): false|int`
- `get_status(): string`
- `get_percent_complete(): float`
- `get_total_rows(): int`
- `get_total_rows_migrated(): int`
- `get_total_rows_failed(): int`
- `get_total_rows_skipped(): int`
- `get_batch_size(): int`
- `get_is_dry_run(): bool`

---

### Abstract_Background_Processor
`Dream_Encode\WordPress_Plugin_Utils\Abstracts\Abstract_Background_Processor`

Template-method base for long-running asynchronous background processors driven by Action Scheduler. Supports prerequisite sub-processors, configurable batch sizes, and per-run progress tracking.

**Must implement (persistence):**
- `queue_new_background_process( $args ): false|array`
- `create_new_background_process( $args ): false|array`
- `create_new_background_process_run( $background_process ): false|int`
- `get_background_process_option( $background_processes_id, $force_refresh ): false|mixed`
- `update_background_process_option( $background_processes_id, $data ): void`
- `update_background_process( $data ): void`
- `get_background_process_run_option( $background_processes_run_id ): false|mixed`
- `update_background_process_run_option( $background_processes_run_id, $data ): void`
- `update_background_process_run( $data ): void`
- `save_completed_background_process( $data ): void`
- `save_completed_background_process_run( $data ): void`
- `get_background_process_by_id( $background_processes_id ): object|null`
- `update_prerequisite_sub_process_parent_background_process( $background_processes_id ): void`

**Must implement (metadata):**
- `get_all_background_process_param_keys(): string[]`
- `get_background_process_progress_keys(): string[]`
- `get_processor_sub_params(): array`
- `get_background_processor_label( $processor ): string`
- `create_background_processes_message( $args ): void`
- `get_plugin_setting( $key ): mixed`
- `get_processor_setting( $processor, $key, $default ): mixed`
- `get_action_scheduler_hook(): string`
- `get_action_scheduler_group(): string`

---

## Settings

### Plugin_Settings_Repository
`Dream_Encode\WordPress_Plugin_Utils\Settings\Plugin_Settings_Repository`

Thin read/write wrapper around a single WordPress option key. Results are cached in-memory for the request lifetime.

**Constructor:** `__construct( string $option_name, array $defaults = [], bool $autoload = true )`

**Public API:**
- `all(): array` — returns all settings, merged with defaults.
- `get( $key, $default = null ): mixed`
- `set( $key, $value ): bool`
- `update( array $settings ): bool` — overwrites the full option.
- `delete(): bool`
- `refresh(): void` — clears the in-memory cache.
- `get_option_name(): string`

---

## Functions

`Dream_Encode\WordPress_Plugin_Utils\Common\Functions`

All methods are static.

- `maybe_define_constant( string $name, mixed $value ): void` — defines a PHP constant only if it is not already defined.
- `get_mysql_datetime( false|float|int $time, string $timezone_string ): string|false` — converts a Unix timestamp to a MySQL `Y-m-d H:i:s` string (defaults to current time, UTC).
- `mysql_datetime_to_datetime_long( string $datetime ): string|false` — formats a MySQL datetime string to `"Monday January 5, 2026 at 3:42:00 pm"`.
- `format_timestamp_to_datetime_long( null|float|int $timestamp, string $timezone_string ): string|false` — formats a Unix timestamp to the same long format.
- `convert_seconds_to_minutes_seconds( int $seconds ): string` — formats an integer number of seconds as `MM:SS` (e.g. `313` → `"05:13"`).
- `get_user_display_name( int $user_id ): string` — returns the user's `user_nicename`, or `'N/A'` if the user does not exist.

---

## Version History

`Dream_Encode\WordPress_Plugin_Utils\VersionHistory\`

Permanent history of WordPress core, plugin, must-use plugin, drop-in and theme versions for a
site. Answers questions like "what version of WooCommerce was installed on July 15" from recorded
evidence, and says the state is unknown when there is none.

**You do not wire this up.** It boots itself. Every copy of this library registers its module
revision at include time, and on `plugins_loaded` the highest revision loads the module and records
the baseline the first time it runs. One ledger per site, three shared tables (`de_vh_events`,
`de_vh_current_state`, `de_vh_checkpoints`), regardless of how many plugins bundle the library.

The election is additive. It does not change how the base classes above load, which is still
first-copy-wins at include time. The module is deliberately self-contained and depends on nothing
outside its own namespace, so it stays correct when the elected copy is newer than the copy that
declared the base classes.

**Key classes:**
- `Version_History` — singleton. `instance()`, `init()`, `has_baseline()`, `status()`.
- `VH_History_Query` — `state_at( $gmt )`, `component_at()`, `component_history()`,
  `resolve_component()`, `current_state()`, `count_events()`.
- `VH_Event_Recorder` — `record()`, `record_baseline()`, `mark_absent()`, `fingerprint()`.
- `VH_Inventory` — `snapshot()` plus per-type readers.
- `VH_Checkpoints` — `create()`, `latest_before()`, `decode()`.
- `VH_Installer` — `maybe_install()`, `get_schema()`, table name accessors.
- `VH_Options` — module options and settings.

`state_at()` returns `known => false` for any timestamp before the baseline. Nothing extrapolates
backwards, and events are never rewritten.

---

## Development

```bash
# Lint code
composer lint

# Auto-format code
composer format

# Static analysis
composer analyze

# Run all checks + tests
composer test
```

## License

GPL-3.0-or-later
