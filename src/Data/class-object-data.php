<?php
/**
 * Object data container.
 *
 * @package Dream_Encode\WordPress_Plugin_Utils\Data
 * @since   1.0.0
 */

declare( strict_types = 1 );

namespace Dream_Encode\WordPress_Plugin_Utils\Data;

defined( 'ABSPATH' ) || exit;

/**
 * Object data container.
 *
 * Provides generic data/change tracking similar to WC_Data.
 *
 * @since 1.0.0
 */
class Object_Data {

	/**
	 * Data.
	 *
	 * @var array<string, mixed>
	 */
	protected array $data = array();

	/**
	 * Changes.
	 *
	 * @var array<string, mixed>
	 */
	protected array $changes = array();

	/**
	 * Extra data.
	 *
	 * @var array<string, mixed>
	 */
	protected array $extra_data = array();

	/**
	 * No cache.
	 *
	 * @var bool
	 */
	protected bool $no_cache = false;

	/**
	 * Construct the object.
	 *
	 * @since  1.0.0
	 * @access public
	 * @param  array<string, mixed>  $data  Initial property values.
	 * @return void
	 */
	public function __construct( array $data = array() ) {
		if ( $data ) {
			$this->set_props( $data );
			$this->apply_changes();
		}
	}

	/**
	 * Get a property value.
	 *
	 * @since  1.0.0
	 * @access public
	 * @param  string  $prop  Property name.
	 * @return mixed
	 */
	public function get_prop( string $prop ): mixed {
		if ( array_key_exists( $prop, $this->changes ) ) {
			return $this->changes[ $prop ];
		}

		if ( array_key_exists( $prop, $this->data ) ) {
			return $this->data[ $prop ];
		}

		return $this->extra_data[ $prop ] ?? null;
	}

	/**
	 * Set a property value.
	 *
	 * @since  1.0.0
	 * @access public
	 * @param  string  $prop   Property name.
	 * @param  mixed   $value  Property value.
	 * @return void
	 */
	public function set_prop( string $prop, mixed $value ): void {
		if ( ! $this->has_prop( $prop ) ) {
			return;
		}

		if ( $this->get_prop( $prop ) === $value ) {
			unset( $this->changes[ $prop ] );

			return;
		}

		$this->changes[ $prop ] = $value;
	}

	/**
	 * Set multiple property values.
	 *
	 * @since  1.0.0
	 * @access public
	 * @param  array<string, mixed>  $props  Property values.
	 * @return void
	 */
	public function set_props( array $props ): void {
		foreach ( $props as $prop => $value ) {
			$this->set_prop( $prop, $value );
		}
	}

	/**
	 * Apply tracked changes to stored data.
	 *
	 * @since  1.0.0
	 * @access public
	 * @return void
	 */
	public function apply_changes(): void {
		foreach ( $this->changes as $prop => $value ) {
			if ( array_key_exists( $prop, $this->data ) ) {
				$this->data[ $prop ] = $value;

				continue;
			}

			if ( array_key_exists( $prop, $this->extra_data ) ) {
				$this->extra_data[ $prop ] = $value;
			}
		}

		$this->changes = array();
	}

	/**
	 * Get data.
	 *
	 * @since  1.0.0
	 * @access public
	 * @return array<string, mixed>
	 */
	public function get_data(): array {
		return array_replace( $this->data, $this->extra_data, $this->changes );
	}

	/**
	 * Get changes.
	 *
	 * @since  1.0.0
	 * @access public
	 * @return array<string, mixed>
	 */
	public function get_changes(): array {
		return $this->changes;
	}

	/**
	 * Get extra data.
	 *
	 * @since  1.0.0
	 * @access public
	 * @return array<string, mixed>
	 */
	public function get_extra_data(): array {
		return array_intersect_key( $this->get_data(), $this->extra_data );
	}

	/**
	 * Get the $no_cache property.
	 *
	 * @since  1.0.0
	 * @access public
	 * @return bool
	 */
	public function get_no_cache(): bool {
		return $this->no_cache;
	}

	/**
	 * Set the $no_cache property.
	 *
	 * @since  1.0.0
	 * @access public
	 * @param  bool  $no_cache  New value.
	 * @return void
	 */
	public function set_no_cache( bool $no_cache ): void {
		$this->no_cache = $no_cache;
	}

	/**
	 * Check if the object has a property.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @param  string  $prop  Prop to check for.
	 * @return bool
	 */
	protected function has_prop( string $prop ): bool {
		return array_key_exists( $prop, $this->data ) || array_key_exists( $prop, $this->extra_data );
	}
}
