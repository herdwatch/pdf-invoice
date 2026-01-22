<?php

namespace Herdwatch\PdfInvoice\Utils\languages;

// Portuguese (Brazil)
use Herdwatch\PdfInvoice\Utils\LangInterface;

readonly class BrLang implements LangInterface
{
    public const string LANG_NAME = 'br';
    public const array LANG = [
        'number' => 'Referência',
        'date' => 'Data de cobrança',
        'time' => 'Tempo de cobrança',
        'due' => 'Data de Vencimento',
        'payment' => 'Data de pagamento',
        'to' => 'Faturamento para',
        'from' => 'Faturamento de',
        'product' => 'Produto',
        'qty' => 'Qtde',
        'price' => 'Preço',
        'discount' => 'Desconto',
        'vat' => 'ICMS',
        'total' => 'Total',
        'page' => 'Página',
        'page_of' => 'de',
    ];
}
