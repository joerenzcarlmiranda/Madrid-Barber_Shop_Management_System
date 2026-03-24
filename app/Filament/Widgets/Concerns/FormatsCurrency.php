<?php

namespace App\Filament\Widgets\Concerns;

trait FormatsCurrency
{
    protected function formatCurrency(float|int $amount): string
    {
        return '$'.number_format((float) $amount, 2);
    }
}
