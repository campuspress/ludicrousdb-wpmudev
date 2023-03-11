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
			$blog_id = wpmu_create_blog( 'localhost', "test-{$i}", 'test', 0 );
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
				$sel->has_dataset( $blog_id, true ),
				'initial blog should have dataset set'
			);

			$shard = $sel->get_dataset( $blog_id );
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
			msds_ldb_delete_blog( $bid );
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

		$sel->unset_dataset( $blog_id );
		$this->assertFalse(
			$sel->has_dataset( $blog_id ),
			"dataset for {$blog_id} should have been unset"
		);
		switch_to_blog( $blog_id );
		$this->assertEquals(
			'SHARDZ',
			get_option( 'blogname' ),
			'using proper shard'
		);
		restore_current_blog();
		$this->assertTrue(
			$sel->has_dataset( $blog_id, true ),
			"dataset for {$blog_id} should have been set now"
		);
		msds_ldb_delete_blog( $blog_id );
	}
}
