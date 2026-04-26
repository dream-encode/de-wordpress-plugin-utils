<?php
/**
 * Test plugin i18n fixture.
 *
 * @package Dream_Encode\WordPress_Plugin_Utils
 */

declare( strict_types = 1 );

namespace Dream_Encode\WordPress_Plugin_Utils\Tests\Fixtures;

use Dream_Encode\WordPress_Plugin_Utils\Abstracts\Abstract_Plugin_I18n;

/**
 * Concrete implementation of Abstract_Plugin_I18n for testing.
 */
class Test_Plugin_I18n extends Abstract_Plugin_I18n {

	private string $text_domain;

	private string $languages_path;

	/**
	 * Constructor.
	 *
	 * @param string $text_domain Text domain.
	 * @param string $languages_path Languages path.
	 */
	public function __construct( string $text_domain, string $languages_path ) {
		$this->text_domain    = $text_domain;
		$this->languages_path = $languages_path;
	}

	protected function get_text_domain(): string {
		return $this->text_domain;
	}

	protected function get_languages_path(): string {
		return $this->languages_path;
	}
}