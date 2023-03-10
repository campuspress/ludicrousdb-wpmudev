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
}
