<?php
/**
 * Test Importer and Parser Class.
 *
 * @package WPCalibrate\LlmsTxtManager\Tests
 */

declare(strict_types=1);

namespace WPCalibrate\LlmsTxtManager\Tests;

use WPCalibrate\LlmsTxtManager\Includes\Importer;

/**
 * Class Test_Importer_Parser
 */
class Test_Importer_Parser {

	/**
	 * Run all importer/parser tests.
	 *
	 * @return list<array{name: string, passed: bool, error: string}>
	 */
	public function run(): array {
		$results = [];

		$results[] = $this->test_strip_utf8_bom();
		$results[] = $this->test_parse_markdown_to_structured();

		return $results;
	}

	private function test_strip_utf8_bom(): array {
		$importer  = new Importer();
		$with_bom  = "\xEF\xBB\xBF# Title with BOM";
		$stripped  = $importer->strip_bom( $with_bom );

		$passed = ( $stripped === '# Title with BOM' );

		return [
			'name'   => 'Importer: Strips UTF-8 BOM cleanly',
			'passed' => $passed,
			'error'  => $passed ? '' : 'Failed to strip UTF-8 BOM correctly.',
		];
	}

	private function test_parse_markdown_to_structured(): array {
		$importer = new Importer();
		$markdown = "# Master Documentation\n\n> Comprehensive guide for LLM crawlers.\n\nAdditional architectural context.\n\n## Core API\n- [Auth Guide](https://example.com/auth): Token handling\n- [Endpoints](https://example.com/endpoints)\n\n## Optional\n- [Terms](https://example.com/terms)";

		$structured = $importer->parse_to_structured( $markdown );

		$correct_title   = ( 'Master Documentation' === $structured['title'] );
		$correct_summary = ( 'Comprehensive guide for LLM crawlers.' === $structured['summary'] );
		$has_sections    = ( count( $structured['sections'] ) >= 2 );
		$first_sec       = $structured['sections'][0] ?? [];
		$first_sec_title = ( 'Core API' === ( $first_sec['title'] ?? '' ) );
		$res_count       = count( $first_sec['resources'] ?? [] );

		$passed = $correct_title && $correct_summary && $has_sections && $first_sec_title && ( 2 === $res_count );

		return [
			'name'   => 'Importer/Parser: Parses raw Markdown into structured sections and resources',
			'passed' => $passed,
			'error'  => $passed ? '' : 'Failed to parse Markdown accurately into structured array.',
		];
	}
}
