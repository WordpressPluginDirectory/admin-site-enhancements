<?php

namespace ASENHA\Classes;

/**
 * Class for Display System Summary module
 *
 * @since 6.9.5
 */
class Display_System_Summary {
    /**
     * Aggregated sizes transient key.
     *
     * @since 9.0.3
     * @var string
     */
    const SIZES_TRANSIENT = 'asenha_dss_sizes';

    /**
     * Calculation lock transient key.
     *
     * @since 9.0.3
     * @var string
     */
    const LOCK_TRANSIENT = 'asenha_dss_calc_lock';

    /**
     * WP-Cron hook for background size calculation.
     *
     * @since 9.0.3
     * @var string
     */
    const CRON_HOOK = 'asenha_dss_calculate_sizes';

    /**
     * Cache TTL for calculated sizes (24 hours).
     *
     * @since 9.0.3
     * @var int
     */
    const CACHE_TTL = 86400;

    // 24 * HOUR_IN_SECONDS when HOUR_IN_SECONDS is available.
    /**
     * Lock TTL while a calculation is in progress (long walks on large sites).
     *
     * @since 9.0.3
     * @var int
     */
    const LOCK_TTL = 1800;

    // 30 minutes.
    /**
     * Display system summary in the "At a Glance" dashboard widget
     *
     * @since 5.6.0
     */
    public function display_system_summary() {
        // When user is logged-in as in an administrator
        if ( is_user_logged_in() ) {
            if ( current_user_can( 'manage_options' ) ) {
                if ( isset( $_SERVER['SERVER_SOFTWARE'] ) ) {
                    $server_software_raw = str_replace( '/', ' ', $_SERVER['SERVER_SOFTWARE'] );
                    $server_software_parts = explode( ' (', $server_software_raw );
                    $server_software = ucfirst( $server_software_parts[0] );
                } else {
                    $server_software = 'Unknown';
                }
                $php_version = phpversion();
                // From WP core /wp-admin/includes/class-wp-debug-data.php
                global $wpdb;
                $db_server = $wpdb->get_var( 'SELECT VERSION()' );
                $db_server_parts = explode( ':', $db_server );
                $db_server = $db_server_parts[0];
                $ip = 'localhost';
                if ( isset( $_SERVER['HTTP_X_SERVER_ADDR'] ) ) {
                    $ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_SERVER_ADDR'] ) );
                } elseif ( isset( $_SERVER['SERVER_ADDR'] ) ) {
                    $ip = sanitize_text_field( wp_unslash( $_SERVER['SERVER_ADDR'] ) );
                }
                echo '<div class="system-summary"><a href="' . esc_url( admin_url( 'site-health.php?tab=debug' ) ) . '">System</a>: ' . esc_html( $server_software ) . ' &#9642; PHP ' . esc_html( $php_version ) . ' (' . esc_html( php_sapi_name() ) . ') &#9642;' . esc_html( $db_server ) . ' &#9642; IP: ' . esc_html( $ip ) . '</div>';
            }
        }
    }

}
