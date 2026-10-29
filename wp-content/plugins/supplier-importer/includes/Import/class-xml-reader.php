<?php

namespace Supplier_Importer\Import;

if (!defined('ABSPATH')) {
    exit;
}

class Xml_Reader implements Row_Reader
{
    /**
     * Путь к XML/YML-файлу.
     */
    private $file_path;

    /**
     * Имя элемента, представляющего товар.
     */
    private $record_element;

    /**
     * XMLReader.
     */
    private $reader;

    /**
     * Создан ли объект читателя.
     */
    private $opened = false;

    /**
     * Предыдущее состояние обработки ошибок libxml.
     *
     * @var bool|null
     */
    private $previous_internal_errors = null;

    /**
     * Включён ли внутренний сбор ошибок libxml для этого reader.
     *
     * @var bool
     */
    private $internal_errors_enabled = false;

    /**
     * @param string $file_path     Путь к файлу.
     * @param string $record_element Имя элемента товара.
     */
    public function __construct($file_path, $record_element = 'offer')
    {
        $this->file_path = $file_path;
        $this->record_element = $record_element;
    }

    /**
     * Открыть файл.
     */
    public function open()
    {
        if (!class_exists('\XMLReader')) {
            throw new \RuntimeException(
                'Расширение PHP XMLReader не установлено или отключено.'
            );
        }

        if (!class_exists('\DOMDocument')) {
            throw new \RuntimeException(
                'Расширение PHP DOM не установлено или отключено.'
            );
        }

        if (!is_file($this->file_path) || !is_readable($this->file_path)) {
            throw new \RuntimeException(
                'XML/YML-файл не найден или недоступен для чтения.'
            );
        }

        // Сохраняем глобальное состояние libxml и включаем сбор ошибок.
        $this->previous_internal_errors = libxml_use_internal_errors(true);
        $this->internal_errors_enabled = true;

        libxml_clear_errors();

        $this->reader = new \XMLReader();

        $result = $this->reader->open(
            $this->file_path,
            null,
            LIBXML_NONET | LIBXML_COMPACT
        );

        if (!$result) {
            $this->close();

            throw new \RuntimeException(
                'Не удалось открыть XML/YML-файл.'
            );
        }

        if (
            !$this->reader->setParserProperty(
                \XMLReader::SUBST_ENTITIES,
                false
            )
        ) {
            $this->close();

            throw new \RuntimeException(
                'Не удалось отключить подстановку XML-сущностей.'
            );
        }

        $this->opened = true;
    }

    /**
     * Прочитать следующую запись.
     *
     * Возвращает массив полей товара или null при нормальном
     * завершении файла.
     *
     * При ошибке разбора XML выбрасывает RuntimeException.
     *
     * @return array|string|null
     */
    public function read()
    {
        if (!$this->opened || !$this->reader) {
            throw new \RuntimeException(
                'Сначала необходимо открыть XML/YML-файл.'
            );
        }

        // Ошибки проверяются отдельно для текущего чтения.
        libxml_clear_errors();

        while (true) {
            $status = $this->reader->read();

            if (!$status) {
                $errors = libxml_get_errors();

                foreach ($errors as $error) {
                    if ($error->level >= LIBXML_ERR_ERROR) {
                        throw new \RuntimeException(
                            sprintf(
                                'Ошибка разбора XML/YML на строке %d: %s',
                                $error->line,
                                trim($error->message)
                            )
                        );
                    }
                }

                // Нет ошибок — это нормальный конец файла.
                return null;
            }

            if (
                $this->reader->nodeType !== \XMLReader::ELEMENT
                || $this->reader->localName !== $this->record_element
            ) {
                continue;
            }

            $element = $this->reader->expand();

            if (!$element instanceof \DOMElement) {
                throw new \RuntimeException(
                    'Не удалось прочитать элемент товара из XML/YML.'
                );
            }

            $dom = new \DOMDocument('1.0', 'UTF-8');

            $node = $dom->importNode($element, true);

            if (!$node instanceof \DOMElement) {
                throw new \RuntimeException(
                    'Не удалось преобразовать элемент товара в DOM.'
                );
            }

            $dom->appendChild($node);

            return $this->element_to_array($node);
        }
    }

    /**
     * Преобразовать XML-элемент в массив.
     *
     * Атрибуты сохраняются под ключом @attributes.
     * Текстовое содержимое — под ключом @value.
     * Повторяющиеся дочерние элементы становятся массивами.
     *
     * @param \DOMElement $element XML-элемент.
     *
     * @return array|string
     */
    private function element_to_array(\DOMElement $element)
    {
        $result = [];
        $repeated = [];

        if ($element->hasAttributes()) {
            foreach ($element->attributes as $attribute) {
                $result['@attributes'][$attribute->nodeName] =
                    $attribute->nodeValue;
            }
        }

        foreach ($element->childNodes as $child) {
            if ($child instanceof \DOMElement) {
                $name = $child->localName;
                $value = $this->element_to_array($child);

                if (!array_key_exists($name, $result)) {
                    $result[$name] = $value;
                    continue;
                }

                if (!isset($repeated[$name])) {
                    $result[$name] = [$result[$name]];
                    $repeated[$name] = true;
                }

                $result[$name][] = $value;

                continue;
            }

            if (
                $child->nodeType === XML_TEXT_NODE
                || $child->nodeType === XML_CDATA_SECTION_NODE
            ) {
                $text = trim($child->nodeValue);

                if ($text !== '') {
                    $result['@value'] =
                        ($result['@value'] ?? '') . $text;
                }
            }
        }

        if (!$result) {
            return trim($element->textContent);
        }

        return $result;
    }
    /**
     * Закрыть файл и восстановить состояние libxml.
     */
    public function close()
    {
        if ($this->reader) {
            $this->reader->close();
            $this->reader = null;
        }

        $this->opened = false;

        if ($this->internal_errors_enabled) {
            libxml_clear_errors();

            libxml_use_internal_errors(
                $this->previous_internal_errors
            );

            $this->previous_internal_errors = null;
            $this->internal_errors_enabled = false;
        }
    }

    public function __destruct()
    {
        $this->close();
    }
}
