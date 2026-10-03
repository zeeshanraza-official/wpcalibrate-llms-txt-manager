<?php
/**
 * Test Generator Class.
 *
 * @package WPCalibrate\LlmsTxtManager\Tests
 */

declare(strict_types=1);

namespace WPCalibrate\LlmsTxtManager\Tests;

use WPCalibrate\LlmsTxtManager\Includes\Generator;

/**
 * Class Test_Generator
 */
class Test_Generator {

	/**
	 * Run all generator tests.
	 *
	 * @return list<array{name: string, passed: bool, error: string}>
	 */
	public function run(): array {
		$results = [];

		$results[] = $this->test_required_h1_and_summary();
		$results[] = $this->test_deterministic_sections_and_resources();
		$results[] = $this->test_unicode_support();
		$results[] = $this->test_optional_section();

		return $results;
	}

	private function test_required_h1_and_summary(): array {
		$generator = new Generator();
		$data      = [
			'title'           => 'Acme Documentation',
			'summary'         => 'High performance API platform for developers.',
			'additional_info' => 'Built for autonomy and scale.',
			'sections'        => [],
		];

		$output = $generator->generate( $data );

		$has_h1      = str_contains( $output, '# Acme Documentation' );
		$has_summary = str_contains( $output, '> High performance API platform for developers.' );
		$has_info    = str_contains( $output, 'Built for autonomy and scale.' );

		$passed = $has_h1 && $has_summary && $has_info;

		return [
			'name'   => 'Generator: Produces required H1 and summary blockquote',
			'passed' => $passed,
			'error'  => $passed ? '' : 'Generated output missing H1 or summary. Output: ' . substr( $output, 0, 100 ),
		];
	}

	private function test_deterministic_sections_and_resources(): array {
		$generator = new Generator();
		$data      = [
			'title'    => 'Acme',
			'sections' => [
				[
					'title'     => 'API Reference',
					'resources' => [
						[
							'title'       => 'Authentication',
							'url'         => 'https://example.com/api/auth',
							'description' => 'Bearer token guide',
						],
						[
							'title'       => 'Endpoints',
							'url'         => 'https://example.com/api/endpoints',
							'description' => '',
						],
					],
				],
			],
		];

		$output1 = $generator->generate( $data );
		$output2 = $generator->generate( $data );

		$has_sec  = str_contains( $output1, '## API Reference' );
		$has_res1 = str_contains( $output1, '- [Authentication](https://example.com/api/auth): Bearer token guide' );
		$has_res2 = str_contains( $output1, '- [Endpoints](https://example.com/api/endpoints)' );
		$is_det   = ( $output1 === $output2 );

		$passed = $has_sec && $has_res1 && $has_res2 && $is_det;

		return [
			'name'   => 'Generator: Formats H2 sections, resource links, and descriptions deterministically',
			'passed' => $passed,
			'error'  => $passed ? '' : 'Failed resource formatting or non-deterministic output.',
		];
	}

	private function test_unicode_support(): array {
		$generator = new Generator();
		$data      = [
			'title'    => 'Café & Résumé 🚀',
			'summary'  => 'Internationalisation 日本語 / العربية testing.',
			'sections' => [],
		];

		$output = $generator->generate( $data );
		$passed = str_contains( $output, '# Café & Résumé 🚀' ) && str_contains( $output, '日本語 / العربية' );

		return [
			'name'   => 'Generator: Preserves Unicode and emojis cleanly',
			'passed' => $passed,
			'error'  => $passed ? '' : 'Unicode characters were corrupted.',
		];
	}

	private function test_optional_section(): array {
		$generator = new Generator();
		$data      = [
			'title'    => 'Project',
			'sections' => [
				[
					'title'     => 'Optional',
					'resources' => [
						[
							'title'       => 'Legal Notice',
							'url'         => 'https://example.com/legal',
							'description' => 'Terms of service',
						],
					],
				],
			],
		];

		$output = $generator->generate( $data );
		$passed = str_contains( $output, '## Optional' ) && str_contains( $output, '- [Legal Notice](https://example.com/legal): Terms of service' );

		return [
			'name'   => 'Generator: Supports Optional section per llms.txt standard',
			'passed' => $passed,
			'error'  => $passed ? '' : 'Optional section was not generated correctly.',
		];
	}
}
