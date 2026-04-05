# duplication-webinars

## Назначение
`duplication-webinars` — продуктовый модуль вебинаров платформы WordPress 2.0.

В рамках этого шага реализован базовый продуктовый шаг по `public_webinar_page` и server-side `page_mode`:
- отдельный плагин;
- безопасная загрузка;
- зависимость от `duplication-core` и `duplication-access`;
- регистрация модуля в `duplication-core`;
- каноника webinar-сущности;
- хранение webinar как `custom post type` + `post meta`;
- service layer webinar/page_mode/access/cta/room-entry;
- server-side рендеринг single webinar на каноническом URL сущности;
- минимальный admin UI для системного управления webinar.

## Структура модуля
- `plugins/duplication-webinars/duplication-webinars.php` — главный файл плагина и публичные точки входа.
- `plugins/duplication-webinars/includes/Autoloader.php` — автозагрузка namespace `Duplication\Webinars\`.
- `plugins/duplication-webinars/src/Bootstrap.php` — bootstrap, регистрация в core, product-события, подключение single-template.
- `plugins/duplication-webinars/src/Domain/Canon.php` — каноника webinar entity, форматов, состояний, источников, page mode, meta keys.
- `plugins/duplication-webinars/src/Domain/WebinarRepository.php` — CPT `webinar` + хранение/чтение canonical post meta.
- `plugins/duplication-webinars/src/Domain/WebinarService.php` — сервис слоя получения webinar/access/page_mode/cta/room-entry + view model публичной страницы.
- `plugins/duplication-webinars/src/Admin/WebinarMetaBox.php` — минимальный admin UI для редактирования webinar-сущности.
- `plugins/duplication-webinars/templates/single-webinar.php` — server-side шаблон канонической публичной страницы webinar.

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

## Публичная страница webinar (server-side)
Публичная страница рендерится на single URL webinar-поста через `templates/single-webinar.php`.

В этом шаге на странице выводятся только базовые блоки:
- `title`;
- `description/content`;
- `status`;
- `format`;
- `minimum_access_level`;
- source block (`source_type`, `source_value`);
- `page_mode`;
- признак `public_webinar_page`;
- `access message` (`reason` + человеко-читаемое сообщение);
- `CTA` (код, label, ссылка/disabled-состояние).

`page_mode` влияет на контекст рендера:
- `pre_event` — страница предстоящего вебинара;
- `live` — страница живого вебинара;
- `finished` — страница завершённого вебинара;
- `canceled` — страница отменённого вебинара.

## Access/CTA и room-entry
- Access-проверка и CTA определяются только в service layer (`WebinarService`) и через `duplication-access` API.
- Шаблон не дублирует access-логику.
- Для `female_club` допуск определяется только через `duplication-access`.
- Если доступ запрещён, публичная страница остаётся видимой и показывает `reason` + корректный `CTA`.
- `room-entry` не открывается при запрете доступа; вход доступен только при `allowed=true` и `page_mode=live`.
- Если `public_webinar_page` выключен, канонический URL сущности не меняется; в рендере это отражается как недоступность публичной страницы.

## Публичные точки входа
- `duplication_webinars_bootstrap()`
- `duplication_webinars_get_webinar(int $webinarId)`
- `duplication_webinars_list_webinars(array $args = [])`
- `duplication_webinars_get_status(int $webinarId)`
- `duplication_webinars_get_page_mode(int $webinarId)`
- `duplication_webinars_check_access(int $webinarId, int $userId)`
- `duplication_webinars_can_enter_room(int $webinarId, int $userId)`
- `duplication_webinars_get_public_page_view(int $webinarId, int $userId)`

## Формат результата access-check
Фиксированный структурированный ответ:
- `allowed`
- `reason`
- `cta`
- `page_mode`

## Базовые события модуля
Публикуются события:
- `duplication/webinar_created`
- `duplication/webinar_updated`
- `duplication/webinar_started`
- `duplication/webinar_finished`
- `duplication/webinar_canceled`

## Реализовано в этом шаге
- Канонический `single webinar` URL работает как `public_webinar_page` сущности.
- `webinar_room` реализован как режим (`page_mode`) той же страницы.
- Добавлен server-side шаблон single webinar для базовых блоков публичной страницы.
- `page_mode` реально влияет на рендер (контекст страницы pre_event/live/finished/canceled).
- Access message и CTA идут через service layer + `duplication-access`.
- Admin UI дополнен отображением канонического публичного URL и полями, нужными для server-side страницы.

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
