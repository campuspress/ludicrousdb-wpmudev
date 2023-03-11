<?php

use PHPUnit\Framework\TestCase;

class ShardingSharderMockTest extends TestCase {
	protected $sharder;

	public function setUp(): void {
		$db            = new MockDB(
			[
				'global'     => 'whatever',
				'mock-one'   => 'whatever',
				'mock-two'   => 'whatever',
				'mock-three' => 'whatever',
				'mock-four'  => 'whatever',
			]
		);
		$this->sharder = new MultisiteDataset_Sharder( $db );
	}

	public function test_get_shards() {
		$shards = $this->sharder->get_shards();
		$this->assertEquals(
			count( $shards ),
			4,
			'there should be exactly 4 shards (check tests/lib/bootstrap.php)'
		);

		$this->assertTrue(
			in_array( 'mock-two', $shards, true ),
			'mock-two should be one of the shards'
		);
	}

	public function test_blogid_sharding() {
		$expected = [
			0    => 'mock-one',
			1    => 'mock-two',
			2    => 'mock-three',
			3    => 'mock-four',
			161  => 'mock-two',
			13   => 'mock-two',
			12   => 'mock-one',
			1312 => 'mock-one',
		];
		foreach ( $expected as $blog_id => $shard ) {
			$this->assertEquals(
				$this->sharder->shard_for( $blog_id ),
				$shard,
				"expected {$shard} for {$blog_id}"
			);
		}
	}

	public function test_is_valid_shard() {
		$suite = [
			'wat'         => false,
			'global'      => true,
			'mock-one'    => true,
			'mock-twenty' => false,
		];
		foreach ( $suite as $shard => $expected ) {
			$actual = $this->sharder->is_valid_shard( $shard );
			$not    = $expected ? '' : 'not';
			$this->assertEquals(
				$actual,
				$expected,
				"shard {$shard} expected to {$not} be valid"
			);
		}
	}
}

class MockDB {
	public $ludicrous_servers = [];

	public function __construct( array $servers ) {
		$this->ludicrous_servers = $servers;
	}
}

class ShardingSharderWpdbTest extends TestCase {
	protected $sharder;

	public function setUp(): void {
		global $wpdb;

		$this->sharder = new MultisiteDataset_Sharder( $wpdb );
	}

	public function test_get_shards() {
		$shards = $this->sharder->get_shards();
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
				$this->sharder->shard_for( $blog_id ),
				$shard,
				"expected {$shard} for {$blog_id}"
			);
		}
	}
}
