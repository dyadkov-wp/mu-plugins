<?php
/**
 * Plugin Name: SEO Fix для новостного сайта
 * Description: Корректная микроразметка (Schema.org, Open Graph, Twitter Cards)
 *              для новостного сайта на WordPress с темой Newspaper.
 *              Перехватывает и отключает встроенную схему tagDiv.
 * Version:     0.4.0
 * Author:      Олег Дядьков
 * License:     MIT
 * License URI: https://opensource.org/licenses/MIT
 *
 * @package Sitename\SEO
 * @since   0.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// =============================================================================
// АВАРИЙНЫЙ ВЫКЛЮЧАТЕЛЬ
// =============================================================================
if ( ! defined( 'SITENAME_ENABLE_CUSTOM_SEO' ) || ! SITENAME_ENABLE_CUSTOM_SEO ) {
	return;
}

// =============================================================================
// КОНСТАНТА ДЛЯ ТОЧЕЧНОГО ТЕСТИРОВАНИЯ
// =============================================================================
/**
 * ID записи для точечного тестирования разметки.
 *
 *   0 — разметка выводится для всех записей (боевой режим).
 *   >0 — разметка выводится только для записи с этим ID (режим отладки).
 *
 * Переопределяется в wp-config.php:
 *   define( 'SITENAME_SEO_TEST_POST_ID', 12345 );
 *
 * @since 0.4.0
 */
if ( ! defined( 'SITENAME_SEO_TEST_POST_ID' ) ) {
	define( 'SITENAME_SEO_TEST_POST_ID', 0 );
}

/**
 * Добавляет микроразметку Schema.org, Open Graph и Twitter Cards
 * и отключает встроенную схему темы Newspaper (tagDiv).
 *
 * @since 0.4.0
 */
class Sitename_SEO {

	/**
	 * URL логотипа издателя.
	 *
	 * @since 0.4.0
	 * @var string
	 */
	private $publisher_logo_url = 'https://example.com/wp-content/uploads/publisher-logo.png';

	/**
	 * Ширина логотипа издателя в пикселях.
	 *
	 * @since 0.4.0
	 * @var int
	 */
	private $publisher_logo_width = 512;

	/**
	 * Высота логотипа издателя в пикселях.
	 *
	 * @since 0.4.0
	 * @var int
	 */
	private $publisher_logo_height = 512;

	/**
	 * Регистрирует хуки WordPress.
	 *
	 * @since 0.4.0
	 */
	public function __construct() {
		// 1. Программно отключаем встроенную микроразметку темы Newspaper (tagDiv).
		// Приоритет 99 гарантирует, что мы переопределим любое значение по умолчанию.
		add_filter( 'td_option', array( $this, 'force_disable_td_schema' ), 99, 2 );

		// 2. Выводим нашу чистую разметку.
		add_action( 'wp_head', array( $this, 'add_open_graph' ), 1 );
		add_action( 'wp_head', array( $this, 'add_twitter_cards' ), 2 );
		add_action( 'wp_head', array( $this, 'add_news_article_jsonld' ), 10 );
	}

	/**
	 * Перехватчик опций tagDiv.
	 * Заставляет тему думать, что галочка "DISABLE ARTICLE SCHEMA" включена,
	 * НО только если наш плагин действительно выводит свою schema для текущей страницы.
	 *
	 * @since 0.4.0
	 *
	 * @param mixed  $value     Текущее значение опции.
	 * @param string $option_id Идентификатор опции.
	 * @return mixed
	 */
	public function force_disable_td_schema( $value, $option_id ) {
		if ( 'tds_disable_article_schema' !== $option_id ) {
			return $value;
		}

		// Не отключаем schema темы, если наша разметка не будет выведена.
		if ( ! $this->should_output() ) {
			return $value;
		}

		return '1';
	}

	// =========================================================================
	// ПРОВЕРКА УСЛОВИЙ ВЫВОДА
	// =========================================================================

	/**
	 * Возвращает ID тестовой записи.
	 *
	 * Вынесено в отдельный метод, чтобы PHPStan не «сворачивал» значение
	 * константы SITENAME_SEO_TEST_POST_ID в 0 и не считал проверку > 0
	 * мёртвым кодом. constant() читает значение во время выполнения,
	 * поэтому анализатор не может предсказать результат.
	 *
	 * @since 0.4.0
	 *
	 * @return int
	 */
	private function get_test_post_id() {
		return (int) constant( 'SITENAME_SEO_TEST_POST_ID' );
	}

	/**
	 * Определяет, должна ли разметка выводиться для текущей записи.
	 *
	 * Учитывает:
	 *   - тип записи (только 'post');
	 *   - константу SITENAME_SEO_TEST_POST_ID (0 = все записи, >0 = одна).
	 *
	 * @since 0.4.0
	 *
	 * @return bool
	 */
	private function should_output() {
		if ( ! is_singular( 'post' ) ) {
			return false;
		}

		$test_id = $this->get_test_post_id();

		if ( $test_id > 0 && get_the_ID() !== $test_id ) {
			return false;
		}

		return true;
	}

	// =========================================================================
	// OPEN GRAPH — критично для Яндекса (сниппет), VK, Telegram
	// =========================================================================

	/**
	 * Выводит метатеги Open Graph в <head>.
	 *
	 * @since 0.4.0
	 */
	public function add_open_graph() {
		if ( ! $this->should_output() ) {
			return;
		}

		global $post;

		$title       = get_the_title();
		$description = $this->get_seo_description();
		$image       = $this->get_og_image();
		$url         = get_permalink( $post );
		$site_name   = get_bloginfo( 'name' );
		$locale      = str_replace( '_', '-', get_locale() );

		$tags = array(
			'og:type'        => 'article',
			'og:title'       => $title,
			'og:description' => $description,
			'og:url'         => $url,
			'og:site_name'   => $site_name,
			'og:locale'      => $locale,
		);

		if ( $image ) {
			$tags['og:image'] = $image['url'];
			// Указываем размеры только если они реально известны.
			if ( ! empty( $image['width'] ) && ! empty( $image['height'] ) ) {
				$tags['og:image:width']  = (string) $image['width'];
				$tags['og:image:height'] = (string) $image['height'];
			}
			$tags['og:image:alt'] = $title;
		}

		$tags['article:published_time'] = get_the_date( 'c', $post );
		$tags['article:modified_time']  = get_the_modified_date( 'c', $post );

		$categories = get_the_category( $post->ID );
		if ( ! empty( $categories ) ) {
			$tags['article:section'] = $categories[0]->name;
		}

		// Требование Яндекс.Метрики: article:author передаётся строкой.
		$author_name = get_the_author_meta( 'display_name', $post->post_author );
		if ( $author_name ) {
			$tags['article:author'] = $author_name;
		}

		$this->render_meta_tags( $tags, 'property' );
	}

	// =========================================================================
	// TWITTER CARDS
	// =========================================================================

	/**
	 * Выводит метатеги Twitter Cards в <head>.
	 *
	 * @since 0.4.0
	 */
	public function add_twitter_cards() {
		if ( ! $this->should_output() ) {
			return;
		}

		$title       = get_the_title();
		$description = $this->get_seo_description();
		$image       = $this->get_og_image();

		$tags = array(
			'twitter:card'        => 'summary_large_image',
			'twitter:title'       => $title,
			'twitter:description' => $description,
		);

		if ( $image ) {
			$tags['twitter:image'] = $image['url'];
		}

		$this->render_meta_tags( $tags, 'name' );
	}

	// =========================================================================
	// JSON-LD: НОВОСТЬ (NewsArticle)
	// =========================================================================

	/**
	 * Выводит JSON-LD разметку NewsArticle в <head>.
	 *
	 * @since 0.4.0
	 */
	public function add_news_article_jsonld() {
		if ( ! $this->should_output() ) {
			return;
		}

		global $post;

		$title       = get_the_title();
		$description = $this->get_seo_description();
		$image       = $this->get_og_image();
		$url         = get_permalink( $post );

		$schema = array(
			'@context'         => 'https://schema.org',
			'@type'            => 'NewsArticle',
			'mainEntityOfPage' => array(
				'@type' => 'WebPage',
				'@id'   => $url,
			),
			'headline'         => $title,
			'description'      => $description,
			'datePublished'    => get_the_date( 'c', $post ),
			'dateModified'     => get_the_modified_date( 'c', $post ),
			'author'           => array(
				'@type' => 'Person',
				'name'  => get_the_author_meta( 'display_name', $post->post_author ),
				'url'   => get_author_posts_url( $post->post_author ),
			),
			'publisher'        => array(
				'@type' => 'Organization',
				'name'  => get_bloginfo( 'name' ),
				'url'   => home_url( '/' ),
				'logo'  => array(
					'@type'      => 'ImageObject',
					'contentUrl' => $this->publisher_logo_url,
					'width'      => $this->publisher_logo_width,
					'height'     => $this->publisher_logo_height,
				),
			),
		);

		if ( $image ) {
			$schema['image'] = array(
				'@type'      => 'ImageObject',
				'contentUrl' => $image['url'],
			);
			if ( ! empty( $image['width'] ) && ! empty( $image['height'] ) ) {
				$schema['image']['width']  = $image['width'];
				$schema['image']['height'] = $image['height'];
			}
		}

		$this->render_jsonld( $schema );
	}

	// =========================================================================
	// ВСПОМОГАТЕЛЬНЫЕ МЕТОДЫ
	// =========================================================================

	/**
	 * Описание: excerpt или первый абзац контента (макс. 160 символов).
	 * Гарантированно удаляет шорткоды.
	 *
	 * @since 0.4.0
	 *
	 * @return string
	 */
	private function get_seo_description() {
		global $post;

		// 1. Приоритет: краткое описание (если заполнено вручную в админке).
		if ( ! empty( $post->post_excerpt ) ) {
			$desc = strip_shortcodes( $post->post_excerpt );
			$desc = wp_strip_all_tags( $desc );
			return trim( preg_replace( '/\s+/', ' ', $desc ) );
		}

		// 2. Берем основной контент и сразу удаляем все шорткоды.
		$content = strip_shortcodes( $post->post_content );

		// 3. Пытаемся выделить именно первый абзац.
		if ( preg_match( '/<p[^>]*>(.*?)<\/p>/is', $content, $matches ) ) {
			$content = $matches[1];
		} else {
			// Если тегов <p> нет, берём всё до первого двойного переноса строки.
			$parts   = preg_split( '/\n\s*\n/', $content, 2 );
			$content = $parts[0];
		}

		// 4. Очищаем от оставшихся HTML-тегов и нормализуем пробелы.
		$content = wp_strip_all_tags( $content );
		$content = preg_replace( '/\s+/', ' ', $content );
		$content = trim( $content );

		// 5. Обрезаем до 160 символов для сниппета Яндекса.
		return mb_substr( $content, 0, 160, 'UTF-8' );
	}

	/**
	 * Изображение: миниатюра поста -> первое изображение из контента -> логотип издателя.
	 *
	 * @since 0.4.0
	 *
	 * @return array{url:string,width?:int,height?:int}
	 */
	private function get_og_image() {
		global $post;

		// 1. Миниатюра поста (возвращает реальные размеры).
		$thumb_id = get_post_thumbnail_id( $post->ID );
		if ( $thumb_id ) {
			$image = wp_get_attachment_image_src( $thumb_id, 'large' );
			if ( $image && ! empty( $image[0] ) ) {
				return array(
					'url'    => $this->make_absolute_url( $image[0] ),
					'width'  => (int) $image[1],
					'height' => (int) $image[2],
				);
			}
		}

		// 2. Первое изображение из контента (без указания размеров, чтобы не врать).
		if ( preg_match( '/<img[^>]+src=["\']([^"\']+)["\']/i', $post->post_content, $matches ) ) {
			return array(
				'url' => $this->make_absolute_url( $matches[1] ),
			);
		}

		// 3. Fallback — логотип издателя.
		return array(
			'url'    => $this->publisher_logo_url,
			'width'  => $this->publisher_logo_width,
			'height' => $this->publisher_logo_height,
		);
	}

	/**
	 * Делает URL абсолютным (добавляет схему и домен, если нужно).
	 *
	 * @since 0.4.0
	 *
	 * @param string $url Относительный или абсолютный URL.
	 * @return string Абсолютный URL.
	 */
	private function make_absolute_url( $url ) {
		$url = trim( $url );

		if ( strpos( $url, 'http://' ) === 0 || strpos( $url, 'https://' ) === 0 ) {
			return $url;
		}
		if ( strpos( $url, '//' ) === 0 ) {
			return ( is_ssl() ? 'https:' : 'http:' ) . $url;
		}
		if ( strpos( $url, '/' ) === 0 ) {
			return home_url( $url );
		}

		return $url;
	}

	/**
	 * Рендер <meta> тегов.
	 *
	 * @since 0.4.0
	 *
	 * @param array  $tags Ассоциативный массив тегов (имя => значение).
	 * @param string $attr Атрибут: 'property' для OG, 'name' для Twitter.
	 * @return void
	 */
	private function render_meta_tags( array $tags, $attr = 'property' ) {
		foreach ( $tags as $key => $value ) {
			if ( is_array( $value ) ) {
				foreach ( $value as $v ) {
					printf(
						'<meta %s="%s" content="%s">' . "\n",
						esc_attr( $attr ),
						esc_attr( $key ),
						esc_attr( $v ),
					);
				}
			} else {
				printf(
					'<meta %s="%s" content="%s">' . "\n",
					esc_attr( $attr ),
					esc_attr( $key ),
					esc_attr( $value ),
				);
			}
		}
	}

	/**
	 * Рендер JSON-LD.
	 *
	 * @since 0.4.0
	 *
	 * @param array $schema Массив схемы для сериализации в JSON.
	 * @return void
	 */
	private function render_jsonld( array $schema ) {
		$json = wp_json_encode(
			$schema,
			JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT,
		);

		if ( false !== $json ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON-LD уже экранирован через wp_json_encode.
			printf( '<script type="application/ld+json">%s</script>' . "\n", $json );
		}
	}
}

new Sitename_SEO();
