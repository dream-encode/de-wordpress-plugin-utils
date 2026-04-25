# Changelog

## [1.1.0] - 2026-04-25
* ENH: Add `Abstract_Plugin` - main plugin bootstrap template using the Template Method pattern.
* ENH: Add `Abstract_Plugin_Loader` - stores and bulk-registers WordPress actions and filters.
* ENH: Add `Abstract_Plugin_Activator` and `Abstract_Plugin_Deactivator` - consistent lifecycle hook entry points.
* ENH: Add `Abstract_Plugin_I18n` - standardized `load_plugin_textdomain` wrapper.
* ENH: Add `Abstract_Object_Data` - WooCommerce-style data/changes/extra_data object base.
* ENH: Add `Abstract_Admin_Asset_Manager` - screen-aware admin stylesheet and script enqueuer.
* ENH: Add `Abstract_Migrator` - multi-run data migration base backed by Action Scheduler with decoupled persistence hooks.
* ENH: Add `Abstract_Background_Processor` - batch background processor base backed by Action Scheduler, with prerequisite sub-processor chain support and decoupled persistence hooks.
* ENH: Add `Plugin_Settings_Repository` - thin in-memory-cached read/write wrapper around a single WordPress option key.
* ENH: Add `maybe_define_constant()` helper function.
* TSK: Expand `composer test` script to run lint and static analysis before PHPUnit.
* BUG: Fix PHPStan Level 8 errors in `Abstract_Migrator` and `Abstract_Background_Processor` - align persistence method signatures with `false|int` IDs and `false|mixed` return types.

## [1.0.0] - 2025-04-21
* Initial release.
