<?php
/**
 * Class to process an uploaded CSV file.
 *
 * @since      [NEXT_VERSION]
 * @package    Dream_Encode\WordPress_Plugin_Utils
 * @subpackage Dream_Encode\WordPress_Plugin_Utils\Upload
 */

namespace Dream_Encode\WordPress_Plugin_Utils\Upload;

use WP_Error;
use WP_Filesystem_Base;

/**
 * Class to process an uploaded CSV file.
 *
 * @since      [NEXT_VERSION]
 * @package    Dream_Encode\WordPress_Plugin_Utils
 * @subpackage Dream_Encode\WordPress_Plugin_Utils\Upload
 * @author     David Baumwald <david@dream-encode.com>
 */
class CSV_File_Upload_Handler {
	/**
	 * Filesystem instance.
	 *
	 * @since  [NEXT_VERSION]
	 * @var    null|WP_Filesystem_Base
	 */
	protected $filesystem = null;

	/**
	 * Raw file.
	 *
	 * @since  [NEXT_VERSION]
	 * @var    array
	 */
	protected $raw_file = array();

	/**
	 * Upload subdirectory name.
	 *
	 * @since  [NEXT_VERSION]
	 * @var    string
	 */
	protected $upload_subdir = 'csv-uploads';

	/**
	 * Upload path.
	 *
	 * @since  [NEXT_VERSION]
	 * @var    false|string
	 */
	protected $upload_path = false;

	/**
	 * Upload URL.
	 *
	 * @since  [NEXT_VERSION]
	 * @var    false|string
	 */
	protected $upload_url = false;

	/**
	 * Uploaded file.
	 *
	 * @since  [NEXT_VERSION]
	 * @var    false|array
	 */
	public $uploaded_csv_file = false;

	/**
	 * Errors.
	 *
	 * @since  [NEXT_VERSION]
	 * @var    WP_Error
	 */
	public $error;

	/**
	 * Initialize upload handler.
	 *
	 * @since  [NEXT_VERSION]
	 * @param  array   $file          File entry from $_FILES.
	 * @param  string  $upload_subdir  Optional. Upload subdirectory name. Default 'csv-uploads'.
	 */
	public function __construct( array $file, string $upload_subdir = 'csv-uploads' ) {
		$this->error        = new WP_Error();
		$this->raw_file     = $file;
		$this->upload_subdir = $upload_subdir;

		require_once ABSPATH . 'wp-admin/includes/file.php';

		if ( ! $this->verify_upload_folder() ) {
			return;
		}

		$this->process_uploaded_csv_file();
	}

	/**
	 * Process uploaded CSV file.
	 *
	 * @since  [NEXT_VERSION]
	 * @return void
	 */
	public function process_uploaded_csv_file(): void {
		if ( ! $this->upload_path || ! $this->upload_url ) {
			$this->error->add( 'upload_error', 'Upload path or URL not set.' );

			return;
		}

		if ( ! isset( $this->raw_file['tmp_name'] ) || ! isset( $this->raw_file['name'] ) ) {
			return;
		}

		$uploaded_csv_file_extension = pathinfo( $this->raw_file['name'], PATHINFO_EXTENSION );

		$new_filename = sprintf(
			'uploaded-csv-%1$s.%2$s',
			wp_date( 'Ymd_his' ),
			$uploaded_csv_file_extension
		);

		$upload_subdir = $this->upload_subdir;

		add_filter(
			'wp_handle_sideload_prefilter',
			function( $prefilter ) use ( $new_filename ) {
				$prefilter['name'] = sanitize_file_name( $new_filename );

				return $prefilter;
			}
		);

		add_filter(
			'upload_dir',
			function( $dirs ) use ( $upload_subdir ) {
				$dirs['subdir'] = '/' . $upload_subdir;
				$dirs['path']   = $dirs['basedir'] . '/' . $upload_subdir;
				$dirs['url']    = $dirs['baseurl'] . '/' . $upload_subdir;

				return $dirs;
			}
		);

		$overrides = array( 'test_form' => false );
		$upload    = wp_handle_upload( $this->raw_file, $overrides );

		remove_all_filters( 'wp_handle_sideload_prefilter' );
		remove_all_filters( 'upload_dir' );

		if ( isset( $upload['error'] ) ) {
			$this->error->add( 'upload_error', $upload['error'] );

			return;
		}

		$fp = file( $upload['file'] );

		if ( ! $fp ) {
			$this->error->add(
				'upload_error',
				sprintf(
					'%s is not a valid file.',
					$upload['file']
				)
			);

			return;
		}

		$new_file_mtime = time();

		$this->uploaded_csv_file = array(
			'file_name'  => $new_filename,
			'path'       => $upload['file'],
			'url'        => $upload['url'],
			'mtime'      => $new_file_mtime,
			'date'       => wp_date( 'l F j, Y \a\t g:i:s a', $new_file_mtime ),
			'total_rows' => count( $fp ),
		);
	}

	/**
	 * Get uploaded CSV file.
	 *
	 * @since  [NEXT_VERSION]
	 * @return false|array
	 */
	public function get_uploaded_csv_file(): false|array {
		return $this->uploaded_csv_file;
	}

	/**
	 * Verify that the upload folder exists and is writable.
	 *
	 * @since  [NEXT_VERSION]
	 * @global WP_Filesystem_Base  $wp_filesystem  WordPress filesystem subclass.
	 * @return bool
	 */
	protected function verify_upload_folder(): bool {
		global $wp_filesystem;

		if ( ! $wp_filesystem instanceof WP_Filesystem_Base ) {
			$credentials = request_filesystem_credentials( site_url() );

			wp_filesystem( $credentials ); // @phpstan-ignore-line
		}

		$this->filesystem = $wp_filesystem;

		$upload_directory  = wp_upload_dir();
		$this->upload_path = trailingslashit( $upload_directory['basedir'] ) . $this->upload_subdir;
		$this->upload_url  = trailingslashit( $upload_directory['baseurl'] ) . $this->upload_subdir;

		if ( ! $this->filesystem->exists( $this->upload_path ) && ! $this->filesystem->mkdir( $this->upload_path ) ) {
			$this->error->add(
				'upload_error',
				sprintf(
					'Could not create directory %s.',
					$this->upload_path
				)
			);

			return false;
		}

		return true;
	}
}
