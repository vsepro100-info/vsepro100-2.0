# duplication-access

## Назначение
`duplication-access` — доменный access-layer модуль платформы WordPress 2.0.

В рамках этого шага реализован только базовый каркас:
- отдельный плагин модуля;
- безопасная загрузка и bootstrap;
- зависимость от `duplication-core`;
- регистрация модуля в `duplication-core`;
- каноника business status / gender / access concepts;
- сервис чтения и базовых проверок доступа;
- структурированные результаты проверок (`allowed`, `reason`);
- минимальный системный admin UI.

В этом шаге дополнительно реализовано админ-управление каноническими полями пользователя
в профиле WordPress (`user-edit.php`, `profile.php`) без выхода за рамки access-layer.

## Структура модуля
- `plugins/duplication-access/duplication-access.php` — главный файл плагина и публичные точки входа.
- `plugins/duplication-access/includes/Autoloader.php` — автозагрузка namespace `Duplication\Access\`.
- `plugins/duplication-access/src/Bootstrap.php` — bootstrap, регистрация в core, обработка событий, дефолты пользователя.
- `plugins/duplication-access/src/Domain/Canon.php` — канонические сущности модуля.
- `plugins/duplication-access/src/Domain/Settings.php` — системные настройки (`admin_override` роли).
- `plugins/duplication-access/src/Domain/AccessService.php` — сервис чтения и проверок доступа.
- `plugins/duplication-access/src/Admin/AdminPage.php` — системная admin-страница access-layer.

## Публичные точки входа
- `duplication_access_bootstrap()`
- `duplication_access_get_business_status(int $userId)`
- `duplication_access_get_gender(int $userId)`
- `duplication_access_get_access_level(int $userId)`
- `duplication_access_check_minimum_access_level(int $userId, int $requiredLevel)`
- `duplication_access_check_female_club(int $userId)`
- `duplication_access_check_admin_override(int $userId)`
- `duplication_access_check(int $userId, array $rule = [])`

Формат результата проверок:
- `allowed`
- `reason`

## Канонические business status
- `candidate`
- `partner`
- `vip_partner`

## Канонические gender
- `male`
- `female`
- `unknown`

## Канонические уровни доступа
- `candidate = 1`
- `partner = 2`
- `vip_partner = 3`

## Канонические access-понятия
- `admin_override`
- `minimum_access_level`
- `female_club`

## Канонические meta-key
- `dp_business_status`
- `dp_gender`

## Дефолтная логика
- после регистрации: `dp_business_status = candidate`;
- если пол не задан: `dp_gender = unknown`.

## События модуля
Публикует:
- `duplication/business_status_changed`
- `duplication/user_gender_changed`
- `duplication/access_rule_checked`

Реагирует на:
- `duplication/platform_loaded`
- `duplication/module_booted`
- `duplication/user_registered`
- `user_register` (WordPress lifecycle hook)

## Административное управление пользователем (этот шаг)

Где управляется:
- в карточке пользователя WordPress (`Пользователи → Все пользователи → Редактировать`).
- в собственном профиле администратора (`Пользователи → Профиль`).

Что показывается в блоке `Duplication Access`:
- текущий `dp_business_status`;
- вычисленный access level (derive only из `dp_business_status`);
- текущий `dp_gender`;
- служебная пометка `admin_override` для пользователя (`enabled` / `not_enabled` + reason).

Что редактируется:
- `dp_business_status` (только `candidate`, `partner`, `vip_partner`);
- `dp_gender` (только `male`, `female`, `unknown`).

Ключевые правила:
- уровень доступа не хранится отдельным meta key;
- уровень каждый раз вычисляется через каноническое соответствие status→level;
- изменения выполняются через канонические методы `AccessService::setBusinessStatus()` и `AccessService::setGender()`;
- альтернативные meta key не создаются.

События при изменении через админку:
- `duplication/business_status_changed` при фактической смене `dp_business_status`;
- `duplication/user_gender_changed` при фактической смене `dp_gender`.

## Реализовано в этой задаче
- Базовый рабочий каркас domain-layer модуля `duplication-access`.
- Подключение к `duplication-core` через проверку зависимости и регистрацию.
- Базовый service layer для status/gender/access-level и access-check.
- Структурированные access-ответы (`allowed`, `reason`).
- Минимальный системный admin UI для каноники и настройки `admin_override` ролей.
- Админ-управление `dp_business_status` и `dp_gender` в карточке пользователя.
- Отображение вычисленного access level без отдельного meta key.
- Отображение служебной пометки `admin_override` для пользователя.
- Публикация канонических событий изменения статуса/пола через access-сервис.

## Не реализовано в этой задаче
- `duplication-webinars`
- `duplication-agents`
- любая webinar/agent/CRM логика
- обучение, реферальная система, share-модуль
- партнёрские анкеты
- модерация логинов во внешних системах
- продуктовые UI других модулей
