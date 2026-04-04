# Пакет передачи в Codex
## Реализация модульной платформы 2.0
## Основа: duplication-core, duplication-access, duplication-webinars, duplication-agents

---

## 1. Цель реализации

Нужно реализовать новую модульную платформу 2.0 для WordPress как чистую архитектуру, в которой:

- `duplication-core` выступает единым инфраструктурным ядром платформы;
- `duplication-access` выступает единым доменным источником истины по business status, gender и access logic;
- `duplication-webinars` реализует продуктовый контур вебинаров, страниц, комнат, расписания, чата, GPT-резюме и напоминаний;
- `duplication-agents` реализует продуктовый агентский контур, owner/source-логику, entitlement и прозрачную модель reward.

Цель первой реализации:
собрать чистый MVP платформы без legacy-хаоса, без смешивания слоёв и без дублирования канонических сущностей. Архитектура должна быть пригодна для поэтапного подключения на прод без переноса старого хаоса в репозиторий.

---

## 2. Список модулей и их роль

### 2.1. duplication-core
Инфраструктурное ядро платформы.

Роль:
- загрузка платформы;
- регистрация и инициализация модулей;
- единый реестр модулей;
- проверка зависимостей;
- единый событийный слой;
- общая системная диагностика;
- общий каркас системной админки.

`duplication-core` не содержит продуктовую бизнес-логику и не подменяет доменные или продуктовые модули.

### 2.2. duplication-access
Базовый доменный модуль доступа.

Роль:
- хранение business status пользователя;
- хранение gender пользователя;
- вычисление уровня доступа;
- проверка minimum_access_level;
- проверка female_club;
- поддержка admin_override;
- единый сервис проверки доступа для других модулей.

`duplication-access` — единственный доменный источник истины по статусам и доступу.

### 2.3. duplication-webinars
Продуктовый модуль вебинаров и онлайн-комнат.

Роль:
- сущность вебинара;
- отдельная публичная страница вебинара;
- расписание вебинаров;
- комната вебинара;
- проверка допуска через duplication-access;
- чат;
- GPT-резюме и Q&A;
- SEO-структура страницы вебинара;
- напоминания участникам и партнёрам;
- интерфейсы спикера и модератора.

`duplication-webinars` не хранит свою отдельную модель access/status layer.

### 2.4. duplication-agents
Продуктовый модуль агентского режима и монетизации.

Роль:
- agent mode;
- entitlement на агентский инструмент;
- связь агент → партнёр;
- связь owner/source по лидам;
- фиксация лидов;
- прозрачная модель вознаграждений;
- кабинет владельца агентского инструмента;
- логика платного усилителя для `vip_partner`.

`duplication-agents` не подменяет status layer и не делает agent отдельным business status.

---

## 3. Зависимости между модулями

Порядок и зависимость модулей жёстко фиксируются так:

1. `duplication-core`
2. `duplication-access`
3. `duplication-webinars`
4. `duplication-agents`

### 3.1. duplication-core
Базовый модуль без зависимости на другие модули платформы. Он является точкой сборки всей системы.

### 3.2. duplication-access
Зависит от:
- `duplication-core`

Без `duplication-core` не должен запускаться как полноценный модуль.

### 3.3. duplication-webinars
Зависит от:
- `duplication-core`
- `duplication-access`

Без этих модулей не должен запускаться как полноценный модуль.

### 3.4. duplication-agents
Зависит от:
- `duplication-core`
- `duplication-access`

Без этих модулей не должен запускаться как полноценный модуль.

### 3.5. Явный контракт точек входа и интеграции между модулями

Для реализации MVP через Codex жёстко фиксируется единый контракт межмодульной интеграции.

#### 3.5.1. Общий принцип

Модули платформы не лезут напрямую во внутренние структуры друг друга.

Разрешены только 3 способа интеграции:

1. через публичный API модуля;
2. через канонические платформенные события;
3. через чтение канонических сущностей и полей там, где это прямо разрешено архитектурой.

Жёсткое правило:
внутренние классы, приватные структуры и внутренняя реализация модуля не считаются точкой входа для других модулей.

---

#### 3.5.2. Контракт duplication-core

`duplication-core` обязан дать другим модулям безопасные точки входа для:

- проверки, загружена ли платформа;
- проверки, зарегистрирован ли модуль;
- проверки, активен ли модуль;
- проверки зависимостей;
- получения статуса модуля;
- получения причины недоступности модуля;
- использования каноники платформенных событий.

Минимальный ожидаемый структурированный результат проверок:

- `ok`
- `status`
- `reason`

---

#### 3.5.3. Контракт duplication-access

`duplication-access` обязан дать другим модулям безопасные точки входа для:

- чтения `dp_business_status`;
- чтения `dp_gender`;
- вычисления уровня доступа;
- проверки `minimum_access_level`;
- проверки режима `female_club`;
- проверки `admin_override`;
- получения структурированного результата проверки доступа.

Минимальный ожидаемый структурированный результат:

- `allowed`
- `reason`

Другие модули не имеют права:
- хранить свои копии business status;
- строить свою иерархию уровней;
- придумывать свои альтернативные значения `candidate / partner / vip_partner`;
- придумывать свои альтернативные значения `male / female / unknown`.

---

#### 3.5.4. Контракт duplication-webinars

`duplication-webinars` обязан дать безопасные точки входа для:

- получения списка вебинаров;
- получения статуса вебинара;
- получения публичной страницы вебинара;
- проверки доступности вебинара для пользователя;
- получения причины недоступности;
- определения CTA;
- определения, можно ли войти в комнату;
- получения режима страницы;
- получения данных для отображения комнаты;
- получения данных GPT-резюме и Q&A.

Минимальный ожидаемый структурированный результат проверок:

- `allowed`
- `reason`
- `cta`
- `page_mode`

Другие модули не имеют права:
- дублировать webinar state machine;
- дублировать access-проверки внутри себя;
- считать `webinar_room` отдельной канонической SEO-страницей.

---

#### 3.5.5. Контракт duplication-agents

`duplication-agents` обязан дать безопасные точки входа для:

- проверки, включён ли agent mode;
- проверки, активно ли entitlement на агентский инструмент;
- получения owner-партнёра агента;
- получения owner/source-связей по лиду;
- проверки права на использование агентского инструмента;
- получения статуса reward;
- получения причин разрешения или отказа.

Минимальный ожидаемый структурированный результат проверок:

- `allowed`
- `reason`

Другие модули не имеют права:
- трактовать agent как business status;
- смешивать owner и source;
- придумывать альтернативные поля для agent mode, entitlement и owner/source-связей.

---

#### 3.5.6. Контракт событийной интеграции

События используются как межмодульный слой уведомления, а не как замена публичного API.

Минимальная каноника платформенных событий:

Инфраструктурные:
- `duplication/platform_loaded`
- `duplication/module_registered`
- `duplication/module_booted`
- `duplication/module_failed`

Доменные:
- `duplication/business_status_changed`
- `duplication/user_gender_changed`
- `duplication/access_rule_checked`

Продуктовые webinar:
- `duplication/webinar_created`
- `duplication/webinar_updated`
- `duplication/webinar_started`
- `duplication/webinar_finished`
- `duplication/webinar_canceled`
- `duplication/webinar_chat_saved`
- `duplication/webinar_summary_generated`
- `duplication/webinar_summary_published`

Продуктовые agent:
- события agent-модуля должны использовать ту же канонику именования `duplication/<event_name>` и не нарушать owner/source-модель.

Жёсткое правило:
если модулю нужен результат вычисления, он обращается в публичный API модуля.
Если модулю нужно уведомить систему о факте события, он публикует событие.

---

#### 3.5.7. Поведение при недоступности зависимости

Если зависимый модуль недоступен:

- модуль не должен падать фатально;
- модуль не должен тихо работать наполовину;
- модуль должен вернуть структурированную причину отказа;
- модуль должен перейти в безопасное состояние;
- причина должна быть видна в системной диагностике платформы.

Это правило обязательно для:
- `duplication-access`
- `duplication-webinars`
- `duplication-agents`

---

## 4. Границы ответственности каждого модуля

### 4.1. duplication-core отвечает только за:
- platform bootstrap;
- module registry;
- module dependency;
- module status;
- platform events;
- admin shell;
- system diagnostics.

Не отвечает за:
- business status;
- access logic;
- webinars;
- rooms;
- reminders;
- GPT-summary;
- agents;
- leads;
- rewards;
- CRM;
- monetization.

### 4.2. duplication-access отвечает только за:
- `dp_business_status`;
- `dp_gender`;
- вычисление уровня доступа;
- minimum_access_level;
- female_club;
- admin_override;
- единый ответ проверки доступа.

Не отвечает за:
- webinars;
- webinar rooms;
- chats;
- schedules;
- reminders;
- CRM;
- forms;
- referral logic;
- webinar moderation;
- product logic других модулей.

### 4.3. duplication-webinars отвечает только за:
- webinars;
- webinar pages;
- webinar room;
- webinar schedule;
- source/player;
- chat;
- GPT-summary/Q&A;
- reminders;
- speaker/moderator controls;
- webinar SEO pages.

Не отвечает за:
- хранение business status;
- gender;
- access levels;
- CRM;
- partner moderation;
- VIP calculation;
- universal referral engine;
- global share engine.

### 4.4. duplication-agents отвечает только за:
- agent mode;
- agent enablement;
- agent owner partner link;
- owner/source logic;
- agent leads;
- reward model;
- entitlement to use agent tool.

Не отвечает за:
- business status;
- gender;
- access levels;
- webinar access;
- WordPress roles;
- universal referral layer;
- webinars;
- CRM;
- learning;
- platform core.

---

## 5. Канонические сущности, поля, статусы и связи

### 5.1. Платформенные типы сущностей
На уровне платформы зафиксированы разные типы сущностей:

1. WordPress role
2. business status
3. participation mode
4. entitlement
5. relationship link

Их запрещено смешивать.

### 5.2. WordPress role
Примеры:
- administrator
- moderator
- speaker
- editor

Role = инфраструктурная системная роль.  
Role не заменяет business status.

### 5.3. Business status
Канонические значения:
- `candidate`
- `partner`
- `vip_partner`

Используются только как продуктовая лестница доступа.  
Не смешиваются с agent-mode, roles и entitlements.

### 5.4. Уровни
Каноническая иерархия:
- `candidate = 1`
- `partner = 2`
- `vip_partner = 3`

Отдельно в базе не хранятся как отдельный канонический meta_key, а вычисляются по `dp_business_status`.

### 5.5. Gender
Канонические значения:
- `male`
- `female`
- `unknown`

### 5.6. Participation mode
Для MVP:
- `agent`

Каноническое поле:
- `dp_is_agent`

Значение:
- `1` = пользователь участвует как агент
- `0` = пользователь не участвует как агент

### 5.7. Entitlement
Для MVP:
- `dp_entitlement_agents`

Это право использовать агентский инструмент.
Не является role, status или mode.

### 5.8. Канонические user_meta платформы
На уровне платформы фиксируются:
- `dp_business_status`
- `dp_gender`
- `dp_is_agent`
- `dp_agent_enabled`
- `dp_agent_owner_partner_id`
- `dp_entitlement_agents`

### 5.9. Канонические relationship links
Канонические связи платформы MVP:
- `dp_agent_owner_partner_id`
- `owner_partner_id`
- `source_agent_id`

### 5.10. Owner / Source
`owner_partner_id`  
Партнёр-владелец лида.

`source_agent_id`  
Агент-источник лида.

Это разные сущности и их запрещено смешивать.

### 5.11. Канонические понятия access layer
- `admin_override`
- `minimum_access_level`
- `female_club`

### 5.12. Канонические значения access layer
Business status:
- `candidate`
- `partner`
- `vip_partner`

Gender:
- `male`
- `female`
- `unknown`

Meta keys:
- `dp_business_status`
- `dp_gender`

### 5.13. Канонические сущности webinars
Ключевые понятия:
- `webinar`
- `webinar_room`
- `webinar_schedule`
- `public_webinar_page`
- `webinar_state`
- `speaker`
- `share_enabled`
- `participant_reminders_enabled`
- `partner_share_reminders_enabled`
- `minimum_access_level`
- `female_club`
- `chat_storage`
- `chat_summary`
- `chat_faq`
- `generate_summary`
- `publish_summary`
- `clear_chat`

### 5.14. Канонические значения webinars
Форматы:
- `standard`
- `female_club`

Состояния:
- `scheduled`
- `live`
- `finished`
- `canceled`

Уровни доступа:
- `candidate`
- `partner`
- `vip_partner`

Типы источников трансляции:
- `youtube_embed`
- `iframe_embed`
- `external_link`

### 5.15. Канонические сущности agents
- `agent`
- `agent_link`
- `agent_owner_partner`
- `agent_lead`
- `agent_reward`

### 5.16. Канонические поля agents
User meta:
- `dp_is_agent`
- `dp_agent_enabled`
- `dp_agent_owner_partner_id`
- `dp_entitlement_agents`

Lead fields:
- `owner_partner_id`
- `source_agent_id`
- `lead_user_id`

Reward fields:
- `reward_amount`
- `reward_status`

### 5.17. Канонические состояния вознаграждения
`reward_status`:
- `pending`
- `approved`
- `paid`
- `canceled`
- `disputed`

### 5.18. Явная модель хранения данных по модулям в WordPress

Для реализации MVP в WordPress жёстко фиксируется следующая модель хранения данных.

#### 5.18.1. Пользовательские сущности платформы

В `user_meta` хранятся только канонические пользовательские поля платформы:

- `dp_business_status`
- `dp_gender`
- `dp_is_agent`
- `dp_agent_enabled`
- `dp_agent_owner_partner_id`
- `dp_entitlement_agents`

Жёсткое правило:
эти поля являются каноническими пользовательскими `user_meta`.
Их нельзя дублировать альтернативными `meta_key` с тем же смыслом.

---

#### 5.18.2. Сущность webinar

Сущность `webinar` в реализации WordPress является:

- **custom post type**
- с хранением полей вебинара в **post meta**

То есть:
- один вебинар = одна запись отдельного `custom post type`
- все поля вебинара хранятся как `post meta` этой записи

В `post meta` вебинара живут, в том числе:
- `webinar_state`
- `minimum_access_level`
- формат вебинара
- источник трансляции
- настройки публичной страницы
- настройки режима комнаты
- SEO-поля
- structured data
- GPT-резюме
- Q&A
- настройки чата
- настройки напоминаний
- CTA и контент после завершения

Жёсткое правило:
данные вебинара не хранятся в `user_meta`, потому что они относятся к сущности вебинара, а не к пользователю.

---

#### 5.18.3. Сущность lead

Сущность `lead` в реализации WordPress является:

- **custom table**

Для лидов должна существовать отдельная таблица модуля, в которой хранятся, как минимум:

- `owner_partner_id`
- `source_agent_id`
- `lead_user_id`

Причина:
lead — это отдельная relationship/entity-сущность платформы, а не пользовательский профиль и не контентная запись.

Жёсткое правило:
lead не хранится как `user_meta` и не реализуется как `custom post type`.

---

#### 5.18.4. Сущность reward

Сущность `reward` в реализации WordPress является:

- **custom table**

Для reward должна существовать отдельная таблица модуля, в которой хранятся, как минимум:

- `reward_amount`
- `reward_status`

Причина:
reward — это отдельная финансово-логическая сущность платформы, требующая прозрачных статусов, отдельного жизненного цикла и изоляции от пользовательских meta-полей и контентных сущностей.

Жёсткое правило:
reward не хранится как `user_meta` и не реализуется как `custom post type`.

---

#### 5.18.5. Что не хранится в duplication-core

`duplication-core` не хранит канонические доменные и продуктовые данные как источник истины.

В ядре запрещено хранить как канонику:

- `dp_business_status`
- `dp_gender`
- `dp_is_agent`
- `dp_agent_enabled`
- `dp_agent_owner_partner_id`
- `dp_entitlement_agents`
- `owner_partner_id`
- `source_agent_id`
- `lead_user_id`
- `reward_amount`
- `reward_status`
- `webinar_state`
- `minimum_access_level`

Жёсткое правило:
`duplication-core` хранит только инфраструктурное состояние платформы и модулей.

---

#### 5.18.6. Общий принцип для Codex

При реализации через Codex нужно соблюдать точное разделение типов хранения:

1. пользовательские сущности платформы = `user_meta`
2. `webinar` = `custom post type` + `post meta`
3. `lead` = `custom table`
4. `reward` = `custom table`

Эти типы хранения не должны смешиваться между собой.

---

## 6. Что обязательно реализовать в MVP

### 6.1. duplication-core
Обязательно реализовать:
1. единый platform bootstrap;
2. module registry;
3. безопасную регистрацию модулей;
4. safe init order;
5. dependency checks;
6. module status:
   - `registered`
   - `booting`
   - `active`
   - `inactive`
   - `failed`
7. platform event naming;
8. system diagnostics;
9. admin shell для системных страниц.

### 6.2. duplication-access
Обязательно реализовать:
1. хранение `dp_business_status`;
2. хранение `dp_gender`;
3. дефолт:
   - `candidate` после регистрации;
   - `unknown` по полу, если не задан;
4. вычисление уровня доступа по статусу;
5. проверку `minimum_access_level`;
6. режим `female_club`;
7. `admin_override`;
8. единый структурированный результат access check;
9. админское управление status/gender;
10. events:
   - `duplication/business_status_changed`
   - `duplication/user_gender_changed`
   - `duplication/access_rule_checked`

### 6.3. duplication-webinars
Обязательно реализовать:
1. сущность вебинара;
2. один отдельный постоянный URL на вебинар;
3. public webinar page;
4. room mode как режим того же вебинара;
5. webinar schedule;
6. видимость всех актуальных вебинаров в расписании, включая закрытые;
7. access through duplication-access;
8. support `standard` и `female_club`;
9. states:
   - `scheduled`
   - `live`
   - `finished`
   - `canceled`
10. ручной запуск/завершение вебинара;
11. source/player support:
   - `youtube_embed`
   - `iframe_embed`
   - `external_link`
12. chat;
13. participant list;
14. GPT-summary and Q&A;
15. reminders to participants;
16. reminders to partners for sharing;
17. SEO fields and structured data;
18. page modes:
   - до начала
   - во время
   - после завершения
19. speaker/moderator controls;
20. events:
   - `duplication/webinar_created`
   - `duplication/webinar_updated`
   - `duplication/webinar_started`
   - `duplication/webinar_finished`
   - `duplication/webinar_canceled`
   - `duplication/webinar_chat_saved`
   - `duplication/webinar_summary_generated`
   - `duplication/webinar_summary_published`

### 6.4. duplication-agents
Обязательно реализовать:
1. agent mode отдельно от business status;
2. `dp_is_agent`;
3. `dp_agent_enabled`;
4. `dp_agent_owner_partner_id`;
5. `dp_entitlement_agents`;
6. owner/source logic;
7. lead fields:
   - `owner_partner_id`
   - `source_agent_id`
   - `lead_user_id`
8. reward model;
9. reward states:
   - `pending`
   - `approved`
   - `paid`
   - `canceled`
   - `disputed`
10. entitlement check:
   - user is `vip_partner`
   - entitlement active
11. прозрачный кабинет владельца агентского инструмента:
   - агенты
   - лиды
   - конверсии
   - rewards
12. правило: агент не становится owner лида;
13. правило: рост агента в партнёра не переносит старые лиды;
14. events and safe API through platform contracts.

---

## 7. Что не входит в MVP

### 7.1. На уровне duplication-core
Не входит:
- access logic;
- webinar logic;
- agent logic;
- CRM;
- monetization logic;
- business analytics;
- product forms;
- product automations.

### 7.2. На уровне duplication-access
Не входит:
- webinar product logic;
- schedule/chats/reminders;
- CRM;
- registration/questionnaire forms;
- referral engine;
- webinar moderation;
- content logic других модулей.

### 7.3. На уровне duplication-webinars
Не входит:
- автостарт вебинара по времени;
- автозавершение вебинара по времени;
- тяжёлая оркестрация;
- сложные конструкторы сценариев;
- модуль CRM;
- универсальный share engine;
- модерация партнёрских заявок;
- расчёт VIP;
- общий referral engine платформы;
- архив записей как отдельный самостоятельный модуль.

### 7.4. На уровне duplication-agents
Не входит:
- автоматический расчёт внешнего бизнес-дохода партнёра;
- автоматические денежные выплаты;
- сложный биллинг;
- автоматические санкции;
- business status logic;
- access logic;
- webinars;
- CRM;
- learning;
- platform core logic.

---

## 8. Что нельзя ломать

### 8.1. Общие архитектурные запреты
Нельзя:
- смешивать infrastructure layer, domain layer и product modules;
- дублировать сущности и поля с тем же смыслом;
- плодить альтернативные статусы, роли и meta_key;
- переносить legacy как новую канонику;
- делать «комбайн на всё».

### 8.2. Нельзя ломать канонику платформы
Нельзя:
- использовать WordPress role вместо business status;
- использовать business status вместо role;
- хранить mode внутри status;
- хранить entitlement внутри status;
- смешивать owner и source;
- плодить параллельные поля с тем же смыслом.

### 8.3. Нельзя ломать access layer
Нельзя:
- добавлять `agent` в `dp_business_status`;
- добавлять `pending_partner`;
- строить product logic на чистых WP roles;
- дублировать access logic внутри других модулей.

### 8.4. Нельзя ломать webinar model
Нельзя:
- делать один динамический URL на разные вебинары;
- делать вторую каноническую SEO-страницу комнаты;
- индексировать живой чат;
- скрывать закрытые вебинары из расписания;
- ослаблять SEO-страницу до «постер + таймер»;
- дублировать share logic внутри webinar module;
- дублировать access logic внутри webinar module.

### 8.5. Нельзя ломать agent model
Нельзя:
- делать `agent` business status;
- делать agent заменой partner;
- смешивать mode, entitlement и status;
- смешивать owner и source;
- давать агенту функции партнёра;
- переносить старые лиды агенту после его роста в партнёра;
- дублировать status layer внутри agent module.

---

## 9. Критерии готовности для Codex

Результат считается корректным, если одновременно выполнено следующее:

### 9.1. По duplication-core
- платформа загружается через единый каркас;
- есть единый реестр модулей;
- есть dependency checks;
- нарушение зависимости не валит платформу фаталом;
- есть module statuses;
- есть базовая диагностика;
- core не содержит продуктовую бизнес-логику.

### 9.2. По duplication-access
- у каждого пользователя есть единый business status;
- status отделён от WP roles;
- есть единый gender;
- уровень доступа вычисляется корректно;
- `minimum_access_level`, `female_club`, `admin_override` работают через единый сервис;
- другие модули используют один service entrypoint;
- нет дублей и альтернативных названий status layer.

### 9.3. По duplication-webinars
- каждый вебинар — отдельная сущность;
- каждый вебинар имеет отдельную публичную страницу;
- room не является второй SEO-page;
- закрытые вебинары видны в расписании;
- вход в комнату проверяется через duplication-access;
- поддерживаются `candidate / partner / vip_partner`;
- поддерживается `female_club`;
- есть ручной control speaker/admin;
- reminders входят в MVP;
- чат может быть сохранён и обработан через GPT;
- summary/Q&A могут быть опубликованы на странице;
- есть participant list и partner statistics;
- нет дублирования с access/share/CRM.

### 9.4. По duplication-agents
- `agent` не является business status;
- mode и entitlement хранятся отдельно;
- owner и source разведены;
- старые лиды не переезжают к агенту после роста в партнёра;
- reward statuses прозрачны;
- агентский модуль не дублирует status layer;
- модуль зависит от `duplication-core` и `duplication-access`;
- владелец видит агентов, лидов и результаты.

### 9.5. Общий критерий готовности
Codex должен отдать реализацию, в которой:
- нет критичных противоречий между модулями;
- нет дублирования сущностей и полей;
- границы ответственности не размыты;
- канонические поля, статусы и связи реализованы в точном виде;
- модуль можно ставить поэтапно без разрушения архитектуры.

---

## 10. Что обновить в документации после реализации

После реализации каждого модуля нужно обязательно обновить документацию так, чтобы она отражала реальный код.

Обязательные обновления:

### 10.1. По каждому модулю
Нужно зафиксировать:
- фактическую структуру модуля;
- точки входа;
- публичный API;
- реальные events/hooks;
- реальные meta_key, поля, сущности и статусы;
- фактические admin pages/settings;
- ограничения MVP;
- зависимость от других модулей;
- что реализовано сейчас;
- что отложено за границы MVP.

### 10.2. По платформенной канонике
Нужно подтвердить в документации:
- что `dp_business_status`, `dp_gender`, `dp_is_agent`, `dp_agent_enabled`, `dp_agent_owner_partner_id`, `dp_entitlement_agents` реализованы без альтернативных дублей;
- что `owner_partner_id`, `source_agent_id`, `lead_user_id`, `reward_amount`, `reward_status` реализованы в точной канонике;
- что `candidate / partner / vip_partner`, `male / female / unknown`, `scheduled / live / finished / canceled`, `pending / approved / paid / canceled / disputed` реализованы без альтернативных значений.

### 10.3. По границам модулей
Нужно явно подтвердить:
- что `duplication-core` не содержит доменную и продуктовую бизнес-логику;
- что `duplication-access` не содержит webinar/CRM/business product logic;
- что `duplication-webinars` не дублирует access/share/status layer;
- что `duplication-agents` не дублирует access/status layer и не превращает agent в business status.

### 10.4. По этапу внедрения
Нужно зафиксировать:
- что новый код реализован как новая каноническая архитектура;
- какие legacy-участки ещё временно остались на проде;
- какие участки уже переведены на новый модуль;
- какие участки можно отключать после подтверждения работоспособности.

### 10.5. Финальная очистка документа передачи в Codex

Перед передачей документа в Codex финальная версия master-документа должна быть очищена от любых служебных хвостов и технической разметки, не относящейся к содержанию постановки.

Из финального документа должны быть полностью удалены:
- любые вставки вида `:contentReference[...]`
- любые служебные хвосты автогенерации
- любые внутренние маркеры, не являющиеся частью постановки
- любые ссылки и пометки, нужные только внутри чата, но не нужные Codex

Жёсткое правило:
в Codex передаётся только чистый цельный текст документа без служебного мусора и без внутренних технических следов подготовки.

---

## Итог для Codex

Реализовать 4 модуля платформы как единую чистую модульную систему WordPress:

1. `duplication-core` — infrastructure layer  
2. `duplication-access` — domain layer  
3. `duplication-webinars` — product module  
4. `duplication-agents` — product module  

Главные требования:
- без дублирования сущностей;
- без смешивания role/status/mode/entitlement/relationship;
- без нарушения owner/source;
- без переноса legacy-хаоса;
- с точной каноникой полей, статусов и связей;
- с поэтапной установкой модулей поверх платформенного каркаса.