
<?php

if (!defined('ABSPATH')) {
    exit;
}

class PWQB_Submissions
{
    /**
     * Название таблицы заявок.
     */
    private static function get_table_name(): string
    {
        global $wpdb;

        return $wpdb->prefix . 'pwqb_submissions';
    }

    /**
     * Создаёт новую заявку.
     *
     * @return int|WP_Error ID созданной заявки или ошибка.
     */
    public static function create(array $data)
    {
        global $wpdb;

        $quiz_id = absint($data['quiz_id'] ?? 0);
        $quiz = get_post($quiz_id);

        if (
            !$quiz ||
            $quiz->post_type !== PWQB_Quiz::POST_TYPE ||
            $quiz->post_status !== 'publish'
        ) {
            return new WP_Error(
                'invalid_quiz',
                'Квиз не найден или не опубликован.'
            );
        }

        $name = sanitize_text_field($data['name'] ?? '');
        $email = sanitize_email($data['email'] ?? '');

        if ($name === '') {
            return new WP_Error(
                'empty_name',
                'Укажите имя.'
            );
        }

        if (!is_email($email)) {
            return new WP_Error(
                'invalid_email',
                'Укажите корректный email.'
            );
        }

        $send_to = sanitize_key($data['send_to'] ?? 'email');

        $allowed_send_to = ['email', 'messenger', 'both'];

        if (!in_array($send_to, $allowed_send_to, true)) {
            return new WP_Error(
                'invalid_send_to',
                'Выберите способ отправки подборки.'
            );
        }

        $messengers = $data['messengers'] ?? [];

        if (!is_array($messengers)) {
            $messengers = [];
        }

        $allowed_messengers = ['telegram', 'whatsapp', 'viber'];

        $messengers = array_values(array_unique(array_filter(
            array_map('sanitize_key', $messengers),
            static function ($messenger) use ($allowed_messengers) {
                return in_array($messenger, $allowed_messengers, true);
            }
        )));

        $phone = sanitize_text_field($data['phone'] ?? '');

        $needs_messenger = in_array($send_to, ['messenger', 'both'], true);

        if ($needs_messenger && empty($messengers)) {
            return new WP_Error(
                'empty_messengers',
                'Выберите хотя бы один мессенджер.'
            );
        }

        if ($needs_messenger) {
            $phone_digits = preg_replace('/\D+/', '', $phone);

            if (strlen($phone_digits) < 7 || strlen($phone_digits) > 15) {
                return new WP_Error(
                    'invalid_phone',
                    'Укажите корректный номер телефона.'
                );
            }
        } else {
            $messengers = [];
            $phone = '';
        }

        $answers = $data['answers'] ?? [];

        if (!is_array($answers)) {
            $answers = [];
        }

        $user_id = get_current_user_id();

        $inserted = $wpdb->insert(
            self::get_table_name(),
            [
                'quiz_id'    => $quiz_id,
                'user_id'    => $user_id,
                'name'       => $name,
                'email'      => $email,
                'phone'      => $phone,
                'send_to'    => $send_to,
                'messengers' => wp_json_encode($messengers),
                'comment'    => sanitize_textarea_field(
                    $data['comment'] ?? ''
                ),
                'answers'    => wp_json_encode($answers),
                'created_at' => current_time('mysql'),
            ],
            [
                '%d',
                '%d',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
            ]
        );

        if ($inserted === false) {
            return new WP_Error(
                'submission_save_failed',
                'Не удалось сохранить заявку.'
            );
        }

        return (int) $wpdb->insert_id;
    }

    /**
     * Получает заявку по ID.
     */
    public static function get(int $submission_id): ?array
    {
        global $wpdb;

        if ($submission_id <= 0) {
            return null;
        }

        $table = self::get_table_name();

        $submission = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE id = %d",
                $submission_id
            ),
            ARRAY_A
        );

        if (!$submission) {
            return null;
        }

        return self::prepare_submission($submission);
    }

    /**
     * Получает список заявок.
     *
     * @return array{
     *     items: array,
     *     total: int,
     *     pages: int
     * }
     */
    public static function get_list(array $args = []): array
    {
        global $wpdb;

        $per_page = max(1, absint($args['per_page'] ?? 20));
        $page = max(1, absint($args['page'] ?? 1));
        $offset = ($page - 1) * $per_page;

        $table = self::get_table_name();

        $total = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$table}"
        );

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table}
                ORDER BY created_at DESC, id DESC
                LIMIT %d OFFSET %d",
                $per_page,
                $offset
            ),
            ARRAY_A
        );

        $items = array_map(
            [self::class, 'prepare_submission'],
            $rows ?: []
        );

        return [
            'items' => $items,
            'total' => $total,
            'pages' => (int) ceil($total / $per_page),
        ];
    }

    /**
     * Подготавливает данные заявки после чтения из БД.
     */
    private static function prepare_submission(array $submission): array
    {
        $submission['messengers'] = json_decode(
            $submission['messengers'],
            true
        );

        $submission['answers'] = json_decode(
            $submission['answers'],
            true
        );

        if (!is_array($submission['messengers'])) {
            $submission['messengers'] = [];
        }

        if (!is_array($submission['answers'])) {
            $submission['answers'] = [];
        }

        $submission['quiz_title'] = get_the_title(
            absint($submission['quiz_id'])
        );

        $submission['is_guest'] = absint($submission['user_id']) === 0;

        return $submission;
    }

    /**
     * Получает IP посетителя.
     */
    private static function get_client_ip(): ?string
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';

        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return null;
        }

        return $ip;
    }

    /**
     * Проверяет лимит отправки заявок.
     */
    private static function check_rate_limit(): bool
    {
        $ip = self::get_client_ip();

        // Если IP определить не удалось, не блокируем посетителя.
        if (!$ip) {
            return true;
        }

        $key = 'pwqb_rate_' . hash_hmac('sha256', $ip, wp_salt('auth'));
        $count = (int) get_transient($key);

        return $count < 3;
    }

    /**
     * Увеличивает счётчик отправленных заявок.
     */
    private static function increment_rate_limit(): void
    {
        $ip = self::get_client_ip();

        if (!$ip) {
            return;
        }

        $key = 'pwqb_rate_' . hash_hmac('sha256', $ip, wp_salt('auth'));
        $count = (int) get_transient($key);

        set_transient($key, $count + 1, 10 * MINUTE_IN_SECONDS);
    }

    /**
     * Увеличивает счётчик заблокированных запросов.
     */
    private static function increment_blocked_requests(): void
    {
        $count = (int) get_option('pwqb_blocked_requests', 0);

        update_option('pwqb_blocked_requests', $count + 1, false);
    }

    /**
     * Обработка AJAX-отправки заявки.
     */
    public static function ajax_submit(): void
    {
        // Проверка nonce.
        $nonce = isset($_POST['pwqb_submission_nonce'])
            ? sanitize_text_field(wp_unslash($_POST['pwqb_submission_nonce']))
            : '';

        if (!wp_verify_nonce($nonce, 'pwqb_submit_quiz')) {
            wp_send_json_error([
                'message' => 'Сессия истекла. Обновите страницу и попробуйте снова.',
            ], 403);
        }

        // Проверка honeypot.
        $honeypot = isset($_POST['pwqb_website'])
            ? wp_unslash($_POST['pwqb_website'])
            : '';

        if (!is_string($honeypot) || trim($honeypot) !== '') {
            self::increment_blocked_requests();

            wp_send_json_error([
                'message' => 'Не удалось отправить заявку.',
            ], 400);
        }

        // Проверка времени прохождения квиза.
        $started_at = isset($_POST['pwqb_started_at'])
            ? absint($_POST['pwqb_started_at'])
            : 0;

        $start_token = isset($_POST['pwqb_start_token'])
            ? sanitize_text_field(wp_unslash($_POST['pwqb_start_token']))
            : '';

        $quiz_id = absint($_POST['quiz_id'] ?? 0);

        $expected_token = hash_hmac(
            'sha256',
            $quiz_id . '|' . $started_at,
            wp_salt('auth')
        );

        $elapsed = time() - $started_at;

        if (
            !$started_at ||
            !$quiz_id ||
            !hash_equals($expected_token, $start_token) ||
            $elapsed < 5
        ) {
            self::increment_blocked_requests();

            wp_send_json_error([
                'message' => 'Не удалось отправить заявку. Обновите страницу и попробуйте снова.',
            ], 400);
        }

        // Ограничение частоты отправки.
        if (!self::check_rate_limit()) {
            wp_send_json_error([
                'message' => 'Вы отправили слишком много заявок. Попробуйте через 10 минут.',
            ], 429);
        }

        // Получаем ответы квиза.
        $answers_json = isset($_POST['answers'])
            ? wp_unslash($_POST['answers'])
            : '';

        $answers = json_decode($answers_json, true);

        if (!is_array($answers)) {
            wp_send_json_error([
                'message' => 'Не удалось получить ответы квиза.',
            ], 400);
        }

        // Собираем данные заявки.
        $data = [
            'quiz_id'   => absint($_POST['quiz_id'] ?? 0),
            'name'      => sanitize_text_field(wp_unslash($_POST['name'] ?? '')),
            'email'     => sanitize_email(wp_unslash($_POST['email'] ?? '')),
            'phone'     => sanitize_text_field(wp_unslash($_POST['phone'] ?? '')),
            'send_to'   => sanitize_key(wp_unslash($_POST['send_to'] ?? '')),
            'messengers' => isset($_POST['messengers']) && is_array($_POST['messengers'])
                ? array_map('sanitize_key', wp_unslash($_POST['messengers']))
                : [],
            'comment'   => sanitize_textarea_field(wp_unslash($_POST['comment'] ?? '')),
            'answers'   => $answers,
        ];

        $submission_id = self::create($data);

        if (is_wp_error($submission_id)) {
            wp_send_json_error([
                'message' => $submission_id->get_error_message(),
            ], 400);
        }

        self::increment_rate_limit();

        // Отправляем уведомление администратору.
        $admin_email = get_option('admin_email');

        $subject = 'Новая заявка на квиз';

        $quiz_title = get_the_title($data['quiz_id']);

        $submission_url = add_query_arg(
            [
                'post_type'     => PWQB_Quiz::POST_TYPE,
                'page'          => 'pwqb-submissions',
                'submission_id' => $submission_id,
            ],
            admin_url('edit.php')
        );

        $message = "Поступила новая заявка.\n\n";
        $message .= "Квиз: {$quiz_title}\n";
        $message .= "Имя: {$data['name']}\n";
        $message .= "Email: {$data['email']}\n";
        $message .= "Телефон: {$data['phone']}\n";
        $message .= "Способ связи: {$data['send_to']}\n";
        if (!empty($data['messengers'])) {
            $message .= "Мессенджеры: " . implode(', ', $data['messengers']) . "\n";
        }
        $message .= "Комментарий: {$data['comment']}\n\n";
        $message .= "Посмотреть заявку: {$submission_url}";

        wp_mail(
            $admin_email,
            $subject,
            $message,
            ['Content-Type: text/plain; charset=UTF-8']
        );

        wp_send_json_success([
            'message' => 'Спасибо! Ваша заявка успешно отправлена.',
            'submission_id' => $submission_id,
        ]);
    }
}

/**
 * AJAX: отправка заявки с фронтенда.
 */
add_action('wp_ajax_pwqb_submit_quiz', ['PWQB_Submissions', 'ajax_submit']);
add_action('wp_ajax_nopriv_pwqb_submit_quiz', ['PWQB_Submissions', 'ajax_submit']);
