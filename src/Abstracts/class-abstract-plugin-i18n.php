<?php
/**
 * Abstract plugin i18n loader.
 *
 * @package Dream_Encode\WordPress_Plugin_Utils\Abstracts
 * @since   1.0.0
 */

declare( strict_types = 1 );

namespace Dream_Encode\WordPress_Plugin_Utils\Abstracts;

defined( 'ABSPATH' ) || exit;

/**
 * Abstract plugin i18n loader.
 *
 * @since 1.0.0
 */
abstract class Abstract_Plugin_I18n {

	/**
	 * Get the plugin text domain.
	 *
	 * @return string
	 */
	abstract protected function get_text_domain(): string;

	/**
	 * Get the plugin languages path relative to WP_PLUGIN_DIR.
	 *
	 * @return string
	 */
	abstract protected function get_languages_path(): string;

	/**
	 * Load plugin translations.
	 *
	 * @return bool
	 */
	public function load_plugin_textdomain(): bool {
		return load_plugin_textdomain( $this->get_text_domain(), false, $this->get_languages_path() );
	}
}
