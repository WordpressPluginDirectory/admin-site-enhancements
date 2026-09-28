<?php

namespace ASENHA\Classes;

use WP_Error;

/**
 * Class for Limit Login Attempts module
 *
 * @since 6.9.5
 */
class Limit_Login_Attempts {

    /**
     * Maximum length for varchar columns written to the failed logins table.
     *
     * @since 9.0.2
     */
    const FAILED_LOGINS_VARCHAR_MAX = 255;

    /**
     * Ensure failed logins log table schema is up to date.
     *
     * @since 9.0.2
     */
    public function maybe_upgrade_failed_logins_log_table() {
        $activation = new Activation();
        $activation->maybe_upgrade_failed_logins_log_table();
    }

    /**
     * Truncate a string to fit failed logins table varchar columns.
     *
     * @since 9.0.2
     * @param string $value Value to truncate.
     * @return string
     */
    private function truncate_failed_login_db_string( $value ) {
        $value = (string) $value;

        if ( function_exists( 'mb_substr' ) ) {
            return mb_substr( $value, 0, self::FAILED_LOGINS_VARCHAR_MAX );
        }

        return substr( $value, 0, self::FAILED_LOGINS_VARCHAR_MAX );
    }

    /**
     * Log failed logins table write errors when debugging is enabled.
     *
     * @since 9.0.2
     * @param string $operation Database operation that failed.
     */
    private function maybe_log_failed_login_db_error( $operation ) {
        global $wpdb;

        if ( defined( 'WP_DEBUG' ) && WP_DEBUG && ! empty( $wpdb->last_error ) ) {
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
            error_log( 'ASE Limit Login Attempts: ' . $operation . ' failed. ' . $wpdb->last_error );
        }
    }

    /**
     * Mark the current request as inside a lockout window.
     *
     * @since 9.1.2
     * @param int $lockout_count Number of lockouts recorded for this IP.
     * @param int $last_fail_on  Unix timestamp of the last failed attempt.
     */
    private function mark_within_lockout_period( $lockout_count, $last_fail_on ) {
        global $asenha_limit_login;

        if ( ! is_array( $asenha_limit_login ) ) {
            $asenha_limit_login = array();
        }

        $login_lockout_maxcount  = isset( $asenha_limit_login['login_lockout_maxcount'] ) ? (int) $asenha_limit_login['login_lockout_maxcount'] : 3;
        $default_lockout_period  = isset( $asenha_limit_login['default_lockout_period'] ) ? (int) $asenha_limit_login['default_lockout_period'] : 60 * 15;
        $extended_lockout_period = isset( $asenha_limit_login['extended_lockout_period'] ) ? (int) $asenha_limit_login['extended_lockout_period'] : 24 * 60 * 60;

        $asenha_limit_login['maybe_lockout'] = true;

        if ( (int) $lockout_count >= $login_lockout_maxcount ) {
            $asenha_limit_login['extended_lockout'] = true;
            $lockout_period = $extended_lockout_period;
        } else {
            $asenha_limit_login['extended_lockout'] = false;
            $lockout_period = $default_lockout_period;
        }

        $remaining = $lockout_period - ( time() - (int) $last_fail_on );
        if ( $remaining < 0 ) {
            $remaining = 0;
        }

        $asenha_limit_login['lockout_period']           = $lockout_period;
        $asenha_limit_login['within_lockout_period']    = true;
        $asenha_limit_login['lockout_period_remaining'] = $remaining;
    }

    /**
     * Human-readable remaining lockout duration.
     *
     * @since 9.1.2
     * @param int $remaining_seconds Seconds remaining in the lockout window.
     * @return string
     */
    private function get_lockout_period_remaining_label( $remaining_seconds ) {
        $remaining_seconds = (int) $remaining_seconds;
        $common_methods    = new Common_Methods();

        if ( $remaining_seconds <= 60 ) {
            return $remaining_seconds . ' seconds';
        } elseif ( $remaining_seconds <= 60 * 60 ) {
            return $common_methods->seconds_to_period( $remaining_seconds, 'to-minutes-seconds' );
        } elseif ( $remaining_seconds > 60 * 60 && $remaining_seconds <= 24 * 60 * 60 ) {
            return $common_methods->seconds_to_period( $remaining_seconds, 'to-hours-minutes-seconds' );
        }

        return $common_methods->seconds_to_period( $remaining_seconds, 'to-days-hours-minutes-seconds' );
    }

    /**
     * Lockout warning shown above the login form.
     *
     * @since 9.1.2
     * @return string
     */
    private function get_lockout_error_message() {
        global $asenha_limit_login;

        $remaining_seconds = isset( $asenha_limit_login['lockout_period_remaining'] ) ? (int) $asenha_limit_login['lockout_period_remaining'] : 0;
        $remaining_label   = $this->get_lockout_period_remaining_label( $remaining_seconds );

        return sprintf(
            /* translators: %s: remaining lockout duration, e.g. "14 minutes and 59 seconds". */
            __( '<b>WARNING:</b> You\'ve been locked out. You can login again in %s.', 'admin-site-enhancements' ),
            $remaining_label
        );
    }

    /**
     * Maybe allow login if not locked out. Should return WP_Error object if not allowed to login.
     *
     * @since 2.5.0
     */
    public function maybe_allow_login( $user_or_error, $username, $password ) {
        global $wpdb, $asenha_limit_login;

        $table_name = $wpdb->prefix . 'asenha_failed_logins';

        $this->maybe_upgrade_failed_logins_log_table();

        // Maybe create table if it does not exist yet, e.g. upgraded from previous version of plugin, so, no activation methods are fired
        $query = $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table_name ) );

        if ( $wpdb->get_var( $query ) === $table_name ) {
            // Table already exists, do nothing.
        } else {
            $activation = new Activation();
            $activation->create_failed_logins_log_table( false );
        }

        // Get values from options needed to do various checks
        $options = get_option( ASENHA_SLUG_U, array() );
        $login_fails_allowed = $options['login_fails_allowed'];
        $login_lockout_maxcount = $options['login_lockout_maxcount'];

        $ip_address_whitelist_raw = ( isset( $options['limit_login_attempts_ip_whitelist'] ) ) ? explode( PHP_EOL, $options['limit_login_attempts_ip_whitelist'] ) : array();
        $ip_address_whitelist = array();
        if ( ! empty( $ip_address_whitelist_raw ) ) {
            foreach( $ip_address_whitelist_raw as $ip_address ) {
                $ip_address_whitelist[] = trim( $ip_address );
            }           
        }

        $change_login_url = $options['change_login_url'];
        $custom_login_slug = $options['custom_login_slug'];

        // Instantiate object to access common methods
        $common_methods = new Common_Methods;

        // Get user/visitor IP address
        $ip_address = $common_methods->get_user_ip_address( 'ip', 'limit-login-attempts' );
        
        if ( ! in_array( $ip_address, $ip_address_whitelist ) ) { // IP is not whitelisted
            // Check if IP address has failed login attempts recorded in the DB log
            $sql = $wpdb->prepare("SELECT * FROM `" . $table_name . "` Where `ip_address` = %s", $ip_address);
            $result = $wpdb->get_results( $sql, ARRAY_A );

            $result_count = count( $result );

            if ( $result_count > 0 ) { // IP address has been recorded in the database.
                $fail_count = $result[0]['fail_count'];
                $lockout_count = $result[0]['lockout_count'];
                $last_fail_on = $result[0]['unixtime'];
            } else {
                $fail_count = 0;
                $lockout_count = 0;
                $last_fail_on = '';
            }
        } else { // IP is whitelisted
            $result = array();
            $result_count = 0;
            $fail_count = 0;
            $lockout_count = 0;
            $last_fail_on = '';
        }
        
        // Initialize the global variable
        $asenha_limit_login = array (
            'ip_address'                => $ip_address,
            'request_uri'               => sanitize_text_field( $_SERVER['REQUEST_URI'] ),
            'ip_address_log'            => $result,
            'fail_count'                => $fail_count,
            'lockout_count'             => $lockout_count,
            'maybe_lockout'             => false,
            'extended_lockout'          => false,
            'within_lockout_period'     => false,
            'lockout_period'            => 0,
            'lockout_period_remaining'  => 0,
            'login_fails_allowed'       => $login_fails_allowed,
            'login_lockout_maxcount'    => $login_lockout_maxcount,
            // 'default_lockout_period'     => 15, // 15 seconds. FOR TESTING.
            // 'default_lockout_period'     => 60, // 1 minutes in seconds
            'default_lockout_period'    => 60*15, // 15 minutes in seconds
            // 'extended_lockout_period'    => 3*60, // 3 minutes in seconds
            'extended_lockout_period'   => 24*60*60, // 24 hours in seconds
            'change_login_url'          => $change_login_url, // is custom login URL enabled?
            'custom_login_slug'         => $custom_login_slug,
        );

        if ( ! in_array( $ip_address, $ip_address_whitelist ) ) { // IP is not whitelisted

            if ( $result_count > 0 ) { // IP address has been recorded in the database.

                // Failed attempts have been recorded and fulfills lockout condition
                $login_fails_allowed = max( 1, (int) $login_fails_allowed ); // Guard against division by zero.
                if ( ! empty( $fail_count ) && ( ( $fail_count ) % $login_fails_allowed == 0 ) ) {

                    $asenha_limit_login['maybe_lockout'] = true;

                    // Has reached max / gone beyond number of lockouts allowed?
                    if ( $lockout_count >= $login_lockout_maxcount ) {
                        $asenha_limit_login['extended_lockout'] = true;
                        $lockout_period = $asenha_limit_login['extended_lockout_period'];
                    } else {
                        $asenha_limit_login['extended_lockout'] = false;
                        $lockout_period = $asenha_limit_login['default_lockout_period'];
                    }

                    $asenha_limit_login['lockout_period'] = $lockout_period;

                    // User/visitor is still within the lockout period
                    if ( ( time() - $last_fail_on ) <= $asenha_limit_login['lockout_period'] ) {

                        $asenha_limit_login['within_lockout_period'] = true;
                        $asenha_limit_login['lockout_period_remaining'] = $asenha_limit_login['lockout_period'] - ( time() - $last_fail_on );

                        return new WP_Error( 'ip_address_blocked', $this->get_lockout_error_message() );

                    } else { // User/visitor is no longer within the lockout period

                        $asenha_limit_login['within_lockout_period'] = false;

                        if ( $lockout_count >= $login_lockout_maxcount ) {

                            // Remove the DB log entry for the current IP address. i.e. release from extended lockout

                            $where = array( 'ip_address' => $ip_address );
                            $where_format = array( '%s' );

                            // Delete existing data in the database
                            $wpdb->delete(
                                $table_name,
                                $where,
                                $where_format
                            );

                        }

                        return $user_or_error;

                    }

                } else {

                    $asenha_limit_login['maybe_lockout'] = false;

                    return $user_or_error;

                }

            } else { // IP address has not been recorded in the database.

                return $user_or_error;

            }
            
        } else {  // IP is whitelisted
            return $user_or_error;          
        }
    }

    /**
     * Handle login errors
     *
     * @link https://developer.wordpress.org/reference/classes/wp_error/#methods
     * @since 2.5.0
     */
    public function login_error_handler( $errors, $redirect_to ) {
        global $asenha_limit_login;

        if ( ! is_wp_error( $errors ) ) {
            return $errors;
        }

        // Same-request lockout (the 3rd failure just incremented the counter) and later
        // blocked retries both land here. Show only the lockout warning.
        if ( ! empty( $asenha_limit_login['within_lockout_period'] ) ) {
            return new WP_Error( 'ip_address_blocked', $this->get_lockout_error_message() );
        }

        $error_codes = $errors->get_error_codes();

        foreach ( $error_codes as $error_code ) {

            if ( $error_code == 'invalid_username' || $error_code == 'incorrect_password' ) {

                // Remove default error messages that may give out valueable info to hackers

                $errors->remove( 'invalid_username' ); // Outputs info that says username does not exist. May encourage login attempt with a different username instead.

                $errors->remove( 'incorrect_password' ); // Outputs info that implies username exist. May encourage login attempt with a different password.

                // Add a new error message that does not provide useful clues to hackers
                $errors->add( 'invalid_username_or_incorrect_password', '<b>' . __( 'Error:', 'admin-site-enhancements' ) . '</b> ' . __( 'Invalid username/email or incorrect password.', 'admin-site-enhancements' ) );

            }

        }

        return $errors;
    }

    /**
     * Disable login form inputs via CSS
     * 
     * @since 2.5.0
     */
    public function maybe_hide_login_form() {
        global $asenha_limit_login;

        if ( isset( $asenha_limit_login['within_lockout_period'] ) && $asenha_limit_login['within_lockout_period'] ) {

            // Hide logo, login form and the links below it
            ?>
            <script>
                document.addEventListener("DOMContentLoaded", function(event) {
                    var loginForm = document.getElementById("loginform");
                    loginForm.remove();
                });
            </script>
            <style type="text/css">

                body.login {
                    background:#f6d6d7;
                }

                #login h1,
                #loginform,
                #login #nav,
                #backtoblog,
                .language-switcher { 
                    display: none; 
                }

                @media screen and (max-height: 550px) {

                    #login {
                        padding: 80px 0 20px !important;
                    }

                }

            </style>
            <?php
        }
    }

    /**
     * Add login error message on top of the login form
     *
     * @since 2.5.0
     */
    public function add_failed_login_message( $message ) {
        global $asenha_limit_login;

        if ( isset( $_REQUEST['failed_login'] ) && $_REQUEST['failed_login'] == 'true' ) {

            if ( ! is_null( $asenha_limit_login ) && isset( $asenha_limit_login['within_lockout_period'] ) && ! $asenha_limit_login['within_lockout_period'] ) {

                $message = '<div id="login_error" class="notice notice-error"><b>' . __( 'Error:', 'admin-site-enhancements' ) . '</b> ' . __( 'Invalid username/email or incorrect password.', 'admin-site-enhancements' ) . '</div>';

            }

        }

        return $message;
    }
    
    /**
     * Log failed login attempts
     *
     * @since 2.5.0
     */
    public function log_failed_login( $username ) {
        global $wpdb, $asenha_limit_login;

        // wp_signon() fires wp_login_failed for every authenticate WP_Error,
        // including our own lockout. Do not count blocked retries: the gate
        // uses fail_count % login_fails_allowed == 0, so an extra increment
        // would both over-count and disable the lockout window.
        if ( ! empty( $asenha_limit_login['within_lockout_period'] ) ) {
            return;
        }

        $table_name = $wpdb->prefix . 'asenha_failed_logins';

        $this->maybe_upgrade_failed_logins_log_table();

        $ip_address = isset( $asenha_limit_login['ip_address'] ) ? $asenha_limit_login['ip_address'] : '';
        $request_uri = isset( $asenha_limit_login['request_uri'] ) ? $asenha_limit_login['request_uri'] : '';
        $login_fails_allowed = isset( $asenha_limit_login['login_fails_allowed'] ) ? $asenha_limit_login['login_fails_allowed'] : 3;
        $login_lockout_maxcount = isset( $asenha_limit_login['login_lockout_maxcount'] ) ? $asenha_limit_login['login_lockout_maxcount'] : 3;

        $login_fails_allowed = max( 1, (int) $login_fails_allowed ); // Defensive: never divide by zero.

        $username = $this->truncate_failed_login_db_string( $username );
        $request_uri = $this->truncate_failed_login_db_string( $request_uri );

        // Time stamps
        $unixtime = time();
        if ( function_exists( 'wp_date' ) ) {
            $datetime_wp = wp_date( 'Y-m-d H:i:s', $unixtime );
        } else {
            $datetime_wp = date_i18n( 'Y-m-d H:i:s', $unixtime );
        }

        // Atomically insert a fresh row for this IP, or increment the existing one, in a
        // single statement. The UNIQUE (ip_address) index serializes concurrent requests
        // on a row lock, so each failed attempt increments the counter exactly once.
        // This closes the read-modify-write race that let parallel requests bypass lockout.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $db_result = $wpdb->query(
            $wpdb->prepare(
                "INSERT INTO `{$table_name}`
                    ( ip_address, username, fail_count, lockout_count, request_uri, unixtime, datetime_wp, info )
                VALUES ( %s, %s, 1, 0, %s, %d, %s, '' )
                ON DUPLICATE KEY UPDATE
                    username    = VALUES( username ),
                    fail_count  = fail_count + 1,
                    request_uri = VALUES( request_uri ),
                    unixtime    = VALUES( unixtime ),
                    datetime_wp = VALUES( datetime_wp )",
                $ip_address,
                $username,
                $request_uri,
                $unixtime,
                $datetime_wp
            )
        );

        if ( false === $db_result ) {
            $this->maybe_log_failed_login_db_error( 'upsert' );
        }

        // Read back the authoritative, post-increment count. The increment above already
        // committed under the row lock, so this value is race-free for the lockout decision.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $new_fail_count = (int) $wpdb->get_var(
            $wpdb->prepare( "SELECT fail_count FROM `{$table_name}` WHERE `ip_address` = %s", $ip_address )
        );

        // Derive lockout state from the atomic count.
        $new_lockout_count = (int) floor( $new_fail_count / $login_fails_allowed );

        // Persist lockout_count when it advances. This is a small derived write; the
        // authoritative gate in maybe_allow_login() reads fail_count, not lockout_count.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->update(
            $table_name,
            array( 'lockout_count' => $new_lockout_count ),
            array( 'ip_address'    => $ip_address ),
            array( '%d' ),
            array( '%s' )
        );

        // Update the global so the login-page UI reflects the fresh count.
        $asenha_limit_login['fail_count']    = $new_fail_count;
        $asenha_limit_login['lockout_count'] = $new_lockout_count;

        // The authenticate gate ran before this increment, so within_lockout_period is
        // still false on the request that hits the threshold. Mark it now so the lockout
        // screen renders on this same POST instead of waiting for a later GET.
        if ( $new_fail_count > 0 && ( 0 === $new_fail_count % $login_fails_allowed ) ) {
            $this->mark_within_lockout_period( $new_lockout_count, $unixtime );
        }
    }

    /** 
     * Clear failed login attempts log after successful login
     *
     * @since 2.5.0
     */
    public function clear_failed_login_log() {
        global $wpdb, $asenha_limit_login;

        $table_name = $wpdb->prefix . 'asenha_failed_logins';
        $ip_address = isset( $asenha_limit_login['ip_address'] ) ? $asenha_limit_login['ip_address'] : '';

        // Remove the DB log entry for the current IP address.

        $where = array( 'ip_address' => $ip_address );
        $where_format = array( '%s' );

        $wpdb->delete(
            $table_name,
            $where,
            $where_format
        );
    }

    /**
     * Trigger scheduling of failed login attempts log clean up event.
     *
     * @since 7.1.1
     */
    public function trigger_clear_or_schedule_log_clean_up_by_amount( $option_name ) {
        if ( ASENHA_SLUG_U === $option_name ) {
            $this->clear_or_schedule_log_clean_up_by_amount();
        }
    }

    /**
     * Schedule failed login attempts log clean up event
     * 
     * @link https://plugins.trac.wordpress.org/browser/lana-email-logger/tags/1.1.0/lana-email-logger.php#L750
     * @since 7.8.3
     */
    public function clear_or_schedule_log_clean_up_by_amount() {
        $options = get_option( ASENHA_SLUG_U, array() );
        $limit_login_attempts = isset( $options['limit_login_attempts'] ) ? $options['limit_login_attempts'] : false;
        $failed_login_attempts_log_schedule_cleanup_by_amount = isset( $options['failed_login_attempts_log_schedule_cleanup_by_amount'] ) ? $options['failed_login_attempts_log_schedule_cleanup_by_amount'] : false;
        
        // If module or scheduled clean up is not enabled, clear the schedule.
        if ( ! $limit_login_attempts || ! $failed_login_attempts_log_schedule_cleanup_by_amount ) {
            wp_clear_scheduled_hook( 'asenha_failed_login_attempts_log_cleanup_by_amount' );
            return;            
        }
        
        // If there's no next scheduled clean up event, let's schedule one
        if ( ! wp_next_scheduled( 'asenha_failed_login_attempts_log_cleanup_by_amount' ) ) {
            wp_schedule_event( time(), 'hourly', 'asenha_failed_login_attempts_log_cleanup_by_amount' );
        }
    }

    /**
     * Perform clean up of failed login attempts log by the amount of entries to keep
     * 
     * @link https://plugins.trac.wordpress.org/browser/lana-email-logger/tags/1.1.0/lana-email-logger.php#L768
     * @since 7.8.3
     */
    public function perform_failed_login_attempts_log_clean_up_by_amount() {
        global $wpdb;
        
        $options = get_option( ASENHA_SLUG_U, array() );
        $limit_login_attempts = isset( $options['limit_login_attempts'] ) ? $options['limit_login_attempts'] : false;
        $failed_login_attempts_log_schedule_cleanup_by_amount = isset( $options['failed_login_attempts_log_schedule_cleanup_by_amount'] ) ? $options['failed_login_attempts_log_schedule_cleanup_by_amount'] : false;
        $failed_login_attempts_log_entries_amount_to_keep = 1000;
        
        // Bail and clear any orphan schedule if clean up should not run.
        if ( ! $limit_login_attempts || ! $failed_login_attempts_log_schedule_cleanup_by_amount ) {
            wp_clear_scheduled_hook( 'asenha_failed_login_attempts_log_cleanup_by_amount' );
            return;
        }

        $table_name = $wpdb->prefix . 'asenha_failed_logins';

        // Maybe create table if it does not exist yet, e.g. module enabled but no login attempt yet.
        $query = $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table_name ) );

        if ( $wpdb->get_var( $query ) === $table_name ) {
            // Table already exists, do nothing.
        } else {
            $activation = new Activation();
            $activation->create_failed_logins_log_table( false );
        }
        
        $wpdb->query( "DELETE failed_login_entries FROM " . $table_name . " 
                        AS failed_login_entries JOIN ( SELECT id FROM " . $table_name . " ORDER BY id DESC LIMIT 1 OFFSET " . $failed_login_attempts_log_entries_amount_to_keep . " ) 
                        AS failed_login_entries_limit ON failed_login_entries.id <= failed_login_entries_limit.id;" );
    }

}