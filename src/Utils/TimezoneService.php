<?php

namespace Herdwatch\PdfInvoice\Utils;

class TimezoneService
{
    public function setTimeZone(string $zone = ''): void
    {
        if (!empty($zone) && true === $this->isValidTimezoneId($zone)) {
            date_default_timezone_set($zone);
        }
    }

    private function isValidTimezoneId(string $zone): bool
    {
        try {
            $d = new \DateTimeZone($zone);
        } catch (\Exception) {
            $d = null;
        }

        return null !== $d;
    }
}
