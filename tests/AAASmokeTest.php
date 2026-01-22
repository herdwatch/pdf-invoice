<?php

/**
 * Contains the AAASmokeTest.php class.
 *
 * @copyright   Copyright (c) 2017 Attila Fulop
 * @author      Attila Fulop
 * @license     GPL
 *
 * @since       2017-12-15
 */

namespace Herdwatch\PdfInvoice\Tests;

use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
class AAASmokeTest extends TestCase
{
    public const string MIN_PHP_VERSION = '7.4.0';

    /**
     * Very Basic smoke test case for testing against parse errors, etc.
     *
     * @test
     */
    public function smoke(): void
    {
        $this->assertTrue(true);
    }
}
