<?php

namespace Supplier_Importer\CSV;

if (!defined('ABSPATH')) {
    exit;
}

use RuntimeException;

class Csv_Validator
{
    /**
     * Максимальный размер файла для предварительного анализа.
     *
     * @var int
     */
    private const SAMPLE_SIZE = 1024 * 1024;

    /**
     * Результат последней проверки.
     *
     * @var array
     */
    private $result = [];

    /**
     * Проверить CSV-файл.
     *
     * @param string $file
     *
     * @return array
     */
    public function validate($file)
    {
        $this->result = [
            'valid'             => false,
            'file'              => $file,
            'size'              => 0,
            'encoding'          => null,
            'delimiter'         => null,
            'headers'           => [],
            'columns_count'     => 0,
            'data_rows'         => 0,
            'errors'            => [],
            'warnings'          => [],
        ];

        /*
         * 1. Проверяем файл.
         */
        if (!$this->validate_file($file)) {
            return $this->result;
        }

        /*
         * 2. Определяем кодировку.
         */
        $this->result['encoding'] = $this->detect_encoding($file);

        /*
         * 3. Определяем разделитель.
         */
        $this->result['delimiter'] = $this->detect_delimiter(
            $file
        );

        if (!$this->result['delimiter']) {
            $this->result['errors'][] = 'Не удалось определить разделитель CSV.';

            return $this->result;
        }

        /*
         * 4. Читаем заголовки.
         */
        $headers = $this->read_headers(
            $file,
            $this->result['delimiter']
        );

        if (empty($headers)) {
            $this->result['errors'][] = 'Не удалось прочитать заголовки CSV.';

            return $this->result;
        }

        $this->result['headers'] = $headers;
        $this->result['columns_count'] = count($headers);

        /*
         * 5. Проверяем заголовки.
         */
        $this->validate_headers($headers);

        /*
         * 6. Считаем строки.
         */
        $this->result['data_rows'] = $this->count_data_rows(
            $file,
            $this->result['delimiter']
        );

        if ($this->result['data_rows'] === 0) {
            $this->result['warnings'][] = 'CSV не содержит товаров.';
        }

        /*
         * Файл считается валидным,
         * если критических ошибок нет.
         */
        $this->result['valid'] = empty($this->result['errors']);

        return $this->result;
    }

    /**
     * Проверка файла.
     *
     * @param string $file
     *
     * @return bool
     */
    private function validate_file($file)
    {
        if (!file_exists($file)) {
            $this->result['errors'][] =
                'CSV-файл не найден.';

            return false;
        }

        if (!is_file($file)) {
            $this->result['errors'][] =
                'Указанный путь не является файлом.';

            return false;
        }

        if (!is_readable($file)) {
            $this->result['errors'][] =
                'CSV-файл недоступен для чтения.';

            return false;
        }

        $size = filesize($file);

        if ($size === false) {
            $this->result['errors'][] =
                'Не удалось определить размер CSV-файла.';

            return false;
        }

        if ($size === 0) {
            $this->result['errors'][] =
                'CSV-файл пустой.';

            return false;
        }

        $this->result['size'] = $size;

        return true;
    }

    /**
     * Определение кодировки.
     *
     * @param string $file
     *
     * @return string|null
     */
    private function detect_encoding($file)
    {
        $content = file_get_contents(
            $file,
            false,
            null,
            0,
            self::SAMPLE_SIZE
        );

        if ($content === false) {
            return null;
        }

        /*
         * UTF-8 BOM.
         */
        if (substr($content, 0, 3) === "\xEF\xBB\xBF") {
            return 'UTF-8';
        }

        /*
         * Если mb_detect_encoding доступна,
         * проверяем распространённые кодировки.
         */
        if (function_exists('mb_detect_encoding')) {
            $encoding = mb_detect_encoding(
                $content,
                [
                    'UTF-8',
                    'Windows-1251',
                    'CP1251',
                    'ISO-8859-1',
                ],
                true
            );

            if ($encoding) {
                if ($encoding === 'CP1251') {
                    return 'Windows-1251';
                }

                return $encoding;
            }
        }

        /*
         * По умолчанию считаем UTF-8.
         */
        return 'UTF-8';
    }

    /**
     * Определить разделитель CSV.
     *
     * @param string $file
     *
     * @return string|null
     */
    private function detect_delimiter($file)
    {
        $handle = fopen($file, 'rb');

        if (!$handle) {
            return null;
        }

        $line = fgets($handle);

        fclose($handle);

        if ($line === false) {
            return null;
        }

        $delimiters = [
            ';',
            ',',
            "\t",
            '|',
        ];

        $best_delimiter = null;
        $best_count = 0;

        foreach ($delimiters as $delimiter) {
            $count = substr_count(
                $line,
                $delimiter
            );

            if ($count > $best_count) {
                $best_count = $count;
                $best_delimiter = $delimiter;
            }
        }

        return $best_delimiter;
    }

    /**
     * Прочитать заголовки.
     *
     * @param string $file
     * @param string $delimiter
     *
     * @return array
     */
    private function read_headers($file, $delimiter)
    {
        $handle = fopen($file, 'rb');

        if (!$handle) {
            return [];
        }

        $headers = fgetcsv(
            $handle,
            0,
            $delimiter,
            '"',
            '\\'
        );

        fclose($handle);

        if ($headers === false) {
            return [];
        }

        foreach ($headers as &$header) {
            $header = trim(
                preg_replace(
                    '/^\xEF\xBB\xBF/',
                    '',
                    (string) $header
                )
            );
        }

        unset($header);

        return $headers;
    }

    /**
     * Проверка заголовков.
     *
     * @param array $headers
     *
     * @return void
     */
    private function validate_headers($headers)
    {
        $non_empty_headers = [];

        foreach ($headers as $header) {
            $header = trim((string) $header);

            if ($header !== '') {
                $non_empty_headers[] = $header;
            }
        }

        if (empty($non_empty_headers)) {
            $this->result['errors'][] =
                'CSV не содержит заголовков колонок.';

            return;
        }

        /*
         * Если в CSV только одна колонка,
         * скорее всего выбран неправильный разделитель.
         */
        if (count($headers) < 2) {
            $this->result['warnings'][] =
                'В CSV обнаружена только одна колонка. Проверьте разделитель.';
        }
    }

    /**
     * Посчитать количество логических строк.
     *
     * Важно:
     * fgetcsv() учитывает многострочные поля.
     *
     * @param string $file
     * @param string $delimiter
     *
     * @return int
     */
    private function count_data_rows($file, $delimiter)
    {
        $handle = fopen($file, 'rb');

        if (!$handle) {
            return 0;
        }

        /*
         * Пропускаем заголовок.
         */
        fgetcsv(
            $handle,
            0,
            $delimiter,
            '"',
            '\\'
        );

        $count = 0;

        while (
            ($row = fgetcsv(
                $handle,
                0,
                $delimiter,
                '"',
                '\\'
            )) !== false
        ) {
            /*
             * Полностью пустую строку
             * товаром не считаем.
             */
            $has_value = false;

            foreach ($row as $value) {
                if (trim((string) $value) !== '') {
                    $has_value = true;
                    break;
                }
            }

            if ($has_value) {
                $count++;
            }
        }

        fclose($handle);

        return $count;
    }

    /**
     * Получить результат последней проверки.
     *
     * @return array
     */
    public function get_result()
    {
        return $this->result;
    }

    /**
     * Проверить, валиден ли файл.
     *
     * @return bool
     */
    public function is_valid()
    {
        return !empty($this->result['valid']);
    }
}
