<?php
/**
 * Plugin loader.
 *
 * @package Dream_Encode\WordPress_Plugin_Utils\Loaders
 * @since   1.0.0
 */

namespace Dream_Encode\WordPress_Plugin_Utils\Loader;

defined( 'ABSPATH' ) || exit;

/**
 * Plugin loader.
 *
 * Stores and registers WordPress hooks.
 *
 * @since 1.0.0
 */
class Plugin_Loader {

	/**
	 * Registered actions.
	 *
	 * @var array<int, array{hook: string, component: object|string, callback: string, priority: int, accepted_args: int}>
	 */
	protected array $actions = array();

	/**
	 * Registered filters.
	 *
	 * @var array<int, array{hook: string, component: object|string, callback: string, priority: int, accepted_args: int}>
	 */
	protected array $filters = array();

	/**
	 * Add an action hook.
	 *
	 * @param string        $hook Hook name.
	 * @param object|string $component Component instance or class name.
	 * @param string        $callback Callback method.
	 * @param int           $priority Hook priority.
	 * @param int           $accepted_args Accepted arguments.
	 */
	public function add_action( string $hook, object|string $component, string $callback, int $priority = 10, int $accepted_args = 1 ): void {
		$this->add( $this->actions, $hook, $component, $callback, $priority, $accepted_args );
	}

	/**
	 * Add a filter hook.
	 *
	 * @param string        $hook Hook name.
	 * @param object|string $component Component instance or class name.
	 * @param string        $callback Callback method.
	 * @param int           $priority Hook priority.
	 * @param int           $accepted_args Accepted arguments.
	 */
	public function add_filter( string $hook, object|string $component, string $callback, int $priority = 10, int $accepted_args = 1 ): void {
		$this->add( $this->filters, $hook, $component, $callback, $priority, $accepted_args );
	}

	/**
	 * Store a hook definition.
	 *
	 * @param array<int, array{hook: string, component: object|string, callback: string, priority: int, accepted_args: int}> $hooks Hook list.
	 * @param string                                                                                                         $hook Hook name.
	 * @param object|string                                                                                                  $component Component instance or class name.
	 * @param string                                                                                                         $callback Callback method.
	 * @param int                                                                                                            $priority Hook priority.
	 * @param int                                                                                                            $accepted_args Accepted arguments.
	 */
	protected function add( array &$hooks, string $hook, object|string $component, string $callback, int $priority, int $accepted_args ): void {
		$hooks[] = array(
			'hook'          => $hook,
			'component'     => $component,
			'callback'      => $callback,
			'priority'      => $priority,
			'accepted_args' => $accepted_args,
		);
	}

	/**
	 * Register all stored hooks.
	 *
	 * @return void
	 */
	public function run(): void {
		foreach ( $this->actions as $hook ) {
			$callback = $this->get_hook_callback( $hook['component'], $hook['callback'] );

			if ( null === $callback ) {
				continue;
			}

			add_action( $hook['hook'], $callback, $hook['priority'], $hook['accepted_args'] );
		}

		foreach ( $this->filters as $hook ) {
			$callback = $this->get_hook_callback( $hook['component'], $hook['callback'] );

			if ( null === $callback ) {
				continue;
			}

			add_filter( $hook['hook'], $callback, $hook['priority'], $hook['accepted_args'] );
		}
	}

	/**
	 * Build a callable hook callback.
	 *
	 * @param object|string $component Component instance or class name.
	 * @param string        $callback Callback method.
	 * @return callable|null
	 */
	protected function get_hook_callback( object|string $component, string $callback ): callable|null {
		$hook_callback = array( $component, $callback );

		if ( ! is_callable( $hook_callback ) ) {
			return null;
		}

		return static function ( ...$args ) use ( $hook_callback ) {
			return call_user_func_array( $hook_callback, $args );
		};
	}
}
