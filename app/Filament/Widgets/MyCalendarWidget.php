<?php

namespace App\Filament\Widgets;

use Closure;
use App\Models\Event;
use Filament\Facades\Filament;
use Illuminate\Support\Collection;
use Filament\Forms\Components\Grid;
use Guava\Calendar\Actions\EditAction;
use Guava\Calendar\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Guava\Calendar\Actions\CreateAction;
use Guava\Calendar\Actions\DeleteAction;
use Guava\Calendar\Widgets\CalendarWidget;
use Filament\Forms\Components\DateTimePicker;

class MyCalendarWidget extends CalendarWidget
{
    protected static ?int $sort = 1;

    // Habilitar interacciones del calendario
    protected bool $eventClickEnabled = true;
    protected bool $eventDragEnabled = true;
    protected bool $eventResizeEnabled = true;
    protected bool $dateClickEnabled = true;
    protected bool $dateSelectEnabled = true;
    protected ?string $defaultEventClickAction = 'view';

    // Obtener eventos
    public function getEvents(array $fetchInfo = []): Collection|array
    {
        return Event::query()
            ->where('store_id', Filament::getTenant()->id)
            ->get()
            ->map(fn(Event $event) => $event->toEvent());
    }

    // Formulario para crear/editar eventos
    protected function getFormSchema(): array
    {
        return [
            TextInput::make('title')
                ->required()
                ->maxLength(255)
                ->label('Título'),
            Textarea::make('description')
                ->label('Descripción'),
            DateTimePicker::make('start_at')
                ->required()
                ->label('Inicio'),
            DateTimePicker::make('end_at')
                ->label('Fin'),
        ];
    }

    // Agregar método para manejar el formulario
    protected function getFormModel(): string
    {
        return Event::class;
    }

    // Menú contextual para clic en fecha
    public function getDateClickContextMenuActions(): array
    {
        return [
            CreateAction::make('create')
                ->model(Event::class)
                ->form($this->getFormSchema())
                ->mutateFormDataUsing(function (array $data) {
                    return array_merge($data, [
                        'store_id' => Filament::getTenant()->id,
                    ]);
                })
                ->successNotificationTitle('Evento Creado'),
        ];
    }

    // Menú contextual para clic en evento
    public function getEventClickContextMenuActions(): array
    {
        return [
            ViewAction::make()
                ->form($this->getFormSchema())
                ->modalWidth('lg')
                ->record(function (array $arguments) {
                    // Usar el argumento correcto para obtener el ID
                    return Event::find($this->getEventRecord()?->id);
                }),
            EditAction::make()
                ->form($this->getFormSchema())
                ->modalWidth('lg')
                ->record(function (array $arguments) {
                    return Event::find($this->getEventRecord()?->id);
                })
                ->mutateRecordDataUsing(function (array $data) {
                    return [
                        'title' => $data['title'],
                        'description' => $data['description'],
                        'start_at' => $data['start_at'],
                        'end_at' => $data['end_at'],
                        'store_id' => Filament::getTenant()->id,
                    ];
                })
                ->after(function () {
                    $this->dispatch('calendar-event-updated');
                }),
            DeleteAction::make()
                ->record(function (array $arguments) {
                    return Event::find($this->getEventRecord()?->id);
                })
                ->after(function () {
                    $this->dispatch('calendar-event-updated');
                }),
        ];
    }

    // Configuración del calendario
    protected function calendarConfig(): array
    {
        return [
            'editable' => true,
            'selectable' => true,
            'initialView' => 'dayGridMonth',
            'headerToolbar' => [
                'left' => 'prev,next today',
                'center' => 'title',
                'right' => 'dayGridMonth,timeGridWeek,timeGridDay',
            ],
            'locale' => 'es',
            'height' => '700px',
            'eventTimeFormat' => [
                'hour' => '2-digit',
                'minute' => '2-digit',
                'hour12' => false,
            ],
            'eventDurationEditable' => true,
            'eventStartEditable' => true,
        ];
    }

    // Manejar eventos de arrastrar y soltar
    public function onEventDrop(array $info = []): bool
    {
        $event = $this->getEventRecord();

        if (!$event) {
            return false;
        }

        $event->update([
            'start_at' => $info['start'] ?? null,
            'end_at' => $info['end'] ?? null,
        ]);

        $this->dispatch('calendar-event-updated');
        return true;
    }

    // Manejar eventos de redimensionamiento
    public function onEventResize(array $info = []): bool
    {
        $event = $this->getEventRecord();

        if (!$event) {
            return false;
        }

        $event->update([
            'end_at' => $info['end'] ?? null,
        ]);

        $this->dispatch('calendar-event-updated');
        return true;
    }

    protected function getIdentifiableKey(): ?string
    {
        return 'calendar';
    }

    public function toEvent(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'start' => $this->start_at->format('Y-m-d\TH:i:s'),
            'end' => $this->end_at?->format('Y-m-d\TH:i:s'),
            'allDay' => false,
            'editable' => true,
            'backgroundColor' => '#4a5568', // Color por defecto
            'textColor' => '#ffffff',
            'extendedProps' => [
                'description' => $this->description,
            ],
        ];
    }
}
