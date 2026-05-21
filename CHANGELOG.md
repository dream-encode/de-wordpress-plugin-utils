# Changelog

## [NEXT_VERSION] - [UNRELEASED]
* BUG: Example fix description.

## [1.9.2] - 2026-05-21
* BUG: Changed the return type of get_background_process_by_id() from object|null to \stdClass|null.
* BUG: Fix tests.

## [1.9.1] - 2026-05-21
* BUG: Background Processor - Fix sub processes race condition.

## [1.9.0] - 2026-05-01
* ENH: Upgrader - No init if in local environment.
* ENH: Functions - Add environment_is_local.

## [1.8.0] - 2026-04-29
* ENH: Background Processor - Add $processor_group param.

## [1.7.1] - 2026-04-29
* BUG: Rest Controller - Make auth methods non-abstract with defaults.

## [1.7.0] - 2026-04-28
* ENH: Add CSV Export & Upload classes.
* ENH: Add mysql_datetime_to_datetime_short function.

## [1.6.0] - 2026-04-26
* ENH: Make Plugin_Settings_Repository more robust.

## [1.5.0] - 2026-04-26
* ENH: Promote `Abstract_Admin_Asset_Manager` to concrete `Asset_Manager` (`Assets\Asset_Manager`) - fully constructor-injected, no subclassing required, works for both admin and front-end assets.
* ENH: `Asset_Manager` - add `add_screens()` and `add_screens_localization_data()` for runtime registration.
* ENH: `Asset_Manager` - add `current_screen_assets()`, `current_screen_has_assets()`, `screen_assets()`, `screen_has_assets()`, `screen_get_localized_data()` public helpers.
* ENH: Replace procedural `helpers.php` with `Functions` static class (`src/Common/class-functions.php`).
* ENH: Add `Functions::get_mysql_datetime()` - timestamp to MySQL datetime string, extracted from multiple plugins.
* ENH: Add `Functions::mysql_datetime_to_datetime_long()` - MySQL datetime to long human-readable format, extracted from multiple plugins.
* ENH: Add `Functions::format_timestamp_to_datetime_long()` - timestamp to long human-readable format.
* ENH: Add `Functions::convert_seconds_to_minutes_seconds()` - seconds to MM:SS string, extracted from multiple plugins.
* ENH: Add `Functions::get_user_display_name()` - user nicename by ID with N/A fallback.
* ENH: Add `declare( strict_types = 1 )` to all source and test PHP files.

## [1.4.0] - 2026-04-25
* ENH: Add `REST_Authentication` - WP REST API authentication handler with cookie/app-password support and static permission helpers.
* ENH: Add `REST_Response` - simple value object for REST API responses.
* BUG: Fix `REST_Authentication::is_rest_api_request()` - `str_contains` arguments were reversed.
* BUG: Add missing `defined( 'ABSPATH' ) || exit` guard to `REST_Authentication`.

## [1.3.0] - 2026-04-25
* ENH: Rename Abstract_Migrator to Abstract_Data_Migrator.
* BUG: Remove some debug logging in the background processor.

## [1.2.0] - 2026-04-25
* ENH: Promote `Abstract_Plugin_Loader` to concrete `Plugin_Loader` (`Loader\Plugin_Loader`) - no abstract methods; usable directly without subclassing.
* ENH: Promote `Abstract_Object_Data` to concrete `Object_Data` (`Data\Object_Data`) - no abstract methods; usable directly or extended for domain-specific data objects.
* ENH: `Abstract_Plugin::create_loader()` now has a default implementation returning `new Plugin_Loader()`, reducing abstract method count from 3 to 2.

## [1.1.1] - 2026-04-25
* BUG: Fix fatal caused by release script sed.

## [1.1.0] - 2026-04-25
* ENH: Add `Abstract_Plugin` - main plugin bootstrap template using the Template Method pattern.
* ENH: Add `Plugin_Loader` - stores and bulk-registers WordPress actions and filters.
* ENH: Add `Abstract_Plugin_Activator` and `Abstract_Plugin_Deactivator` - consistent lifecycle hook entry points.
* ENH: Add `Abstract_Plugin_I18n` - standardized `load_plugin_textdomain` wrapper.
* ENH: Add `Object_Data` - WooCommerce-style data/changes/extra_data object base.
* ENH: Add `Abstract_Admin_Asset_Manager` - screen-aware admin stylesheet and script enqueuer.
* ENH: Add `Abstract_Data_Migrator` - multi-run data migration base backed by Action Scheduler with decoupled persistence hooks.
* ENH: Add `Abstract_Background_Processor` - batch background processor base backed by Action Scheduler, with prerequisite sub-processor chain support and decoupled persistence hooks.
* ENH: Add `Plugin_Settings_Repository` - thin in-memory-cached read/write wrapper around a single WordPress option key.
* ENH: Add `maybe_define_constant()` helper function.
* TSK: Expand `composer test` script to run lint and static analysis before PHPUnit.
* BUG: Fix PHPStan Level 8 errors in `Abstract_Data_Migrator` and `Abstract_Background_Processor` - align persistence method signatures with `false|int` IDs and `false|mixed` return types.

## [1.0.0] - 2025-04-21
* Initial release.
