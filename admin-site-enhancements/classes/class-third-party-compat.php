<?php

namespace ASENHA\Classes;

/**
 * Compatibility shims for third-party plugins that conflict with ASE.
 *
 * @since 9.0.2
 */
class Third_Party_Compat {

	/**
	 * Register third-party compatibility fixes.
	 *
	 * @since 9.0.2
	 */
	public static function init() {
		self::maybe_fix_dreamhost_panel_login_update_filter();

		// DreamHost may load after ASE (alphabetical plugin order), so run again once all plugins are loaded.
		add_action( 'plugins_loaded', array( __CLASS__, 'maybe_fix_dreamhost_panel_login_update_filter' ), 1 );
	}

	/**
	 * Replace DreamHost Panel Login's broken update-check filter.
	 *
	 * DreamHost's dh_sso_disable_my_plugin_update() assumes every request to
	 * api.wordpress.org/plugins/update-check/ includes an "active" array. Freemius
	 * (and other callers) send a minimal payload without that key, which fatals
	 * on PHP 8+ when array_search() receives null.
	 *
	 * @since 9.0.2
	 */
	public static function maybe_fix_dreamhost_panel_login_update_filter() {
		if ( ! function_exists( 'dh_sso_disable_my_plugin_update' ) ) {
			return;
		}

		remove_filter( 'http_request_args', 'dh_sso_disable_my_plugin_update', 10 );
		add_filter( 'http_request_args', array( __CLASS__, 'dreamhost_panel_login_disable_plugin_update' ), 10, 2 );
	}

	/**
	 * Hide DreamHost Panel Login from WordPress.org plugin update checks (safe version).
	 *
	 * @since 9.0.2
	 *
	 * @param array  $args Request arguments.
	 * @param string $url  Request URL.
	 * @return array
	 */
	public static function dreamhost_panel_login_disable_plugin_update( $args, $url ) {
		if ( 0 !== strpos( $url, 'https://api.wordpress.org/plugins/update-check/' ) ) {
			return $args;
		}

		if ( empty( $args['body']['plugins'] ) ) {
			return $args;
		}

		$plugins = json_decode( $args['body']['plugins'], true );
		if ( ! is_array( $plugins ) ) {
			return $args;
		}

		$dh_plugin = self::get_dreamhost_panel_login_basename();
		if ( '' === $dh_plugin ) {
			return $args;
		}

		if ( isset( $plugins['plugins'][ $dh_plugin ] ) ) {
			unset( $plugins['plugins'][ $dh_plugin ] );
		}

		if ( ! empty( $plugins['active'] ) && is_array( $plugins['active'] ) ) {
			$key = array_search( $dh_plugin, $plugins['active'], true );
			if ( false !== $key ) {
				unset( $plugins['active'][ $key ] );
			}
		}

		$args['body']['plugins'] = wp_json_encode( $plugins );

		return $args;
	}

	/**
	 * Resolve the DreamHost Panel Login plugin basename.
	 *
	 * @since 9.0.2
	 *
	 * @return string
	 */
	private static function get_dreamhost_panel_login_basename() {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		foreach ( array_keys( get_plugins() ) as $plugin_basename ) {
			if ( false !== strpos( $plugin_basename, 'dreamhost-panel-login' ) ) {
				return $plugin_basename;
			}
		}

		return 'dreamhost-panel-login.php';
	}
}
