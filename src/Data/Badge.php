<?php

namespace Herdwatch\PdfInvoice\Data;

class Badge
{
    public function __construct(
        private string $badge,
        private Color $badgeColor,
    ) {
    }

    public function setBadgeColor(Color $badgeColor): void
    {
        $this->badgeColor = $badgeColor;
    }

    public function setBadge(string $badge): void
    {
        $this->badge = $badge;
    }

    public function getBadgeColor(): Color
    {
        return $this->badgeColor;
    }

    public function getBadge(): string
    {
        return $this->badge;
    }
}
