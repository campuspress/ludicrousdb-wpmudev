<?php

use PHPUnit\Framework\TestCase;

class MockCallbacksDB {
	public $ludicrous_callbacks = [];

	public function __construct() {
		$this->ludicrous_callbacks = [
			'dataset' => [],
		];
	}

	public function add_callback( $callback, $group = 'dataset' ) {
		$this->ludicrous_callbacks[ $group ][] = $callback;
	}
}

class ShardingDatasetTest extends TestCase {

	public function test_remove_query_selector() {
		$msd = new MultisiteDataset( new MockCallbacksDB() );
		$this->assertFalse(
			$msd->remove_query_selector(),
			'initial query selector removal should fail because we never added it'
		);

		$msd->add_query_selector();
		$this->assertTrue(
			$msd->remove_query_selector(),
			'query selector removal should succeed after we added it'
		);
	}

	public function test_create_new_blog() {
		global $wpdb;
		$msd = new MultisiteDataset( $wpdb );
		$msd->init();

		$blog_id = wpmu_create_blog( 'localhost', 'test', 'test', 0 );
		$this->assertTrue(
			is_numeric( $blog_id ),
			'blog should have been created'
		);
		$this->assertTrue(
			(int) $blog_id > 1,
			'blog should have been actually created'
		);
		$sel = $msd->get_selector();

		$this->assertTrue(
			$sel->has_handler( $blog_id, true ),
			'initial blog should have handler set'
		);
	}
}
