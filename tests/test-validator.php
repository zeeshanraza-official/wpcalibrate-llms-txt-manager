<?php
/**
 * Test Validator Class.
 *
 * @package WPCalibrate\LlmsTxtManager\Tests
 */

declare(strict_types=1);

namespace WPCalibrate\LlmsTxtManager\Tests;

use WPCalibrate\LlmsTxtManager\Includes\Validator;

/**
 * Class Test_Validator
 */
class Test_Validator {

	/**
	 * Run all validator tests.
	 *
	 * @return list<array{name: string, passed: bool, error: string}>
	 */
	public function run(): array {
		$results = [];

		$results[] = $this->test_reject_empty_content();
		$results[] = $this->test_reject_missing_h1();
		$results[] = $this->test_reject_multiple_h1();
		$results[] = $this->test_reject_invalid_url();
		$results[] = $this->test_reject_raw_html();
		$results[] = $this->test_accept_valid_document();
		$results[] = $this->test_duplicate_resource_detection();

		return $results;
	}

	private function test_reject_empty_content(): array {
		$validator = new Validator();
		$res       = $validator->validate( "   \n\t  " );

		$passed = ( false === $res['valid'] ) && count( $res['errors'] ) > 0;

		return [
			'name'   => 'Validator: Rejects empty or whitespace-only content',
			'passed' => $passed,
			'error'  => $passed ? '' : 'Validator unexpectedly passed empty content.',
		];
	}

	private function test_reject_missing_h1(): array {
		$validator = new Validator();
		$markdown  = "## Main Section\n- [Test](https://example.com/test)";
		$res       = $validator->validate( $markdown );

		$passed = ( false === $res['valid'] );

		return [
			'name'   => 'Validator: Rejects content missing required H1 heading',
			'passed' => $passed,
			'error'  => $passed ? '' : 'Validator allowed content without H1 heading.',
		];
	}

	private function test_reject_multiple_h1(): array {
		$validator = new Validator();
		$markdown  = "# Project Title 1\n# Project Title 2\n## Core\n- [Link](https://example.com)";
		$res       = $validator->validate( $markdown );

		$passed = ( false === $res['valid'] );

		return [
			'name'   => 'Validator: Rejects content containing multiple H1 headings',
			'passed' => $passed,
			'error'  => $passed ? '' : 'Validator allowed multiple H1 headings.',
		];
	}

	private function test_reject_invalid_url(): array {
		$validator = new Validator();
		$markdown  = "# Project\n## Section\n- [Invalid](ftp://example.com/file)\n- [Relative](/about)";
		$res       = $validator->validate( $markdown );

		$passed = ( false === $res['valid'] );

		return [
			'name'   => 'Validator: Rejects non-HTTP/HTTPS or relative URLs',
			'passed' => $passed,
			'error'  => $passed ? '' : 'Validator allowed non-HTTP/HTTPS URLs.',
		];
	}

	private function test_reject_raw_html(): array {
		$validator = new Validator();
		$markdown  = "# Project\n<script>alert(1);</script>\n## Section\n- [Valid](https://example.com)";
		$res       = $validator->validate( $markdown );

		$passed = ( false === $res['valid'] );

		return [
			'name'   => 'Validator: Detects and rejects dangerous raw HTML tags',
			'passed' => $passed,
			'error'  => $passed ? '' : 'Validator allowed raw HTML script tags.',
		];
	}

	private function test_accept_valid_document(): array {
		$validator = new Validator();
		$markdown  = "# Official Project\n\n> Comprehensive developer resources.\n\n## Core Documentation\n- [Getting Started](https://example.com/start): Quick onboarding\n- [API Guide](https://example.com/api)\n\n## Optional\n- [Terms](https://example.com/terms)";
		$res       = $validator->validate( $markdown );

		$passed = ( true === $res['valid'] ) && ( 0 === count( $res['errors'] ) );

		return [
			'name'   => 'Validator: Accepts standard-compliant llms.txt document',
			'passed' => $passed,
			'error'  => $passed ? '' : 'Validator rejected a compliant document. Errors: ' . implode( ', ', $res['errors'] ),
		];
	}

	private function test_duplicate_resource_detection(): array {
		$validator = new Validator();
		$markdown  = "# Project\n> Summary\n## Section 1\n- [Link 1](https://example.com/doc)\n## Section 2\n- [Link 2](https://example.com/doc/)";
		$res       = $validator->validate( $markdown );

		$has_warning = false;
		foreach ( $res['warnings'] as $w ) {
			if ( str_contains( strtolower( $w ), 'duplicate' ) ) {
				$has_warning = true;
				break;
			}
		}

		return [
			'name'   => 'Validator: Identifies duplicate resource URLs as non-fatal warnings',
			'passed' => $has_warning && $res['valid'],
			'error'  => $has_warning ? '' : 'Validator did not warn about duplicate resource URLs.',
		];
	}
}
