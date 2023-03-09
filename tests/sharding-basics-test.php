<?php

use PHPUnit\Framework\TestCase;

class ShardingBasicsTest extends TestCase {

	public function test_getters_presence() {
		$this->assertTrue(
			class_exists( 'MultisiteDataset_Sharder' ),
			'sharder class should be present'
		);
		$this->assertTrue(
			class_exists( 'MultisiteDataset_QuerySelector' ),
			'query selector class should be present'
		);
		$this->assertTrue(
			class_exists( 'MultisiteDataset' ),
			'driver class should be present'
		);
	}
}
