<?php

use PHPUnit\Framework\TestCase;

class ShardingBasicsTest extends TestCase {

	public function test_getters_presence() {
		$this->assertTrue(
			function_exists( 'get_shards' ),
			'all shards getter should be present'
		);
		$this->assertTrue(
			function_exists( 'shard_for' ),
			'shard getter should be present'
		);
	}
}
