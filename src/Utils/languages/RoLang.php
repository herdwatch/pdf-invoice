<?php

namespace Herdwatch\PdfInvoice\Utils\languages;

use Herdwatch\PdfInvoice\Utils\LangInterface;

readonly class RoLang implements LangInterface
{
    public const string LANG_NAME = 'ro';
    public const array LANG = [
        'number' => 'Nr. facturii',
        'date' => 'Data facturii',
        'time' => 'Ora facturii',
        'due' => 'Data scadenței',
        'payment' => 'Data de plată',
        'to' => 'Client',
        'from' => 'Furnizor',
        'product' => 'Produs',
        'qty' => 'Cantitate',
        'price' => 'Preț',
        'discount' => 'Reducere',
        'vat' => 'T.V.A.',
        'total' => 'Total',
        'page' => 'Pagina',
        'page_of' => 'din',
    ];
}
