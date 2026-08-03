<?php
/**
 * Base test case.
 *
 * @package ucsc-communications-functionality
 */

namespace UCSC\UcscCommunicationsFunctionality\Tests;

use PHPUnit\Framework\TestCase as PHPUnitTestCase;

/**
 * Resets the doubles between tests while preserving the hook registry.
 *
 * Hook registrations happen once, at include time, in the bootstrap - they are
 * permanent facts about the loaded plugin rather than per-test state, so they
 * survive the reset.
 */
abstract class TestCase extends PHPUnitTestCase {

	/**
	 * Reset the doubles.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		ucsccomms_test_reset( true );
	}
}
