<?php
/**
 * Standalone automated test runner.
 *
 * Runs test suites for Generator, Validator, and Importer/Parser with full mock isolation.
 *
 * @package WPCalibrate\LlmsTxtManager\Tests
 */

declare(strict_types=1);

// Mock WordPress functions if running outside WordPress.
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/../' );
}

if ( ! function_exists( 'esc_html' ) ) {
	function esc_html( string $text ): string {
		return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_attr' ) ) {
	function esc_attr( string $text ): string {
		return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( '__' ) ) {
	function __( string $text, string $domain = 'default' ): string {
		return $text;
	}
}

if ( ! function_exists( '_x' ) ) {
	function _x( string $text, string $context, string $domain = 'default' ): string {
		return $text;
	}
}

if ( ! function_exists( 'esc_html__' ) ) {
	function esc_html__( string $text, string $domain = 'default' ): string {
		return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_attr__' ) ) {
	function esc_attr__( string $text, string $domain = 'default' ): string {
		return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_html_e' ) ) {
	function esc_html_e( string $text, string $domain = 'default' ): void {
		echo htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_attr_e' ) ) {
	function esc_attr_e( string $text, string $domain = 'default' ): void {
		echo htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'apply_filters' ) ) {
	function apply_filters( string $tag, mixed $value, mixed ...$args ): mixed {
		return $value;
	}
}

if ( ! function_exists( 'wp_parse_url' ) ) {
	function wp_parse_url( string $url, int $component = -1 ): mixed {
		return parse_url( $url, $component );
	}
}

if ( ! function_exists( 'get_bloginfo' ) ) {
	function get_bloginfo( string $show = 'name' ): string {
		return 'name' === $show ? 'WPCalibrate Testing' : 'Enterprise WordPress Development';
	}
}

if ( ! function_exists( 'wp_generate_password' ) ) {
	function wp_generate_password( int $length = 12, bool $special_chars = true ): string {
		return substr( md5( (string) mt_rand() ), 0, $length );
	}
}

if ( ! function_exists( 'home_url' ) ) {
	function home_url( string $path = '' ): string {
		return 'https://example.com' . ( $path ? '/' . ltrim( $path, '/' ) : '' );
	}
}

if ( ! function_exists( 'get_post' ) ) {
	function get_post( int|object|null $post = null ): ?object {
		return null;
	}
}

if ( ! function_exists( 'get_option' ) ) {
	function get_option( string $option, mixed $default = false ): mixed {
		return $default;
	}
}

// Require plugin classes.
require_once __DIR__ . '/../includes/class-generator.php';
require_once __DIR__ . '/../includes/class-validator.php';
require_once __DIR__ . '/../includes/class-importer.php';

// Require test classes.
require_once __DIR__ . '/test-generator.php';
require_once __DIR__ . '/test-validator.php';
require_once __DIR__ . '/test-importer-parser.php';

use WPCalibrate\LlmsTxtManager\Tests\Test_Generator;
use WPCalibrate\LlmsTxtManager\Tests\Test_Importer_Parser;
use WPCalibrate\LlmsTxtManager\Tests\Test_Validator;

$suites = [
	'Generator Test Suite'        => new Test_Generator(),
	'Validator Test Suite'        => new Test_Validator(),
	'Importer & Parser Test Suite' => new Test_Importer_Parser(),
];

$total_tests  = 0;
$total_passed = 0;
$failures     = [];

echo "========================================================\n";
echo "   WPCalibrate LLMs.txt Manager - Test Suite Runner\n";
echo "========================================================\n\n";

foreach ( $suites as $suite_name => $suite ) {
	echo "--- Running {$suite_name} ---\n";
	$results = $suite->run();

	foreach ( $results as $r ) {
		$total_tests++;
		if ( $r['passed'] ) {
			$total_passed++;
			echo "  [PASS] {$r['name']}\n";
		} else {
			$failures[] = $r;
			echo "  [FAIL] {$r['name']}\n";
			echo "         Error: {$r['error']}\n";
		}
	}
	echo "\n";
}

echo "========================================================\n";
echo "Test Execution Summary:\n";
echo "  Total Tests:  {$total_tests}\n";
echo "  Total Passed: {$total_passed}\n";
echo "  Total Failed: " . count( $failures ) . "\n";
echo "========================================================\n";

if ( count( $failures ) > 0 ) {
	echo "\nFAILED TESTS DETAIL:\n";
	foreach ( $failures as $f ) {
		echo "- {$f['name']}: {$f['error']}\n";
	}
	exit( 1 );
}

echo "\nAll test suites passed successfully!\n";
exit( 0 );
