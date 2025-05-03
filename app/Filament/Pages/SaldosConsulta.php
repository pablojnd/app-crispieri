<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Livewire\SaldosSearch;
use Filament\Support\Enums\MaxWidth;

class SaldosConsulta extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-magnifying-glass';
    protected static ?string $navigationLabel = 'Consulta de Saldos';
    protected static ?string $title = 'Consulta de Saldos';
    protected static ?string $slug = 'saldos-consulta';
    protected static ?string $navigationGroup = 'Consultas';
    protected static ?int $navigationSort = 10;

    protected static string $view = 'filament.pages.saldos-consulta';

    public function getMaxContentWidth(): MaxWidth
    {
        return MaxWidth::Full;
    }

    public function getHeaderWidgets(): array
    {
        return [];
    }

    public function getFooterWidgets(): array
    {
        return [];
    }
}
