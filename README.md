<p align="center">
    <h1 align="center">🌐 AtmoTeam Social Network</h1>
    <h3 align="center">Полнофункциональная социальная сеть на Yii Framework 2</h3>
    <br>
    <p align="center">
        <img src="https://img.shields.io/badge/PHP-7.4+-blue.svg" alt="PHP Version">
        <img src="https://img.shields.io/badge/Yii-2.0-success.svg" alt="Yii Version">
        <img src="https://img.shields.io/badge/MySQL-5.7+-orange.svg" alt="MySQL Version">
        <img src="https://img.shields.io/badge/License-BSD--3--Clause-lightgrey.svg" alt="License">
    </p>
</p>

**AtmoTeam Social Network** - это современная социальная сеть с полным набором функций для общения, обмена контентом и взаимодействия пользователей. Построена на фреймворке Yii Framework 2.1 с использованием современных подходов к разработке.

---

## 📋 Содержание

- [🚀 Основные возможности](#-основные-возможности)
- [📁 Структура проекта](#-структура-проекта)
- [📋 Требования](#-требования)
- [🚀 Установка](#-установка)
- [⚙️ Конфигурация](#️-конфигурация)
- [🔌 API Эндпоинты](#-api-эндпоинты)
- [🎨 Фронтенд](#-фронтенд)
- [🧪 Тестирование](#-тестирование)
- [🔒 Безопасность](#-безопасность)
- [🚀 Производительность](#-производительность)
- [🐳 Docker](#-docker)
- [📄 Лицензия](#-лицензия)

---

## 🚀 Основные возможности

### 📝 Публикации и контент
- **Текстовые посты** с поддержкой форматирования
- **Изображения** - загрузка и отображение
- **Опросы** с множественным выбором вариантов
- **Репосты** - делитесь контентом с аудиторией
- **Сохранение постов** - личная коллекция
- **Хэштеги и упоминания** - @username #tag
- **Лайки и комментарии** с функцией редактирования
- **Редактирование постов** после публикации

### 👥 Социальное взаимодействие
- **Система подписчиков/подписок** - Follow/Follower
- **Персонализированная лента** - только от подписанных
- **Уведомления в реальном времени** - лайки, комментарии, подписки
- **Приватные профили** - контроль видимости контента
- **Система блокировок** - Block/Unblock пользователей
- **Онлайн статус** - отображение активных пользователей
- **Популярные и активные пользователи** с кэшированием

### 💬 Общение и коммуникация
- **Личные сообщения** (Direct Messages) - чат в реальном времени
- **Истории (Stories)** - 24-часовый срок хранения
- **Комментарии к постам** с деревом ответов
- **Поиск пользователей и контента** - по имени, хэштегам
- **Модерация комментариев** - редактирование, удаление

### 🎨 Пользовательский интерфейс
- **Современный адаптивный дизайн** - Material-inspired
- **Темная и светлая тема** - переключение в один клик
- **Плавные анимации и переходы** - CSS transitions
- **Мобильная версия** - touch-friendly интерфейс
- **Интуитивная навигация** - breadcrumb, меню
- **Уведомления** - toast notifications
- **Модальные окна** - для подтверждений и действий

### 🛡️ Безопасность
- **CSRF защита** - токены для всех форм и AJAX
- **XSS защита** - автоматическое экранирование
- **SQL инъекции** - подготовленные запросы PDO
- **Rate limiting** - ограничение частоты API запросов
- **API валидация** - проверка входных данных
- **RBAC (Role-Based Access Control)** - управление правами
- **Безопасное хранение паролей** - password_hash

### ⚙️ Администрирование
- **Полная админ-панель** - управление всем сайтом
- **Модерация контента** - посты, комментарии
- **Управление пользователями** - блокировка, удаление
- **Статистика и аналитика** - в реальном времени
- **RBAC роли и разрешения** - кастомные права доступа
- **Журнал активности** - ActivityLog

### 🔄 Архитектура и технологии

**Backend:**
- Yii Framework 2.0 - PHP framework
- MySQL 5.7+ / MariaDB - база данных
- Redis - (опционально) для кэширования
- Composer - управление зависимостями

**Frontend:**
- Vanilla JavaScript - без фреймворков
- CSS3 - современные стили, flexbox, grid
- AJAX/Fetch API - асинхронные запросы
- LocalStorage - клиентское кэширование
- Service Worker - PWA поддержка

**Оптимизация:**
- Файловое кэширование Yii
- Lazy loading для отношений моделей
- Оптимизированные SQL запросы
- Сжатие CSS/JS
- CDN-ready архитектура

---

## 📁 Структура проекта

```
atmoteam/
├── assets/                      # Asset bundles Yii
│   └── AdminAsset.php           # Админ-панель ресурсы
├── commands/                    # Консольные команды
├── components/                  # Компоненты приложения
│   ├── ApiValidator.php         # Валидация API запросов
│   ├── FileCache.php            # Файловое кэширование
│   └── RateLimiter.php          # Ограничение частоты запросов
├── config/                      # Конфигурация
│   ├── db.php                   # Настройки БД
│   ├── web.php                  # Web application config
│   ├── console.php              # Console application config
│   └── params.php               # Параметры приложения
├── controllers/                 # Контроллеры (11 файлов)
│   ├── admin/                   # Админ контроллеры
│   │   ├── RoleController.php   # Управление ролями
│   │   └── UserController.php   # Управление пользователями
│   ├── AdminController.php      # Админ-панель
│   ├── BlockController.php      # Блокировки
│   ├── FeedController.php       # Лента и посты
│   ├── MessageController.php    # Личные сообщения
│   ├── PollController.php       # Опросы
│   ├── PostController.php       # Посты
│   ├── ProfileController.php    # Профили
│   ├── SearchController.php     # Поиск
│   ├── SiteController.php       # Главная, login, register
│   └── StoryController.php      # Истории
├── migrations/                  # Миграции БД (10 файлов)
│   ├── m240503_000001_create_user_table.php
│   ├── m240503_000002_create_post_table.php
│   ├── m240503_000003_create_interaction_tables.php
│   ├── m240503_000004_create_social_tables.php
│   ├── m240503_000005_create_messaging_tables.php
│   ├── m240503_000006_create_story_poll_tables.php
│   ├── m240503_000007_setup_rbac.php
│   ├── m240503_000008_create_online_user_table.php
│   └── ...
├── models/                      # Модели данных (19 моделей)
│   ├── ActivityLog.php          # Журнал активности
│   ├── AuthAssignment.php       # RBAC назначения
│   ├── AuthItemChild.php        # RBAC отношения
│   ├── Block.php                # Блокировки
│   ├── Comment.php              # Комментарии
│   ├── Follow.php               # Подписки
│   ├── Like.php                 # Лайки
│   ├── Message.php              # Сообщения
│   ├── Notification.php         # Уведомления
│   ├── OnlineUser.php           # Онлайн статус
│   ├── Poll.php                 # Опросы
│   ├── PollOption.php           # Варианты опроса
│   ├── PollVote.php             # Голоса в опросах
│   ├── Post.php                 # Посты
│   ├── Repost.php               # Репосты
│   ├── SavedPost.php            # Сохраненные посты
│   ├── Story.php                # Истории
│   ├── User.php                 # Пользователи
│   └── search/                  # Классы для поиска
├── modules/                     # Модули приложения
│   └── api/                     # API модуль
│       └── controllers/v1/      # API контроллеры v1
│           └── UserController.php
├── views/                       # Вьюхи
│   ├── admin/                   # Админ-панель
│   │   ├── index.php            # Главная админки
│   │   ├── users.php            # Список пользователей
│   │   ├── posts.php            # Список постов
│   │   └── comments.php         # Список комментариев
│   ├── feed/                    # Лента
│   ├── profile/                 # Профили
│   ├── message/                 # Сообщения
│   ├── story/                   # Истории
│   ├── site/                    # Главная, login, register
│   └── layouts/                 # Макеты
├── web/                         # Веб-корень
│   ├── css/                     # Стили (13 файлов)
│   │   ├── main.css             # Основные стили
│   │   ├── feed.css             # Лента
│   │   ├── profile.css          # Профили
│   │   ├── message.css          # Сообщения
│   │   ├── story.css            # Истории
│   │   ├── admin.css            # Админка
│   │   ├── dark.css             # Темная тема
│   │   └── ...
│   ├── js/                      # JavaScript (14 файлов)
│   │   ├── common.js            # Общие функции
│   │   ├── admin.js             # Админка
│   │   ├── feed.js              # Лента
│   │   ├── profile.js           # Профили
│   │   ├── message.js           # Сообщения
│   │   ├── story.js             # Истории
│   │   ├── theme.js             # Темы
│   │   └── ...
│   ├── uploads/                 # Загруженные файлы
│   │   └── avatars/             # Аватарки
│   └── svg/                     # SVG изображения
├── widgets/                     # Виджеты Yii
├── runtime/                     # Временные файлы (кэш, логи)
├── tests/                       # Тесты Codeception
└── config/                      # Конфигурация
```

---



## 📋 Требования

### Серверные требования
- **PHP** 7.4.0 или выше
- **MySQL** 5.7+ или **MariaDB** 10.2+
- **Web Server**: Apache 2.4+ с mod_rewrite или Nginx
- **Composer** 2.0+ для управления зависимостями

### PHP расширения
- `pdo_mysql` - работа с базой данных
- `gd` - обработка изображений
- `json` - работа с JSON
- `mbstring` - многобайтовые строки
- `openssl` - шифрование
- `fileinfo` - определение типов файлов

### Рекомендации
- **PHP OpCache** - для производительности
- **Redis** - для кэширования (опционально)
- **SSL/TLS** - для безопасных соединений

---

## 🚀 Установка

### 1. Клонирование проекта

```bash
git clone <repository-url> atmoteam
cd atmoteam
```

### 2. Установка зависимостей

```bash
composer install
```

Для production:
```bash
composer install --no-dev --optimize-autoloader
```

### 3. Настройка базы данных

Создайте базу данных:
```sql
CREATE DATABASE social_network CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Отредактируйте `config/db.php`:
```php
return [
    'class' => 'yii\db\Connection',
    'dsn' => 'mysql:host=localhost;dbname=social_network',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8mb4',
];
```

### 4. Запуск миграций

```bash
php yii migrate
```

### 5. Создание первого администратора

Зарегистрируйтесь через форму регистрации с логином `admin`. Пользователь с именем `admin` автоматически получает права администратора через RBAC.

### 6. Настройка прав доступа

```bash
# Linux/Mac
chmod -R 775 runtime/
chmod -R 775 web/assets/
chmod +x yii

# Windows (через GUI или icacls)
icacls runtime /grant Users:F /T
```

### 7. Настройка веб-сервера

#### Apache (.htaccess должен быть включен)

```apache
<IfModule mod_rewrite.c>
    RewriteEngine on
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*)$ index.php?$1 [L,QSA]
</IfModule>
```

#### Nginx

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/atmoteam/web;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_index index.php;
    }

    location ~ /\.ht {
        deny all;
    }
}
```

### 8. Доступ к приложению

- **Главная**: `http://your-domain.com/`
- **Лента**: `http://your-domain.com/feed`
- **Регистрация**: `http://your-domain.com/register`
- **Вход**: `http://your-domain.com/login`
- **Админка**: `http://your-domain.com/admin`

---


## ⚙️ Конфигурация

### Основные настройки

Файл `config/web.php` содержит основные настройки приложения:

```php
return [
    'id' => 'social-network',
    'basePath' => dirname(__DIR__),
    'components' => [
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'user' => [
            'identityClass' => 'app\models\User',
            'enableAutoLogin' => true,
        ],
    ],
];
```

### Кэширование

Проект использует файловое кэширование с автоматической очисткой при изменениях данных.

### Rate Limiting

Настроены следующие лимиты API:
- Посты: 10 в час
- Комментарии: 30 в час  
- Лайки: 100 в час
- Подписки: 50 в час
- Сообщения: 20 в час
- Голосования: 50 в час

## 🔌 API Эндпоинты

### Аутентификация
```
POST /login                    - Вход пользователя
POST /register                 - Регистрация пользователя
POST /site/logout              - Выход
```

### Лента и посты
```
GET  /feed                     - Лента постов
POST /api/post/create          - Создать пост
POST /api/post/like            - Лайкнуть пост
POST /api/post/save            - Сохранить пост
POST /api/repost               - Сделать репост
GET  /api/comments/<post_id>   - Получить комментарии
POST /api/comment/create       - Создать комментарий
POST /api/comment/update       - Обновить комментарий
POST /api/comment/delete       - Удалить комментарий
```

### Пользователи и социальное взаимодействие
```
GET  /profile                  - Мой профиль
GET  /profile/<id>             - Профиль пользователя
POST /profile/<id>/follow      - Подписаться
POST /profile/<id>/unfollow    - Отписаться
POST /api/profile/block        - Заблокировать
POST /api/profile/unblock      - Разблокировать
GET  /profile/<id>/followers   - Подписчики
GET  /profile/<id>/following   - Подписки
```

### Сообщения
```
GET  /message                  - Список сообщений
GET  /message/dialogue/<id>    - Диалог с пользователем
POST /api/message/send         - Отправить сообщение
POST /api/message/mark-read    - Пометить прочитанным
GET  /api/message/unread-count - Непрочитанные сообщения
```

### Истории (Stories)
```
GET  /story                    - Список историй
POST /api/story/upload         - Загрузить историю
POST /api/story/delete         - Удалить историю
GET  /api/story/get-stories    - Получить истории пользователя
```

### Опросы
```
POST /api/vote                 - Голосовать в опросе
POST /api/poll                 - Создать опрос
```

### Админ-панель
```
GET  /admin                    - Главная админки
POST /api/admin/delete-user    - Удалить пользователя
POST /api/admin/delete-post    - Удалить пост
POST /api/admin/delete-comment - Удалить комментарий
POST /api/admin/block-user     - Заблокировать пользователя
POST /api/admin/unblock-user   - Разблокировать пользователя
```

---

## 🎨 Фронтенд

### JavaScript Компоненты (14 файлов)

| Файл | Описание |
|------|----------|
| `common.js` | Общие функции (showNotification, postWithCsrf, showDeleteModal) |
| `main.js` | Инициализация, события |
| `theme.js` | Переключение тем (светлая/темная) |
| `feed.js` | Лента, опросы, лайки, репосты |
| `profile.js` | Профили, подписки, блокировки |
| `message.js` | Чат, отправка сообщений |
| `story.js` | Истории, загрузка |
| `admin.js` | Админ-панель |
| `search.js` | Поиск |
| `block.js` | Блокировки |
| `post.js` | Посты |
| `posts-unified.js` | Универсальные посты |
| `feed-exports.js` | Экспорт ленты |
| `service-worker.js` | PWA поддержка |

### CSS Стили (13 файлов)

| Файл | Описание |
|------|----------|
| `main.css` | Основные стили, переменные, сброс |
| `feed.css` | Лента постов |
| `profile.css` | Профили пользователей |
| `message.css` | Чат и сообщения |
| `story.css` | Истории |
| `admin.css` | Админ-панель |
| `admin-base.css` | Базовые стили админки |
| `dark.css` | Темная тема |
| `auth.css` | Авторизация и регистрация |
| `components.css` | Компоненты UI |
| `post.css` | Посты |
| `search.css` | Поиск |

---


## 🔧 Разработка

### Добавление новой функции

1. **Создайте миграцию**
   ```bash
   php yii migrate/create create_new_table
   ```

2. **Создайте модель** в `models/`

3. **Создайте контроллер** в `controllers/`

4. **Добавьте вьюхи** в `views/`

5. **Добавьте маршрут** в `config/web.php`

6. **Напишите JavaScript** для фронтенда

7. **Добавьте CSS стили** в соответствующий файл

### Стандарты кодирования
- **PSR-12** - стандарты PHP
- **Имена переменных** - camelCase
- **Имена классов** - PascalCase
- **Комментарии** - для сложной логики
- **Тесты** - для новых функций

### Отладка

Включите debug режим в `config/web.php` (только для development):

```php
if (YII_ENV_DEV) {
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => 'yii\debug\Module',
        'allowedIPs' => ['127.0.0.1'], // Ограничьте IP!
    ];
}
```

Доступ к debug toolbar: `http://localhost/debug`

---

## 🧪 Тестирование

Проект использует **Codeception** для тестирования.

### Запуск тестов

```bash
# Все тесты
vendor/bin/codecept run

# Unit тесты
vendor/bin/codecept run unit

# Функциональные тесты
vendor/bin/codecept run functional

# Тесты с покрытием кода
vendor/bin/codecept run --coverage --coverage-html
```

### Структура тестов

```
tests/
├── unit/              # Unit тесты
├── functional/        # Функциональные тесты  
└── acceptance/        # Acceptance тесты (browser)
```

---

## 🔒 Безопасность

### Встроенная защита
- **CSRF токены** для всех форм и AJAX
- **XSS защита** через `Html::encode()`
- **SQL инъекции** через подготовленные запросы PDO
- **Rate limiting** для API запросов
- **RBAC** - управление правами доступа
- **Password hashing** - bcrypt

### Рекомендации для production
1. Используйте **HTTPS** с SSL/TLS сертификатом
2. Обновляйте зависимости регулярно: `composer update`
3. Настройте **брандмауэр**
4. Резервное копирование БД
5. Мониторьте логи в `runtime/logs/`
6. Отключите `YII_DEBUG` в production
7. Используйте сильные `cookieValidationKey`

---

## 🚀 Производительность

### Оптимизации в проекте
- **Файловое кэширование** Yii
- **Lazy loading** для отношений моделей
- **Оптимизированные SQL запросы** с индексами
- **Кэширование статических файлов** - browser caching
- **Оптимизация изображений** - аватарки

### Индексы БД
- `idx-user-status`, `idx-user-private`, `idx-user-created_at`
- `idx-post-user_id`, `idx-post-created_at`
- `idx-comment-post_id`, `idx-comment-user_id`
- `idx-follow-follower_id`, `idx-follow-following_id`
- `idx-like-post_id`, `idx-like-user_id`

### Мониторинг
- Используйте **Xdebug** для профилирования
- Мониторьте время выполнения запросов
- Оптимизируйте медленные запросы
- Следите за использованием памяти

---

## 🐳 Docker

### Использование Docker Compose

```bash
# Запуск проекта
docker-compose up -d

# Остановка
docker-compose down

# Пересборка
docker-compose build --no-cache
docker-compose up -d

# Просмотр логов
docker-compose logs -f
```

**Контейнеры:**
- `app` - PHP + Yii приложение
- `db` - MySQL/MariaDB
- `nginx` - веб-сервер

### Переменные окружения

Создайте `.env` файл:
```env
DB_HOST=db
DB_PORT=3306
DB_NAME=social_network
DB_USER=root
DB_PASSWORD=secret
```

---

## 📄 Лицензия

Этот проект распространяется под лицензией BSD-3-Clause.

## 🤝 Вклад в проект

1. Fork проекта
2. Создайте feature branch
3. Внесите изменения
4. Отправьте Pull Request

## 📞 Поддержка

- **Документация**: [Yii Framework](https://www.yiiframework.com/doc/guide/2.0/ru)
- **Сообщество**: [Yii Forum](https://www.yiiframework.com/forum/)
- **GitHub**: [Issues](https://github.com/yiisoft/yii2/issues)

---

## 🌟 Особенности проекта

| Функция | Статус |
|---------|--------|
| Регистрация и авторизация | ✅ Готово |
| Профили пользователей | ✅ Готово |
| Лента постов | ✅ Готово |
| Лайки и комментарии | ✅ Готово |
| Репосты и сохранение | ✅ Готово |
| Опросы | ✅ Готово |
| Подписчики/подписки | ✅ Готово |
| Личные сообщения | ✅ Готово |
| Истории (Stories) | ✅ Готово |
| Уведомления | ✅ Готово |
| Поиск | ✅ Готово |
| Блокировки | ✅ Готово |
| Приватные профили | ✅ Готово |
| Админ-панель | ✅ Готово |
| RBAC | ✅ Готово |
| Темная тема | ✅ Готово |
| Мобильная адаптивность | ✅ Готово |
| API | ✅ Готово |
| Rate limiting | ✅ Готово |

---

## 📊 Статистика проекта

- **Контроллеры**: 13 файлов
- **Моделей**: 19 файлов
- **Миграций**: 10 файлов
- **CSS файлов**: 13 файлов
- **JS файлов**: 14 файлов
- **Вьюхов**: 30+ файлов

---

**AtmoTeam Social Network** - Готово к production! 🚀

Последнее обновление: 2024
