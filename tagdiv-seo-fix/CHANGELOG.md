Все значимые изменения фиксируются в этом файле.
Формат основан на [Keep a Changelog](https://keepachangelog.com/ru/1.1.0/),
версионирование — [SemVer](https://semver.org/lang/ru/).

## [Unreleased]

### Added
- Константа `SITENAME_PUBLISHER_LOGO_URL` и размеры `WIDTH`/`HEIGHT` для логотипа издателя.
- Константы `SITENAME_PUBLISHER_ADDRESS` и `SITENAME_PUBLISHER_PHONE` для Яндекс.Справочника.
- Метод `get_safe_constant()` с защитой от утечки плейсхолдеров в боевую разметку.
- Fallback логотипа на иконку сайта (`site_icon`) из настроек WordPress.

### Changed
- Удалены свойства класса `$publisher_logo_url`, `$publisher_logo_width`, `$publisher_logo_height`.
- `publisher` в JSON-LD собирается методом `get_publisher_schema()`.
- `get_og_image()` использует `get_publisher_logo()` вместо свойств класса.

### Fixed
- Фильтр `td_option` теперь проверяет `should_output()` перед отключением schema темы.