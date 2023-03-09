<?php

use PHPUnit\Framework\TestCase;

class ShardingSelectorTest extends TestCase {

	public function test_handler_setting() {
		$qs = new MultisiteDataset_QuerySelector();

		$this->assertFalse(
			$qs->has_handler( 0 ),
			'there should be no registered datasets initially'
		);

		$qs->set_handler( 0, '' );
		$this->assertTrue(
			$qs->has_handler( 0 ),
			'initial handler registered'
		);
		$this->assertFalse(
			$qs->has_handler( 0, true ),
			'initial handler is empty'
		);

		$qs->unset_handler( 0 );
		$this->assertFalse(
			$qs->has_handler( 0 ),
			'initial handler unset'
		);

		$qs->set_handler( 0, 'test' );
		$this->assertTrue(
			$qs->has_handler( 0 ),
			'handler registered'
		);
		$this->assertTrue(
			$qs->has_handler( 0, true ),
			'handler is not empty'
		);
	}
}
