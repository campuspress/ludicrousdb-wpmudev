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
			161,
			count( $shards ),
			'there should be exactly 161 shards (check tests/lib/bootstrap.php)'
		);

		$this->assertTrue(
			in_array( 'srv-two', $shards, true ),
			'srv-two should be one of the shards'
		);
	}

	public function test_blogid_sharding() {
		$expected = [
			0    => 'cfc',
			1    => 'c4c',
			2    => 'c81',
			3    => 'ecc',
			161  => 'cfc',
			13   => 'c51',
			12   => 'c20',
			1312 => '1ff',
		];
		foreach ( $expected as $blog_id => $shard ) {
			$this->assertEquals(
				$shard,
				$this->sharder->shard_for( $blog_id ),
				"expected {$shard} for {$blog_id}"
			);
		}
	}

	public function test_established_shard_naming() {
		$suite = [
			9910317,
			9920666,
			9931744,
			9937231,
			9947617,
			9958159,
			9961904,
			9990288,
			9995786,
		];
		foreach ( $suite as $test ) {
			$this->assertEquals(
				'abb',
				$this->sharder->shard_name( $test ),
				"invalid name for blog id {$test}"
			);
		}
	}
}
