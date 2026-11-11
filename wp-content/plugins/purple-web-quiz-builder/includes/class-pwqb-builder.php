<?php

if (!defined('ABSPATH')) {
    exit;
}

class PWQB_Builder
{
    private const NONCE_ACTION = 'pwqb_save_builder';
    private const NONCE_NAME = 'pwqb_builder_nonce';

    public static function init(): void
    {
        add_action('add_meta_boxes', [self::class, 'add_meta_box']);
        add_action(
            'save_post_' . PWQB_Quiz::POST_TYPE,
            [self::class, 'save'],
            10,
            2
        );

        add_action('admin_enqueue_scripts', [self::class, 'enqueue_assets']);
    }

    /**
     * Добавление метабокса конструктора.
     */
    public static function add_meta_box(): void
    {
        add_meta_box(
            'pwqb_quiz_builder',
            'Конструктор квиза',
            [self::class, 'render'],
            PWQB_Quiz::POST_TYPE,
            'normal',
            'high'
        );
    }

    /**
     * Подключение ресурсов.
     */
    public static function enqueue_assets(string $hook): void
    {
        $screen = get_current_screen();

        if (
            !$screen ||
            $screen->post_type !== PWQB_Quiz::POST_TYPE ||
            !in_array($hook, ['post.php', 'post-new.php'], true)
        ) {
            return;
        }

        wp_enqueue_media();

        wp_enqueue_style(
            'pwqb-builder',
            PWQB_URL . 'admin/css/quiz-builder.css',
            [],
            PWQB_VERSION
        );

        wp_enqueue_script(
            'pwqb-builder',
            PWQB_URL . 'admin/js/quiz-builder.js',
            [],
            PWQB_VERSION,
            true
        );
    }

    /**
     * Получение настроек квиза.
     */
    private static function get_settings(int $post_id): array
    {
        $settings = get_post_meta(
            $post_id,
            PWQB_Quiz::META_SETTINGS,
            true
        );

        if (!is_array($settings)) {
            $settings = [];
        }

        // Поддержка описаний, сохранённых старой версией плагина.
        if (!isset($settings['description'])) {
            $settings['description'] = get_post_meta(
                $post_id,
                PWQB_Quiz::META_DESCRIPTION,
                true
            );
        }

        $settings['categories'] = isset($settings['categories'])
            && is_array($settings['categories'])
            ? array_map('absint', $settings['categories'])
            : [];

        $settings['result_mode'] = isset($settings['result_mode'])
            && $settings['result_mode'] === 'closed'
            ? 'closed'
            : 'open';

        $settings['questions'] = isset($settings['questions'])
            && is_array($settings['questions'])
            ? $settings['questions']
            : [];

        return $settings;
    }

    /**
     * Вывод конструктора.
     */
    public static function render(WP_Post $post): void
    {
        wp_nonce_field(
            self::NONCE_ACTION,
            self::NONCE_NAME
        );

        $settings = self::get_settings($post->ID);
        $description = $settings['description'];

?>
        <div class="pwqb-builder">

            <section class="pwqb-builder__section">
                <h2>Описание квиза</h2>

                <textarea
                    name="pwqb_settings[description]"
                    rows="3"
                    class="widefat"
                    placeholder="Расскажите, что поможет подобрать этот квиз."><?php echo esc_textarea($description); ?></textarea>
            </section>


            <section class="pwqb-builder__section">
                <h2>Результат квиза</h2>

                <p class="description">
                    Выберите, что увидит посетитель после прохождения квиза.
                </p>

                <label>
                    <input
                        type="checkbox"
                        name="pwqb_settings[result_mode]"
                        value="closed"
                        <?php checked($settings['result_mode'], 'closed'); ?>>

                    <strong>Закрытый ответ</strong>
                </label>

                <p class="description">
                    Если включено, вместо результата посетителю будет показана форма
                    для отправки контактных данных.
                </p>
            </section>


            <section class="pwqb-builder__section">
                <h2>Категории товаров</h2>

                <p class="description">
                    Выберите категории, в которых нужно искать товары.
                    Если ничего не выбрано, ограничение по категориям применяться не будет.
                </p>

                <div class="pwqb-builder__categories">
                    <?php if (class_exists('WooCommerce')) : ?>

                        <?php
                        wp_terms_checklist(0, [
                            'taxonomy'      => 'product_cat',
                            'selected_cats' => $settings['categories'],
                            'checked_ontop' => false,
                        ]);
                        ?>

                    <?php else : ?>

                        <p>Для выбора категорий необходимо установить WooCommerce.</p>

                    <?php endif; ?>
                </div>
            </section>

            <section class="pwqb-builder__section">
                <div class="pwqb-builder__heading">
                    <h2>Вопросы</h2>

                    <button
                        type="button"
                        class="button button-primary"
                        data-pwqb-add-question>
                        Добавить вопрос
                    </button>
                </div>

                <div class="pwqb-questions" data-pwqb-questions>
                    <?php
                    foreach ($settings['questions'] as $index => $question) {
                        self::render_question($question, (int) $index);
                    }
                    ?>
                </div>

                <div class="pwqb-empty" data-pwqb-empty <?php echo !empty($settings['questions']) ? 'hidden' : ''; ?>>
                    Пока нет вопросов. Добавьте первый вопрос.
                </div>
            </section>

        </div>
    <?php

        self::render_templates();
    }

    /**
     * Вывод одного вопроса.
     */
    private static function render_question(array $question, int $index): void
    {
        $id = isset($question['id'])
            ? sanitize_key($question['id'])
            : 'q_' . wp_generate_password(8, false, false);

        $title = isset($question['title'])
            ? $question['title']
            : '';

        $description = isset($question['description'])
            ? $question['description']
            : '';

        $image_id = isset($question['image_id'])
            ? absint($question['image_id'])
            : 0;

        $type = isset($question['type'])
            && $question['type'] === 'multiple'
            ? 'multiple'
            : 'single';

        $answers = isset($question['answers']) && is_array($question['answers'])
            ? $question['answers']
            : [];

    ?>
        <div class="pwqb-question" data-pwqb-question>

            <input
                type="hidden"
                name="pwqb_settings[questions][<?php echo esc_attr($index); ?>][id]"
                value="<?php echo esc_attr($id); ?>"
                data-field="id">

            <div class="pwqb-question__header">
                <strong class="pwqb-question__number">
                    Вопрос <?php echo esc_html($index + 1); ?>
                </strong>

                <div class="pwqb-question__actions">
                    <button type="button" class="button" data-pwqb-up>↑</button>
                    <button type="button" class="button" data-pwqb-down>↓</button>
                    <button type="button" class="button" data-pwqb-remove-question>
                        Удалить
                    </button>
                </div>
            </div>

            <div class="pwqb-question__body">

                <p>
                    <label><strong>Текст вопроса</strong></label>
                    <input
                        type="text"
                        class="widefat"
                        name="pwqb_settings[questions][<?php echo esc_attr($index); ?>][title]"
                        value="<?php echo esc_attr($title); ?>"
                        data-field="title">
                </p>

                <p>
                    <label><strong>Описание вопроса</strong></label>
                    <textarea
                        class="widefat"
                        rows="2"
                        name="pwqb_settings[questions][<?php echo esc_attr($index); ?>][description]"
                        data-field="description"><?php echo esc_textarea($description); ?></textarea>
                </p>

                <?php self::render_image_field($image_id, "pwqb_settings[questions][$index][image_id]", 'Изображение вопроса', 'question-image'); ?>

                <p>
                    <label><strong>Тип ответа</strong></label>
                    <select
                        name="pwqb_settings[questions][<?php echo esc_attr($index); ?>][type]"
                        data-field="type">
                        <option value="single" <?php selected($type, 'single'); ?>>
                            Один вариант
                        </option>
                        <option value="multiple" <?php selected($type, 'multiple'); ?>>
                            Несколько вариантов
                        </option>
                    </select>
                </p>

                <div class="pwqb-answers">
                    <div class="pwqb-answers__heading">
                        <strong>Варианты ответов</strong>

                        <button
                            type="button"
                            class="button"
                            data-pwqb-add-answer>
                            Добавить ответ
                        </button>
                    </div>

                    <div data-pwqb-answers>
                        <?php
                        foreach ($answers as $answer_index => $answer) {
                            self::render_answer(
                                $answer,
                                $index,
                                (int) $answer_index
                            );
                        }
                        ?>
                    </div>
                </div>

            </div>
        </div>
    <?php
    }

    /**
     * Вывод одного ответа.
     */
    private static function render_answer(
        array $answer,
        int $question_index,
        int $answer_index
    ): void {
        $id = isset($answer['id'])
            ? sanitize_key($answer['id'])
            : 'a_' . wp_generate_password(8, false, false);

        $title = isset($answer['title'])
            ? $answer['title']
            : '';

        $image_id = isset($answer['image_id'])
            ? absint($answer['image_id'])
            : 0;

        $next_question = isset($answer['next_question'])
            ? sanitize_key($answer['next_question'])
            : '';

    ?>
        <div class="pwqb-answer" data-pwqb-answer>

            <input
                type="hidden"
                name="pwqb_settings[questions][<?php echo esc_attr($question_index); ?>][answers][<?php echo esc_attr($answer_index); ?>][id]"
                value="<?php echo esc_attr($id); ?>"
                data-field="id">

            <div class="pwqb-answer__content">

                <p>
                    <label><strong>Текст ответа</strong></label>
                    <input
                        type="text"
                        class="widefat"
                        name="pwqb_settings[questions][<?php echo esc_attr($question_index); ?>][answers][<?php echo esc_attr($answer_index); ?>][title]"
                        value="<?php echo esc_attr($title); ?>"
                        data-field="title">
                </p>

                <?php
                self::render_image_field(
                    $image_id,
                    "pwqb_settings[questions][$question_index][answers][$answer_index][image_id]",
                    'Изображение ответа',
                    'answer-image'
                );
                ?>

                <p>
                    <label><strong>Следующий вопрос</strong></label>

                    <select
                        name="pwqb_settings[questions][<?php echo esc_attr($question_index); ?>][answers][<?php echo esc_attr($answer_index); ?>][next_question]"
                        data-field="next_question">
                        <option value="">Следующий по порядку</option>

                        <?php
                        // Список вопросов будет сформирован при следующем этапе.
                        ?>
                    </select>
                </p>

            </div>

            <button
                type="button"
                class="button"
                data-pwqb-remove-answer>
                Удалить ответ
            </button>

        </div>
    <?php
    }

    /**
     * Поле выбора изображения.
     */
    private static function render_image_field(
        int $image_id,
        string $name,
        string $label,
        string $class
    ): void {
        $image_url = $image_id
            ? wp_get_attachment_image_url($image_id, 'thumbnail')
            : '';

    ?>
        <div class="pwqb-image-field" data-pwqb-image>
            <strong><?php echo esc_html($label); ?></strong>

            <input
                type="hidden"
                name="<?php echo esc_attr($name); ?>"
                value="<?php echo esc_attr($image_id); ?>"
                data-field="image_id">

            <div class="pwqb-image-field__preview" data-image-preview>
                <?php if ($image_url) : ?>
                    <img src="<?php echo esc_url($image_url); ?>" alt="">
                <?php else : ?>
                    <span>Изображение не выбрано</span>
                <?php endif; ?>
            </div>

            <button type="button" class="button" data-pwqb-select-image>
                Выбрать изображение
            </button>

            <button
                type="button"
                class="button"
                data-pwqb-remove-image
                <?php echo !$image_id ? 'hidden' : ''; ?>>
                Удалить изображение
            </button>
        </div>
    <?php
    }

    /**
     * HTML-шаблоны для динамического добавления вопросов и ответов.
     */
    private static function render_templates(): void
    {
    ?>
        <script type="text/html" id="tmpl-pwqb-question">
            <div class="pwqb-question" data-pwqb-question>
                <input type="hidden" data-field="id">

                <div class="pwqb-question__header">
                    <strong class="pwqb-question__number"></strong>
                    <div class="pwqb-question__actions">
                        <button type="button" class="button" data-pwqb-up>↑</button>
                        <button type="button" class="button" data-pwqb-down>↓</button>
                        <button type="button" class="button" data-pwqb-remove-question>Удалить</button>
                    </div>
                </div>

                <div class="pwqb-question__body">
                    <p>
                        <label><strong>Текст вопроса</strong></label>
                        <input type="text" class="widefat" data-field="title">
                    </p>

                    <p>
                        <label><strong>Описание вопроса</strong></label>
                        <textarea class="widefat" rows="2" data-field="description"></textarea>
                    </p>

                    <div class="pwqb-image-field" data-pwqb-image>
                        <strong>Изображение вопроса</strong>
                        <input type="hidden" data-field="image_id" value="0">
                        <div class="pwqb-image-field__preview" data-image-preview>
                            <span>Изображение не выбрано</span>
                        </div>
                        <button type="button" class="button" data-pwqb-select-image>Выбрать изображение</button>
                        <button type="button" class="button" data-pwqb-remove-image hidden>Удалить изображение</button>
                    </div>

                    <p>
                        <label><strong>Тип ответа</strong></label>
                        <select data-field="type">
                            <option value="single">Один вариант</option>
                            <option value="multiple">Несколько вариантов</option>
                        </select>
                    </p>

                    <div class="pwqb-answers">
                        <div class="pwqb-answers__heading">
                            <strong>Варианты ответов</strong>
                            <button type="button" class="button" data-pwqb-add-answer>Добавить ответ</button>
                        </div>
                        <div data-pwqb-answers></div>
                    </div>
                </div>
            </div>
        </script>

        <script type="text/html" id="tmpl-pwqb-answer">
            <div class="pwqb-answer" data-pwqb-answer>
                <input type="hidden" data-field="id">

                <div class="pwqb-answer__content">
                    <p>
                        <label><strong>Текст ответа</strong></label>
                        <input type="text" class="widefat" data-field="title">
                    </p>

                    <div class="pwqb-image-field" data-pwqb-image>
                        <strong>Изображение ответа</strong>
                        <input type="hidden" data-field="image_id" value="0">
                        <div class="pwqb-image-field__preview" data-image-preview>
                            <span>Изображение не выбрано</span>
                        </div>
                        <button type="button" class="button" data-pwqb-select-image>Выбрать изображение</button>
                        <button type="button" class="button" data-pwqb-remove-image hidden>Удалить изображение</button>
                    </div>

                    <p>
                        <label><strong>Следующий вопрос</strong></label>
                        <select data-field="next_question">
                            <option value="">Следующий по порядку</option>
                        </select>
                    </p>
                </div>

                <button type="button" class="button" data-pwqb-remove-answer>Удалить ответ</button>
            </div>
        </script>
<?php
    }

    /**
     * Сохранение настроек конструктора.
     */
    public static function save(int $post_id, WP_Post $post): void
    {
        if (
            !isset($_POST[self::NONCE_NAME]) ||
            !wp_verify_nonce(
                sanitize_text_field(wp_unslash($_POST[self::NONCE_NAME])),
                self::NONCE_ACTION
            )
        ) {
            return;
        }

        if (
            (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) ||
            wp_is_post_revision($post_id) ||
            !current_user_can('edit_post', $post_id)
        ) {
            return;
        }

        $raw = isset($_POST['pwqb_settings']) && is_array($_POST['pwqb_settings'])
            ? wp_unslash($_POST['pwqb_settings'])
            : [];

        $settings = [
            'description' => isset($raw['description'])
                ? sanitize_textarea_field($raw['description'])
                : '',

            'result_mode' => isset($raw['result_mode'])
                && $raw['result_mode'] === 'closed'
                ? 'closed'
                : 'open',

            'categories'  => self::sanitize_categories($_POST['tax_input']['product_cat'] ?? []),

            'questions'   => self::sanitize_questions($raw['questions'] ?? []),
        ];

        update_post_meta(
            $post_id,
            PWQB_Quiz::META_SETTINGS,
            $settings
        );

        // Обновляем старое поле, чтобы старые интеграции продолжали работать.
        update_post_meta(
            $post_id,
            PWQB_Quiz::META_DESCRIPTION,
            $settings['description']
        );
    }

    /**
     * Проверка категорий WooCommerce.
     */
    private static function sanitize_categories($categories): array
    {
        if (!is_array($categories)) {
            return [];
        }

        $result = [];

        foreach ($categories as $category_id) {
            $category_id = absint($category_id);

            if (
                $category_id &&
                term_exists($category_id, 'product_cat')
            ) {
                $result[] = $category_id;
            }
        }

        return array_values(array_unique($result));
    }

    /**
     * Проверка вопросов и ответов.
     */
    private static function sanitize_questions($questions): array
    {
        if (!is_array($questions)) {
            return [];
        }

        $result = [];
        $used_ids = [];

        foreach ($questions as $question) {
            if (!is_array($question)) {
                continue;
            }

            $question_id = isset($question['id'])
                ? sanitize_key($question['id'])
                : '';

            if (!$question_id || isset($used_ids[$question_id])) {
                $question_id = 'q_' . wp_generate_password(8, false, false);
            }

            $used_ids[$question_id] = true;

            $type = isset($question['type']) && $question['type'] === 'multiple'
                ? 'multiple'
                : 'single';

            $answers = [];

            if (isset($question['answers']) && is_array($question['answers'])) {
                $used_answer_ids = [];

                foreach ($question['answers'] as $answer) {
                    if (!is_array($answer)) {
                        continue;
                    }

                    $answer_id = isset($answer['id'])
                        ? sanitize_key($answer['id'])
                        : '';

                    if (!$answer_id || isset($used_answer_ids[$answer_id])) {
                        $answer_id = 'a_' . wp_generate_password(8, false, false);
                    }

                    $used_answer_ids[$answer_id] = true;

                    $answers[] = [
                        'id'            => $answer_id,
                        'title'         => isset($answer['title'])
                            ? sanitize_text_field($answer['title'])
                            : '',
                        'image_id'      => self::sanitize_image_id($answer['image_id'] ?? 0),
                        'next_question' => isset($answer['next_question'])
                            ? sanitize_key($answer['next_question'])
                            : '',
                    ];
                }
            }

            $result[] = [
                'id'          => $question_id,
                'title'       => isset($question['title'])
                    ? sanitize_text_field($question['title'])
                    : '',
                'description' => isset($question['description'])
                    ? sanitize_textarea_field($question['description'])
                    : '',
                'image_id'    => self::sanitize_image_id($question['image_id'] ?? 0),
                'type'        => $type,
                'answers'     => $answers,
            ];
        }

        // Проверяем, что переходы ведут только к последующим вопросам.
        $question_positions = [];

        foreach ($result as $index => $question) {
            $question_positions[$question['id']] = $index;
        }

        foreach ($result as $index => &$question) {
            foreach ($question['answers'] as &$answer) {
                $next_question = $answer['next_question'];

                if ($next_question === '') {
                    continue;
                }

                // Проверяем существование целевого вопроса.
                if (!isset($question_positions[$next_question])) {
                    $answer['next_question'] = '';
                    continue;
                }

                // Запрещаем переход к текущему или предыдущему вопросу.
                if ($question_positions[$next_question] <= $index) {
                    $answer['next_question'] = '';
                }
            }

            unset($answer);
        }

        unset($question);

        return $result;
    }

    /**
     * Проверка изображения.
     */
    private static function sanitize_image_id($image_id): int
    {
        $image_id = absint($image_id);

        if (!$image_id || !wp_attachment_is_image($image_id)) {
            return 0;
        }

        return $image_id;
    }
}
