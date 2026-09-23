<?php

namespace Supplier_Importer\Suppliers;

if (!defined('ABSPATH')) {
    exit;
}

use InvalidArgumentException;

class Supplier_Manager
{
    /**
     * Зарегистрированные поставщики.
     *
     * @var Supplier[]
     */
    private $suppliers = [];

    /**
     * Добавить поставщика.
     *
     * @param Supplier $supplier
     *
     * @return void
     */
    public function register(Supplier $supplier)
    {
        $this->suppliers[$supplier->get_id()] = $supplier;
    }

    /**
     * Получить поставщика по ID.
     *
     * @param string $id
     *
     * @return Supplier|null
     */
    public function get($id)
    {
        return isset($this->suppliers[$id])
            ? $this->suppliers[$id]
            : null;
    }

    /**
     * Получить всех поставщиков.
     *
     * @return Supplier[]
     */
    public function all()
    {
        return $this->suppliers;
    }

    /**
     * Определить поставщика по заголовкам CSV.
     *
     * @param array $headers
     *
     * @return Supplier|null
     */
    public function detect($headers)
    {
        foreach ($this->suppliers as $supplier) {
            if ($supplier->supports($headers)) {
                return $supplier;
            }
        }

        return null;
    }

    /**
     * Определить ID поставщика по заголовкам.
     *
     * @param array $headers
     *
     * @return string|null
     */
    public function detect_id($headers)
    {
        $supplier = $this->detect($headers);

        if (!$supplier) {
            return null;
        }

        return $supplier->get_id();
    }

    /**
     * Проверить существование поставщика.
     *
     * @param string $id
     *
     * @return bool
     */
    public function has($id)
    {
        return isset($this->suppliers[$id]);
    }

    /**
     * Требуемый поставщик.
     *
     * @param string $id
     *
     * @return Supplier
     *
     * @throws InvalidArgumentException
     */
    public function require($id)
    {
        $supplier = $this->get($id);

        if (!$supplier) {
            throw new InvalidArgumentException(
                sprintf(
                    'Поставщик "%s" не зарегистрирован.',
                    $id
                )
            );
        }

        return $supplier;
    }
}
