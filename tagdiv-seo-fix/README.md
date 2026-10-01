# SEO Fix для новостного сайта

**Версия:** 0.4.0 (черновик, на боевом сайте не тестировался)
**Автор:** Команда разработки сайта
**Лицензия:** MIT

## Назначение

Корректная микроразметка для новостного сайта на WordPress с темой Newspaper:

- Schema.org `NewsArticle` (JSON-LD)
- Open Graph (`og:*`, `article:*`)
- Twitter Cards (`twitter:*`)

Плагин **перехватывает и отключает** встроенную микроразметку темы Newspaper (tagDiv), чтобы избежать дублирования и конфликтов.

## Аварийный выключатель

Плагин активен только если в `wp-config.php` задана константа:

```php
define( 'SITENAME_ENABLE_CUSTOM_SEO', true );
```

Без этой константы (или при значении `false`) плагин немедленно завершает работу через `return` и не вмешивается в вывод темы.

Это позволяет:

- Держать файл в `mu-plugins` всегда, но включать его только на нужных окружениях.
- Быстро отключать разметку без удаления файла и без Git-деплоя.

## Что делает плагин

1. **Отключает schema темы tagDiv.** Через фильтр `td_option` принудительно выставляет опцию `tds_disable_article_schema` в `'1'`. Это заставляет тему думать, что соответствующая галочка в настройках включена, и она не выводит свою разметку.

2. **Выводит Open Graph.** Теги `og:type`, `og:title`, `og:description`, `og:url`, `og:site_name`, `og:locale`, `og:image` (с размерами и `alt`), `article:published_time`, `article:modified_time`, `article:section`, `article:author`.

3. **Выводит Twitter Cards.** `twitter:card` = `summary_large_image`, `twitter:title`, `twitter:description`, `twitter:image`.

4. **Выводит JSON-LD `NewsArticle`.** Содержит `mainEntityOfPage`, `headline`, `description`, `datePublished`, `dateModified`, `author` (объект `Person` с `name` и `url`), `publisher` (объект `Organization` с `logo`), `image` (объект `ImageObject` с размерами, если они известны).

## Управление

### Отключить полностью

Уберите константу `SITENAME_ENABLE_CUSTOM_SEO` из `wp-config.php` или установите `false`.

### Ограничить одной записью (для теста)

В коде предусмотрена константа `SITENAME_SEO_TEST_POST_ID`. По умолчанию она равна `0`, что означает «разметка выводится для всех записей». Любое значение больше нуля ограничивает вывод одной записью с этим ID.

Пример для `wp-config.php` на тестовом окружении:

```php
define( 'SITENAME_SEO_TEST_POST_ID', 12345 );
```

Чтобы вернуть боевой режим — уберите строку из `wp-config.php` или установите `0`.

### Заменить логотип издателя

В классе заданы приватные свойства:

```php
private $publisher_logo_url = 'https://example.com/wp-content/uploads/publisher-logo.png';
private $publisher_logo_width = 512;
private $publisher_logo_height = 512;
```

Размеры должны соответствовать реальному файлу. Требования:

- **Формат:** PNG. Google с 2026 года принимает и SVG, но Яндекс-валидатор относится к SVG настороженно, поэтому для `publisher.logo` используйте растровый формат.
- **Размер:** квадратный, минимум 512×512.
- **Без прозрачности, теней и скруглений.** Фон — однотонный (белый или цвет сайта).

**Важно:** в JSON-LD поле называется `contentUrl`, а не `url`. Это требование валидатора Яндекса — с `url` внутри `ImageObject` он выдаёт ошибку `В свойстве content тега meta не может содержаться ссылка`. В Open Graph и Twitter Cards по-прежнему используется `og:image` и `twitter:image` — там URL передаётся строкой.

## Зависимости

- WordPress 5.0+.
- PHP 7.4+.
- Тема Newspaper (tagDiv) — для перехвата опции `tds_disable_article_schema`. Без неё плагин не сломается, просто фильтр не будет ни на что влиять.

## Логика работы

### Приоритеты хуков

- `td_option` (фильтр) — приоритет `99`, чтобы переопределить любое значение по умолчанию.
- `wp_head` → Open Graph — приоритет `1` (выводим раньше остальных).
- `wp_head` → Twitter Cards — приоритет `2`.
- `wp_head` → JSON-LD — приоритет `10`.

### Источник описания

1. Если заполнен `post_excerpt` — берём его.
2. Иначе — первый абзац `<p>...</p>` из `post_content`.
3. Если `<p>` нет — текст до первого двойного переноса строки.
4. Из результата удаляются шорткоды (`strip_shortcodes`), HTML-теги (`wp_strip_all_tags`), лишние пробелы.
5. Обрезается до 160 символов (`mb_substr` с UTF-8).

### Источник изображения

1. Миниатюра записи (`get_post_thumbnail_id`, размер `large`) — с реальными размерами.
2. Первое `<img>` из контента записи — **без указания размеров**, чтобы не выдавать недостоверные данные.
3. Логотип издателя — как fallback.

### Безопасность

- Прямой доступ к файлу блокируется проверкой `ABSPATH`.
- Все значения экранируются через `esc_attr` при выводе `<meta>`.
- JSON-LD формируется через `wp_json_encode` с флагами `JSON_UNESCAPED_UNICODE` и `JSON_UNESCAPED_SLASHES`. Повторное экранирование не применяется — `wp_json_encode` уже обеспечивает безопасность внутри `<script>`.

## Отладка

1. Включите `WP_DEBUG` в `wp-config.php`:

   ```php
   define( 'WP_DEBUG', true );
   define( 'WP_DEBUG_LOG', true );
   ```

2. Откройте тестовую запись, `Ctrl+U` — найдите `<script type="application/ld+json">`.

3. Скопируйте JSON и вставьте в [validator.schema.org](https://validator.schema.org/) — покажет ошибки структуры.

4. Для проверки OG-тегов — [opengraph.xyz](https://www.opengraph.xyz/) или [developers.facebook.com/tools/debug](https://developers.facebook.com/tools/debug/).

5. Для Google — [Rich Results Test](https://search.google.com/test/rich-results).

## Совместимость с темой

Плагин перехватывает опцию `tds_disable_article_schema` через фильтр `td_option`. Если тема Newspaper обновится и фильтр перестанет вызываться, в исходном коде страницы появятся **два** блока JSON-LD: один от темы, один от плагина.

Проверка: `Ctrl+U` → поиск `application/ld+json` → должен быть **один** блок. Если два — тема не отдаёт опцию через фильтр, и нужно искать другой способ отключения её схемы (например, через настройки темы в админке).

## Соответствие требованиям 2026 года

- **Google:** структура `NewsArticle` содержит все рекомендованные поля. Формат логотипа издателя — PNG, минимум 512×512, без прозрачности.
- **Яндекс:** поддерживает JSON-LD `NewsArticle`. В Open Graph поле `article:author` передаётся **строкой** с именем автора — это требование Яндекс.Метрики, не менять на URL. В `ImageObject` используется `contentUrl`, а не `url`.
- **AI-поиск:** JSON-LD с `author.url`, ведущим на страницу автора, и `publisher.logo` с корректными размерами улучшает цитируемость.

## Что стоит проверить перед продакшеном

1. Логотип издателя — PNG, минимум 512×512, без прозрачности.
2. Даты в ISO 8601 с таймзоной (проверить через Rich Results Test).
3. Отсутствие дублирующей разметки от темы (в исходном коде — один блок `application/ld+json`).
4. Валидность JSON-LD через официальные валидаторы Google и Яндекса.

## Проверка дат и разметки через WP-CLI

Перед включением плагина на боевом сайте стоит убедиться, что WordPress отдаёт даты в формате ISO 8601 с таймзоной. Это делается из корня сайта:

```bash
wp eval '
$posts = get_posts(["numberposts" => 5, "post_status" => "publish"]);
foreach ($posts as $p) {
    printf(
        "ID %d | published: %s | modified: %s\n",
        $p->ID,
        get_the_date("c", $p),
        get_the_modified_date("c", $p)
    );
}
'
```

**Ожидаемый формат:** `2026-10-01T13:36:59+05:00` — дата, время, смещение от UTC. Если в выводе нет `+XX:XX` или вместо `T` стоит пробел — это повод разобраться с настройками даты в WordPress, прежде чем включать плагин.

**Проверка таймзоны сайта:**

```bash
wp option get timezone_string
wp option get gmt_offset
```

**Проверка конкретной записи (для тестового режима):**

```bash
wp eval '
$id = 167438; // замените на реальный ID
$p = get_post($id);
if (!$p) { echo "Запись не найдена\n"; exit; }
printf("Title:     %s\n", get_the_title($p));
printf("Published: %s\n", get_the_date("c", $p));
printf("Modified:  %s\n", get_the_modified_date("c", $p));
printf("Author:    %s\n", get_the_author_meta("display_name", $p->post_author));
printf("Thumb:     %s\n", get_post_thumbnail_id($p) ?: "нет");
'
```

Это заодно покажет, есть ли у записи миниатюра — от неё зависит, попадёт ли в OG-теги реальное изображение или fallback на логотип издателя.

## История версий

### 0.4.0 — черновик

- Обезличены имена классов, констант и URL.
- Константа `SITENAME_SEO_TEST_POST_ID` всегда определена, по умолчанию `0`.
- Введён метод `get_test_post_id()` для обхода ложного срабатывания PHPStan.
- Логотип издателя приведён к PNG 512×512.
- В JSON-LD `ImageObject` использует `contentUrl` вместо `url` — снимает ошибку валидатора Яндекса.
- Добавлены docblock для класса, свойств, методов — проходит WordPress-Coding-Standards.
- Лицензия MIT.

**Не проверялось:** вывод на реальном сайте, валидация в Rich Results Test, поведение с темой Newspaper в боевом режиме.

## Лицензия

MIT. См. файл [LICENSE](LICENSE) в папке плагина.

Код можно свободно использовать, изменять и распространять при условии сохранения текста лицензии и указания авторства. Авторы не несут ответственности за возможный ущерб от использования.