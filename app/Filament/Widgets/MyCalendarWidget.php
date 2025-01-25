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
            CreateAction::make()
                ->label('Crear Evento')
                ->model(Event::class)
                ->form($this->getFormSchema())
                ->mutateFormDataUsing(function (array $data) {
                    return array_merge($data, [
                        'store_id' => Filament::getTenant()->id,
                    ]);
                })
                ->successNotificationTitle('Evento creado')
                ->after(fn() => $this->dispatch('calendar-event-updated')),
        ];
    }

    // Menú contextual para clic en evento
    public function getEventClickContextMenuActions(): array
    {
        return [
            ViewAction::make()
                ->label('Ver')
                ->form($this->getFormSchema())
                ->modalWidth('lg')
                ->record(fn() => $this->getEventRecord()),

            EditAction::make()
                ->label('Editar')
                ->form($this->getFormSchema())
                ->modalWidth('lg')
                ->record(fn() => $this->getEventRecord())
                ->mutateFormDataUsing(function (array $data) {
                    return array_merge($data, [
                        'store_id' => Filament::getTenant()->id,
                    ]);
                })
                ->successNotificationTitle('Evento actualizado')
                ->after(fn() => $this->dispatch('calendar-event-updated')),

            DeleteAction::make()
                ->label('Eliminar')
                ->record(fn() => $this->getEventRecord())
                ->successNotificationTitle('Evento eliminado')
                ->after(fn() => $this->dispatch('calendar-event-updated')),
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
            'titleFormat' => [ // Agregamos esta configuración
                'year' => 'numeric',
                'month' => 'long',
                'day' => 'numeric'
            ],
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
            'title' => $this->title, // Solo el título sin la hora
            'start' => $this->start_at->format('Y-m-d\TH:i:s'),
            'end' => $this->end_at?->format('Y-m-d\TH:i:s'),
            'allDay' => false,
            'editable' => true,
            'backgroundColor' => '#4a5568',
            'textColor' => '#ffffff',
            'displayEventTime' => false, // Agregamos esta línea para ocultar la hora en el título
            'extendedProps' => [
                'description' => $this->description,
            ],
        ];
    }

    // Agregar método de autorización
    public function authorize($ability, $arguments = []): bool
    {
        return true; // Modificar según tus necesidades de autorización
    }
}
