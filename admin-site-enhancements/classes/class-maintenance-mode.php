<?php

namespace ASENHA\Classes;

use WP_Error;
/**
 * Class for Maintenance Mode module
 *
 * @since 6.9.5
 */
class Maintenance_Mode {
    /**
     * Backfill bypass key on load when maintenance mode is enabled.
     *
     * Deferred to plugins_loaded so wp_generate_password() is available.
     *
     * @since 8.5.2
     */
    public static function ensure_bypass_key_on_load() {
        $options = get_option( ASENHA_SLUG_U, array() );
        if ( empty( $options['maintenance_mode'] ) ) {
            return;
        }
        self::ensure_bypass_key( $options );
    }

    /**
     * Ensure a bypass key exists when maintenance mode is enabled.
     *
     * Generates and persists a random key once for upgrade sites that have
     * maintenance mode on but have not re-saved ASE settings after the
     * performance fix. Also rotates legacy keys (phpass/bcrypt hashes of the
     * public site URL), which are forgeable by anyone, to a random secret.
     *
     * @since 8.5.2
     * @param array $options Plugin options.
     * @return array Updated plugin options.
     */
    public static function ensure_bypass_key( $options ) {
        if ( !function_exists( 'wp_generate_password' ) ) {
            return $options;
        }
        $existing_key = ( isset( $options['maintenance_mode_bypass_key'] ) ? $options['maintenance_mode_bypass_key'] : '' );
        // Legacy keys are phpass/bcrypt hashes of the public site URL (e.g. '$P$...', '$2y$...' or '$wp$...').
        // They are forgeable by anyone and must be rotated to a random secret.
        $is_legacy_key = '' !== $existing_key && false !== strpos( $existing_key, '$' );
        if ( empty( $existing_key ) || $is_legacy_key ) {
            $options['maintenance_mode_bypass_key'] = wp_generate_password( 32, false, false );
            update_option( ASENHA_SLUG_U, $options );
        }
        return $options;
    }

    /**
     * Get the stored maintenance mode bypass key.
     *
     * @since 8.5.2
     * @param array $options Plugin options.
     * @return string Stored bypass key.
     */
    private function get_bypass_key( $options ) {
        return ( isset( $options['maintenance_mode_bypass_key'] ) ? $options['maintenance_mode_bypass_key'] : '' );
    }

    /**
     * Validate a bypass URL parameter against the stored random bypass key.
     *
     * @since 8.5.2
     * @param string $bypass_param Value of the bypass query parameter.
     * @param string $bypass_key   Stored bypass key from plugin options.
     * @return bool Whether the bypass parameter is valid.
     */
    private function is_bypass_request_valid( $bypass_param, $bypass_key ) {
        if ( empty( $bypass_param ) || empty( $bypass_key ) ) {
            return false;
        }
        return hash_equals( $bypass_key, $bypass_param );
    }

    /**
     * Determine whether the current request is allowed past the maintenance
     * mode gate. Shared by the send_headers gate, the REST gate, the
     * admin-ajax gate and the XML-RPC gate.
     *
     * @since 9.1.2
     * @return bool True when the request may proceed, false when the gate applies.
     */
    private function is_request_allowed() {
        // wp-admin stays open; admin-ajax is gated separately via ajax_gate().
        if ( is_admin() && !wp_doing_ajax() ) {
            return true;
        }
        if ( is_login() ) {
            return true;
        }
        // Novamira OAuth discovery and token exchange stay reachable while maintenance mode is on.
        // MCP Toolkit tool listing stays reachable on its own HTTP endpoint.
        $common_methods = new Common_Methods();
        if ( $common_methods->is_novamira_auth_request() || $common_methods->is_mcp_toolkit_auth_request() ) {
            return true;
        }
        if ( $this->is_user_allowed_frontend_access() ) {
            return true;
        }
        $options = get_option( ASENHA_SLUG_U, array() );
        if ( isset( $_GET['bypass'] ) && $this->is_bypass_request_valid( sanitize_text_field( wp_unslash( $_GET['bypass'] ) ), $this->get_bypass_key( $options ) ) ) {
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            return true;
        }
        if ( $this->is_authentication_ajax_request() ) {
            return true;
        }
        return false;
    }

    /**
     * Whether this admin-ajax request is a login authentication call that must
     * succeed while maintenance mode is on.
     *
     * Wordfence Login Security posts credentials to admin-ajax.php before it
     * shows the 2FA, CAPTCHA, or passkey step. Those actions only authenticate.
     * A non-AJAX request that carries the same action name stays gated.
     *
     * @since 9.1.4
     * @return bool
     */
    private function is_authentication_ajax_request() {
        if ( !wp_doing_ajax() ) {
            return false;
        }
        // Action name only. Wordfence registers these endpoints with nonce verification disabled.
        $action = ( isset( $_REQUEST['action'] ) ? sanitize_key( wp_unslash( $_REQUEST['action'] ) ) : '' );
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if ( '' === $action ) {
            return false;
        }
        $allowed_actions = array('wordfence_ls_authenticate', 'wordfence_ls_begin_passkey_login', 'wordfence_ls_finish_passkey_login');
        /**
         * Filters admin-ajax actions allowed through Maintenance Mode.
         *
         * Only authentication actions belong here. A return value that is not
         * an array of strings is ignored, and the default list stays in place.
         *
         * @since 9.1.4
         * @param string[] $allowed_actions Action names.
         */
        $filtered_actions = apply_filters( 'asenha_maintenance_mode_allowed_ajax_actions', $allowed_actions );
        if ( is_array( $filtered_actions ) ) {
            $strings_only = true;
            foreach ( $filtered_actions as $filtered_action ) {
                if ( !is_string( $filtered_action ) ) {
                    $strings_only = false;
                    break;
                }
            }
            if ( $strings_only ) {
                $allowed_actions = $filtered_actions;
            }
        }
        return in_array( $action, $allowed_actions, true );
    }

    /**
     * Return 503 for REST API requests when maintenance mode applies, since
     * /wp-json/* requests never reach the send_headers gate.
     *
     * Used on rest_authentication_errors (primary: skips dispatch) and on
     * rest_pre_dispatch at PHP_INT_MAX (defense in depth so a later callback
     * such as ACF_Rest_Api::initialize cannot replace a 503 with null).
     *
     * @since 9.1.2
     * @param mixed $result Prior filter value; passed through when allowed or already a WP_Error.
     * @return mixed Original $result when allowed, WP_Error otherwise.
     */
    public function rest_gate( $result ) {
        if ( is_wp_error( $result ) ) {
            return $result;
        }
        $common_methods = new Common_Methods();
        $common_methods->recover_basic_auth_from_request_headers();
        if ( $this->is_request_allowed() ) {
            return $result;
        }
        $application_password_error = $common_methods->get_application_password_authentication_error();
        if ( is_wp_error( $application_password_error ) ) {
            return $application_password_error;
        }
        return new WP_Error('asenha_maintenance_mode', __( 'This site is currently under maintenance.', 'admin-site-enhancements' ), array(
            'status' => 503,
        ));
    }

    /**
     * Return 503 for admin-ajax.php requests when maintenance mode applies,
     * since AJAX requests never reach the send_headers gate.
     *
     * @since 9.1.2
     */
    public function ajax_gate() {
        if ( !wp_doing_ajax() ) {
            return;
        }
        if ( $this->is_request_allowed() ) {
            return;
        }
        wp_die( '', '', array(
            'response' => 503,
        ) );
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }

    /**
     * Return a 503 XML-RPC fault when maintenance mode applies, since
     * xmlrpc.php requests never reach the send_headers gate.
     *
     * @since 9.1.2
     * @param array $methods Registered XML-RPC methods.
     * @return array Original $methods when allowed; exits with 503 otherwise.
     */
    public function xmlrpc_gate( $methods ) {
        if ( $this->is_request_allowed() ) {
            return $methods;
        }
        status_header( 503 );
        nocache_headers();
        exit( '<?xml version="1.0" encoding="UTF-8"?><methodResponse><fault><value><struct><member><name>faultCode</name><value><int>503</int></value></member><member><name>faultString</name><value><string>Service Unavailable</string></value></member></struct></value></fault></methodResponse>' );
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }

    /**
     * Redirect for when maintenance mode is enabled
     *
     * @since 4.7.0
     */
    public function maintenance_mode_redirect( $wp ) {
        if ( $this->is_request_allowed() ) {
            return;
        }
        $options = get_option( ASENHA_SLUG_U, array() );
        $maintenance_page_type = 'custom';
        // ======== Customizable maintenance page ========
        if ( 'custom' == $maintenance_page_type ) {
            header( 'HTTP/1.1 503 Service Unavailable', true, 503 );
            header( 'Status: 503 Service Unavailable' );
            header( 'Retry-After: 3600' );
            // Tell search engine bots to return after 3600 seconds, i.e. 1 hour
            $heading = $options['maintenance_page_heading'];
            $description = $options['maintenance_page_description'];
            $background = ( isset( $options['maintenance_page_background'] ) ? $options['maintenance_page_background'] : 'stripes' );
            $background_options = array('lines', 'stripes', 'curves');
            // Set default
            if ( !in_array( $background, $background_options ) ) {
                $background = 'stripes';
            }
            $title = '';
            $custom_head_code = '';
            if ( 'lines' === $background ) {
                // https://bgjar.com/curve-line
                $background_image = "url(\"data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' version='1.1' xmlns:xlink='http://www.w3.org/1999/xlink' xmlns:svgjs='http://svgjs.com/svgjs' width='1920' height='1280' preserveAspectRatio='none' viewBox='0 0 1920 1280'%3e%3cg mask='url(%26quot%3b%23SvgjsMask1804%26quot%3b)' fill='none'%3e%3crect width='1920' height='1280' x='0' y='0' fill='url(%23SvgjsLinearGradient1805)'%3e%3c/rect%3e%3cpath d='M2294.46 927.36C2128.65 934.22 2078.52 1270.56 1693.36 1208.96 1308.19 1147.36 1373.24 145.96 1092.25-67.11' stroke='rgba(158%2c 160%2c 161%2c 0.57)' stroke-width='2'%3e%3c/path%3e%3cpath d='M2225.25 303.97C1963.34 332.56 1808.36 909.76 1359.97 905.57 911.59 901.38 820.47-55.06 494.7-167.42' stroke='rgba(158%2c 160%2c 161%2c 0.57)' stroke-width='2'%3e%3c/path%3e%3cpath d='M2247.58 281.19C2070.08 293.95 1967.68 651 1632.53 639.59 1297.39 628.18 1265.17-143.39 1017.49-253.69' stroke='rgba(158%2c 160%2c 161%2c 0.57)' stroke-width='2'%3e%3c/path%3e%3cpath d='M1924.29 917.21C1696.21 904.78 1584.63 530.74 1114.13 494.81 643.63 458.88 546.92-26.2 303.97-50.85' stroke='rgba(158%2c 160%2c 161%2c 0.57)' stroke-width='2'%3e%3c/path%3e%3cpath d='M2009.59 400.31C1847.79 399.06 1696.02 240.31 1382.45 240.31 1068.87 240.31 1083.3 404.62 755.3 400.31 427.31 396 332.72-108.61 128.16-144.89' stroke='rgba(158%2c 160%2c 161%2c 0.57)' stroke-width='2'%3e%3c/path%3e%3c/g%3e%3cdefs%3e%3cmask id='SvgjsMask1804'%3e%3crect width='1920' height='1280' fill='white'%3e%3c/rect%3e%3c/mask%3e%3clinearGradient x1='8.33%25' y1='-12.5%25' x2='91.67%25' y2='112.5%25' gradientUnits='userSpaceOnUse' id='SvgjsLinearGradient1805'%3e%3cstop stop-color='rgba(255%2c 255%2c 255%2c 1)' offset='0'%3e%3c/stop%3e%3cstop stop-color='rgba(193%2c 192%2c 192%2c 1)' offset='1'%3e%3c/stop%3e%3c/linearGradient%3e%3c/defs%3e%3c/svg%3e\")";
                $background_style = 'background-image: ' . $background_image;
            } elseif ( 'stripes' === $background ) {
                // https://bgjar.com/shiny-overlay
                $background_image = "url(\"data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' version='1.1' xmlns:xlink='http://www.w3.org/1999/xlink' xmlns:svgjs='http://svgjs.com/svgjs' width='2560' height='2560' preserveAspectRatio='none' viewBox='0 0 2560 2560'%3e%3cg mask='url(%26quot%3b%23SvgjsMask1276%26quot%3b)' fill='none'%3e%3crect width='2560' height='2560' x='0' y='0' fill='url(%23SvgjsLinearGradient1277)'%3e%3c/rect%3e%3cpath d='M0 0L524.59 0L0 986.23z' fill='rgba(255%2c 255%2c 255%2c .1)'%3e%3c/path%3e%3cpath d='M0 986.23L524.59 0L684.6500000000001 0L0 1251.4z' fill='rgba(255%2c 255%2c 255%2c .075)'%3e%3c/path%3e%3cpath d='M0 1251.4L684.6500000000001 0L1140.02 0L0 1816.94z' fill='rgba(255%2c 255%2c 255%2c .05)'%3e%3c/path%3e%3cpath d='M0 1816.94L1140.02 0L1666.1399999999999 0L0 1973.71z' fill='rgba(255%2c 255%2c 255%2c .025)'%3e%3c/path%3e%3cpath d='M2560 2560L1477.86 2560L2560 2129.39z' fill='rgba(0%2c 0%2c 0%2c .1)'%3e%3c/path%3e%3cpath d='M2560 2129.39L1477.86 2560L669.0099999999999 2560L2560 1244.5099999999998z' fill='rgba(0%2c 0%2c 0%2c .075)'%3e%3c/path%3e%3cpath d='M2560 1244.51L669.0099999999998 2560L531.5999999999998 2560L2560 928.88z' fill='rgba(0%2c 0%2c 0%2c .05)'%3e%3c/path%3e%3cpath d='M2560 928.8800000000001L531.5999999999997 2560L354.62999999999965 2560L2560 697.8700000000001z' fill='rgba(0%2c 0%2c 0%2c .025)'%3e%3c/path%3e%3c/g%3e%3cdefs%3e%3cmask id='SvgjsMask1276'%3e%3crect width='2560' height='2560' fill='white'%3e%3c/rect%3e%3c/mask%3e%3clinearGradient x1='0%25' y1='0%25' x2='100%25' y2='100%25' gradientUnits='userSpaceOnUse' id='SvgjsLinearGradient1277'%3e%3cstop stop-color='rgba(255%2c 255%2c 255%2c 1)' offset='0'%3e%3c/stop%3e%3cstop stop-color='rgba(172%2c 172%2c 172%2c 1)' offset='1'%3e%3c/stop%3e%3c/linearGradient%3e%3c/defs%3e%3c/svg%3e\")";
                $background_style = 'background-image: ' . $background_image;
            } elseif ( 'curves' === $background ) {
                // https://www.svgbackgrounds.com/
                $background_image = "url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='100%25' height='100%25' viewBox='0 0 1600 800'%3E%3Cg %3E%3Cpath fill='%23e0e0e0' d='M486 705.8c-109.3-21.8-223.4-32.2-335.3-19.4C99.5 692.1 49 703 0 719.8V800h843.8c-115.9-33.2-230.8-68.1-347.6-92.2C492.8 707.1 489.4 706.5 486 705.8z'/%3E%3Cpath fill='%23e2e2e2' d='M1600 0H0v719.8c49-16.8 99.5-27.8 150.7-33.5c111.9-12.7 226-2.4 335.3 19.4c3.4 0.7 6.8 1.4 10.2 2c116.8 24 231.7 59 347.6 92.2H1600V0z'/%3E%3Cpath fill='%23e5e5e5' d='M478.4 581c3.2 0.8 6.4 1.7 9.5 2.5c196.2 52.5 388.7 133.5 593.5 176.6c174.2 36.6 349.5 29.2 518.6-10.2V0H0v574.9c52.3-17.6 106.5-27.7 161.1-30.9C268.4 537.4 375.7 554.2 478.4 581z'/%3E%3Cpath fill='%23e7e7e7' d='M0 0v429.4c55.6-18.4 113.5-27.3 171.4-27.7c102.8-0.8 203.2 22.7 299.3 54.5c3 1 5.9 2 8.9 3c183.6 62 365.7 146.1 562.4 192.1c186.7 43.7 376.3 34.4 557.9-12.6V0H0z'/%3E%3Cpath fill='%23EAEAEA' d='M181.8 259.4c98.2 6 191.9 35.2 281.3 72.1c2.8 1.1 5.5 2.3 8.3 3.4c171 71.6 342.7 158.5 531.3 207.7c198.8 51.8 403.4 40.8 597.3-14.8V0H0v283.2C59 263.6 120.6 255.7 181.8 259.4z'/%3E%3Cpath fill='%23ededed' d='M1600 0H0v136.3c62.3-20.9 127.7-27.5 192.2-19.2c93.6 12.1 180.5 47.7 263.3 89.6c2.6 1.3 5.1 2.6 7.7 3.9c158.4 81.1 319.7 170.9 500.3 223.2c210.5 61 430.8 49 636.6-16.6V0z'/%3E%3Cpath fill='%23f0f0f0' d='M454.9 86.3C600.7 177 751.6 269.3 924.1 325c208.6 67.4 431.3 60.8 637.9-5.3c12.8-4.1 25.4-8.4 38.1-12.9V0H288.1c56 21.3 108.7 50.6 159.7 82C450.2 83.4 452.5 84.9 454.9 86.3z'/%3E%3Cpath fill='%23f2f2f2' d='M1600 0H498c118.1 85.8 243.5 164.5 386.8 216.2c191.8 69.2 400 74.7 595 21.1c40.8-11.2 81.1-25.2 120.3-41.7V0z'/%3E%3Cpath fill='%23f5f5f5' d='M1397.5 154.8c47.2-10.6 93.6-25.3 138.6-43.8c21.7-8.9 43-18.8 63.9-29.5V0H643.4c62.9 41.7 129.7 78.2 202.1 107.4C1020.4 178.1 1214.2 196.1 1397.5 154.8z'/%3E%3Cpath fill='%23F8F8F8' d='M1315.3 72.4c75.3-12.6 148.9-37.1 216.8-72.4h-723C966.8 71 1144.7 101 1315.3 72.4z'/%3E%3C/g%3E%3C/svg%3E\")";
                $background_style = 'background-image: ' . $background_image;
            } elseif ( 'pattern' === $background ) {
                $background_style = 'background-image: none;';
            } elseif ( 'image' === $background ) {
                $background_style = 'background-image: none;';
            } elseif ( 'solid_color' === $background ) {
                $background_style = 'background-color: #ffffff;';
            } else {
            }
            ?>
                <html>
                    <head>
                        <title><?php 
            echo esc_html( $title );
            ?></title>
                        <link rel="stylesheet" id="asenha-maintenance" href="<?php 
            echo esc_html( ASENHA_URL ) . 'assets/css/maintenance.css';
            ?>" media="all">
                        <?php 
            echo wp_kses( $custom_head_code, get_kses_with_style_src_ruleset() );
            ?>
                        <meta name="viewport" content="width=device-width">
                        <?php 
            wp_site_icon();
            ?>
                        <style>
                            body {
                                <?php 
            echo wp_kses_post( $background_style );
            ?>;
                                background-size: cover;
                                background-position: center center;
                            }
                            <?php 
            ?>
                        </style>
                    </head>
                    <body>
                        <div class="page-wrapper">
                            <div class="page-overlay">
                            </div>
                            <div class="message-box">
                                <h1><?php 
            echo wp_kses_post( $heading );
            ?></h1>
                                <div class="description"><?php 
            echo wp_kses_post( $description );
            ?></div>
                            </div>
                        </div>
                    </body>
                </html>
                <?php 
            exit;
        }
    }

    /**
     * Show Password Protection admin bar status icon
     *
     * @since 4.1.0
     */
    public function show_maintenance_mode_admin_bar_icon() {
        add_action( 'wp_before_admin_bar_render', [$this, 'add_maintenance_mode_admin_bar_item'] );
        add_action( 'admin_head', [$this, 'add_maintenance_mode_admin_bar_item_styles'] );
        add_action( 'wp_head', [$this, 'add_maintenance_mode_admin_bar_item_styles'] );
    }

    /**
     * Add WP Admin Bar item
     *
     * @since 4.1.0
     */
    public function add_maintenance_mode_admin_bar_item() {
        global $wp_admin_bar;
        $allow_frontend_access = $this->is_user_allowed_frontend_access();
        if ( is_user_logged_in() ) {
            if ( $allow_frontend_access ) {
                $wp_admin_bar->add_menu( array(
                    'id'    => 'maintenance_mode',
                    'title' => '',
                    'href'  => admin_url( 'tools.php?page=admin-site-enhancements#utilities' ),
                    'meta'  => array(
                        'title' => __( 'Maintenance mode is currently enabled for this site.', 'admin-site-enhancements' ),
                    ),
                ) );
            }
        }
    }

    /**
     * Add icon and CSS for admin bar item
     *
     * @since 4.1.0
     */
    public function add_maintenance_mode_admin_bar_item_styles() {
        $allow_frontend_access = $this->is_user_allowed_frontend_access();
        if ( is_user_logged_in() ) {
            if ( $allow_frontend_access ) {
                ?>
                <style>
                    #wp-admin-bar-maintenance_mode { 
                        background-color: #ff800c !important;
                        transition: .25s;
                    }
                    #wp-admin-bar-maintenance_mode > .ab-item { 
                        color: #fff !important;  
                    }
                    #wp-admin-bar-maintenance_mode > .ab-item:before { 
                        content: "\f308"; 
                        top: 2px; 
                        color: #fff !important; 
                        margin-right: 0px; 
                    }
                    #wp-admin-bar-maintenance_mode:hover > .ab-item { 
                        background-color: #e5730a !important; 
                        color: #fff; 
                    }
                </style>
                <?php 
            }
        }
    }

    /**
     * Check if a user role is allowed to access the frontend
     * 
     * @since 6.9.3
     */
    public function is_user_allowed_frontend_access() {
        $allow_frontend_access = false;
        if ( current_user_can( 'edit_posts' ) ) {
            $allow_frontend_access = true;
        }
        return $allow_frontend_access;
    }

}
