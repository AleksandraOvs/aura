<?php

namespace Supplier_Importer\Import;

if (!defined('ABSPATH')) {
    exit;
}

class Yml_Validator
{
    /**
     * Проверяет структуру и целостность YML-фида.
     *
     * @param string $file_path
     *
     * @return void
     *
     * @throws \RuntimeException
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
        $shop_found = false;
        $offers_found = false;

        $shop_depth = null;
        $offers_depth = null;

        $offer_count = 0;

        try {
            if (!$reader->open(
                $file_path,
                null,
                LIBXML_NONET | LIBXML_COMPACT
            )) {
                throw new \RuntimeException(
                    'Не удалось открыть YML-файл для проверки.'
                );
            }

            while ($reader->read()) {

                if ($reader->nodeType === \XMLReader::ELEMENT) {

                    $name = $reader->localName;
                    $depth = $reader->depth;

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

                    if (
                        $name === 'shop'
                        && $depth === 1
                    ) {
                        $shop_found = true;
                        $shop_depth = $depth;
                        continue;
                    }

                    if (
                        $name === 'offers'
                        && $shop_depth !== null
                        && $depth === $shop_depth + 1
                    ) {
                        $offer_count++;
                        $offers_found = true;
                        continue;
                    }

                    if (
                        $name === 'offer'
                        && $offers_depth !== null
                        && $depth === $offers_depth + 1
                    ) {
                        $offer_count++;
                    }
                }

                if ($reader->nodeType === \XMLReader::END_ELEMENT) {

                    $name = $reader->localName;
                    $depth = $reader->depth;

                    if (
                        $offers_depth !== null
                        && $depth === $offers_depth
                        && $name === 'offers'
                    ) {
                        $offers_depth = null;
                    }

                    if (
                        $shop_depth !== null
                        && $depth === $shop_depth
                        && $name === 'shop'
                    ) {
                        $shop_depth = null;
                    }
                }
            }

            $errors = libxml_get_errors();

            foreach ($errors as $error) {
                if ($error->level >= LIBXML_ERR_ERROR) {
                    throw new \RuntimeException(
                        sprintf(
                            'Ошибка разбора XML на строке %d: %s',
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

            if (!$shop_found) {
                throw new \RuntimeException(
                    'В YML-файле не найдена секция shop.'
                );
            }

            if (!$offers_found) {
                throw new \RuntimeException(
                    'В YML-файле не найдена секция offers.'
                );
            }

            if (
                $shop_depth !== null
                || $offers_depth !== null
            ) {
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
