<?php
/**
 * A dedicated class to export stuff to CSV files.
 *
 * @since      [NEXT_VERSION]
 * @package    Dream_Encode\WordPress_Plugin_Utils
 * @subpackage Dream_Encode\WordPress_Plugin_Utils\Export
 * @author     David Baumwald <david@dream-encode.com>
 */

namespace Dream_Encode\WordPress_Plugin_Utils\Export;

use WP_Error;
use WP_Filesystem_Base;

/**
 * A dedicated class to export stuff to CSV files.
 *
 * @since      [NEXT_VERSION]
 * @package    Dream_Encode\WordPress_Plugin_Utils
 * @subpackage Dream_Encode\WordPress_Plugin_Utils\Export
 * @author     David Baumwald <david@dream-encode.com>
 */
class Export_CSV {
	/**
	 * Filename.
	 *
	 * @since  [NEXT_VERSION]
	 * @access protected
	 * @var    string  $filename  The name of the CSV file.
	 */
	protected $filename = '';

	/**
	 * Export directory.
	 *
	 * @since  [NEXT_VERSION]
	 * @access protected
	 * @var    string  $export_directory  The name of export directory.
	 */
	protected $export_directory = '';

	/**
	 * Headers.
	 *
	 * @since  [NEXT_VERSION]
	 * @access protected
	 * @var    array  $header_row  The column headers for the CSV file.
	 */
	protected $header_row = array();

	/**
	 * Data.
	 *
	 * @since  [NEXT_VERSION]
	 * @access protected
	 * @var    array  $data_rows  The data rows for the CSV file.
	 */
	protected $data_rows = array();

	/**
	 * Errors.
	 *
	 * @since  [NEXT_VERSION]
	 * @access public
	 * @var    WP_Error  $error  Container for errors.
	 */
	public $error;

	/**
	 * Export_CSV constructor.
	 *
	 * @since  [NEXT_VERSION]
	 * @access public
	 * @param  array  $options  File options.
	 * @return void
	 */
	public function __construct( array $options = array() ) {
		$this->error = new WP_Error();

		$options = wp_parse_args(
			$options,
			array(
				'export_directory' => 'csv-exports',
				'filename'         => array(),
				'headers'          => array(),
				'data'             => array(),
			)
		);

		$this->set_export_directory( $options['export_directory'] );

		if ( ! empty( $options['filename'] ) ) {
			$this->set_filename( $options['filename'] );
		}

		if ( ! empty( $options['headers'] ) ) {
			$this->set_column_headers( $options['headers'] );
		}

		if ( ! empty( $options['data'] ) ) {
			$this->add_data_rows( $options['data'] );
		}
	}

	/**
	 * Set the export directory.
	 *
	 * @since  [NEXT_VERSION]
	 * @param  string  $export_directory  Export directory.
	 * @return void
	 */
	public function set_export_directory( $export_directory ) {
		$this->export_directory = $export_directory;
	}

	/**
	 * Specify the name for the CSV file.
	 *
	 * This method takes an array of string segments that will be concatenated into a single file name string.
	 * It is not necessary to include the file name suffix (.csv).
	 *
	 * Example:
	 *
	 *   array( 'Payment Activity', '2017-01-01', '2017-12-31' )
	 *
	 *   will become:
	 *
	 *   payment-activity_2017-01-01_2017-12-31.csv
	 *
	 * @since  [NEXT_VERSION]
	 * @param  array|string  $name_segments  One or more string segments that will comprise the CSV file name.
	 * @return void
	 */
	public function set_filename( $name_segments ) {
		if ( ! is_array( $name_segments ) ) {
			$name_segments = (array) $name_segments;
		}

		$name_segments = array_map(
			function( $segment ) {
				$segment = strtolower( $segment );
				$segment = str_replace( '_', '-', $segment );
				$segment = sanitize_file_name( $segment );
				$segment = str_replace( '.csv', '', $segment );

				return $segment;
			},
			$name_segments
		);

		if ( ! empty( $name_segments ) ) {
			$this->filename = implode( '_', $name_segments ) . '.csv';
		}
	}

	/**
	 * Set the first row of the CSV file as headers for each column.
	 *
	 * If used, this also determines how many columns each row should have. Note that, while optional, this method
	 * must be used before data rows are added.
	 *
	 * @since  [NEXT_VERSION]
	 * @param  array  $headers  The column header strings.
	 * @return bool  True if the column headers were successfully set. Otherwise false.
	 */
	public function set_column_headers( array $headers ) {
		if ( ! empty( $this->data_rows ) ) {
			$this->error->add(
				'csv_error',
				'Column headers cannot be set after data rows have been added.'
			);

			return false;
		}

		if ( ! array_is_list( $headers ) ) {
			$headers = array_values( $headers );
		}

		$this->header_row = array_map( 'sanitize_text_field', $headers );

		return true;
	}

	/**
	 * Add a single row of data to the CSV file.
	 *
	 * @since  [NEXT_VERSION]
	 * @param  array  $row  A single row of data.
	 * @return bool  True if the data row was successfully added. Otherwise false.
	 */
	public function add_row( array $row ) {
		$column_count = 0;

		if ( ! empty( $this->header_row ) ) {
			$column_count = count( $this->header_row );
		} elseif ( ! empty( $this->data_rows ) ) {
			$column_count = count( $this->data_rows[0] );
		}

		if ( $column_count && count( $row ) !== $column_count ) {
			$this->error->add(
				'csv_error',
				sprintf(
					'Could not add row because it has %1$d columns, when it should have %2$d.',
					absint( count( $row ) ),
					absint( $column_count )
				)
			);

			return false;
		}

		$this->data_rows[] = array_map( 'sanitize_text_field', $row );

		return true;
	}

	/**
	 * Wrapper method for adding multiple data rows at once.
	 *
	 * @since  [NEXT_VERSION]
	 * @param  array  $data  Array of data.
	 * @return void
	 */
	public function add_data_rows( array $data ) {
		foreach ( $data as $row ) {
			$result = $this->add_row( $row );

			if ( ! $result ) {
				break;
			}
		}
	}

	/**
	 * Escape an array of strings to be used in a CSV context.
	 *
	 * Malicious input can inject formulas into CSV files, opening up the possibility for phishing attacks,
	 * information disclosure, and arbitrary command execution.
	 *
	 * @see http://www.contextis.com/resources/blog/comma-separated-vulnerabilities/.
	 * @see https://hackerone.com/reports/72785.
	 *
	 * Note that this method is not recursive, so should only be used for individual data rows, not an entire data set.
	 *
	 * @since  [NEXT_VERSION]
	 * @param  array  $fields  Data.
	 * @return array
	 */
	public static function esc_csv( array $fields ) {
		$active_content_triggers = array( '=', '+', '-', '@' );

		/**
		 * Formulas that follow all common delimiters need to be escaped, because the user may choose any delimiter
		 * when importing a file into their spreadsheet program. Different delimiters are also used as the default
		 * in different locales. For example, Windows + Russian uses `;` as the delimiter, rather than a `,`.
		 *
		 * The file encoding can also effect the behavior; e.g., opening/importing as UTF-8 will enable newline
		 * characters as delimiters.
		 */
		$delimiters = array( ',', ';', ':', '|', '^', "\n", "\t", ' ' );

		foreach ( $fields as $index => $field ) {
			// Escape trigger characters at the start of a new field.
			$first_cell_character = mb_substr( $field, 0, 1 );
			$is_trigger_character = in_array( $first_cell_character, $active_content_triggers, true );
			$is_delimiter         = in_array( $first_cell_character, $delimiters, true );

			if ( $is_trigger_character || $is_delimiter ) {
				$field = "'" . $field;
			}

			// Escape trigger characters that follow delimiters.
			foreach ( $delimiters as $delimiter ) {
				foreach ( $active_content_triggers as $trigger ) {
					$field = str_replace( $delimiter . $trigger, $delimiter . "'" . $trigger, $field );
				}
			}

			$fields[ $index ] = $field;
		}

		return $fields;
	}

	/**
	 * Generate the contents of the CSV file.
	 *
	 * @since  [NEXT_VERSION]
	 * @return string|false
	 */
	protected function generate_file_content() {
		if ( empty( $this->data_rows ) ) {
			$this->error->add(
				'csv_error',
				'No data.'
			);

			return '';
		}

		ob_start();

		$csv = fopen( 'php://output', 'w' );

		if ( ! $csv ) {
			$this->error->add(
				'file_error',
				'Error creating file resource.'
			);

			return '';
		}

		if ( ! empty( $this->header_row ) ) {
			fputcsv( $csv, self::esc_csv( $this->header_row ) );
		}

		foreach ( $this->data_rows as $row ) {
			fputcsv( $csv, self::esc_csv( $row ) );
		}

		fclose( $csv );

		return ob_get_clean();
	}

	/**
	 * Output the CSV file, or a text file with error messages.
	 *
	 * @since  [NEXT_VERSION]
	 * @return void
	 */
	public function emit_file() {
		if ( ! $this->filename ) {
			$this->error->add(
				'csv_error',
				'Could not generate a CSV file without a file name.'
			);
		}

		$content = $this->generate_file_content();

		header( 'Cache-control: private' );
		header( 'Pragma: private' );
		header( 'Expires: Mon, 26 Jul 1997 05:00:00 GMT' );

		if ( ! empty( $this->error->get_error_messages() ) ) {
			header( 'Content-Type: text' );
			header( 'Content-Disposition: attachment; filename="error.txt"' );

			foreach ( $this->error->get_error_codes() as $code ) {
				foreach ( $this->error->get_error_messages( $code ) as $message ) {
					echo "$code: $message\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				}
			}

			die();
		}

		header( 'Content-Type: text/csv' );
		header( sprintf( 'Content-Disposition: attachment; filename="%s"', sanitize_file_name( $this->filename ) ) );

		echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		die();
	}

	/**
	 * Save the CSV file to a local directory.
	 *
	 * @since  [NEXT_VERSION]
	 * @global WP_Filesystem_Base  $wp_filesystem  WordPress filesystem subclass.
	 * @return false|array
	 */
	public function save_file() {
		if ( ! $this->filename ) {
			$this->error->add(
				'csv_error',
				'Could not generate a CSV file without a file name.'
			);

			return false;
		}

		global $wp_filesystem;

		if ( ! $wp_filesystem instanceof WP_Filesystem_Base ) {
			$credentials = request_filesystem_credentials( site_url() );

			wp_filesystem( $credentials ); // @phpstan-ignore-line
		}

		$upload_directory = wp_upload_dir();
		$upload_path      = trailingslashit( $upload_directory['baseurl'] ) . trailingslashit( $this->export_directory );

		if ( ! $wp_filesystem->exists( $upload_path ) && ! $wp_filesystem->mkdir( $upload_path ) ) {
			$this->error->add(
				'csv_error',
				sprintf(
					'Could not create directory %s.',
					$upload_path
				)
			);

			return false;
		}

		$full_path = trailingslashit( $upload_path ) . $this->filename;
		$content   = $this->generate_file_content();

		$file = fopen( $full_path, 'w' );

		if ( ! $file ) {
			$this->error->add(
				'file_error',
				'Error creating file resource.'
			);

			return false;
		}

		fwrite( $file, (string) $content );
		fclose( $file );

		return array(
			'filename' => $this->filename,
			'path'     => $full_path,
			'url'      => trailingslashit( $upload_directory['baseurl'] ) . trailingslashit( $this->export_directory ) . $this->filename,
		);
	}
}
