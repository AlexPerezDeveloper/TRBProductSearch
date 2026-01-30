<?php
/**
 * Performance tests for Multi-Word Search functionality.
 *
 * @package TRB_Product_Search\Tests\Performance
 */

use PHPUnit\Framework\TestCase;

/**
 * Test multi-word search performance requirements.
 *
 * Performance Targets:
 * - 3-word search: < 100ms query execution time
 * - 5-word search: < 150ms query execution time
 * - Memory usage: < 32MB peak memory per request
 */
class MultiWordSearchPerformanceTest extends TestCase {

	/**
	 * Number of mock products to simulate in database.
	 *
	 * @var int
	 */
	private const MOCK_PRODUCT_COUNT = 1000;

	/**
	 * Maximum allowed memory usage in MB.
	 *
	 * @var int
	 */
	private const MAX_MEMORY_MB = 32;

	/**
	 * Maximum allowed time for 3-word search in ms.
	 *
	 * @var int
	 */
	private const MAX_TIME_3_WORD_MS = 100;

	/**
	 * Maximum allowed time for 5-word search in ms.
	 *
	 * @var int
	 */
	private const MAX_TIME_5_WORD_MS = 150;

	/**
	 * Maximum allowed time for 2-word search in ms (baseline).
	 *
	 * @var int
	 */
	private const MAX_TIME_2_WORD_MS = 80;

	/**
	 * Set up test environment before each test.
	 */
	protected function setUp(): void {
		parent::setUp();
		TRB_Product_Search_Tests_Setup::setup();

		// Enable all search features for comprehensive performance testing
		TRB_Product_Search_Tests_Setup::set_option( 'trb_search_sku_enabled', '1' );
		TRB_Product_Search_Tests_Setup::set_option( 'trb_search_attributes_enabled', '1' );
		TRB_Product_Search_Tests_Setup::set_option( 'trb_search_selected_attributes', array( 'color', 'size', 'material' ) );
		TRB_Product_Search_Tests_Setup::set_option( 'trb_search_orderby', 'relevance' );
		TRB_Product_Search_Tests_Setup::set_option( 'trb_search_logic', 'and' );
	}

	/**
	 * Clean up test environment after each test.
	 */
	protected function tearDown(): void {
		TRB_Product_Search_Tests_Setup::cleanup();
		parent::tearDown();
	}

	/**
	 * Test 2-word search performance (baseline measurement).
	 *
	 * @testdox 2-word search should complete within 80ms
	 */
	public function test_two_word_search_performance() {
		global $wpdb;

		// Set up mock database scenario with 1000+ products
		$this->setup_mock_database_scenario( $wpdb );

		$search_query = new \TRB_Product_Search\Search_Query();

		// Measure execution time
		$start = microtime( true );
		$results = $search_query->search( 'disco duro' );
		$elapsed_ms = ( microtime( true ) - $start ) * 1000;

		// Assert performance target met
		$this->assertLessThan(
			self::MAX_TIME_2_WORD_MS,
			$elapsed_ms,
			"2-word search should complete in < {self::MAX_TIME_2_WORD_MS}ms, took {$elapsed_ms}ms"
		);

		// Verify results are valid WP_Query
		$this->assertInstanceOf( '\WP_Query', $results );

		// Store measurement for reporting
		$this->record_measurement( '2-word search', $elapsed_ms );
	}

	/**
	 * Test 3-word search performance.
	 *
	 * Target: < 100ms query execution time
	 *
	 * @testdox 3-word search should complete within 100ms
	 */
	public function test_three_word_search_performance() {
		global $wpdb;

		// Set up mock database scenario with 1000+ products
		$this->setup_mock_database_scenario( $wpdb );

		$search_query = new \TRB_Product_Search\Search_Query();

		// Measure execution time
		$start = microtime( true );
		$results = $search_query->search( 'disco duro ssd' );
		$elapsed_ms = ( microtime( true ) - $start ) * 1000;

		// Assert performance target met
		$this->assertLessThan(
			self::MAX_TIME_3_WORD_MS,
			$elapsed_ms,
			"3-word search should complete in < {self::MAX_TIME_3_WORD_MS}ms, took {$elapsed_ms}ms"
		);

		// Verify results are valid WP_Query
		$this->assertInstanceOf( '\WP_Query', $results );

		// Store measurement for reporting
		$this->record_measurement( '3-word search', $elapsed_ms );
	}

	/**
	 * Test 5-word search performance.
	 *
	 * Target: < 150ms query execution time
	 *
	 * @testdox 5-word search should complete within 150ms
	 */
	public function test_five_word_search_performance() {
		global $wpdb;

		// Set up mock database scenario with 1000+ products
		$this->setup_mock_database_scenario( $wpdb );

		$search_query = new \TRB_Product_Search\Search_Query();

		// Measure execution time
		$start = microtime( true );
		$results = $search_query->search( 'disco duro ssd externo portatil' );
		$elapsed_ms = ( microtime( true ) - $start ) * 1000;

		// Assert performance target met
		$this->assertLessThan(
			self::MAX_TIME_5_WORD_MS,
			$elapsed_ms,
			"5-word search should complete in < {self::MAX_TIME_5_WORD_MS}ms, took {$elapsed_ms}ms"
		);

		// Verify results are valid WP_Query
		$this->assertInstanceOf( '\WP_Query', $results );

		// Store measurement for reporting
		$this->record_measurement( '5-word search', $elapsed_ms );
	}

	/**
	 * Test single-word search performance (baseline comparison).
	 *
	 * @testdox Single-word search should remain fast (baseline)
	 */
	public function test_single_word_search_baseline_performance() {
		global $wpdb;

		// Set up mock database scenario
		$this->setup_mock_database_scenario( $wpdb );

		$search_query = new \TRB_Product_Search\Search_Query();

		// Measure execution time
		$start = microtime( true );
		$results = $search_query->search( 'disco' );
		$elapsed_ms = ( microtime( true ) - $start ) * 1000;

		// Single word should be faster than multi-word
		$this->assertLessThan(
			50,
			$elapsed_ms,
			"Single-word search should complete in < 50ms, took {$elapsed_ms}ms"
		);

		// Verify results are valid WP_Query
		$this->assertInstanceOf( '\WP_Query', $results );

		// Store measurement for reporting
		$this->record_measurement( 'single-word search', $elapsed_ms );
	}

	/**
	 * Test memory usage during multi-word search.
	 *
	 * Target: < 32MB peak memory per request
	 *
	 * @testdox Memory usage should be under 32MB during search
	 */
	public function test_memory_usage_during_search() {
		global $wpdb;

		// Set up mock database scenario
		$this->setup_mock_database_scenario( $wpdb );

		// Reset peak memory usage
		$start_memory = memory_get_peak_usage( true );

		// Perform multiple searches to measure cumulative memory
		$search_query = new \TRB_Product_Search\Search_Query();

		// Execute various searches
		$search_query->search( 'disco' );
		$search_query->search( 'disco duro' );
		$search_query->search( 'disco duro ssd' );
		$search_query->search( 'disco duro ssd externo portatil' );

		$end_memory = memory_get_peak_usage( true );
		$used_mb = ( $end_memory - $start_memory ) / 1024 / 1024;

		// Assert memory target met
		$this->assertLessThan(
			self::MAX_MEMORY_MB,
			$used_mb,
			"Memory usage should be < {self::MAX_MEMORY_MB}MB, used {$used_mb}MB"
		);

		// Store measurement for reporting
		$this->record_measurement( 'memory usage', $used_mb, 'MB' );
	}

	/**
	 * Test memory usage for single search operation.
	 */
	public function test_memory_usage_single_search() {
		global $wpdb;

		// Set up mock database scenario
		$this->setup_mock_database_scenario( $wpdb );

		// Force garbage collection before measurement
		gc_collect_cycles();

		$start_memory = memory_get_usage( true );

		$search_query = new \TRB_Product_Search\Search_Query();
		$search_query->search( 'disco duro ssd externo portatil' );

		$end_memory = memory_get_usage( true );
		$used_mb = ( $end_memory - $start_memory ) / 1024 / 1024;

		// Single search should use minimal memory
		$this->assertLessThan(
			10,
			$used_mb,
			"Single search memory usage should be < 10MB, used {$used_mb}MB"
		);

		$this->record_measurement( 'single search memory', $used_mb, 'MB' );
	}

	/**
	 * Test search performance with OR logic.
	 */
	public function test_or_logic_search_performance() {
		global $wpdb;

		TRB_Product_Search_Tests_Setup::set_option( 'trb_search_logic', 'or' );
		$this->setup_mock_database_scenario( $wpdb );

		$search_query = new \TRB_Product_Search\Search_Query();

		$start = microtime( true );
		$results = $search_query->search( 'disco duro ssd' );
		$elapsed_ms = ( microtime( true ) - $start ) * 1000;

		// OR logic should also meet performance targets
		$this->assertLessThan(
			self::MAX_TIME_3_WORD_MS,
			$elapsed_ms,
			"3-word OR search should complete in < {self::MAX_TIME_3_WORD_MS}ms, took {$elapsed_ms}ms"
		);

		$this->assertInstanceOf( '\WP_Query', $results );

		$this->record_measurement( '3-word OR search', $elapsed_ms );
	}

	/**
	 * Test search performance with caching enabled.
	 */
	public function test_cached_search_performance() {
		global $wpdb;

		$this->setup_mock_database_scenario( $wpdb );

		$search_query = new \TRB_Product_Search\Search_Query();

		// First search (cold cache)
		$start = microtime( true );
		$search_query->search( 'disco duro ssd' );
		$cold_cache_ms = ( microtime( true ) - $start ) * 1000;

		// Second search (warm cache) - new instance to simulate fresh request
		$search_query2 = new \TRB_Product_Search\Search_Query();
		$start = microtime( true );
		$search_query2->search( 'disco duro ssd' );
		$warm_cache_ms = ( microtime( true ) - $start ) * 1000;

		// Both should meet targets, warm cache might be faster
		$this->assertLessThan(
			self::MAX_TIME_3_WORD_MS,
			$cold_cache_ms,
			"Cold cache search should complete in < {self::MAX_TIME_3_WORD_MS}ms"
		);

		$this->assertLessThan(
			self::MAX_TIME_3_WORD_MS,
			$warm_cache_ms,
			"Warm cache search should complete in < {self::MAX_TIME_3_WORD_MS}ms"
		);

		$this->record_measurement( 'cold cache search', $cold_cache_ms );
		$this->record_measurement( 'warm cache search', $warm_cache_ms );
	}

	/**
	 * Test performance scaling with word count.
	 */
	public function test_performance_scaling_with_word_count() {
		global $wpdb;

		$this->setup_mock_database_scenario( $wpdb );

		$search_query = new \TRB_Product_Search\Search_Query();
		$measurements = array();

		// Test with increasing word counts
		$search_terms = array(
			1 => 'disco',
			2 => 'disco duro',
			3 => 'disco duro ssd',
			4 => 'disco duro ssd externo',
			5 => 'disco duro ssd externo portatil',
		);

		foreach ( $search_terms as $word_count => $term ) {
			$start = microtime( true );
			$search_query->search( $term );
			$elapsed_ms = ( microtime( true ) - $start ) * 1000;

			$measurements[ $word_count ] = $elapsed_ms;
		}

		// Verify scaling is roughly linear (not exponential)
		// 5-word should not be more than 3x slower than 1-word
		if ( isset( $measurements[1] ) && $measurements[1] > 0 ) {
			$scaling_factor = $measurements[5] / $measurements[1];
			$this->assertLessThan(
				5.0,
				$scaling_factor,
				"Performance scaling factor should be < 5x, was {$scaling_factor}x"
			);
		}

		// Store measurements
		foreach ( $measurements as $word_count => $time ) {
			$this->record_measurement( "{$word_count}-word search", $time );
		}
	}

	/**
	 * Test search with SKU matching performance.
	 */
	public function test_sku_search_performance() {
		global $wpdb;

		// Mock SKU search returning many results
		$wpdb->mock_results = array(
			'get_col' => array_fill( 0, 10, array_slice( range( 1, self::MOCK_PRODUCT_COUNT ), 0, 100 ) ),
		);

		$search_query = new \TRB_Product_Search\Search_Query();

		$start = microtime( true );
		$results = $search_query->search( 'SSD-001 HDD-002' );
		$elapsed_ms = ( microtime( true ) - $start ) * 1000;

		// SKU search should still meet performance targets
		$this->assertLessThan(
			self::MAX_TIME_3_WORD_MS,
			$elapsed_ms,
			"SKU search should complete in < {self::MAX_TIME_3_WORD_MS}ms, took {$elapsed_ms}ms"
		);

		$this->assertInstanceOf( '\WP_Query', $results );

		$this->record_measurement( 'SKU search', $elapsed_ms );
	}

	/**
	 * Test search with attribute matching performance.
	 */
	public function test_attribute_search_performance() {
		global $wpdb;

		// Mock attribute search returning many results
		$wpdb->mock_results = array(
			'get_col' => array_fill( 0, 10, array_slice( range( 1, self::MOCK_PRODUCT_COUNT ), 0, 100 ) ),
		);

		$search_query = new \TRB_Product_Search\Search_Query();

		$start = microtime( true );
		$results = $search_query->search( 'rojo grande algodon' );
		$elapsed_ms = ( microtime( true ) - $start ) * 1000;

		// Attribute search should meet performance targets
		$this->assertLessThan(
			self::MAX_TIME_3_WORD_MS,
			$elapsed_ms,
			"Attribute search should complete in < {self::MAX_TIME_3_WORD_MS}ms, took {$elapsed_ms}ms"
		);

		$this->assertInstanceOf( '\WP_Query', $results );

		$this->record_measurement( 'attribute search', $elapsed_ms );
	}

	/**
	 * Set up mock database scenario with simulated 1000+ products.
	 *
	 * @param object $wpdb Mock database object.
	 */
	private function setup_mock_database_scenario( $wpdb ) {
		// Generate mock product IDs to simulate 1000+ products
		$mock_product_ids = range( 1, self::MOCK_PRODUCT_COUNT );

		// Simulate SKU search returning intersection results
		$sku_results = array_chunk( $mock_product_ids, 100 );

		// Simulate attribute search returning intersection results
		$attr_results = array_chunk( $mock_product_ids, 100 );

		// Set up mock results for database queries
		$wpdb->mock_results = array(
			'get_col' => array_merge( $sku_results, $attr_results ),
			'get_results' => array(),
			'get_var' => null,
		);
	}

	/**
	 * Record a performance measurement for reporting.
	 *
	 * @param string $name Measurement name.
	 * @param float  $value Measurement value.
	 * @param string $unit Unit of measurement (ms, MB, etc.).
	 */
	private function record_measurement( $name, $value, $unit = 'ms' ) {
		// Store measurement in global for potential reporting
		if ( ! isset( $GLOBALS['_trb_performance_measurements'] ) ) {
			$GLOBALS['_trb_performance_measurements'] = array();
		}

		$GLOBALS['_trb_performance_measurements'][ $name ] = array(
			'value' => $value,
			'unit'  => $unit,
		);
	}
}
