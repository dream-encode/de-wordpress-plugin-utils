<?php
/**
 * Abstract REST API base class.
 *
 * @since 1.0.0
 */

namespace Dream_Encode\WordPress_Plugin_Utils\Abstracts;

defined( 'ABSPATH' ) || exit;

/**
 * Abstract REST API base class.
 *
 * @since 1.0.0
 */
abstract class Abstract_REST_API {
	/**
	 * The current version.
	 *
	 * @since 1.0.0
	 * @var   string
	 */
	public string $version = 'v1';

	/**
	 * The current endpoint.
	 *
	 * @since 1.0.0
	 * @var   string
	 */
	public string $endpoint = '';

	/**
	 * Controllers to load.
	 *
	 * @since 1.0.0
	 * @var   array<string, mixed>
	 */
	public array $controllers = array();

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->rest_api_init();
	}

	/**
	 * Init REST.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function rest_api_init(): void {
		if ( ! class_exists( '\WP_REST_Server' ) ) {
			return;
		}

		$this->rest_api_includes();

		add_action( 'rest_api_init', array( $this, 'rest_api_register_routes' ), 10 );
	}

	/**
	 * Get the controller namespace prefix.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	abstract protected function get_controller_namespace(): string;

	/**
	 * Include relevant files.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function rest_api_includes(): void {}

	/**
	 * Register routes.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function rest_api_register_routes(): void {
		if ( ! $this->controllers ) {
			return;
		}

		$controllers = array();

		foreach ( $this->controllers as $name => $controller ) {
			if ( is_string( $controller ) ) {
				$name                 = $controller;
				$class                = $this->get_controller_namespace() . $controller;
				$controllers[ $name ] = new $class();
			} else {
				$controllers[ $name ] = $controller;
			}

			$controllers[ $name ]->register_routes();
		}

		$this->controllers = $controllers;
	}
}
