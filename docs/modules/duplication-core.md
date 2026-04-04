# duplication-core

## Назначение
`duplication-core` — инфраструктурное ядро платформы WordPress 2.0.

В рамках этого шага реализован только технический каркас:
- bootstrap платформы;
- безопасная загрузка плагина;
- базовый loader (PSR-4-подобный autoloader);
- реестр модулей;
- статусная модель модулей;
- проверка зависимостей модулей;
- системная диагностика по зарегистрированным модулям;
- системный admin shell (только root/modules/diagnostics).

## Структура модуля
- `plugins/duplication-core/duplication-core.php` — главный файл плагина и публичные точки входа.
- `plugins/duplication-core/includes/Autoloader.php` — базовая загрузка классов namespace `Duplication\Core\`.
- `plugins/duplication-core/src/Bootstrap.php` — bootstrap ядра, platform loaded, события, диагностика.
- `plugins/duplication-core/src/ModuleRegistry.php` — регистрация, статус, reason, проверки registered/active.
- `plugins/duplication-core/src/ModuleStatus.php` — каноника статусов инфраструктурного модуля.
- `plugins/duplication-core/src/Admin/AdminShell.php` — системные страницы admin shell.

## Публичные точки входа
Функции из главного файла плагина:
- `duplication_core_bootstrap()`
- `duplication_core_platform_loaded()`
- `duplication_core_register_module(string $moduleSlug, array $definition)`
- `duplication_core_is_module_registered(string $moduleSlug)`
- `duplication_core_is_module_active(string $moduleSlug)`
- `duplication_core_get_module_status(string $moduleSlug)`
- `duplication_core_get_module_reason(string $moduleSlug)`
- `duplication_core_check_module_dependencies(string $moduleSlug)`
- `duplication_core_system_diagnostics()`

Минимальный формат структурированных проверок:
- `ok`
- `status`
- `reason`

## Каноника статусов модуля
- `registered`
- `booting`
- `active`
- `inactive`
- `failed`

## Базовые платформенные события
- `duplication/platform_loaded`
- `duplication/module_registered`
- `duplication/module_booted`
- `duplication/module_failed`

## Реализовано в этой задаче
- Создан отдельный плагин `duplication-core`.
- Подключён безопасный bootstrap ядра без продуктового кода.
- Добавлен реестр модулей с хранением status/reason.
- Добавлены проверки registered/active/status/reason/dependencies.
- Добавлена базовая системная диагностика.
- Добавлен минимальный admin shell (root + modules + diagnostics).

## Не реализовано в этой задаче
- `duplication-access`
- `duplication-webinars`
- `duplication-agents`
- business status layer
- user_meta платформы
- продуктовые сущности
- CRM
- share-модуль
- монетизация
- любая доменная или продуктовая логика
