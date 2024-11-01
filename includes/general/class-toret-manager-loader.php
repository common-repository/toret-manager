<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Register all actions and filters for the plugin
 */
class Toret_Manager_Loader {

	/**
	 * The array of actions registered with WordPress.
     *
     * @var array $actions
	 */
	protected array $actions;

	/**
	 * The array of filters registered with WordPress.
     *
     * @var array $filters
	 */
	protected array $filters;

	/**
	 * Initialize the collections used to maintain the actions and filters.
	 */
	public function __construct() {
		$this->actions = array();
		$this->filters = array();
	}

	/**
	 * Add a new action to the collection to be registered with WordPress.
     *
     * @param string $hook
     * @param object $component
     * @param string $callback
     * @param int $priority
     * @param int $accepted_args     *
	 */
	public function add_action(string $hook, object $component, string $callback, int $priority = 10, int $accepted_args = 1 ) {
		$this->actions = $this->add( $this->actions, $hook, $component, $callback, $priority, $accepted_args );
	}

	/**
	 * Add a new filter to the collection to be registered with WordPress.
     *
     * @param string $hook
     * @param object $component
     * @param string $callback
     * @param int $priority
     * @param int $accepted_args
     */
	public function add_filter(string $hook, object $component, string $callback, int $priority = 10, int $accepted_args = 1 ) {
		$this->filters = $this->add( $this->filters, $hook, $component, $callback, $priority, $accepted_args );
	}

	/**
	 * A utility function that is used to register the actions and hooks into a single collection.
     *
     * @param array $hooks
     * @param string $hook
     * @param object $component
     * @param string $callback
     * @param int $priority
     * @param int $accepted_args
     * @return array
	 */
	private function add(array $hooks, string $hook, object $component, string $callback, int $priority, int $accepted_args ): array
    {
		$hooks[] = array(
			'hook'          => $hook,
			'component'     => $component,
			'callback'      => $callback,
			'priority'      => $priority,
			'accepted_args' => $accepted_args
		);

		return $hooks;
	}

	/**
	 * Register the filters and actions with WordPress.
	 */
	public function run() {
		foreach ( $this->filters as $hook ) {
			add_filter( $hook['hook'], array( $hook['component'], $hook['callback'] ), $hook['priority'], $hook['accepted_args'] );
		}

		foreach ( $this->actions as $hook ) {
			add_action( $hook['hook'], array( $hook['component'], $hook['callback'] ), $hook['priority'], $hook['accepted_args'] );
		}
	}

}
