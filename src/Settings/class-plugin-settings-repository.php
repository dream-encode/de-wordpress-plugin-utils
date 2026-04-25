<?php
/**
 * Plugin settings repository.
 *
 * @package Dream_Encode\WordPress_Plugin_Utils\Settings
 * @since   1.0.0
 */

namespace Dream_Encode\WordPress_Plugin_Utils\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Plugin settings repository.
 *
 * Wraps a single WordPress option that stores an associative array of plugin
 * settings and exposes a simple, cached API for reading and writing values.
 *
 * @since 1.0.0
 */
class Plugin_Settings_Repository {

	/**
	 * Option name used to store settings.
	 *
	 * @var string
	 */
	protected string $option_name;

	/**
	 * Default setting values.
	 *
	 * @var array<string, mixed>
	 */
	protected array $defaults;

	/**
	 * Whether the option should autoload.
	 *
	 * @var bool
	 */
	protected bool $autoload;

	/**
	 * In-memory cache of the resolved settings.
	 *
	 * @var array<string, mixed>|null
	 */
	private ?array $cache = null;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 * @param string               $option_name Option name used to store settings.
	 * @param array<string, mixed> $defaults    Optional. Default setting values.
	 * @param bool                 $autoload    Optional. Whether the option should autoload. Default true.
	 */
	public function __construct( string $option_name, array $defaults = array(), bool $autoload = true ) {
		$this->option_name = $option_name;
		$this->defaults    = $defaults;
		$this->autoload    = $autoload;
	}

	/**
	 * Get all settings merged with defaults.
	 *
	 * @since  1.0.0
	 * @return array<string, mixed>
	 */
	public function all(): array {
		if ( null !== $this->cache ) {
			return $this->cache;
		}

		$stored = get_option( $this->option_name, array() );

		if ( ! is_array( $stored ) ) {
			$stored = array();
		}

		$this->cache = array_replace( $this->defaults, $stored );

		return $this->cache;
	}

	/**
	 * Get a single setting by key.
	 *
	 * @since  1.0.0
	 * @param  string  $key     Setting key.
	 * @param  mixed   $default Optional. Default value when key is missing. Default null.
	 * @return mixed
	 */
	public function get( string $key, mixed $default = null ): mixed {
		$settings = $this->all();

		if ( array_key_exists( $key, $settings ) ) {
			return $settings[ $key ];
		}

		return $default;
	}

	/**
	 * Set a single setting by key.
	 *
	 * @since  1.0.0
	 * @param  string  $key   Setting key.
	 * @param  mixed   $value Setting value.
	 * @return bool True on successful option update.
	 */
	public function set( string $key, mixed $value ): bool {
		$settings         = $this->all();
		$settings[ $key ] = $value;

		return $this->update( $settings );
	}

	/**
	 * Replace all stored settings.
	 *
	 * @since  1.0.0
	 * @param  array<string, mixed> $settings Full settings array.
	 * @return bool True on successful option update.
	 */
	public function update( array $settings ): bool {
		$result      = update_option( $this->option_name, $settings, $this->autoload );
		$this->cache = array_replace( $this->defaults, $settings );

		return (bool) $result;
	}

	/**
	 * Delete the stored option.
	 *
	 * @since  1.0.0
	 * @return bool True on successful option deletion.
	 */
	public function delete(): bool {
		$this->cache = null;

		return delete_option( $this->option_name );
	}

	/**
	 * Invalidate the in-memory cache.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function refresh(): void {
		$this->cache = null;
	}

	/**
	 * Get the option name.
	 *
	 * @since  1.0.0
	 * @return string
	 */
	public function get_option_name(): string {
		return $this->option_name;
	}
}
