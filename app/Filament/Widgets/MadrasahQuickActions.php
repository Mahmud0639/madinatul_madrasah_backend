<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class MadrasahQuickActions extends Widget
{
    protected static ?int $sort = 0;

    protected int | string | array $columnSpan = 'full';

    protected static string $view = 'filament.widgets.madrasah-quick-actions';
}