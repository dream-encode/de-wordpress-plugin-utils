<?php
/**
 * Abstract REST Controller base class.
 *
 * @since 1.0.0
 */

namespace Dream_Encode\WordPress_Plugin_Utils\Abstracts;

use WP_REST_Controller;
use WP_REST_Response;

defined( 'ABSPATH' ) || exit;

/**
 * Abstract REST Controller base class.
 *
 * @since 1.0.0
 */
abstract class Abstract_REST_Controller extends WP_REST_Controller {
	/**
	 * The current namespace.
	 *
	 * @since 1.0.0
	 * @var   string
	 */
	public $namespace = '';

	/**
	 * The current rest_base.
	 *
	 * @since 1.0.0
	 * @var   string
	 */
	public $rest_base = '';

	/**
	 * Array of routes.
	 *
	 * @since 1.0.0
	 * @var   array<int|string, mixed>
	 */
	public array $routes = array();

	/**
	 * Register routes for controller.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_routes(): void {
		if ( ! $this->routes ) {
			return;
		}

		foreach ( $this->routes as $key => $args ) {
			$rest_base = $this->rest_base;
			$override  = false;

			if ( is_bool( end( $args ) ) ) {
				$override = array_pop( $args );
			}

			if ( ! is_numeric( $key ) ) {
				$rest_base = "{$rest_base}/{$key}";
			}

			register_rest_route( $this->namespace, '/' . $rest_base, $args, $override );
		}
	}

	/**
	 * Ensure rest response.
	 *
	 * @since 1.0.0
	 * @param mixed $data Current response data.
	 * @return WP_REST_Response
	 */
	public function ensure_response( mixed $data ): WP_REST_Response {
		return rest_ensure_response( $data );
	}

	/**
	 * Check user is admin.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	abstract public function check_admin_permission(): bool;

	/**
	 * Check user can do action.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	abstract public function check_user_permission(): bool;
}

