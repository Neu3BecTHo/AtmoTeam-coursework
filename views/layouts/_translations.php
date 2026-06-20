<?php
/**
 * JavaScript translations
 * This file outputs translations as a JavaScript object
 */
$translations = [
    // Common
    'close' => Yii::t('app', 'Закрыть'),
    'close_notification' => Yii::t('app', 'Закрыть уведомление'),
    'delete' => Yii::t('app', 'Удалить'),
    'delete_question' => Yii::t('app', 'Удалить?'),
    'error' => Yii::t('app', 'Ошибка'),
    'success' => Yii::t('app', 'Успешно'),
    'user' => Yii::t('app', 'Пользователь'),
    'loading' => Yii::t('app', 'Загрузка...'),

    // Auth & actions
    'login_to_like' => Yii::t('app', 'Войдите, чтобы поставить лайк'),
    'login_to_save' => Yii::t('app', 'Войдите, чтобы сохранить'),
    'like_added' => Yii::t('app', 'Лайк поставлен'),
    'like_removed' => Yii::t('app', 'Лайк убран'),
    'saved' => Yii::t('app', 'Пост сохранен'),
    'removed_from_saved' => Yii::t('app', 'Пост удален из сохраненных'),
    'login_to_repost' => Yii::t('app', 'Войдите, чтобы сделать репост'),
    'repost_made' => Yii::t('app', 'Репост сделан'),
    'repost_cancelled' => Yii::t('app', 'Репост отменён'),
    'login' => Yii::t('app', 'Войти'),
    'register' => Yii::t('app', 'Регистрация'),

    // Posts & comments
    'delete_post_question' => Yii::t('app', 'Удалить пост?'),
    'post_deleted' => Yii::t('app', 'Пост удалён'),
    'error_loading_comments' => Yii::t('app', 'Ошибка загрузки комментариев'),
    'write_comment' => Yii::t('app', 'Напишите комментарий'),
    'login_to_comment' => Yii::t('app', 'Войдите, чтобы оставить комментарий'),
    'comment_added' => Yii::t('app', 'Комментарий добавлен!'),
    'error_sending' => Yii::t('app', 'Ошибка отправки'),
    'send' => Yii::t('app', '📤 Отправить'),
    'input_field_not_found' => Yii::t('app', 'Ошибка: поле ввода не найдено'),
    'sending' => Yii::t('app', '⏳ Отправка...'),
    'error_sending_comment' => Yii::t('app', 'Ошибка отправки комментария'),
    'delete_comment_question' => Yii::t('app', 'Удалить комментарий?'),
    'comment_deleted' => Yii::t('app', 'Комментарий удалён'),
    'no_comments' => Yii::t('app', 'Нет комментариев'),
    'be_first_to_comment' => Yii::t('app', 'Будьте первым, кто оставит комментарий!'),
    'error_deleting' => Yii::t('app', 'Ошибка удаления'),
    'save' => Yii::t('app', '💾 Сохранить'),
    'cancel' => Yii::t('app', '❌ Отмена'),
    'edited_mark' => Yii::t('app', '(ред.)'),
    'comment_updated' => Yii::t('app', 'Комментарий обновлён'),
    'link_copied' => Yii::t('app', 'Ссылка скопирована!'),
    'comments_count' => Yii::t('app', '{n} комментариев'),

    // Poll
    'poll_container_not_found' => Yii::t('app', 'Ошибка: контейнер опроса не найден'),
    'select_answer_option' => Yii::t('app', 'Выберите вариант ответа'),
    'votes' => Yii::t('app', 'голосов'),
    'votes_abbr' => Yii::t('app', 'гол.'),
    'vote_counted' => Yii::t('app', 'Ваш голос учтён!'),
    'voting_error' => Yii::t('app', 'Ошибка голосования'),
    'vote_button' => Yii::t('app', '🗳️ Голосовать'),
    'vote_cancelled' => Yii::t('app', 'Голос отменён'),
    'your_vote' => Yii::t('app', '✓ Ваш голос'),
    'poll_option_1' => Yii::t('app', 'Вариант ответа 1...'),
    'poll_option_2' => Yii::t('app', 'Вариант ответа 2...'),
    'poll_option_n' => Yii::t('app', 'Вариант ответа {n}...'),
    'max_poll_options' => Yii::t('app', 'Максимум 10 вариантов'),
    'min_poll_options' => Yii::t('app', 'Минимум 2 варианта'),

    // Feed
    'login_to_see_feed' => Yii::t('app', 'Войдите, чтобы увидеть ленту'),
    'publish_likes_friends' => Yii::t('app', 'Публикуйте посты, ставьте лайки и общайтесь с друзьями'),
    'no_posts' => Yii::t('app', 'Нет постов'),
    'post_load_error' => Yii::t('app', 'Ошибка загрузки поста'),
    'fill_at_least_one_field' => Yii::t('app', 'Заполните хотя бы одно поле: текст, изображение или опрос'),
    'fill_at_least_one_field_short' => Yii::t('app', 'Заполните хотя бы одно поле'),
    'image_size_warning' => Yii::t('app', 'Общий размер изображений ({size} МБ) превышает лимит {limit} МБ. Пожалуйста, удалите некоторые файлы.'),
    'image_total_size_exceeds' => Yii::t('app', 'Общий размер изображений ({size} МБ) превышает лимит 7 МБ. Удалите часть файлов.'),
    'publishing' => Yii::t('app', '⏳ Публикация...'),
    'post_published' => Yii::t('app', 'Пост опубликован!'),
    'publish_error' => Yii::t('app', 'Ошибка публикации'),
    'publish_button' => Yii::t('app', '📤 Опубликовать'),

    // Story
    'story_published' => Yii::t('app', 'История опубликована!'),
    'story_deleted' => Yii::t('app', 'История удалена'),
    'delete_story_question' => Yii::t('app', 'Удалить эту историю?'),
    'delete_this_story_question' => Yii::t('app', 'Удалить эту историю?'),
    'select_image' => Yii::t('app', 'Выберите изображение'),
    'file_not_selected' => Yii::t('app', 'Файл не выбран'),
    'unsupported_format' => Yii::t('app', 'Неподдерживаемый формат. Допустимы: JPG, PNG, GIF, WebP'),
    'compression_error' => Yii::t('app', 'Ошибка сжатия:'),
    'compression_failed' => Yii::t('app', 'Не удалось сжать изображение. Попробуйте другое фото или меньшего размера.'),
    'story_not_found' => Yii::t('app', 'Ошибка: не удалось найти историю'),
    'modal_not_found' => Yii::t('app', 'Ошибка: модальное окно не найдено'),
    'loading_error' => Yii::t('app', 'Ошибка загрузки'),
    'network_error' => Yii::t('app', 'Ошибка сети'),
    'story_loading_error' => Yii::t('app', 'Ошибка загрузки истории'),
    'stories_loading_error' => Yii::t('app', 'Ошибка загрузки историй'),
    'blob_creation_error' => Yii::t('app', 'Не удалось создать Blob'),
    'image_loading_error' => Yii::t('app', 'Ошибка загрузки изображения (возможно, файл повреждён или слишком велик)'),
    'file_reading_error' => Yii::t('app', 'Ошибка чтения файла'),
    'or_drag_file_here' => Yii::t('app', 'или перетащите файл сюда'),
    'compressed' => Yii::t('app', 'сжато'),
    'choose_another_image' => Yii::t('app', 'Выбрать другое изображение'),
    'publish' => Yii::t('app', 'Опубликовать'),
    'view' => Yii::t('app', 'Просмотр'),
    'no_active_stories' => Yii::t('app', 'Нет активных историй'),
    'stories_appear_here' => Yii::t('app', 'Истории появляются здесь, когда ваши подписки делятся моментами из жизни'),
    'create_first_story' => Yii::t('app', 'Создать первую историю'),
    'story' => Yii::t('app', 'История'),
    'stories' => Yii::t('app', 'историй'),
    'story_genitive' => Yii::t('app', 'история'),

    // Theme
    'toggle_theme' => Yii::t('app', 'Переключить тему'),

    // Search
    'popular_searches' => Yii::t('app', 'Популярные запросы'),
    'nothing_found' => Yii::t('app', 'Ничего не найдено'),
    'users' => Yii::t('app', 'Пользователи'),
    'posts' => Yii::t('app', 'Посты'),
    'show_all_results' => Yii::t('app', 'Показать все результаты →'),
    'no_internet' => Yii::t('app', 'Отсутствует подключение к интернету'),

    // Time
    'just_now' => Yii::t('app', 'только что'),
    'minutes_ago' => Yii::t('app', 'мин. назад'),
    'hours_ago' => Yii::t('app', 'ч. назад'),
    'days_ago' => Yii::t('app', 'дн. назад'),
    'hours' => Yii::t('app', 'ч'),
    'minutes' => Yii::t('app', 'м'),
    'expired' => Yii::t('app', 'Истекло'),

    // Notifications (dropdown)
    'no_notifications' => Yii::t('app', 'Нет уведомлений'),

    // HTTP & validation
    'http_error' => Yii::t('app', 'Ошибка HTTP {status}'),
    'auth_required' => Yii::t('app', 'Требуется авторизация'),
    'access_denied' => Yii::t('app', 'Доступ запрещен'),
    'not_found' => Yii::t('app', 'Ресурс не найден'),
    'too_many_requests' => Yii::t('app', 'Слишком много запросов'),
    'server_error' => Yii::t('app', 'Ошибка сервера'),
    'field_required' => Yii::t('app', 'Это поле обязательно'),
    'invalid_email' => Yii::t('app', 'Введите корректный email'),
    'min_chars' => Yii::t('app', 'Минимум {n} символов'),
    'max_chars' => Yii::t('app', 'Максимум {n} символов'),

    // Messages
    'message_too_long' => Yii::t('app', 'Сообщение слишком длинное (максимум 1000 символов)'),
    'send_error' => Yii::t('app', 'Ошибка отправки'),
    'message_sent' => Yii::t('app', 'Сообщение отправлено'),
    'no_messages' => Yii::t('app', 'У вас пока нет сообщений'),
    'find_users' => Yii::t('app', 'Найти пользователей'),
    'start_dialogue' => Yii::t('app', 'Начните диалог...'),
    'delete_message_question' => Yii::t('app', 'Удалить это сообщение?'),
    'message_deleted' => Yii::t('app', 'Сообщение удалено'),

    // Profile
    'compressing_images' => Yii::t('app', 'Сжатие изображений... (WebP)'),
    'compression_failed_originals' => Yii::t('app', 'Сжатие не удалось, используются оригиналы'),
    'comment_send_error' => Yii::t('app', 'Ошибка отправки комментария'),
    'compressing_avatar' => Yii::t('app', 'Сжатие аватара...'),
    'compression_failed_original' => Yii::t('app', 'Сжатие не удалось, используется оригинал'),
    'image_load_error' => Yii::t('app', 'Ошибка загрузки изображения'),
    'no_posts_yet' => Yii::t('app', 'Пока нет постов'),
    'posts_load_error' => Yii::t('app', 'Ошибка загрузки постов'),
    'unfollow' => Yii::t('app', 'Отписаться'),
    'follow' => Yii::t('app', 'Подписаться'),
    'followed_user' => Yii::t('app', 'Вы подписались на {username}'),
    'unfollowed_user' => Yii::t('app', 'Вы отписались от {username}'),
    'unblock_user_question' => Yii::t('app', 'Разблокировать пользователя {username}?'),
    'block_user_question' => Yii::t('app', 'Заблокировать пользователя {username}?'),
    'user_unblocked' => Yii::t('app', 'Пользователь {username} разблокирован'),
    'user_blocked' => Yii::t('app', 'Пользователь {username} заблокирован'),
    'unblock_button' => Yii::t('app', '✅ Разблокировать'),
    'block_button' => Yii::t('app', '🚫 Заблокировать'),
    'no_reposts' => Yii::t('app', 'Нет репостов'),
    'no_saved_posts' => Yii::t('app', 'Нет сохранённых постов'),
    'block_error' => Yii::t('app', 'Ошибка блокировки'),
    'unblock_error' => Yii::t('app', 'Ошибка разблокировки'),
    'no_followers' => Yii::t('app', 'Нет подписчиков'),
    'no_following' => Yii::t('app', 'Нет подписок'),

    // Admin
    'scripts_not_loaded' => Yii::t('app', 'Не загружены скрипты интерфейса (common.js)'),
    'scripts_not_loaded_short' => Yii::t('app', 'Не загружены скрипты интерфейса'),
    'delete_post_question_admin' => Yii::t('app', 'Удалить этот пост?'),
    'post_deleted_admin' => Yii::t('app', 'Пост удален'),
    'delete_user_question_admin' => Yii::t('app', 'Удалить этого пользователя? Все его данные будут удалены!'),
    'user_deleted_admin' => Yii::t('app', 'Пользователь удален'),
    'delete_comment_question_admin' => Yii::t('app', 'Удалить этот комментарий?'),
    'comment_deleted_admin' => Yii::t('app', 'Комментарий удален'),
    'block_user_site_question' => Yii::t('app', 'Заблокировать этого пользователя на сайте?'),
    'block_user_success' => Yii::t('app', 'Пользователь заблокирован'),
    'unblock_user_site_question' => Yii::t('app', 'Разблокировать этого пользователя?'),
    'unblock_user_success' => Yii::t('app', 'Пользователь разблокирован'),

    // Block list
    'no_blocked_users' => Yii::t('app', 'У вас нет заблокированных пользователей'),
    'blocked_at' => Yii::t('app', 'Заблокирован {time}'),
    'unblock_title' => Yii::t('app', 'Разблокировать'),

    // Delete modal
    'delete_confirmation' => Yii::t('app', 'Подтверждение удаления'),
    'delete_cannot_undo' => Yii::t('app', 'Это действие нельзя отменить!'),
    'yes_delete' => Yii::t('app', 'Да, удалить'),
    'delete_confirm_default' => Yii::t('app', 'Вы уверены, что хотите удалить этот элемент?'),

    // Language
    'language' => Yii::t('app', 'Язык'),
    'russian' => Yii::t('app', 'Русский'),
    'english' => Yii::t('app', 'English'),
    'delete_post' => Yii::t('app', 'Удалить пост'),
    'post_image_n' => Yii::t('app', 'Изображение поста {n}'),
    'avatar_username' => Yii::t('app', 'Аватар {username}'),
    'login_to_vote' => Yii::t('app', ', чтобы голосовать'),
    'login_to_comment_suffix' => Yii::t('app', ', чтобы оставить комментарий'),
    'save_post' => Yii::t('app', 'Сохранить'),
    'remove_from_saved' => Yii::t('app', 'Удалить из сохранённых'),
    'undo_repost' => Yii::t('app', 'Отменить репост'),
    'make_repost' => Yii::t('app', 'Сделать репост'),
    'comments' => Yii::t('app', 'Комментарии'),
    'post_save_error' => Yii::t('app', 'Ошибка сохранения поста'),
];
?>

<script>
window.translations = <?php echo json_encode($translations, JSON_UNESCAPED_UNICODE); ?>;
window.t = function(key, params) {
    let str = (window.translations && window.translations[key]) || key;
    if (params) {
        Object.keys(params).forEach(function(k) {
            str = str.replace(new RegExp('\\{' + k + '\\}', 'g'), params[k]);
        });
    }
    return str;
};
</script>
