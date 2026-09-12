<?php

namespace SupplierImporter\Parsers;

use SupplierImporter\Normalizer\ValueCleaner;

class CsvParser
{
    private string $delimiter;

    private string $enclosure;

    private string $escape;

    public function __construct(
        string $delimiter = ';',
        string $enclosure = '"',
        string $escape = '\\'
    ) {
        $this->delimiter = $delimiter;
        $this->enclosure = $enclosure;
        $this->escape = $escape;
    }

    /**
     * Чтение CSV-файла.
     *
     * @return array{
     *     headers: array,
     *     rows: array
     * }
     */
    public function parse(string $file): array
    {
        if (!file_exists($file)) {
            throw new \RuntimeException(
                'CSV-файл не найден: ' . $file
            );
        }

        if (!is_readable($file)) {
            throw new \RuntimeException(
                'CSV-файл недоступен для чтения: ' . $file
            );
        }

        $handle = fopen($file, 'rb');

        if (!$handle) {
            throw new \RuntimeException(
                'Не удалось открыть CSV-файл.'
            );
        }

        /*
         * Определяем кодировку.
         */
        $encoding = $this->detectEncoding($handle);

        if ($encoding) {
            rewind($handle);
        }

        /*
         * Читаем заголовок.
         */
        $headers = fgetcsv(
            $handle,
            0,
            $this->delimiter,
            $this->enclosure,
            $this->escape
        );

        if ($headers === false) {
            fclose($handle);

            throw new \RuntimeException(
                'Не удалось прочитать заголовок CSV.'
            );
        }

        $headers = $this->normalizeHeaders($headers);

        $rows = [];

        while (($row = fgetcsv(
            $handle,
            0,
            $this->delimiter,
            $this->enclosure,
            $this->escape
        )) !== false) {

            /*
             * Полностью пустые строки пропускаем.
             */
            if ($this->isEmptyRow($row)) {
                continue;
            }

            /*
             * Если количество значений меньше колонок —
             * дополняем null.
             */
            if (count($row) < count($headers)) {
                $row = array_pad(
                    $row,
                    count($headers),
                    null
                );
            }

            /*
             * Если значений неожиданно больше —
             * не теряем их.
             */
            if (count($row) > count($headers)) {
                $row = array_slice(
                    $row,
                    0,
                    count($headers)
                );
            }

            $item = [];

            foreach ($headers as $index => $header) {

                $value = $row[$index] ?? null;

                $item[$header] = ValueCleaner::clean($value);
            }

            $rows[] = $item;
        }

        fclose($handle);

        return [
            'headers' => $headers,
            'rows' => $rows,
            'count' => count($rows),
        ];
    }

    public function parseChunk(
        string $file,
        int $offset,
        int $limit
    ): array {

        if (!file_exists($file)) {
            throw new \RuntimeException(
                'CSV-файл не найден: ' . $file
            );
        }

        if (!is_readable($file)) {
            throw new \RuntimeException(
                'CSV-файл недоступен для чтения: ' . $file
            );
        }

        $handle = fopen($file, 'rb');

        if (!$handle) {
            throw new \RuntimeException(
                'Не удалось открыть CSV-файл.'
            );
        }

        /*
     * Заголовки.
     */
        $headers = fgetcsv(
            $handle,
            0,
            $this->delimiter,
            $this->enclosure,
            $this->escape
        );

        if ($headers === false) {
            fclose($handle);

            throw new \RuntimeException(
                'Не удалось прочитать заголовок CSV.'
            );
        }

        $headers = $this->normalizeHeaders($headers);

        /*
     * Пропускаем уже обработанные строки.
     */
        $current_row = 0;

        while ($current_row < $offset) {

            $row = fgetcsv(
                $handle,
                0,
                $this->delimiter,
                $this->enclosure,
                $this->escape
            );

            if ($row === false) {
                fclose($handle);

                return [
                    'headers' => $headers,
                    'rows'    => [],
                    'count'   => 0,
                    'offset'  => $offset,
                    'limit'   => $limit,
                    'next'    => false,
                ];
            }

            if ($this->isEmptyRow($row)) {
                continue;
            }

            $current_row++;
        }

        /*
     * Читаем только нужную порцию.
     */
        $rows = [];

        while (count($rows) < $limit) {

            $row = fgetcsv(
                $handle,
                0,
                $this->delimiter,
                $this->enclosure,
                $this->escape
            );

            if ($row === false) {
                break;
            }

            if ($this->isEmptyRow($row)) {
                continue;
            }

            if (count($row) < count($headers)) {
                $row = array_pad(
                    $row,
                    count($headers),
                    null
                );
            }

            if (count($row) > count($headers)) {
                $row = array_slice(
                    $row,
                    0,
                    count($headers)
                );
            }

            $item = [];

            foreach ($headers as $index => $header) {

                $value = $row[$index] ?? null;

                $item[$header] =
                    ValueCleaner::clean($value);
            }

            $rows[] = $item;
        }

        fclose($handle);

        return [
            'headers' => $headers,
            'rows'    => $rows,
            'count'   => count($rows),
            'offset'  => $offset,
            'limit'   => $limit,
            'next'    => count($rows) === $limit,
        ];
    }

    /**
     * Читаем только первые строки файла.
     *
     * Это пригодится для предпросмотра в админке.
     */
    public function preview(
        string $file,
        int $limit = 10
    ): array {

        if (!file_exists($file)) {
            throw new \RuntimeException(
                'CSV-файл не найден.'
            );
        }

        $handle = fopen($file, 'rb');

        if (!$handle) {
            throw new \RuntimeException(
                'Не удалось открыть CSV-файл.'
            );
        }

        $headers = fgetcsv(
            $handle,
            0,
            $this->delimiter,
            $this->enclosure,
            $this->escape
        );

        if ($headers === false) {
            fclose($handle);

            return [];
        }

        $headers = $this->normalizeHeaders($headers);

        $rows = [];

        while (
            count($rows) < $limit &&
            ($row = fgetcsv(
                $handle,
                0,
                $this->delimiter,
                $this->enclosure,
                $this->escape
            )) !== false
        ) {

            if ($this->isEmptyRow($row)) {
                continue;
            }

            $row = array_pad(
                $row,
                count($headers),
                null
            );

            $row = array_slice(
                $row,
                0,
                count($headers)
            );

            $item = [];

            foreach ($headers as $index => $header) {
                $item[$header] = ValueCleaner::clean(
                    $row[$index] ?? null
                );
            }

            $rows[] = $item;
        }

        fclose($handle);

        return [
            'headers' => $headers,
            'rows' => $rows,
            'count' => count($rows),
        ];
    }

    /**
     * Нормализация названий колонок.
     */
    private function normalizeHeaders(array $headers): array
    {
        $result = [];

        foreach ($headers as $index => $header) {

            $header = (string) $header;

            // Удаляем BOM / ZERO WIDTH NO-BREAK SPACE
            $header = str_replace("\xEF\xBB\xBF", '', $header);
            $header = str_replace("\x{FEFF}", '', $header);

            $header = ValueCleaner::clean($header);

            if ($header === null) {
                $header = 'column_' . $index;
            }

            $result[] = $header;
        }

        return $this->makeUniqueHeaders($result);
    }

    /**
     * Повторяющиеся названия колонок.
     *
     * Например Lightstar:
     *
     * Картинка
     * Картинка
     * Картинка
     *
     * превращаются в:
     *
     * Картинка
     * Картинка_2
     * Картинка_3
     */
    private function makeUniqueHeaders(array $headers): array
    {
        $result = [];
        $counts = [];

        foreach ($headers as $header) {

            if (!isset($counts[$header])) {
                $counts[$header] = 1;
                $result[] = $header;

                continue;
            }

            $counts[$header]++;

            $result[] = $header . '_' . $counts[$header];
        }

        return $result;
    }

    /**
     * Проверка пустой строки.
     */
    private function isEmptyRow(array $row): bool
    {
        foreach ($row as $value) {

            if (ValueCleaner::clean($value) !== null) {
                return false;
            }
        }

        return true;
    }

    /**
     * Определение BOM.
     */
    private function detectEncoding($handle): ?string
    {
        $bom = fread($handle, 4);

        if ($bom === false) {
            return null;
        }

        if (substr($bom, 0, 3) === "\xEF\xBB\xBF") {
            return 'UTF-8';
        }

        if (substr($bom, 0, 2) === "\xFF\xFE") {
            return 'UTF-16LE';
        }

        if (substr($bom, 0, 2) === "\xFE\xFF") {
            return 'UTF-16BE';
        }

        return null;
    }
}
