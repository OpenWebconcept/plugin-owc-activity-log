<?php
/**
 * Pest configuration.
 *
 * @package OWC_Activity_Log
 * @author  Yard | Digital Agency
 * @since   1.0.0
 */

declare(strict_types=1);

uses( Tests\TestCase::class )->in( 'Unit' );

beforeEach(
	function () {
		WP_Mock::setUp();
	}
);

afterEach(
	function () {
		WP_Mock::tearDown();
		Mockery::close();
	}
);
