<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class ComexContainerItem extends Pivot
{
    protected $table = 'comex_container_items';

    protected $casts = [
        'quantity' => 'decimal:2',
        'weight' => 'decimal:2'
    ];
}
