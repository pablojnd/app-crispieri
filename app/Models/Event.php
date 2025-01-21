<?php

namespace App\Models;

use Guava\Calendar\Contracts\Eventable;
use Guava\Calendar\ValueObjects\Event as EventObject;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\HasStoreTenancy;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Event extends Model implements Eventable
{
    use HasFactory, HasStoreTenancy;

    protected $fillable = [
        'store_id',
        'shipping_line_container_id',
        'title',
        'description',
        'start_at',
        'end_at',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
    ];

    public function toEvent(): array
    {
        return [
            'id' => $this->id, // Cambiar a número en lugar de string
            'title' => $this->title,
            'start' => $this->start_at->format('Y-m-d\TH:i:s'),
            'end' => $this->end_at?->format('Y-m-d\TH:i:s'),
            'allDay' => false,
            'editable' => true,
            'backgroundColor' => '#4a5568',
            'textColor' => '#ffffff',
            'extendedProps' => [
                'description' => $this->description,
            ],
        ];
    }

    public function shippingLineContainer()
    {
        return $this->belongsTo(ComexShippingLineContainer::class, 'shipping_line_container_id');
    }
}
