<?php

if (!defined('ABSPATH')) {
    exit;
}

class PWQB_Shortcode
{
    public static function init(): void
    {
        add_shortcode('pw_quiz', [self::class, 'render']);
        add_action('wp_ajax_pwqb_load_quiz', [self::class, 'ajax_load_quiz']);
        add_action('wp_ajax_nopriv_pwqb_load_quiz', [self::class, 'ajax_load_quiz']);
        add_action('wp_footer', [self::class, 'render_popup']);
        add_action('wp_enqueue_scripts', [self::class, 'enqueue_assets']);
    }

    /**
     * AJAX-загрузка квиза для попапа.
     */
    public static function ajax_load_quiz(): void
    {
        $quiz_id = isset($_POST['quiz_id'])
            ? absint($_POST['quiz_id'])
            : 0;

        if (!$quiz_id) {
            wp_send_json_error([
                'message' => 'Не указан ID квиза.',
            ], 400);
        }

        $quiz = get_post($quiz_id);

        if (
            !$quiz ||
            $quiz->post_type !== PWQB_Quiz::POST_TYPE ||
            $quiz->post_status !== 'publish'
        ) {
            wp_send_json_error([
                'message' => 'Квиз не найден.',
            ], 404);
        }

        $html = self::render([
            'id' => $quiz_id,
        ]);

        if ($html === '') {
            wp_send_json_error([
                'message' => 'Не удалось загрузить квиз.',
            ], 500);
        }

        wp_send_json_success([
            'html' => $html,
        ]);
    }

    /**
     * Вывод общего попапа для квизов.
     */
    public static function render_popup(): void
    {
?>
        <div class="pwqb-popup" data-pwqb-popup hidden>

            <div class="pwqb-popup__overlay" data-pwqb-popup-close></div>

            <div
                class="pwqb-popup__dialog"
                role="dialog"
                aria-modal="true"
                aria-label="Квиз">

                <button
                    type="button"
                    class="pwqb-popup__close"
                    data-pwqb-popup-close
                    aria-label="Закрыть">
                    &times;
                </button>

                <div class="pwqb-popup__content" data-pwqb-popup-content>
                    <div class="pwqb-popup__loading" hidden>
                        Загрузка...
                    </div>
                </div>

            </div>

        </div>
    <?php
    }

    /**
     * Подключение ресурсов фронтенда.
     */
    public static function enqueue_assets(): void
    {
        wp_enqueue_style(
            'pwqb-quiz',
            PWQB_URL . 'public/css/quiz.css',
            [],
            PWQB_VERSION
        );

        // Основной файл.
        wp_enqueue_script(
            'pwqb-quiz',
            PWQB_URL . 'public/js/quiz.js',
            [],
            PWQB_VERSION,
            true
        );

        // Передаём настройки AJAX.
        wp_localize_script(
            'pwqb-quiz',
            'PWQBQuiz',
            [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'loadQuizAction' => 'pwqb_load_quiz',
                'productsNonce' => wp_create_nonce('pwqb_get_products'),
                'openSubmissionNonce' => wp_create_nonce('pwqb_submit_open_quiz'),
            ]
        );

        // Дополнительные модули.
        $scripts = [
            'quiz-answers',
            'quiz-attributes',
            'quiz-navigation',
            'quiz-result',
            'quiz-form',
            'quiz-products',
            'quiz-contact',
            'quiz-popup',
        ];

        $dependency = 'pwqb-quiz';

        foreach ($scripts as $script) {
            $handle = 'pwqb-' . $script;

            $dependencies = [$dependency];

            if ($script === 'quiz-products') {
                $dependencies[] = 'tr-swiper';
            }

            wp_enqueue_script(
                $handle,
                PWQB_URL . 'public/js/' . $script . '.js',
                $dependencies,
                PWQB_VERSION,
                true
            );

            $dependency = $handle;
        }
    }

    /**
     * Получение изображения.
     */
    private static function render_image(int $image_id, string $class = ''): string
    {
        if (!$image_id || !wp_attachment_is_image($image_id)) {
            return '';
        }

        return (string) wp_get_attachment_image(
            $image_id,
            'medium',
            false,
            [
                'class'   => $class,
                'loading' => 'lazy',
                'alt'     => '',
            ]
        );
    }

    /**
     * Вывод квиза.
     */
    public static function render($atts): string
    {
        $atts = shortcode_atts(
            ['id' => 0],
            $atts,
            'pw_quiz'
        );

        $quiz_id = absint($atts['id']);

        if (!$quiz_id) {
            return '';
        }

        $quiz = get_post($quiz_id);

        if (
            !$quiz ||
            $quiz->post_type !== PWQB_Quiz::POST_TYPE ||
            $quiz->post_status !== 'publish'
        ) {
            return '';
        }

        $settings = get_post_meta(
            $quiz_id,
            PWQB_Quiz::META_SETTINGS,
            true
        );

        if (!is_array($settings)) {
            return '';
        }

        $questions = $settings['questions'] ?? [];

        if (!is_array($questions) || empty($questions)) {
            return '';
        }

        self::enqueue_assets();

        $description = $settings['description']
            ?? get_post_meta(
                $quiz_id,
                PWQB_Quiz::META_DESCRIPTION,
                true
            );

        $result_mode = ($settings['result_mode'] ?? 'open') === 'closed'
            ? 'closed'
            : 'open';


        $started_at = time();

        $start_token = hash_hmac(
            'sha256',
            $quiz_id . '|' . $started_at,
            wp_salt('auth')
        );
        ob_start();
    ?>

        <div
            class="pwqb-quiz"
            data-quiz-id="<?php echo esc_attr($quiz_id); ?>"
            data-started-at="<?php echo esc_attr($started_at); ?>"
            data-start-token="<?php echo esc_attr($start_token); ?>">

            <div class="pwqb-quiz__intro" data-pwqb-intro>
                <h2 class="pwqb-quiz__title">
                    <?php echo esc_html(get_the_title($quiz_id)); ?>
                </h2>

                <?php if ($description !== '') : ?>
                    <div class="pwqb-quiz__description">
                        <?php echo wpautop(esc_html($description)); ?>
                    </div>
                <?php endif; ?>

                <div class="intro-buttons">


                    <button
                        type="button"
                        class="pwqb-quiz__button pwqb-quiz__button--next"
                        data-pwqb-start>
                        Подобрать решение
                    </button>

                    <a href="#" class="button quiz-help">
                        Нужна помощь эксперта
                    </a>

                </div>


            </div>

            <div class="pwqb-quiz__questions" data-pwqb-frontend-questions hidden>

                <?php foreach ($questions as $question_index => $question) : ?>

                    <?php
                    if (!is_array($question)) {
                        continue;
                    }

                    $question_id = sanitize_key($question['id'] ?? '');

                    if ($question_id === '') {
                        continue;
                    }

                    $answers = $question['answers'] ?? [];

                    if (!is_array($answers)) {
                        $answers = [];
                    }

                    $question_type = $question['type'] ?? 'single';
                    $is_multiple = $question_type === 'multiple';
                    ?>

                    <section
                        class="pwqb-quiz__question <?php echo $question_index === 0 ? 'pwqb-quiz__question--intro-step' : ''; ?>"
                        data-pwqb-frontend-question
                        data-question-id="<?php echo esc_attr($question_id); ?>"
                        data-question-index="<?php echo esc_attr($question_index); ?>"
                        data-question-type="<?php echo $is_multiple ? 'multiple' : 'single'; ?>"
                        <?php echo $question_index > 0 ? 'hidden' : ''; ?>>

                        <div class="pwqb-quiz__question-heading">

                            <!-- <span class="pwqb-quiz__question-number">
                                Вопрос <?php //echo esc_html($question_index + 1); 
                                        ?>
                            </span> -->

                            <div class="pwqb-quiz__question-heading__content">
                                <h3 class="pwqb-quiz__question-title">
                                    <?php echo esc_html($question['title'] ?? ''); ?>
                                </h3>

                                <?php if (!empty($question['description'])) : ?>
                                    <div class="pwqb-quiz__question-description">
                                        <?php echo wpautop(esc_html($question['description'])); ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <a href="#" class="button quiz-help">Нужна помощь эксперта</a>

                            <?php
                            // echo self::render_image(
                            //     absint($question['image_id'] ?? 0),
                            //     'pwqb-quiz__question-image'
                            // );
                            ?>

                        </div>
                        <div class="pwqb-quiz__question-inner">

                            <div class="pwqb-quiz__answers">

                                <?php foreach ($answers as $answer) : ?>

                                    <?php
                                    if (!is_array($answer)) {
                                        continue;
                                    }

                                    $answer_id = sanitize_key($answer['id'] ?? '');

                                    if ($answer_id === '') {
                                        continue;
                                    }

                                    $next_question = sanitize_key(
                                        $answer['next_question'] ?? ''
                                    );

                                    $answer_attributes = isset($answer['attributes']) && is_array($answer['attributes'])
                                        ? $answer['attributes']
                                        : [];

                                    $answer_attributes_json = wp_json_encode($answer_attributes);


                                    $answer_image_id = absint($answer['image_id'] ?? 0);

                                    $answer_classes = ['pwqb-quiz__answer'];

                                    if ($answer_image_id) {
                                        $answer_classes[] = 'pwqb-quiz__answer--has-image';
                                    }

                                    if ($question_index === 0) {
                                        $answer_classes[] = 'pwqb-quiz__answer--intro-step';
                                    }
                                    ?>
                                    <label
                                        class="<?php echo esc_attr(implode(' ', $answer_classes)); ?>"
                                        data-pwqb-frontend-answer
                                        data-answer-id="<?php echo esc_attr($answer_id); ?>"
                                        data-next-question="<?php echo esc_attr($next_question); ?>"
                                        data-attributes="<?php echo esc_attr($answer_attributes_json); ?>">
                                        <?php
                                        echo self::render_image(
                                            $answer_image_id,
                                            'pwqb-quiz__answer-image'
                                        );
                                        ?>



                                        <span class="pwqb-quiz__answer-content">

                                            <input
                                                type="<?php echo $is_multiple ? 'checkbox' : 'radio'; ?>"
                                                name="pwqb_answer_<?php echo esc_attr($quiz_id . '_' . $question_id); ?>"
                                                value="<?php echo esc_attr($answer_id); ?>"
                                                data-pwqb-answer-input>

                                            <div class="pwqb-quiz__answer-content__inner">
                                                <span class="pwqb-quiz__answer-title">
                                                    <?php echo esc_html($answer['title'] ?? ''); ?>
                                                </span>

                                                <?php if (!empty($answer['description'])) : ?>
                                                    <span class="pwqb-quiz__answer-description">
                                                        <?php echo esc_html($answer['description']); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </span>

                                    </label>

                                <?php endforeach; ?>
                                <?php if ($is_multiple) : ?>
                                    <p class="pwqb-quiz__hint">
                                        Можно выбрать несколько вариантов.
                                    </p>
                                <?php endif; ?>
                            </div>

                            <?php echo $question_index === 0 ? '<a href="#" class="button quiz-help">Нужна помощь эксперта</a>' : ''; ?>



                            <div class="pwqb-quiz__controls">

                                <div class="pwqb-quiz__progress" data-pwqb-progress hidden>
                                    <div class="pwqb-quiz__progress-info">
                                        <span data-pwqb-progress-label></span>
                                    </div>

                                    <div
                                        class="pwqb-quiz__progress-track"
                                        role="progressbar"
                                        aria-valuemin="0"
                                        aria-valuemax="100"
                                        aria-valuenow="0"
                                        data-pwqb-progress-bar>
                                        <div class="pwqb-quiz__progress-fill"></div>
                                    </div>
                                </div>

                                <div class="pwqb-quiz__navigation" data-pwqb-navigation hidden>
                                    <button
                                        type="button"
                                        class="pwqb-quiz__button pwqb-quiz__button--next"
                                        data-pwqb-next
                                        disabled>
                                        Далее
                                    </button>
                                </div>

                            </div>
                        </div>

                    </section>

                <?php endforeach; ?>

                <div class="pwqb-quiz__progress" hidden>

                    <div
                        class="pwqb-quiz__progress-track"
                        role="progressbar"
                        aria-valuemin="0"
                        aria-valuemax="100"
                        aria-valuenow="0"
                        data-pwqb-progress-bar>
                        <div class="pwqb-quiz__progress-fill"></div>
                    </div>
                </div>

            </div>


            <div class="pwqb-quiz__result" data-pwqb-result hidden>

                <?php if ($result_mode === 'closed') : ?>

                    <div class="pwqb-quiz__closed-result">

                        <h3 class="pwqb-quiz__result-title">
                            Подборка почти готова!
                        </h3>

                        <p class="pwqb-quiz__result-description">
                            Оставьте контактные данные, и мы подготовим для вас подборку.
                        </p>

                        <form
                            class="pwqb-quiz__submission-form"
                            data-pwqb-submission-form>

                            <?php wp_nonce_field('pwqb_submit_quiz', 'pwqb_submission_nonce'); ?>

                            <?php
                            $quiz_started_at = time();

                            $quiz_start_token = hash_hmac(
                                'sha256',
                                $quiz_id . '|' . $quiz_started_at,
                                wp_salt('auth')
                            );
                            ?>

                            <input
                                type="hidden"
                                name="pwqb_started_at"
                                value="<?php echo esc_attr($quiz_started_at); ?>">

                            <input
                                type="hidden"
                                name="pwqb_start_token"
                                value="<?php echo esc_attr($quiz_start_token); ?>">

                            <div class="pwqb-quiz__honeypot" aria-hidden="true">
                                <label for="pwqb-website-<?php echo esc_attr($quiz_id); ?>">
                                    Не заполняйте это поле
                                </label>
                                <input
                                    type="text"
                                    id="pwqb-website-<?php echo esc_attr($quiz_id); ?>"
                                    name="pwqb_website"
                                    value=""
                                    tabindex="-1"
                                    autocomplete="off">
                            </div>

                            <div class="pwqb-quiz__form-field">
                                <label for="pwqb-name-<?php echo esc_attr($quiz_id); ?>">
                                    Ваше имя *
                                </label>
                                <input
                                    type="text"
                                    id="pwqb-name-<?php echo esc_attr($quiz_id); ?>"
                                    name="name"
                                    required
                                    maxlength="190">
                            </div>

                            <div class="pwqb-quiz__form-field">
                                <label for="pwqb-email-<?php echo esc_attr($quiz_id); ?>">
                                    Email *
                                </label>
                                <input
                                    type="email"
                                    id="pwqb-email-<?php echo esc_attr($quiz_id); ?>"
                                    name="email"
                                    required
                                    maxlength="190">
                            </div>

                            <div class="pwqb-quiz__form-field">
                                <label for="pwqb-send-to-<?php echo esc_attr($quiz_id); ?>">
                                    Куда отправить подборку?
                                </label>
                                <select
                                    id="pwqb-send-to-<?php echo esc_attr($quiz_id); ?>"
                                    name="send_to"
                                    required
                                    data-pwqb-send-to>
                                    <option value="email">На email</option>
                                    <option value="messenger">В мессенджер</option>
                                    <option value="both">На email и в мессенджер</option>
                                </select>
                            </div>

                            <div class="pwqb-quiz__messenger-fields" data-pwqb-messenger-fields hidden>

                                <fieldset class="pwqb-quiz__form-field" data-pwqb-messengers>
                                    <legend>Какими мессенджерами пользуетесь? *</legend>

                                    <label>
                                        <input type="checkbox" name="messengers[]" value="telegram">
                                        Telegram
                                    </label>

                                    <label>
                                        <input type="checkbox" name="messengers[]" value="whatsapp">
                                        WhatsApp
                                    </label>

                                    <label>
                                        <input type="checkbox" name="messengers[]" value="viber">
                                        Viber
                                    </label>
                                </fieldset>

                                <div class="pwqb-quiz__form-field">
                                    <label for="pwqb-phone-<?php echo esc_attr($quiz_id); ?>">
                                        Номер телефона *
                                    </label>

                                    <input
                                        type="tel"
                                        id="pwqb-phone-<?php echo esc_attr($quiz_id); ?>"
                                        name="phone"
                                        placeholder="+7 (___) ___-__-__"
                                        autocomplete="tel"
                                        data-pwqb-phone>
                                </div>

                            </div>

                            <div class="pwqb-quiz__form-field">
                                <label for="pwqb-comment-<?php echo esc_attr($quiz_id); ?>">
                                    Ваш вопрос или комментарий
                                </label>
                                <textarea
                                    id="pwqb-comment-<?php echo esc_attr($quiz_id); ?>"
                                    name="comment"
                                    rows="4"></textarea>
                            </div>

                            <div
                                class="pwqb-quiz__form-message"
                                data-pwqb-form-message
                                hidden>
                            </div>

                            <button
                                type="submit"
                                class="pwqb-quiz__button pwqb-quiz__button--next">
                                Отправить
                            </button>

                        </form>

                    </div>


                <?php else : ?>

                    <div class="pwqb-quiz__open-result">

                        <div class="pwqb-quiz__contact-offer">

                            <div class="pwqb-quiz__contact-offer__inner">
                                <h3 class="pwqb-quiz__result-title">
                                    Нет подходящего варианта?
                                </h3>

                                <p class="pwqb-quiz__result-description">
                                    Оставьте ваши контакты, поможем выбрать нужное.
                                </p>

                                <form
                                    class="pwqb-quiz__contact-form"
                                    data-pwqb-contact-form>

                                    <div class="pwqb-quiz__form-field">
                                        <label for="pwqb-contact-phone-<?php echo esc_attr($quiz_id); ?>">
                                            <input
                                                type="tel"
                                                id="pwqb-contact-phone-<?php echo esc_attr($quiz_id); ?>"
                                                name="phone"
                                                placeholder="Телефон*"
                                                autocomplete="tel"
                                                required
                                                data-pwqb-contact-phone>
                                        </label>


                                    </div>

                                    <label class="pwqb-quiz__consent">
                                        <input
                                            type="checkbox"
                                            name="consent"
                                            value="1"
                                            required
                                            data-pwqb-contact-consent>

                                        <span>
                                            Я выражаю согласие на передачу и обработку
                                            персональных данных, в соответствии с
                                            <a href="/privacy-policy/" target="_blank">
                                                Политикой конфиденциальности
                                            </a>.
                                        </span>
                                    </label>

                                    <button
                                        type="submit"
                                        class="pwqb-quiz__button pwqb-quiz__button--next"
                                        data-pwqb-contact-submit
                                        disabled>
                                        Отправить
                                    </button>

                                    <div
                                        class="pwqb-quiz__contact-message"
                                        data-pwqb-contact-message
                                        role="status"
                                        aria-live="polite"
                                        hidden>
                                    </div>

                                </form>
                            </div>
                            <div class="pwqb-quiz__contact-alternative">
                                <span>или свяжитесь с нами</span>

                                <a href="tel:+70000000000">
                                    +7 (000) 000-00-00
                                </a>
                            </div>

                        </div>

                        <div
                            class="pwqb-quiz__products-result"
                            data-pwqb-products-result>

                            <div class="products-loader" hidden>
                                <span class="loading-dots" aria-label="Загрузка">
                                    <span></span>
                                    <span></span>
                                    <span></span>
                                </span>
                            </div>

                            <h3 class="pwqb-quiz__products-title">
                                Найдено
                                <span data-pwqb-products-count>0</span>
                                <span data-pwqb-products-label>товаров</span>
                                по вашему запросу
                            </h3>

                            <div class="pwqb-quiz__products-slider swiper">
                                <button
                                    type="button"
                                    class="pwqb-quiz__products-prev"
                                    aria-label="Предыдущие товары">
                                    <svg width="21" height="35" viewBox="0 0 21 35" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M17.6194 0.107826L20.2597 2.70776C20.4086 2.85592 20.4094 3.09479 20.2628 3.24219L6.22408 17.3886L20.2435 31.6211C20.3871 31.7674 20.3879 32.0029 20.2435 32.1495L17.6424 34.7926C17.495 34.9392 17.2561 34.9384 17.1095 34.791L0.109559 17.7087C-0.0363307 17.5621 -0.0367086 17.3243 0.109559 17.1773L17.0861 0.112739C17.2338 -0.0357963 17.4716 -0.037686 17.6194 0.107826Z" fill="#1D1D1F" />
                                    </svg>
                                </button>
                                <div
                                    class="swiper-wrapper"
                                    data-pwqb-products-list>
                                </div>
                                <button
                                    type="button"
                                    class="pwqb-quiz__products-next"
                                    aria-label="Следующие товары">
                                    <svg width="21" height="35" viewBox="0 0 21 35" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M2.75273 0.107826L0.112349 2.70776C-0.0365641 2.85592 -0.0373207 3.09479 0.109325 3.24219L14.148 17.3886L0.128601 31.6211C-0.0150211 31.7674 -0.015777 32.0029 0.128601 32.1495L2.72967 34.7926C2.87707 34.9392 3.11594 34.9384 3.26259 34.791L20.2625 17.7087C20.4084 17.5621 20.4088 17.3243 20.2625 17.1773L3.28602 0.112739C3.13824 -0.0357963 2.90051 -0.037686 2.75273 0.107826Z" fill="#1D1D1F" />
                                    </svg>
                                </button>
                            </div>

                        </div>

                    </div>

                <?php endif; ?>


                <button
                    type="button"
                    class="pwqb-quiz__button"
                    data-pwqb-restart>
                    Пройти ещё раз
                </button>

            </div>


        </div>

<?php

        return (string) ob_get_clean();
    }
}
