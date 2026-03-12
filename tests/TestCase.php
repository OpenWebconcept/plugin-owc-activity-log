<?php

declare(strict_types=1);

/**
 * Base test case.
 *
 * @package OWC_Activity_Log
 * @author  Yard | Digital Agency
 * @since   1.0.0
 */

namespace Tests;

use PHPUnit\Framework\TestCase as PHPUnitTestCase;

/**
 * @since 1.0.0
 */
class TestCase extends PHPUnitTestCase
{
	protected function setUp(): void
	{
		parent::setUp();
		\WP_Mock::setUp();
	}
}
