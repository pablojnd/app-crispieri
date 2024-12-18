<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class ComexDocumentItem extends Pivot
{
    protected $table = 'comex_document_items';

    protected $casts = [
        'quantity' => 'decimal:2',
        'cif_amount' => 'decimal:4'
    ];
}
