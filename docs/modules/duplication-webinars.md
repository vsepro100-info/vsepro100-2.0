# duplication-webinars

## Назначение
`duplication-webinars` — продуктовый модуль вебинаров платформы WordPress 2.0.

В рамках этого шага реализован только базовый каркас product-module:
- отдельный плагин;
- безопасная загрузка;
- зависимость от `duplication-core` и `duplication-access`;
- регистрация модуля в `duplication-core`;
- каноника webinar-сущности;
- хранение webinar как `custom post type` + `post meta`;
- базовый service layer webinar/page_mode/access/cta/room-entry;
- минимальный admin UI для системного управления webinar.

## Структура модуля
- `plugins/duplication-webinars/duplication-webinars.php` — главный файл плагина и публичные точки входа.
- `plugins/duplication-webinars/includes/Autoloader.php` — автозагрузка namespace `Duplication\Webinars\`.
- `plugins/duplication-webinars/src/Bootstrap.php` — bootstrap, регистрация в core, базовые product-события.
- `plugins/duplication-webinars/src/Domain/Canon.php` — каноника webinar entity, форматов, состояний, источников, page mode, meta keys.
- `plugins/duplication-webinars/src/Domain/WebinarRepository.php` — CPT `webinar` + хранение/чтение canonical post meta.
- `plugins/duplication-webinars/src/Domain/WebinarService.php` — сервис слоя получения webinar/access/page_mode/cta/room-entry.
- `plugins/duplication-webinars/src/Admin/WebinarMetaBox.php` — минимальный admin UI для редактирования webinar-сущности.

## Зависимости
`duplication-webinars` запускается только если доступны и активны:
- `duplication-core`
- `duplication-access`

Модуль регистрируется в `duplication-core` с зависимостями:
- `duplication-core`
- `duplication-access`

## Модель хранения webinar
Фиксированная каноника хранения:
- `webinar` хранится как отдельный WordPress `custom post type` (`webinar`);
- поля webinar хранятся только в `post meta`.

Канонические meta-key:
- `dp_webinar_format`
- `dp_webinar_status`
- `dp_webinar_minimum_access_level`
- `dp_webinar_source_type`
- `dp_webinar_source_value`
- `dp_webinar_public_page_enabled`
- `dp_webinar_page_mode`

## Канонические форматы webinar
- `standard`
- `female_club`

## Канонические состояния webinar
- `scheduled`
- `live`
- `finished`
- `canceled`

## Канонические типы источника трансляции
- `youtube_embed`
- `iframe_embed`
- `external_link`

## Канонические page_mode
- `pre_event`
- `live`
- `finished`
- `canceled`

`public_webinar_page` фиксирован как один постоянный URL webinar-поста.
`webinar_room` трактуется как режим (`page_mode`) этой же страницы, а не отдельная SEO-сущность.

## Публичные точки входа
- `duplication_webinars_bootstrap()`
- `duplication_webinars_get_webinar(int $webinarId)`
- `duplication_webinars_list_webinars(array $args = [])`
- `duplication_webinars_get_status(int $webinarId)`
- `duplication_webinars_get_page_mode(int $webinarId)`
- `duplication_webinars_check_access(int $webinarId, int $userId)`
- `duplication_webinars_can_enter_room(int $webinarId, int $userId)`

## Формат результата access-check
Фиксированный структурированный ответ:
- `allowed`
- `reason`
- `cta`
- `page_mode`

Проверки доступа выполняются через `duplication-access`:
- обычный webinar: `minimum_access_level` как канонический business status (`candidate | partner | vip_partner`);
- `female_club`: отдельная проверка female_club через access-layer;
- admin_override учитывается через `duplication_access_check_admin_override()`.

## Базовые события модуля
Публикуются события:
- `duplication/webinar_created`
- `duplication/webinar_updated`
- `duplication/webinar_started`
- `duplication/webinar_finished`
- `duplication/webinar_canceled`

## Реализовано в этом шаге
- Создан отдельный плагин `duplication-webinars`.
- Реализован bootstrap + dependency gate (`core` + `access`).
- Добавлена регистрация модуля в `duplication-core`.
- Реализован `custom post type` webinar (`webinar`).
- Реализовано canonical хранение webinar в post meta.
- Реализован минимальный слой entity/repository/service.
- Реализован минимальный admin UI для создания/редактирования webinar-сущности.
- Реализован базовый server-side access-check с результатом `allowed/reason/cta/page_mode`.

## Что ещё не реализовано
- чат;
- GPT summary / Q&A;
- reminders;
- participant list;
- statistics (`all / mine / structure`);
- share-модуль;
- CRM;
- agents;
- обучение;
- автостарт / автозавершение;
- отдельная SEO-страница для room;
- любая бизнес-логика других модулей.
`dp_webinar_minimum_access_level` хранится как каноническая строка:
- `candidate`
- `partner`
- `vip_partner`
