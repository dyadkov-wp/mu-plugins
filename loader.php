<?php
/**
 * Plugin Name: MU Loader
 * Description: Загружает mu-plugins из подпапок.
 * Version:     1.0.0
 * Author:      Олег Дядьков
 * License:     MIT
 * License URI: https://opensource.org/licenses/MIT
 *
 * @package Dyseo\MU
 * @since   0.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Список подключаемых плагинов.
 * Пути указываются относительно корня mu-plugins/.
 *
 * @since 0.4.0
 * @var array
 */
$dyseo_mu_plugins = array(
	'tagdiv-seo-fix/tagdiv-seo-fix.php',
	// 'another-plugin/another-plugin.php',
);

foreach ( $dyseo_mu_plugins as $dyseo_mu_plugin ) {
	$dyseo_mu_plugin_path = __DIR__ . '/' . $dyseo_mu_plugin;

	if ( file_exists( $dyseo_mu_plugin_path ) ) {
		require_once $dyseo_mu_plugin_path;
	}
}

unset( $dyseo_mu_plugins, $dyseo_mu_plugin, $dyseo_mu_plugin_path );
