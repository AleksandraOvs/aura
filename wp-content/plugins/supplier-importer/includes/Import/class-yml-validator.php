<?php

namespace Supplier_Importer\Import;

if (!defined('ABSPATH')) {
    exit;
}

class Yml_Validator
{
    /**
     * Проверить структуру и целостность YML-фида.
     *
     * Проверяется весь XML-документ, а не только его начало.
     *
     * @param string $file_path Путь к файлу.
     *
     * @return void
     *
     * @throws \RuntimeException Если фид некорректен.
     */
    public function validate($file_path)
    {
        if (!class_exists('\XMLReader')) {
            throw new \RuntimeException(
                'Для проверки YML необходим PHP-модуль XMLReader.'
            );
        }

        if (!is_file($file_path) || !is_readable($file_path)) {
            throw new \RuntimeException(
                'YML-файл не найден или недоступен для чтения.'
            );
        }

        $previous_internal_errors = libxml_use_internal_errors(true);
        libxml_clear_errors();

        $reader = new \XMLReader();

        $root_found = false;
        $shop_depth = null;
        $offers_depth = null;
        $offer_count = 0;

        try {
            if (
                !$reader->open(
                    $file_path,
                    null,
                    LIBXML_NONET | LIBXML_COMPACT
                )
            ) {
                throw new \RuntimeException(
                    'Не удалось открыть YML-файл для проверки.'
                );
            }

            while ($reader->read()) {
                if ($reader->nodeType === \XMLReader::ELEMENT) {
                    $name = $reader->localName;
                    $depth = $reader->depth;

                    // Первый элемент документа должен быть yml_catalog.
                    if (!$root_found) {
                        if ($name !== 'yml_catalog') {
                            throw new \RuntimeException(
                                'Некорректный YML: корневой элемент '
                                    . 'должен быть yml_catalog.'
                            );
                        }

                        $root_found = true;
                        continue;
                    }

                    // Секция shop должна находиться непосредственно
                    // внутри yml_catalog.
                    if (
                        $name === 'shop'
                        && $depth === 1
                    ) {
                        $shop_depth = $depth;
                        continue;
                    }

                    // Секция offers должна находиться внутри shop.
                    if (
                        $name === 'offers'
                        && $shop_depth !== null
                        && $depth === $shop_depth + 1
                    ) {
                        $offers_depth = $depth;
                        continue;
                    }

                    // Считаем только offer непосредственно внутри offers.
                    if (
                        $name === 'offer'
                        && $offers_depth !== null
                        && $depth === $offers_depth + 1
                    ) {
                        ++$offer_count;
                    }
                }

                if ($reader->nodeType === \XMLReader::END_ELEMENT) {
                    if (
                        $offers_depth !== null
                        && $reader->depth === $offers_depth
                        && $reader->localName === 'offers'
                    ) {
                        $offers_depth = null;
                    }

                    if (
                        $shop_depth !== null
                        && $reader->depth === $shop_depth
                        && $reader->localName === 'shop'
                    ) {
                        $shop_depth = null;
                    }
                }
            }

            // XMLReader может обнаружить ошибку разбора во время чтения.
            // Проверяем ошибки после прохода по всему документу.
            $errors = libxml_get_errors();

            foreach ($errors as $error) {
                if ($error->level >= LIBXML_ERR_ERROR) {
                    throw new \RuntimeException(
                        sprintf(
                            'Ошибка разбора YML на строке %d: %s',
                            $error->line,
                            trim($error->message)
                        )
                    );
                }
            }

            if (!$root_found) {
                throw new \RuntimeException(
                    'YML-файл не содержит корневого элемента.'
                );
            }

            if ($shop_depth !== null || $offers_depth !== null) {
                throw new \RuntimeException(
                    'Некорректная структура YML: '
                        . 'не закрыта секция shop или offers.'
                );
            }

            if ($offer_count === 0) {
                throw new \RuntimeException(
                    'В YML-файле не найдено ни одного товара offer.'
                );
            }
        } finally {
            $reader->close();

            libxml_clear_errors();
            libxml_use_internal_errors($previous_internal_errors);
        }
    }
}
