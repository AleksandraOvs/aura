```php
<?php

if (!defined('ABSPATH')) {
    exit;
}

use SupplierImporter\Parsers\YmlParser;

$file = SUPPLIER_IMPORTER_PATH . 'crystal-lux.xml';

$errors = [];
$result = null;

if (!file_exists($file)) {

    $errors[] = 'Файл не найден: ' . $file;
} elseif (!is_readable($file)) {

    $errors[] = 'Файл недоступен для чтения: ' . $file;
} else {

    /*
     * -----------------------------------------------------
     * Проверяем XML напрямую через SimpleXML
     * -----------------------------------------------------
     */

    libxml_use_internal_errors(true);

    $xml = simplexml_load_file(
        $file,
        'SimpleXMLElement',
        LIBXML_NOCDATA | LIBXML_NONET
    );

    if ($xml === false) {

        foreach (libxml_get_errors() as $error) {

            $errors[] = sprintf(
                'Строка %d: %s',
                $error->line,
                trim($error->message)
            );
        }

        libxml_clear_errors();
    } else {

        libxml_clear_errors();


        /*
         * -------------------------------------------------
         * Проверяем offers
         * -------------------------------------------------
         */

        $offers = $xml->xpath('//offer');

        if ($offers === false) {
            $offers = [];
        }


        /*
         * -------------------------------------------------
         * Проверяем категории
         * -------------------------------------------------
         */

        $categories = $xml->xpath(
            '//shop/categories/category'
        );

        if ($categories === false) {
            $categories = [];
        }


        /*
         * -------------------------------------------------
         * Проверяем через наш YmlParser
         * -------------------------------------------------
         */

        try {

            $parser = new YmlParser();

            $result = $parser->preview(
                $file,
                3
            );
        } catch (\Throwable $e) {

            $errors[] =
                'YmlParser: ' .
                $e->getMessage();
        }
    }
}

?>

<div class="wrap">

    <h1>Crystal Lux XML — диагностика</h1>


    <h2>Файл</h2>

    <table class="widefat striped">

        <tbody>

            <tr>
                <td width="200">
                    Путь
                </td>

                <td>
                    <code>
                        <?php echo esc_html($file); ?>
                    </code>
                </td>
            </tr>


            <tr>
                <td>
                    Размер
                </td>

                <td>

                    <?php if (file_exists($file)): ?>

                        <?php
                        echo esc_html(
                            size_format(
                                filesize($file)
                            )
                        );
                        ?>

                    <?php else: ?>

                        —

                    <?php endif; ?>

                </td>
            </tr>


            <tr>
                <td>
                    Доступен для чтения
                </td>

                <td>

                    <?php if (is_readable($file)): ?>

                        <strong style="color:green;">
                            Да
                        </strong>

                    <?php else: ?>

                        <strong style="color:red;">
                            Нет
                        </strong>

                    <?php endif; ?>

                </td>
            </tr>

        </tbody>

    </table>


    <?php if (!empty($errors)): ?>

        <div class="notice notice-error">

            <p>
                <strong>Обнаружены ошибки:</strong>
            </p>

            <ul>

                <?php foreach ($errors as $error): ?>

                    <li>
                        <code>
                            <?php echo esc_html($error); ?>
                        </code>
                    </li>

                <?php endforeach; ?>

            </ul>

        </div>

    <?php endif; ?>


    <?php if ($xml !== false && isset($xml)): ?>

        <div class="notice notice-success">

            <p>
                <strong>
                    XML успешно разобран SimpleXML.
                </strong>
            </p>

        </div>


        <h2>Структура XML</h2>

        <table class="widefat striped">

            <tbody>

                <tr>

                    <td width="250">
                        Корневой элемент
                    </td>

                    <td>
                        <code>
                            <?php
                            echo esc_html(
                                $xml->getName()
                            );
                            ?>
                        </code>
                    </td>

                </tr>


                <tr>

                    <td>
                        Дата YML
                    </td>

                    <td>
                        <code>
                            <?php
                            echo esc_html(
                                (string) $xml['date']
                            );
                            ?>
                        </code>
                    </td>

                </tr>


                <tr>

                    <td>
                        Товаров &lt;offer&gt;
                    </td>

                    <td>

                        <strong>
                            <?php
                            echo count($offers);
                            ?>
                        </strong>

                    </td>

                </tr>


                <tr>

                    <td>
                        Категорий
                    </td>

                    <td>

                        <strong>
                            <?php
                            echo count($categories);
                            ?>
                        </strong>

                    </td>

                </tr>

            </tbody>

        </table>


        <h2>Первые товары</h2>

        <?php if (!empty($result['rows'])): ?>

            <?php foreach (
                $result['rows'] as $index => $row
            ): ?>

                <details
                    style="
                        background:#fff;
                        border:1px solid #ccd0d4;
                        margin-bottom:15px;
                    ">

                    <summary
                        style="
                            cursor:pointer;
                            padding:15px;
                            font-size:16px;
                            font-weight:600;
                        ">

                        Товар №<?php echo $index + 1; ?>

                        <?php if (!empty($row['id'])): ?>

                            —
                            ID:
                            <?php
                            echo esc_html(
                                $row['id']
                            );
                            ?>

                        <?php endif; ?>

                        <?php if (!empty($row['model'])): ?>

                            —
                            <?php
                            echo esc_html(
                                $row['model']
                            );
                            ?>

                        <?php endif; ?>

                    </summary>


                    <div style="padding:20px;">

                        <h3>
                            Основные поля
                        </h3>

                        <pre style="
                            background:#f6f7f7;
                            padding:15px;
                            overflow:auto;
                        "><?php

                            $main = [
                                'id'          => $row['id'] ?? null,
                                'available'   => $row['available'] ?? null,
                                'url'         => $row['url'] ?? null,
                                'price'       => $row['price'] ?? null,
                                'currencyId'  => $row['currencyId'] ?? null,
                                'categoryId'  => $row['categoryId'] ?? null,
                                'vendor'      => $row['vendor'] ?? null,
                                'model'       => $row['model'] ?? null,
                                'count'       => $row['count'] ?? null,
                            ];

                            echo esc_html(
                                print_r(
                                    $main,
                                    true
                                )
                            );

                            ?></pre>


                        <h3>
                            Категория
                        </h3>

                        <pre style="
                            background:#f6f7f7;
                            padding:15px;
                            overflow:auto;
                        "><?php

                            echo esc_html(
                                print_r(
                                    $row['_category'] ?? null,
                                    true
                                )
                            );

                            ?></pre>


                        <h3>
                            Изображения
                        </h3>

                        <pre style="
                            background:#f6f7f7;
                            padding:15px;
                            overflow:auto;
                        "><?php

                            echo esc_html(
                                print_r(
                                    $row['picture'] ?? [],
                                    true
                                )
                            );

                            ?></pre>


                        <h3>
                            Параметры
                        </h3>

                        <pre style="
                            background:#f6f7f7;
                            padding:15px;
                            overflow:auto;
                        "><?php

                            echo esc_html(
                                print_r(
                                    $row['param'] ?? [],
                                    true
                                )
                            );

                            ?></pre>


                        <h3>
                            Полная строка после YmlParser
                        </h3>

                        <pre style="
                            background:#1d2327;
                            color:#fff;
                            padding:20px;
                            overflow:auto;
                            max-height:700px;
                        "><?php

                            echo esc_html(
                                print_r(
                                    $row,
                                    true
                                )
                            );

                            ?></pre>

                    </div>

                </details>

            <?php endforeach; ?>

        <?php else: ?>

            <p>
                Товары не найдены.
            </p>

        <?php endif; ?>


        <h2>
            Первые категории
        </h2>

        <pre style="
            background:#1d2327;
            color:#fff;
            padding:20px;
            overflow:auto;
            max-height:500px;
        "><?php

            $categoryPreview = [];

            foreach (
                array_slice(
                    $categories,
                    0,
                    20
                ) as $category
            ) {

                $categoryPreview[] = [
                    'id' => (string) $category['id'],
                    'parent_id' =>
                    isset($category['parentId'])
                        ? (string) $category['parentId']
                        : null,
                    'name' =>
                    trim((string) $category),
                ];
            }

            echo esc_html(
                print_r(
                    $categoryPreview,
                    true
                )
            );

            ?></pre>


    <?php endif; ?>

</div>
```