<?php

namespace Herdwatch\PdfInvoice\Utils\languages;

use Herdwatch\PdfInvoice\Utils\LangInterface;

readonly class EsLang implements LangInterface
{
    public const string LANG_NAME = 'es';
    public const array LANG = [
        'number' => 'Referencia',
        'date' => 'Fecha',
        'due' => 'Fecha de vencimiento',
        'time' => 'Fecha de factura',
        'payment' => 'Fecha de pago',
        'to' => 'Facturación a',
        'from' => 'Nuestra información',
        'product' => 'Producto',
        'qty' => 'Cantidad',
        'price' => 'Precio',
        'discount' => 'Descuento',
        'vat' => 'Impuestos',
        'total' => 'Total de',
        'page' => 'Página',
        'page_of' => 'de',
    ];
}
