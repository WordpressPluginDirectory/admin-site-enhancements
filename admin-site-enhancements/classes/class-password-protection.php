<?php

namespace ASENHA\Classes;

use WP_Error;
/**
 * Class for Password Protection module
 *
 * @since 6.9.5
 */
class Password_Protection {
    /**
     * Whether a failed ?bypass= attempt has already been counted this request.
     *
     * @since 9.1.2
     * @var bool
     */
    private $bypass_failure_counted = false;

    /**
     * Show Password Protection admin bar status icon
     *
     * @since 4.1.0
     */
    public function show_password_protection_admin_bar_icon() {
        add_action( 'wp_before_admin_bar_render', [$this, 'add_password_protection_admin_bar_item'] );
        add_action( 'admin_head', [$this, 'add_password_protection_admin_bar_item_styles'] );
        add_action( 'wp_head', [$this, 'add_password_protection_admin_bar_item_styles'] );
    }

    /**
     * Add WP Admin Bar item
     *
     * @since 4.1.0
     */
    public function add_password_protection_admin_bar_item() {
        global $wp_admin_bar;
        if ( is_user_logged_in() ) {
            if ( current_user_can( 'manage_options' ) ) {
                $wp_admin_bar->add_menu( array(
                    'id'    => 'password_protection',
                    'title' => '',
                    'href'  => admin_url( 'tools.php?page=admin-site-enhancements#utilities' ),
                    'meta'  => array(
                        'title' => __( 'Password protection is currently enabled for this site.', 'admin-site-enhancements' ),
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
    public function add_password_protection_admin_bar_item_styles() {
        if ( is_user_logged_in() ) {
            if ( current_user_can( 'manage_options' ) ) {
                ?>
                <style>
                    #wp-admin-bar-password_protection { 
                        background-color: #c32121 !important;
                        transition: .25s;
                    }
                    #wp-admin-bar-password_protection > .ab-item { 
                        color: #fff !important;  
                    }
                    #wp-admin-bar-password_protection > .ab-item:before { 
                        content: "\f160"; 
                        top: 2px; 
                        color: #fff !important; 
                        margin-right: 0px; 
                    }
                    #wp-admin-bar-password_protection:hover > .ab-item { 
                        background-color: #af1d1d !important; 
                        color: #fff; 
                    }
                </style>
                <?php 
            }
        }
    }

    /**
     * Disable page caching
     *
     * @since 4.1.0
     */
    public function maybe_disable_page_caching() {
        if ( !defined( 'DONOTCACHEPAGE' ) ) {
            define( 'DONOTCACHEPAGE', true );
        }
    }

    /**
     * Whether the current user can edit content in the block editor.
     *
     * Used by the admin-ajax gate so contributors, authors, editors,
     * page-only roles, and CPT-only roles can use Heartbeat and other AJAX
     * while Password Protection is on. REST allows any logged-in user.
     * The frontend HTML gate still requires an administrator or a valid
     * unlock cookie.
     *
     * @since 9.1.3
     * @return bool
     */
    private function current_user_can_edit_content() {
        if ( !is_user_logged_in() ) {
            return false;
        }
        if ( current_user_can( 'edit_posts' ) || current_user_can( 'edit_pages' ) ) {
            return true;
        }
        $post_types = get_post_types( array(
            'show_ui'      => true,
            'show_in_rest' => true,
        ), 'objects' );
        foreach ( $post_types as $post_type ) {
            if ( !empty( $post_type->cap->edit_posts ) && current_user_can( $post_type->cap->edit_posts ) ) {
                return true;
            }
        }
        return false;
    }

    /**
     * Determine whether the current request is allowed past the password
     * protection gate. Shared by the template_redirect gate, the REST gate
     * and the admin-ajax gate.
     *
     * @since 9.1.2
     * @return bool True when the request may proceed, false when the gate applies.
     */
    private function is_request_allowed() {
        // Do not gate WP-Cron requests; wp-cron.php must return from bootstrap so core can run scheduled hooks.
        if ( function_exists( 'wp_doing_cron' ) && wp_doing_cron() ) {
            return true;
        }
        if ( defined( 'DOING_CRON' ) && DOING_CRON ) {
            return true;
        }
        // Novamira OAuth discovery and token exchange stay reachable while the site is password protected.
        // MCP Toolkit tool listing stays reachable on its own HTTP endpoint.
        $common_methods = new Common_Methods();
        if ( $common_methods->is_novamira_auth_request() || $common_methods->is_mcp_toolkit_auth_request() ) {
            return true;
        }
        $options = get_option( ASENHA_SLUG_U, array() );
        $stored_password = ( isset( $options['password_protection_password'] ) ? $options['password_protection_password'] : '' );
        // When user is logged-in as an administrator
        if ( is_user_logged_in() ) {
            if ( current_user_can( 'manage_options' ) ) {
                return true;
            }
        }
        // When site visitor has entered correct password, get the auth cookie.
        // The value is a wp_hash_password() hash, not user text: do not run it
        // through sanitize_text_field(), which strips %[a-f0-9]{2} octets.
        $auth_cookie = ( isset( $_COOKIE['asenha_password_protection'] ) ? (string) wp_unslash( $_COOKIE['asenha_password_protection'] ) : '' );
        // Compared $auth_cookie against hashed string set in maybe_process_login()
        if ( '' !== $stored_password && '' !== $auth_cookie && true === wp_check_password( $_SERVER['HTTP_HOST'] . '__' . $stored_password, $auth_cookie ) ) {
            return true;
        }
        return false;
    }

    /**
     * Set the password protection auth cookie. The value is a portable hash of
     * the host and site password. Secure follows the current connection
     * (is_ssl()) so HTTP responses never send a Secure cookie the browser
     * would discard. TLS-terminating proxies should map X-Forwarded-Proto
     * so is_ssl() is already true on public HTTPS.
     *
     * @since 9.1.2
     * @param string $stored_password The site-wide protection password.
     */
    private function set_auth_cookie( $stored_password ) {
        // $expiration = time() + DAY_IN_SECONDS; // in 24 hours
        $expiration = 0;
        // by the end of browsing session
        $hashed_cookie_value = wp_hash_password( $_SERVER['HTTP_HOST'] . '__' . $stored_password );
        $secure = is_ssl();
        setcookie(
            'asenha_password_protection',
            $hashed_cookie_value,
            $expiration,
            COOKIEPATH,
            COOKIE_DOMAIN,
            $secure,
            true
        );
        // setcookie() does not populate $_COOKIE; mirror it so same-request
        // REST preloads and admin-ajax see the unlock without ?bypass=.
        $_COOKIE['asenha_password_protection'] = $hashed_cookie_value;
    }

    /**
     * Keep the ?bypass= query arg across redirect_canonical so a homepage
     * trailing-slash or scheme redirect does not drop the unlock before
     * the session cookie is stored by the browser.
     *
     * @since 9.1.2
     * @param string|false $redirect_url  Canonical target, or false to skip.
     * @param string       $requested_url Original requested URL.
     * @return string|false
     */
    public function preserve_bypass_query_arg( $redirect_url, $requested_url ) {
        // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
        if ( empty( $redirect_url ) ) {
            return $redirect_url;
        }
        $password_in_url = ( isset( $_GET['bypass'] ) ? sanitize_text_field( wp_unslash( $_GET['bypass'] ) ) : '' );
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if ( '' === $password_in_url ) {
            return $redirect_url;
        }
        return add_query_arg( 'bypass', $password_in_url, $redirect_url );
    }

    /**
     * Return 401 for REST API requests when the protection gate applies, since
     * /wp-json/* requests never reach the template_redirect gate.
     *
     * Used on rest_authentication_errors (primary: skips dispatch) and on
     * rest_pre_dispatch at PHP_INT_MAX (defense in depth so a later callback
     * such as ACF_Rest_Api::initialize cannot replace a 401 with null).
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
        if ( is_user_logged_in() ) {
            return $result;
        }
        $application_password_error = $common_methods->get_application_password_authentication_error();
        if ( is_wp_error( $application_password_error ) ) {
            return $application_password_error;
        }
        if ( $this->is_request_allowed() ) {
            return $result;
        }
        return new WP_Error('asenha_password_protection_required', __( 'This site is password protected.', 'admin-site-enhancements' ), array(
            'status' => 401,
        ));
    }

    /**
     * Return 401 for admin-ajax.php requests when the protection gate applies,
     * since AJAX requests never reach the template_redirect gate.
     *
     * Hooked on init priority 20 so plugin CPTs are registered before the
     * content-editor capability check.
     *
     * @since 9.1.3
     */
    public function ajax_gate() {
        if ( !wp_doing_ajax() ) {
            return;
        }
        if ( $this->current_user_can_edit_content() ) {
            return;
        }
        if ( $this->is_request_allowed() ) {
            return;
        }
        wp_die( '', '', array(
            'response' => 401,
        ) );
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }

    /**
     * Maybe show login form
     *
     * @since 4.1.0
     */
    public function maybe_show_login_form() {
        if ( $this->is_request_allowed() ) {
            return;
        }
        if ( isset( $_REQUEST['protected-page'] ) && 'view' == $_REQUEST['protected-page'] ) {
            // Show login form
            $password_protected_login_page_template = ASENHA_PATH . 'includes/password-protected-login.php';
            load_template( $password_protected_login_page_template );
            exit;
        } else {
            // Redirect from current URL to login form
            $current_url = (( is_ssl() ? 'https://' : 'http://' )) . sanitize_text_field( $_SERVER['HTTP_HOST'] ) . sanitize_text_field( $_SERVER['REQUEST_URI'] );
            $args = array(
                'protected-page' => 'view',
                'source'         => urlencode( $current_url ),
            );
            $pwd_protect_login_url = add_query_arg( $args, home_url( '/' ) );
            nocache_headers();
            wp_safe_redirect( $pwd_protect_login_url );
            exit;
        }
    }

    /**
     * Maybe process login to access protected page content
     *
     * @since 4.1.0
     */
    public function maybe_process_login() {
        global $password_protected_errors;
        $password_protected_errors = new WP_Error();
        if ( isset( $_REQUEST['protected_page_pwd'] ) ) {
            $password_input = sanitize_text_field( $_REQUEST['protected_page_pwd'] );
            $options = get_option( ASENHA_SLUG_U, array() );
            $stored_password = ( isset( $options['password_protection_password'] ) ? $options['password_protection_password'] : '' );
            if ( !empty( $password_input ) ) {
                // Per-IP throttle: 5 attempts per 10 minutes.
                $common_methods = new Common_Methods();
                $ip_address = $common_methods->get_user_ip_address( 'ip', 'password-protection' );
                $attempts_key = 'asenha_pwd_protect_' . md5( $ip_address );
                $attempts = (int) get_transient( $attempts_key );
                if ( $attempts >= 5 ) {
                    $password_protected_errors->add( 'too_many_attempts', __( 'Too many attempts. Please try again in 10 minutes.', 'admin-site-enhancements' ) );
                    return;
                }
                // Constant-time comparison; loose == would admit type-juggled
                // equivalents like '0123' vs '123' or magic-hash '0e...' strings.
                if ( '' !== $stored_password && hash_equals( (string) $stored_password, (string) $password_input ) ) {
                    // Password is correct
                    delete_transient( $attempts_key );
                    // Set auth cookie
                    $this->set_auth_cookie( $stored_password );
                    // Redirect
                    $redirect_to_url = ( isset( $_REQUEST['source'] ) ? sanitize_url( $_REQUEST['source'] ) : '' );
                    wp_safe_redirect( $redirect_to_url );
                    exit;
                } else {
                    // Password is incorrect
                    set_transient( $attempts_key, $attempts + 1, 10 * MINUTE_IN_SECONDS );
                    // Add error message
                    $password_protected_errors->add( 'incorrect_password', __( 'Incorrect password.', 'admin-site-enhancements' ) );
                }
            } else {
                // Password input is empty
                // Add error message
                $password_protected_errors->add( 'empty_password', __( 'Password can not be empty.', 'admin-site-enhancements' ) );
            }
        }
    }

    /**
     * Add custom login error messages
     *
     * @since 4.1.0
     */
    public function add_login_error_messages() {
        global $password_protected_errors;
        if ( $password_protected_errors->get_error_code() ) {
            $messages = '';
            $errors = '';
            // Extract the error message
            foreach ( $password_protected_errors->get_error_codes() as $code ) {
                $severity = $password_protected_errors->get_error_data( $code );
                foreach ( $password_protected_errors->get_error_messages( $code ) as $error ) {
                    if ( 'message' == $severity ) {
                        $messages .= $error . '<br />';
                    } else {
                        $errors .= $error . '<br />';
                    }
                }
            }
            // Output the error message
            if ( !empty( $messages ) ) {
                echo '<p class="message">' . wp_kses_post( $messages ) . '</p>';
            }
            if ( !empty( $errors ) ) {
                echo '<div id="login_error">' . wp_kses_post( $errors ) . '</div>';
            }
        }
    }

}
