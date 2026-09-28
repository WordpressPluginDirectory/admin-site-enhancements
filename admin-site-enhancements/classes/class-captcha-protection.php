<?php

namespace ASENHA\Classes;

/**
 * Class for Redirect After Login module
 *
 * @since 6.9.5
 */
class CAPTCHA_Protection {
    
    /**
     * Maybe keep original redirect
     * 
     * @since 7.8.0
     */
    public function maybe_keep_original_redirect( $username, $user ) {
        // Skip redirection if login is performed from a WooCommerce checkout page
        // This will ensure user is redirected back to the checkout page after successful login
        if ( isset( $_REQUEST['woocommerce-login-nonce'] ) 
            && isset( $_REQUEST['redirect'] )
            && wc_get_checkout_url() == $_REQUEST['redirect']
        ) {
            wp_safe_redirect( wc_get_checkout_url() );
            exit();
        }
    }

    /**
     * Whether the current request is served by a given PHP script (pagenow, with SCRIPT_FILENAME fallback).
     *
     * @param string $script_basename Script filename, e.g. wp-login.php.
     * @return bool
     */
    private function is_request_script( $script_basename ) {
        if ( isset( $GLOBALS['pagenow'] ) && $script_basename === $GLOBALS['pagenow'] ) {
            return true;
        }

        if ( ! empty( $_SERVER['SCRIPT_FILENAME'] ) ) {
            $script_filename = sanitize_text_field( wp_unslash( $_SERVER['SCRIPT_FILENAME'] ) );
            return $script_basename === basename( $script_filename );
        }

        return false;
    }

    /**
     * Whether the current request is served by wp-login.php.
     *
     * Do not use core is_login(): Change Login URL rewrites wp_login_url() to a custom slug
     * while SCRIPT_NAME remains wp-login.php.
     *
     * @return bool
     */
    public function is_wp_login_script() {
        return $this->is_request_script( 'wp-login.php' );
    }

    /**
     * The wp-login.php action from the current request.
     *
     * Empty string is the default login form (no action query/post arg).
     *
     * @return string
     */
    public function get_wp_login_action() {
        if ( ! isset( $_REQUEST['action'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            return '';
        }

        return sanitize_key( wp_unslash( $_REQUEST['action'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    }

    /**
     * Whether this is a core WordPress lost-password request on wp-login.php.
     *
     * @return bool
     */
    public function is_wordpress_lostpassword_request() {
        if ( ! $this->is_wp_login_script() ) {
            return false;
        }

        return in_array( $this->get_wp_login_action(), array( 'lostpassword', 'retrievepassword' ), true );
    }

    /**
     * Whether this is a core WordPress registration request on wp-login.php.
     *
     * @return bool
     */
    public function is_wordpress_registration_request() {
        if ( ! $this->is_wp_login_script() ) {
            return false;
        }

        return 'register' === $this->get_wp_login_action();
    }

    /**
     * Whether the POST has the core WordPress login field shape (log + pwd).
     *
     * Used as the login fail-closed gate instead of pagenow === wp-login.php,
     * so frontend wp_login_form() submissions are still protected.
     *
     * @return bool
     */
    public function is_wordpress_login_submission() {
        return isset( $_POST['log'] ) && isset( $_POST['pwd'] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
    }

    /**
     * Whether this is a core WordPress comment POST to wp-comments-post.php.
     *
     * @return bool
     */
    public function is_wordpress_comment_submission() {
        return $this->is_request_script( 'wp-comments-post.php' );
    }

    /**
     * Whether the request Referer matches a Change Login URL "Allow login from" whitelist path.
     *
     * Parsing matches Change_Login_URL helpers: same-site host, first non-empty
     * path segment compared to trimmed whitelist lines. Empty lines and
     * wp-login.php / wp-signup.php never match.
     *
     * @return bool
     */
    public function is_change_login_url_whitelisted_referer() {
        $options = get_option( ASENHA_SLUG_U, array() );

        if ( empty( $options['change_login_url'] ) ) {
            return false;
        }

        $http_referrer = isset( $_SERVER['HTTP_REFERER'] ) ? sanitize_url( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '';

        if ( empty( $http_referrer ) ) {
            return false;
        }

        $change_login_url = new Change_Login_URL();

        if ( ! $change_login_url->is_same_site_referer( $http_referrer ) ) {
            return false;
        }

        return $change_login_url->is_whitelisted_login_referer( $http_referrer );
    }
}