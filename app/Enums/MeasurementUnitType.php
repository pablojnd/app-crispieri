<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum MeasurementUnitType: string implements HasLabel
{
    case LENGTH = 'length';
    case WEIGHT = 'weight';
    case VOLUME = 'volume';
    case AREA = 'area';
    case COUNT = 'count';
    case TIME = 'time';
    case SQUARE_METERS = 'square_meters';
    case METER_LINEAL = 'meter_lineal';
    case OTHER = 'other';

    /**
     * Obtiene una descripción legible para cada tipo de unidad
     *
     * @return string
     */
    public function getlabel(): string
    {
        return match ($this) {
            self::LENGTH => 'Longitud',
            self::WEIGHT => 'Peso',
            self::VOLUME => 'Volumen',
            self::AREA => 'Área',
            self::COUNT => 'Conteo',
            self::TIME => 'Tiempo',
            self::SQUARE_METERS => 'Metros cuadrados',
            self::METER_LINEAL => 'Metro lineal',
            self::OTHER => 'Otro'
        };
    }

    /**
     * Obtiene un ícono representativo para cada tipo de unidad
     *
     * @return string
     */
    public function icon(): string
    {
        return match ($this) {
            self::LENGTH => 'heroicon-o-ruler',
            self::WEIGHT => 'heroicon-o-scale',
            self::VOLUME => 'heroicon-o-beaker',
            self::AREA => 'heroicon-o-map',
            self::COUNT => 'heroicon-o-hashtag',
            self::TIME => 'heroicon-o-clock',
            self::SQUARE_METERS => 'heroicon-o-grid',
            self::METER_LINEAL => 'heroicon-o-ruler',
            self::OTHER => 'heroicon-o-question-mark-circle'
        };
    }

    /**
     * Devuelve todos los tipos de unidades
     *
     * @return array
     */
    public static function toArray(): array
    {
        return array_column(self::cases(), 'value');
    }
}
