# Changelog

Все значимые изменения фиксируются в этом файле.
Формат основан на [Keep a Changelog](https://keepachangelog.com/ru/1.1.0/),
версионирование — [SemVer](https://semver.org/lang/ru/).

## [Unreleased]

### Added

### Fixed

## [0.5.0] — 2026-10-01

### Added
- Метод `add_og_prefix()` — добавляет префиксы `og` и `article` в тег `<html>`. Закрывает ошибку валидатора Яндекса «префикс article неизвестен валидатору».
- Поле `image` в схему `Organization` — закрывает предупреждение Google Rich Results Test «Отсутствует поле image».

### Fixed
- Обновлены `url` и `contentUrl` в `ImageObject` — совместимость с Google и Яндексом одновременно.


## [0.4.0] — черновик

### Added
- Константа `DYSEO_SEO_TEST_POST_ID` со значением по умолчанию `0`.
- Приватный метод `get_test_post_id()` для обхода ложного срабатывания PHPStan.
- Опциональные константы `DYSEO_PUBLISHER_ADDRESS`, `DYSEO_PUBLISHER_PHONE`, `DYSEO_PUBLISHER_LOGO_URL`, `DYSEO_PUBLISHER_LOGO_WIDTH`, `DYSEO_PUBLISHER_LOGO_HEIGHT`.
- Метод `get_safe_constant()` с защитой от утечки плейсхолдеров в боевую разметку.
- Метод `get_publisher_logo()` с fallback на иконку сайта (`site_icon`).
- Метод `get_publisher_schema()` — сборка схемы `Organization`.
- Файл `LICENSE` (MIT).

### Changed
- Класс `Sitename_SEO` переименован в `Dyseo_SEO`.
- Константы `SITENAME_*` переименованы в `DYSEO_*`.
- Префикс глобальных переменных в `loader.php` — `$dyseo_`.
- В `ImageObject` (JSON-LD) добавлены оба ключа — `url` и `contentUrl` — с одинаковым значением. Закрывает требования Google (ожидает `url`) и Яндекса (ожидает `contentUrl`).
- Логотип издателя и изображение новости читаются через `get_publisher_logo()` / `get_og_image()`, а не из свойств класса.
- Реальные URL и имена заменены на обезличенные.

### Fixed
- Фильтр `td_option` теперь проверяет `should_output()` перед отключением schema темы. Раньше при включённом тестовом режиме schema отключалась на всех записях, а своя выводилась только на одной — остальные оставались без разметки.

### Known issues
- Не тестировалось на боевом сайте.
- Не проверялось в Rich Results Test (Google) и валидаторе Яндекса.
- Поведение с темой Newspaper проверено только по коду, не на живой установке.