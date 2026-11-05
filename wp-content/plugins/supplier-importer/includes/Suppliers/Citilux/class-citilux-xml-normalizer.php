<?php

namespace Supplier_Importer\Suppliers\Citilux;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Нормализует XML-фид CITILUX.
 *
 * Удаляет HTML-теги из содержимого description,
 * чтобы некорректная HTML-разметка не ломала XML-разбор.
 */
class Citilux_Xml_Normalizer
{
    /**
     * Подготовить нормализованную копию XML-файла.
     *
     * Исходный файл не изменяется.
     *
     * @param string $file_path
     *
     * @return string Путь к временному XML-файлу.
     */
    public function normalize($file_path)
    {
        if (
            !is_file($file_path)
            || !is_readable($file_path)
        ) {
            throw new \RuntimeException(
                'Исходный XML-файл CITILUX недоступен для чтения.'
            );
        }

        $content = file_get_contents($file_path);

        if ($content === false) {
            throw new \RuntimeException(
                'Не удалось прочитать XML-файл CITILUX.'
            );
        }

        $normalized = preg_replace_callback(
            '~(<description\b[^>]*>)(.*?)(</description\s*>)~is',
            [$this, 'normalize_description'],
            $content
        );

        if ($normalized === null) {
            throw new \RuntimeException(
                'Ошибка нормализации описаний CITILUX: '
                    . preg_last_error_msg()
            );
        }

        if ($normalized === $content) {
            throw new \RuntimeException(
                'В XML-файле CITILUX не найдены блоки description.'
            );
        }

        $temp_path = tempnam(
            sys_get_temp_dir(),
            'citilux_xml_'
        );

        if ($temp_path === false) {
            throw new \RuntimeException(
                'Не удалось создать временный XML-файл CITILUX.'
            );
        }

        if (file_put_contents($temp_path, $normalized) === false) {
            @unlink($temp_path);

            throw new \RuntimeException(
                'Не удалось записать нормализованный XML-файл CITILUX.'
            );
        }

        return $temp_path;
    }

    /**
     * Удалить HTML-разметку из одного description.
     *
     * @param array $matches
     *
     * @return string
     */
    private function normalize_description($matches)
    {
        $description = html_entity_decode(
            $matches[2],
            ENT_QUOTES | ENT_HTML5,
            'UTF-8'
        );

        $description = strip_tags($description);

        // Убираем лишние пробелы и переносы строк.
        $description = preg_replace(
            '/\s+/u',
            ' ',
            $description
        );

        if ($description === null) {
            throw new \RuntimeException(
                'Не удалось обработать текст описания CITILUX.'
            );
        }

        $description = trim($description);

        // Экранируем текст для корректного XML.
        $description = htmlspecialchars(
            $description,
            ENT_QUOTES | ENT_XML1,
            'UTF-8'
        );

        return $matches[1]
            . $description
            . $matches[3];
    }
}
