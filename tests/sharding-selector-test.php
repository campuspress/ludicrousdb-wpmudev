<?php

use PHPUnit\Framework\TestCase;

class ShardingSelectorTest extends TestCase {

	public function test_dataset_setting() {
		$qs = new MultisiteDataset_QuerySelector();

		$this->assertFalse(
			$qs->has_dataset( 0 ),
			'there should be no registered datasets initially'
		);

		$qs->set_dataset( 0, '' );
		$this->assertTrue(
			$qs->has_dataset( 0 ),
			'initial handler registered'
		);
		$this->assertFalse(
			$qs->has_dataset( 0, true ),
			'initial handler is empty'
		);

		$qs->unset_dataset( 0 );
		$this->assertFalse(
			$qs->has_dataset( 0 ),
			'initial handler unset'
		);

		$qs->set_dataset( 0, 'test' );
		$this->assertTrue(
			$qs->has_dataset( 0 ),
			'handler registered'
		);
		$this->assertTrue(
			$qs->has_dataset( 0, true ),
			'handler is not empty'
		);
	}

	public function test_shard_update_fail_conditions() {
		global $wpdb;
		$qs = new MultisiteDataset_QuerySelector();

		$this->assertFalse(
			$qs->shard_update( 0, 'wat', $wpdb ),
			'shard update should fail for blog ID=0'
		);
		$this->assertFalse(
			$qs->shard_update( 'wat', 'wat', $wpdb ),
			'shard update should fail for blog ID=wat'
		);

		$this->assertFalse(
			$qs->shard_update( 1, 'wat', $wpdb ),
			'shard update should fail for invalid shard'
		);

		$blog_id = wpmu_create_blog( 'localhost', 'test-shard-mainline', 'GLOBAL', 0 );
		$this->assertTrue(
			is_numeric( $blog_id ),
			'blog should have been created'
		);
		$this->assertTrue(
			(int) $blog_id > 1,
			'blog should have been actually created'
		);
		$this->assertTrue(
			$qs->shard_update( $blog_id, MultisiteDataset_Sharder::GLOBAL_DATASET, $wpdb ),
			'blog shard update happy path'
		);
		msds_ldb_delete_blog( $blog_id );
	}
}
