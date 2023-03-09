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

		$previous_id    = 0;
		$previous_shard = '';

		$ids_to_remove = [];

		for ( $i = 0; $i < 4; $i++ ) {
			$blog_id = wpmu_create_blog( 'localhost', "test-${i}", 'test', 0 );
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

			$shard = $sel->get_handler( $blog_id );
			$this->assertTrue(
				! empty( $shard ),
				"shard [{$shard}] is present"
			);

			$this->assertNotEquals(
				$blog_id,
				$previous_id,
				'new blog ID should not be equal to previous blog ID'
			);
			$this->assertNotEquals(
				$shard,
				$previous_shard,
				'new blog shard should not be equal to previous blog shard'
			);

			$previous_id    = $blog_id;
			$previous_shard = $shard;

			$ids_to_remove[] = $blog_id;
		}

		foreach ( $ids_to_remove as $bid ) {
			$this->_kill( $bid );
		}
	}

	public function test_switch_to_blog() {
		$sel     = $GLOBALS[ LDB_MULTISITE_DATASET ]->get_selector();
		$blog_id = wpmu_create_blog( 'localhost', 'test-shard-switching', 'SHARDZ', 0 );
		$this->assertTrue(
			is_numeric( $blog_id ),
			'blog should have been created'
		);
		$this->assertTrue(
			(int) $blog_id > 1,
			'blog should have been actually created'
		);

		$sel->unset_handler( $blog_id );
		$this->assertFalse(
			$sel->has_handler( $blog_id ),
			"handler for {$blog_id} should have been unset"
		);
		switch_to_blog( $blog_id );
		$this->assertEquals(
			'SHARDZ',
			get_option( 'blogname' ),
			'using proper shard'
		);
		restore_current_blog();
		$this->assertTrue(
			$sel->has_handler( $blog_id, true ),
			"handler for {$blog_id} should have been set now"
		);
		$this->_kill( $blog_id );
	}

	private function _kill( $bid ) {
		global $wpdb;
		$tables = [
			"wptests_{$bid}_commentmeta",
			"wptests_{$bid}_comments",
			"wptests_{$bid}_links",
			"wptests_{$bid}_options",
			"wptests_{$bid}_postmeta",
			"wptests_{$bid}_posts",
			"wptests_{$bid}_term_relationships",
			"wptests_{$bid}_term_taxonomy",
			"wptests_{$bid}_termmeta",
			"wptests_{$bid}_terms",
		];
		$wpdb->query( "DELETE FROM {$wpdb->blogs} WHERE blog_id=$bid" );
		foreach ( $tables as $table ) {
			$wpdb->query( "DROP TABLE {$table}" );
		}
	}
}
