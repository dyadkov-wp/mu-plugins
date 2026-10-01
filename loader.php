<?php
/**
 * Plugin Name: MU Loader
 * Description: Загружает mu-plugins из подпапок.
 * Version:     1.0.0
 * Author:      Команда разработки сайта
 * License:     MIT
 * License URI: https://opensource.org/licenses/MIT
 *
 * @package Sitename\MuPlugins
 * @since   0.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$sitename_mu_plugins = array(
	'tagdiv-seo-fix/tagdiv-seo-fix.php',
	// 'another-plugin/another-plugin.php',
);

foreach ( $sitename_mu_plugins as $sitename_mu_plugin ) {
	$sitename_mu_plugin_path = __DIR__ . '/' . $sitename_mu_plugin;

	if ( file_exists( $sitename_mu_plugin_path ) ) {
		require_once $sitename_mu_plugin_path;
	}
}

unset( $sitename_mu_plugins, $sitename_mu_plugin, $sitename_mu_plugin_path );
