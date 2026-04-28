<?php
/**
 * Tests for Export_CSV.
 *
 * @package Dream_Encode\WordPress_Plugin_Utils
 */

declare( strict_types = 1 );

namespace Dream_Encode\WordPress_Plugin_Utils\Tests;

use Dream_Encode\WordPress_Plugin_Utils\Export\Export_CSV;
use ReflectionProperty;
use WP_UnitTestCase;

/**
 * Test case for Export_CSV.
 */
class ExportCsvTest extends WP_UnitTestCase {

	/**
	 * Helper to read a protected property value via reflection.
	 *
	 * @param Export_CSV $instance  Object under test.
	 * @param string     $property  Property name.
	 * @return mixed
	 */
	private function get_protected( Export_CSV $instance, string $property ): mixed {
		$ref = new ReflectionProperty( Export_CSV::class, $property );
		$ref->setAccessible( true );

		return $ref->getValue( $instance );
	}

	/**
	 * Test that the export directory defaults to 'csv-exports' when not provided.
	 */
	public function test_constructor_defaults_export_directory_to_csv_exports(): void {
		$exporter = new Export_CSV();

		$this->assertSame( 'csv-exports', $this->get_protected( $exporter, 'export_directory' ) );
	}

	/**
	 * Test that a custom export directory is set when provided in options.
	 */
	public function test_constructor_sets_export_directory_from_options(): void {
		$exporter = new Export_CSV( array( 'export_directory' => 'my-exports' ) );

		$this->assertSame( 'my-exports', $this->get_protected( $exporter, 'export_directory' ) );
	}

	/**
	 * Test that the filename is set from the constructor options.
	 */
	public function test_constructor_sets_filename_from_options(): void {
		$exporter = new Export_CSV( array( 'filename' => 'my-report' ) );

		$this->assertSame( 'my-report.csv', $this->get_protected( $exporter, 'filename' ) );
	}

	/**
	 * Test that column headers are set from the constructor options.
	 */
	public function test_constructor_sets_headers_from_options(): void {
		$exporter = new Export_CSV( array( 'headers' => array( 'ID', 'Name', 'Email' ) ) );

		$this->assertSame( array( 'ID', 'Name', 'Email' ), $this->get_protected( $exporter, 'header_row' ) );
	}

	/**
	 * Test that data rows are set from the constructor options.
	 */
	public function test_constructor_sets_data_from_options(): void {
		$exporter = new Export_CSV(
			array(
				'data' => array(
					array( '1', 'Alice', 'alice@example.com' ),
				),
			)
		);

		$this->assertCount( 1, $this->get_protected( $exporter, 'data_rows' ) );
	}

	/**
	 * Test that the error property is initialised as a WP_Error instance.
	 */
	public function test_error_property_is_wp_error_instance(): void {
		$exporter = new Export_CSV();

		$this->assertInstanceOf( \WP_Error::class, $exporter->error );
	}

	/**
	 * Test that set_export_directory updates the export directory.
	 */
	public function test_set_export_directory(): void {
		$exporter = new Export_CSV();
		$exporter->set_export_directory( 'custom-dir' );

		$this->assertSame( 'custom-dir', $this->get_protected( $exporter, 'export_directory' ) );
	}

	/**
	 * Test that set_filename accepts a plain string and appends .csv.
	 */
	public function test_set_filename_with_string_input(): void {
		$exporter = new Export_CSV();
		$exporter->set_filename( 'my-report' );

		$this->assertSame( 'my-report.csv', $this->get_protected( $exporter, 'filename' ) );
	}

	/**
	 * Test that set_filename joins multiple segments with underscores.
	 */
	public function test_set_filename_with_multiple_segments(): void {
		$exporter = new Export_CSV();
		$exporter->set_filename( array( 'Payment Activity', '2024-01-01', '2024-12-31' ) );

		$this->assertSame( 'payment-activity_2024-01-01_2024-12-31.csv', $this->get_protected( $exporter, 'filename' ) );
	}

	/**
	 * Test that set_filename lowercases segments.
	 */
	public function test_set_filename_lowercases_segments(): void {
		$exporter = new Export_CSV();
		$exporter->set_filename( 'MY_REPORT' );

		$this->assertSame( 'my-report.csv', $this->get_protected( $exporter, 'filename' ) );
	}

	/**
	 * Test that set_filename strips any trailing .csv from the input.
	 */
	public function test_set_filename_strips_csv_extension_from_input(): void {
		$exporter = new Export_CSV();
		$exporter->set_filename( 'report.csv' );

		$this->assertSame( 'report.csv', $this->get_protected( $exporter, 'filename' ) );
	}

	/**
	 * Test that set_column_headers returns true on success.
	 */
	public function test_set_column_headers_returns_true(): void {
		$exporter = new Export_CSV();

		$this->assertTrue( $exporter->set_column_headers( array( 'ID', 'Name' ) ) );
	}

	/**
	 * Test that set_column_headers sets the header row.
	 */
	public function test_set_column_headers_sets_header_row(): void {
		$exporter = new Export_CSV();
		$exporter->set_column_headers( array( 'ID', 'Name', 'Email' ) );

		$this->assertSame( array( 'ID', 'Name', 'Email' ), $this->get_protected( $exporter, 'header_row' ) );
	}

	/**
	 * Test that set_column_headers returns false when data rows already exist.
	 */
	public function test_set_column_headers_returns_false_after_data_added(): void {
		$exporter = new Export_CSV();
		$exporter->add_row( array( '1', 'Alice' ) );

		$this->assertFalse( $exporter->set_column_headers( array( 'ID', 'Name' ) ) );
	}

	/**
	 * Test that set_column_headers adds an error when data rows exist.
	 */
	public function test_set_column_headers_adds_error_when_data_exists(): void {
		$exporter = new Export_CSV();
		$exporter->add_row( array( '1', 'Alice' ) );
		$exporter->set_column_headers( array( 'ID', 'Name' ) );

		$this->assertNotEmpty( $exporter->error->get_error_messages( 'csv_error' ) );
	}

	/**
	 * Test that add_row returns true when the row has the correct column count.
	 */
	public function test_add_row_returns_true_on_success(): void {
		$exporter = new Export_CSV();
		$exporter->set_column_headers( array( 'ID', 'Name' ) );

		$this->assertTrue( $exporter->add_row( array( '1', 'Alice' ) ) );
	}

	/**
	 * Test that add_row returns false when the row has the wrong column count.
	 */
	public function test_add_row_returns_false_with_wrong_column_count(): void {
		$exporter = new Export_CSV();
		$exporter->set_column_headers( array( 'ID', 'Name', 'Email' ) );

		$this->assertFalse( $exporter->add_row( array( '1', 'Alice' ) ) );
	}

	/**
	 * Test that add_row registers an error when column count mismatches.
	 */
	public function test_add_row_adds_error_on_column_mismatch(): void {
		$exporter = new Export_CSV();
		$exporter->set_column_headers( array( 'ID', 'Name', 'Email' ) );
		$exporter->add_row( array( '1', 'Alice' ) );

		$this->assertNotEmpty( $exporter->error->get_error_messages( 'csv_error' ) );
	}

	/**
	 * Test that add_data_rows appends all valid rows.
	 */
	public function test_add_data_rows_adds_all_rows(): void {
		$exporter = new Export_CSV();
		$exporter->add_data_rows(
			array(
				array( '1', 'Alice' ),
				array( '2', 'Bob' ),
				array( '3', 'Carol' ),
			)
		);

		$this->assertCount( 3, $this->get_protected( $exporter, 'data_rows' ) );
	}

	/**
	 * Test that add_data_rows stops adding rows after a column mismatch.
	 */
	public function test_add_data_rows_stops_on_column_mismatch(): void {
		$exporter = new Export_CSV();
		$exporter->set_column_headers( array( 'ID', 'Name' ) );
		$exporter->add_data_rows(
			array(
				array( '1', 'Alice' ),
				array( '2' ),
				array( '3', 'Carol' ),
			)
		);

		$this->assertCount( 1, $this->get_protected( $exporter, 'data_rows' ) );
	}

	/**
	 * Test that esc_csv prefixes trigger characters at the start of a field with a single quote.
	 *
	 * @dataProvider data_esc_csv_trigger_characters
	 *
	 * @param string $trigger  The trigger character to test.
	 */
	public function test_esc_csv_escapes_trigger_character_at_field_start( string $trigger ): void {
		$result = Export_CSV::esc_csv( array( $trigger . 'value' ) );

		$this->assertSame( "'" . $trigger . 'value', $result[0] );
	}

	/**
	 * Data provider for trigger character escaping.
	 *
	 * @return array<array<string>>
	 */
	public static function data_esc_csv_trigger_characters(): array {
		return array(
			'equals sign' => array( '=' ),
			'plus sign'   => array( '+' ),
			'minus sign'  => array( '-' ),
			'at sign'     => array( '@' ),
		);
	}

	/**
	 * Test that esc_csv does not modify fields that do not start with trigger characters.
	 */
	public function test_esc_csv_does_not_modify_safe_values(): void {
		$fields = array( 'Alice', 'bob@example.com', '42', 'Normal text' );
		$result = Export_CSV::esc_csv( $fields );

		$this->assertSame( $fields, $result );
	}

	/**
	 * Test that esc_csv escapes trigger characters that follow a delimiter inside a field.
	 */
	public function test_esc_csv_escapes_trigger_after_delimiter(): void {
		$result = Export_CSV::esc_csv( array( 'safe,=formula' ) );

		$this->assertSame( "safe,'=formula", $result[0] );
	}
}

