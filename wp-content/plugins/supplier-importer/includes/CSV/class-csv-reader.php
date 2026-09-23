<?php

namespace Supplier_Importer\CSV;

if (!defined('ABSPATH')) {
    exit;
}

use RuntimeException;

class Csv_Reader
{
    /**
     * Путь к CSV-файлу.
     *
     * @var string
     */
    private $file;

    /**
     * Разделитель CSV.
     *
     * @var string
     */
    private $delimiter;

    /**
     * Символ обрамления поля.
     *
     * @var string
     */
    private $enclosure;

    /**
     * Escape-символ.
     *
     * @var string
     */
    private $escape;

    /**
     * Дескриптор файла.
     *
     * @var resource|null
     */
    private $handle = null;

    /**
     * Заголовки CSV.
     *
     * @var array
     */
    private $headers = [];

    /**
     * Номер текущей логической записи.
     *
     * @var int
     */
    private $row_number = 0;

    /**
     * Конструктор.
     *
     * @param string $file
     * @param string $delimiter
     * @param string $enclosure
     * @param string $escape
     */
    public function __construct(
        $file,
        $delimiter = ';',
        $enclosure = '"',
        $escape = '\\'
    ) {
        $this->file = $file;
        $this->delimiter = $delimiter;
        $this->enclosure = $enclosure;
        $this->escape = $escape;
    }

    /**
     * Открыть CSV-файл.
     *
     * @return void
     */
    public function open()
    {
        if (!file_exists($this->file)) {
            throw new RuntimeException(
                'CSV-файл не найден: ' . $this->file
            );
        }

        if (!is_readable($this->file)) {
            throw new RuntimeException(
                'CSV-файл недоступен для чтения: ' . $this->file
            );
        }

        $this->handle = fopen($this->file, 'rb');

        if ($this->handle === false) {
            throw new RuntimeException(
                'Не удалось открыть CSV-файл.'
            );
        }

        $this->read_headers();
    }

    /**
     * Прочитать заголовки.
     *
     * @return array
     */
    private function read_headers()
    {
        if (!$this->handle) {
            throw new RuntimeException(
                'CSV-файл не открыт.'
            );
        }

        $headers = fgetcsv(
            $this->handle,
            0,
            $this->delimiter,
            $this->enclosure,
            $this->escape
        );

        if ($headers === false) {
            throw new RuntimeException(
                'Не удалось прочитать заголовок CSV.'
            );
        }

        $this->headers = $this->normalize_headers($headers);

        return $this->headers;
    }

    /**
     * Нормализовать заголовки.
     *
     * Повторяющиеся заголовки получают уникальные имена:
     *
     * Картинка
     * Картинка_2
     * Картинка_3
     *
     * @param array $headers
     *
     * @return array
     */
    private function normalize_headers($headers)
    {
        $result = [];
        $used = [];

        foreach ($headers as $index => $header) {
            $header = $this->normalize_value($header);

            if ($header === '') {
                $header = 'column_' . ($index + 1);
            }

            $base_name = $header;
            $name = $base_name;
            $counter = 1;

            while (isset($used[$name])) {
                $counter++;

                $name = $base_name . '_' . $counter;
            }

            $used[$name] = true;

            $result[] = $name;
        }

        return $result;
    }

    /**
     * Получить заголовки CSV.
     *
     * @return array
     */
    public function get_headers()
    {
        return $this->headers;
    }

    /**
     * Прочитать следующую логическую запись.
     *
     * @return array|null
     */
    public function read()
    {
        if (!$this->handle) {
            throw new RuntimeException(
                'CSV-файл не открыт.'
            );
        }

        $row = fgetcsv(
            $this->handle,
            0,
            $this->delimiter,
            $this->enclosure,
            $this->escape
        );

        if ($row === false) {
            return null;
        }

        $this->row_number++;

        $row = $this->normalize_row($row);

        return [
            'row_number' => $this->row_number,
            'data'       => $this->combine_row($row),
        ];
    }

    /**
     * Нормализовать значения строки.
     *
     * @param array $row
     *
     * @return array
     */
    private function normalize_row($row)
    {
        foreach ($row as $key => $value) {
            $row[$key] = $this->normalize_value($value);
        }

        return $row;
    }

    /**
     * Нормализовать одно значение.
     *
     * @param mixed $value
     *
     * @return string
     */
    private function normalize_value($value)
    {
        if ($value === null) {
            return '';
        }

        $value = (string) $value;

        /*
         * Убираем BOM только у начала файла/первого значения.
         */
        $value = preg_replace('/^\xEF\xBB\xBF/', '', $value);

        return trim($value);
    }

    /**
     * Объединить значения строки с заголовками.
     *
     * @param array $row
     *
     * @return array
     */
    private function combine_row($row)
    {
        $result = [];

        foreach ($this->headers as $index => $header) {
            $result[$header] = isset($row[$index])
                ? $row[$index]
                : '';
        }

        /*
         * Если в строке оказалось больше значений,
         * чем заголовков, сохраняем их отдельно.
         */
        if (count($row) > count($this->headers)) {
            $result['_extra'] = array_slice(
                $row,
                count($this->headers)
            );
        }

        return $result;
    }

    /**
     * Получить номер текущей строки.
     *
     * @return int
     */
    public function get_row_number()
    {
        return $this->row_number;
    }

    /**
     * Закрыть файл.
     *
     * @return void
     */
    public function close()
    {
        if ($this->handle) {
            fclose($this->handle);
            $this->handle = null;
        }
    }

    /**
     * Деструктор.
     */
    public function __destruct()
    {
        $this->close();
    }
}
