<?php
/**
 * Class REST_Response.
 *
 * @since [NEXT_VERSION]
 */

namespace Dream_Encode\WordPress_Plugin_Utils\RestApi;

use stdClass;

defined( 'ABSPATH' ) || exit;

/**
 * Class REST_Response
 *
 * @since [NEXT_VERSION]
 */
class REST_Response {
	/**
	 * Status.
	 *
	 * @var string.
	 */
	public $status = 'error';

	/**
	 * Message.
	 *
	 * @var string .
	 */
	public $message = '';

	/**
	 * Extra data
	 *
	 * @var mixed
	 */
	public $data;

	/**
	 * Success
	 *
	 * @var bool|null
	 */
	public $success = null;

	/**
	 * REST_Response constructor.
	 */
	public function __construct() {
		$this->data = new stdClass();
	}
}
