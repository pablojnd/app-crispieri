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
    // Habilitar interacciones del calendario
    protected bool $eventClickEnabled = true;
    protected bool $eventDragEnabled = true;
    protected bool $eventResizeEnabled = true;
    protected bool $dateClickEnabled = true;
    protected bool $dateSelectEnabled = true;

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
                ->label('Inicio')
                ->timezone('America/La_Paz')
                ->default(now()),
            DateTimePicker::make('end_at')
                ->required()
                ->label('Fin')
                ->timezone('America/La_Paz')
                ->default(fn() => now()->addHour()),
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
                ->record(function ($arguments) {
                    return Event::find($arguments['id']);
                }),
            EditAction::make()
                ->form($this->getFormSchema())
                ->modalWidth('lg')
                ->record(function ($arguments) {
                    return Event::find($arguments['id']);
                })
                ->after(function () {
                    $this->dispatch('calendar-event-updated');
                }),
            // DeleteAction::make()
            //     ->record(function ($arguments) {
            //         return Event::find($arguments['id']);
            //     })
            //     ->after(function () {
            //         $this->dispatch('calendar-event-updated');
            //     }),
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
        $event = Event::find($info['event']['id']);

        if (!$event) {
            return false;
        }

        $event->update([
            'start_at' => $info['event']['start'],
            'end_at' => $info['event']['end'],
        ]);

        $this->dispatch('calendar-event-updated');
        return true;
    }

    // Manejar eventos de redimensionamiento
    public function onEventResize(array $info = []): bool
    {
        $event = Event::find($info['event']['id']);

        if (!$event) {
            return false;
        }

        $event->update([
            'end_at' => $info['event']['end'],
        ]);

        $this->dispatch('calendar-event-updated');
        return true;
    }

    protected function getIdentifiableKey(): ?string
    {
        return 'my-calendar-widget';
    }
}
