<?php

use PHPUnit\Framework\TestCase;

class ShardingGettersTest extends TestCase {

	public function test_get_shards() {
		global $wpdb;
		$shards = get_shards( $wpdb );
		$this->assertEquals(
			count( $shards ),
			4,
			'there should be exactly 4 shards (check tests/lib/bootstrap.php)'
		);

		$this->assertTrue(
			in_array( 'srv-two', $shards, true ),
			'srv-two should be one of the shards'
		);
	}

	public function test_blogid_sharding() {
		global $wpdb;
		$expected = [
			0    => 'srv-one',
			1    => 'srv-two',
			2    => 'srv-three',
			3    => 'srv-four',
			161  => 'srv-two',
			13   => 'srv-two',
			12   => 'srv-one',
			1312 => 'srv-one',
		];
		foreach ( $expected as $blog_id => $shard ) {
			$this->assertEquals(
				shard_for( $blog_id, $wpdb ),
				$shard,
				"expected {$shard} for {$blog_id}"
			);
		}
	}
}
