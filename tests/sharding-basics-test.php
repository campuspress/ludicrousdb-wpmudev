<?php

use PHPUnit\Framework\TestCase;

class ShardingBasicsTest extends TestCase {

	public function test_getters_presence() {
		$this->assertTrue(
			class_exists('MultisiteDataset_Sharder'),
			'sharder class should be present'
		);
	}
}
