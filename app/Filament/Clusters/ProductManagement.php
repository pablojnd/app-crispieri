<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class ProductManagement extends Cluster
{
    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?string $navigationGroup = 'Gestión de Inventario';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel  = 'Gestión de Productos';
}
