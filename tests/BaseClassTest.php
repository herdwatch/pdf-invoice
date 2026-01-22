<?php

/**
 * Contains the BaseClassTest class.
 *
 * @copyright   Copyright (c) 2017 Attila Fulop
 * @author      Attila Fulop
 * @license     GPL
 *
 * @since       2017-12-15
 */

namespace Herdwatch\PdfInvoice\Tests;

use Herdwatch\PdfInvoice\InvoicePrinter;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
class BaseClassTest extends TestCase
{
    /**
     * @test
     */
    public function canBeInstantiated(): void
    {
        $invoice = new InvoicePrinter();

        $this->assertInstanceOf(InvoicePrinter::class, $invoice);
    }
}
