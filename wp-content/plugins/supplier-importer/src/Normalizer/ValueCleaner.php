<?php

namespace SupplierImporter\Normalizer;

class ValueCleaner
{
    /**
     * Очистка значения из файла поставщика.
     */
    public static function clean($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = (string) $value;

        // NBSP → обычный пробел
        $value = str_replace("\xC2\xA0", ' ', $value);

        // Убираем пробелы по краям
        $value = trim($value);

        // HTML-сущности
        $value = html_entity_decode(
            $value,
            ENT_QUOTES | ENT_HTML5,
            'UTF-8'
        );

        $value = trim($value);

        if ($value === '') {
            return null;
        }

        // Значения, которые некоторые поставщики используют
        // вместо пустого поля.
        $empty_values = [
            '<Пустое значение>',
            '<пустое значение>',
            'пустое значение',
            'отсутствует',
            'нет данных',
            'не указано',
            '-',
        ];

        if (in_array(mb_strtolower($value), array_map(
            'mb_strtolower',
            $empty_values
        ), true)) {
            return null;
        }

        return $value;
    }

    /**
     * Преобразовать значение в число.
     */
    public static function number($value): ?float
    {
        $value = self::clean($value);

        if ($value === null) {
            return null;
        }

        // Убираем пробелы внутри числа
        $value = str_replace(' ', '', $value);

        // Российский формат: 1 099,00
        $value = str_replace(',', '.', $value);

        // Оставляем цифры, точку и минус
        $value = preg_replace('/[^0-9.\-]/', '', $value);

        if ($value === '' || !is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }

    /**
     * Да / Нет → boolean.
     */
    public static function boolean($value): ?bool
    {
        $value = self::clean($value);

        if ($value === null) {
            return null;
        }

        $true_values = [
            'да',
            'yes',
            'true',
            '1',
        ];

        $false_values = [
            'нет',
            'no',
            'false',
            '0',
        ];

        $normalized = mb_strtolower($value);

        if (in_array($normalized, $true_values, true)) {
            return true;
        }

        if (in_array($normalized, $false_values, true)) {
            return false;
        }

        return null;
    }
}
