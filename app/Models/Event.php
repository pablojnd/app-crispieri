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

    public function toEvent(): EventObject|array
    {
        $event = EventObject::make($this)
            ->title($this->title)
            ->start($this->start_at);

        // Si end_at existe, añadirlo al evento
        if ($this->end_at) {
            $event->end($this->end_at);
        } else {
            // Si no hay end_at, usar start_at como end
            $event->end($this->start_at);
        }

        return $event
            ->allDay(false)
            ->editable(true)
            ->backgroundColor('#4a5568')
            ->textColor('#ffffff')
            ->display('block')
            ->extendedProps([
                'description' => $this->description,
                'displayEventTime' => false,
            ]);
    }

    public function shippingLineContainer()
    {
        return $this->belongsTo(ComexShippingLineContainer::class, 'shipping_line_container_id');
    }
}
