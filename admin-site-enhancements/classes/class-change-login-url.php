<?php

namespace ASENHA\Classes;

/**
 * Class for Change Login URL module
 *
 * @since 6.9.5
 */
class Change_Login_URL {
    /**
     * Redirect to valid login URL when custom login slug is part of the request URL
     *
     * @link https://plugins.trac.wordpress.org/browser/admin-login-url-change/trunk/admin-login-url-change.php#L134
     * @since 1.4.0
     */
    public function redirect_on_custom_login_url() {
        $options = get_option( ASENHA_SLUG_U );
        $custom_login_slug = $options['custom_login_slug'];
        $url_input = sanitize_text_field( $_SERVER['REQUEST_URI'] );
        // Make sure $url_input ends with /
        if ( false !== strpos( $url_input, $custom_login_slug ) ) {
            if ( substr( $url_input, -1 ) != '/' ) {
                $url_input = $url_input . '/';
            }
        }
        // If URL contains the custom login slug, redirect to the dashboard
        if ( false !== strpos( $url_input, '/' . $custom_login_slug . '/' ) ) {
            if ( is_user_logged_in() ) {
                if ( array_key_exists( 'redirect_after_login', $options ) && $options['redirect_after_login'] ) {
                    if ( array_key_exists( 'redirect_after_login_for', $options ) && !empty( $options['redirect_after_login_for'] ) ) {
                        // An almost exact replica of redirect_after_login() in class-redirect-after-login.php
                        $redirect_after_login_to_slug_raw = ( isset( $options['redirect_after_login_to_slug'] ) ? $options['redirect_after_login_to_slug'] : '' );
                        if ( !empty( $redirect_after_login_to_slug_raw ) ) {
                            $redirect_after_login_to_slug = trim( trim( $redirect_after_login_to_slug_raw ), '/' );
                            if ( false !== strpos( $redirect_after_login_to_slug, '.php' ) ) {
                                $slug_suffix = '';
                            } else {
                                $slug_suffix = '/';
                            }
                            $relative_path = $redirect_after_login_to_slug . $slug_suffix;
                        } else {
                            $relative_path = '';
                        }
                        $redirect_after_login_for = $options['redirect_after_login_for'];
                        if ( isset( $redirect_after_login_for ) && count( $redirect_after_login_for ) > 0 ) {
                            // Assemble single-dimensional array of roles for which custom URL redirection should happen
                            $roles_for_custom_redirect = array();
                            foreach ( $redirect_after_login_for as $role_slug => $custom_redirect ) {
                                if ( $custom_redirect ) {
                                    $roles_for_custom_redirect[] = $role_slug;
                                }
                            }
                            // Does the user have roles data in array form?
                            $user = wp_get_current_user();
                            if ( isset( $user->roles ) && is_array( $user->roles ) ) {
                                $current_user_roles = $user->roles;
                            }
                            // Set custom redirect URL for roles set in the settings. Otherwise, leave redirect URL to the default, i.e. admin dashboard.
                            foreach ( $current_user_roles as $role ) {
                                if ( in_array( $role, $roles_for_custom_redirect ) ) {
                                    wp_safe_redirect( home_url( $relative_path ) );
                                    exit;
                                } else {
                                    // Redirect to dashboard
                                    wp_safe_redirect( get_admin_url() );
                                }
                            }
                        }
                    } else {
                        // Redirect to dashboard
                        wp_safe_redirect( get_admin_url() );
                    }
                } else {
                    // Redirect to dashboard
                    wp_safe_redirect( get_admin_url() );
                }
            } else {
                // Redirect to the login URL with custom login slug in the query parameters
                wp_safe_redirect( site_url( '/wp-login.php?' . $custom_login_slug . '&redirect=false' ) );
            }
            exit;
        }
    }

    /**
     * Customize login URL returned when calling wp_login_url(). Add the custom login slug.
     * 
     * @since 5.8.0
     */
    public function customize_login_url( $login_url, $redirect, $force_reauth ) {
        $options = get_option( ASENHA_SLUG_U );
        $custom_login_slug = $options['custom_login_slug'];
        $login_url = home_url( '/' . $custom_login_slug . '/' );
        if ( !empty( $redirect ) ) {
            $login_url = add_query_arg( 'redirect_to', urlencode( $redirect ), $login_url );
        }
        if ( $force_reauth ) {
            $login_url = add_query_arg( 'reauth', '1', $login_url );
        }
        return $login_url;
    }

    /**
     * Customize lost password URL. Add the custom login slug.
     * 
     * @since 5.8.0
     */
    public function customize_lost_password_url( $lostpassword_url ) {
        $options = get_option( ASENHA_SLUG_U );
        $custom_login_slug = $options['custom_login_slug'];
        // return home_url( '/wp-login.php?manage&action=lostpassword' );
        return $lostpassword_url . '&' . $custom_login_slug;
    }

    /**
     * Customize registration URL. Add the custom login slug.
     * 
     * @since 6.2.5
     */
    public function customize_register_url( $registration_url ) {
        $options = get_option( ASENHA_SLUG_U );
        $custom_login_slug = $options['custom_login_slug'];
        // return home_url( '/wp-login.php?manage&action=lostpassword' );
        return $registration_url . '&' . $custom_login_slug;
    }

    /**
     * Redirect to /not_found when login URL does not contain the custom login slug
     * This will redirect /wp-login.php and /wp-admin/ to /not_found/
     *
     * @link https://plugins.trac.wordpress.org/browser/admin-login-url-change/trunk/admin-login-url-change.php#L121
     * @since 1.4.0
     */
    public function redirect_on_default_login_urls() {
        global $interim_login;
        if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
            return;
        }
        if ( defined( 'DOING_CRON' ) && DOING_CRON ) {
            return;
        }
        $options = get_option( ASENHA_SLUG_U );
        $custom_login_slug = $options['custom_login_slug'];
        // e.g. manage
        $url_input = sanitize_text_field( $_SERVER['REQUEST_URI'] );
        // e.g. /wp-admin/index.php?page=page-slug
        $url_input_parts = explode( '/', $url_input );
        $redirect_slug = 'not_found';
        // When logging-in
        if ( isset( $_POST['log'] ) && isset( $_POST['pwd'] ) || isset( $_POST['post_password'] ) ) {
            // Do nothing. i.e. do not redirect to /not_found/ as this contains a login POST request
            // upon successful login, redirection to logged-in view of /wp-admin/ happens.
            // Without this condition, login attempt will redirect to /not_found/
        } elseif ( is_user_logged_in() ) {
            // Do nothing user is already logged-in
            // Redirect to /wp-admin/ (Dashboard) when accessing /wp-login.php without any $_POST data
            if ( isset( $url_input_parts[1] ) && 'wp-login.php' == $url_input_parts[1] && empty( $_POST ) ) {
                wp_safe_redirect( admin_url(), 302 );
                exit;
            }
        } elseif ( !is_user_logged_in() ) {
            // Check if request URL ends in /admin/, /wp-admin/, /login/, /wp-login/ or /wp-login.php
            if ( isset( $url_input_parts[1] ) && in_array( $url_input_parts[1], array(
                'admin',
                'wp-admin',
                'login',
                'wp-login',
                'wp-login.php'
            ) ) && (!isset( $url_input_parts[2] ) || isset( $url_input_parts[2] ) && empty( $url_input_parts[2] ) || isset( $url_input_parts[2] ) && false !== strpos( $url_input_parts[2], '.php' )) ) {
                // Redirect to /not_found/ or custom redirect slug
                wp_safe_redirect( home_url( $redirect_slug . '/' ), 302 );
                exit;
            } elseif ( false !== strpos( $url_input, 'wp-login.php' ) ) {
                if ( isset( $_GET['action'] ) && ('logout' == $_GET['action'] || 'rp' == $_GET['action'] || 'resetpass' == $_GET['action']) || isset( $_GET['checkemail'] ) && ('confirm' == $_GET['checkemail'] || 'registered' == $_GET['checkemail']) || isset( $_GET['interim-login'] ) && '1' == $_GET['interim-login'] || 'success' == $interim_login || isset( $_GET['redirect_to'] ) && isset( $_GET['reauth'] ) && false !== strpos( $url_input, 'comment' ) ) {
                    // When we're logging out, inside the reset password flow, inside the registration flow or within the interim login flow
                    // e.g. https://www.example.com/wp-login.php?action=logout&_wpnonce=49bb818269
                    // e.g. https://www.example.com/wp-login.php?action=rp --> reset password
                    // e.g. https://www.example.com/wp-login.php?action=resetpass --> reset password
                    // e.g. https://www.example.com/wp-login.php?checkmail=confirm --> reset password
                    // e.g. https://www.example.com/wp-login.php?checkmail=registered --> register account
                    // e.g. https://www.example.com/wp-login.php?interim-login=1&wp_lang=en_US
                    // e.g. https://www.example.com/wp-admin/comment.php?action=approve&c=14#wpbody-content --> https://www.example.com/wp-login.php?redirect_to=https%3A%2F%2Fwww.example.com%2Fwp-admin%2Fcomment.php%3Faction%3Dapprove%26c%3D14&reauth=1#wpbody-content --> comment approve
                    // Do nothing.. proceed...
                } elseif ( isset( $_GET['action'] ) && ('lostpassword' == $_GET['action'] || 'register' == $_GET['action']) ) {
                    // When resetting password or registering an account
                    if ( isset( $_POST['user_login'] ) ) {
                        // Sending the form to reset password or register an account...
                        // Do nothing.. proceed with password reset or account registration
                    } else {
                        // When landing on the password reset or registration form
                        // ...and custom login slug is not in the URL
                        if ( false === strpos( $url_input, $custom_login_slug ) ) {
                            // Redirect to /not_found/
                            wp_safe_redirect( home_url( $redirect_slug . '/' ), 302 );
                            exit;
                        }
                        // or, custom login slug is in the url
                        // e.g. https://www.example.com/wp-login.php?action=lostpassword&customloginslug
                        // e.g. https://www.example.com/wp-login.php?action=register&customloginslug
                        // Do nothing... allow reset password or registration
                    }
                } elseif ( false === strpos( $url_input, $custom_login_slug ) ) {
                    // When landing on the login form /wp-login.php
                    // ...and custom login slug is not in the URL
                    // Redirect to /not_found/
                    wp_safe_redirect( home_url( $redirect_slug . '/' ), 302 );
                    exit;
                } elseif ( false !== strpos( $url_input, $custom_login_slug ) ) {
                    // When landing on the login form /wp-login.php
                    // ...and custom login slug is in the URL
                    // e.g. https://www.example.com/wp-login.php?customloginslug&redirect=false
                    // Do nothing. Do not redirect. Allow login.
                } else {
                }
            } else {
            }
        } else {
        }
    }

    /**
     * Redirect to custom login URL on failed login
     *
     * @link https://plugins.trac.wordpress.org/browser/admin-login-url-change/trunk/admin-login-url-change.php#L148
     * @since 1.4.0
     */
    public function redirect_to_custom_login_url_on_login_fail() {
        global $asenha_limit_login;
        $options = get_option( ASENHA_SLUG_U );
        $custom_login_slug = $options['custom_login_slug'];
        if ( isset( $asenha_limit_login ) && is_array( $asenha_limit_login ) && $asenha_limit_login['within_lockout_period'] ) {
            // Do nothing. This prevents redirection loop.
        } else {
            $should_redirect = true;
            if ( $should_redirect ) {
                // Append 'failed_login=true' so we can output custom error message above the login form
                wp_safe_redirect( home_url( 'wp-login.php?' . $custom_login_slug . '&redirect=false&failed_login=true' ) );
                exit;
            }
        }
    }

    /**
     * Add login error message on top of the login form. 
     * Only shown if there's a failed_login URL parameter, and Limit Login Attempts module is not enabled. 
     * If LLA module is enabled, the same custom login error message is handled there.
     *
     * @since 6.9.1
     */
    public function add_failed_login_message( $message ) {
        global $asenha_limit_login;
        if ( isset( $_REQUEST['failed_login'] ) && $_REQUEST['failed_login'] == 'true' ) {
            if ( is_null( $asenha_limit_login ) ) {
                $message = '<div id="login_error" class="notice notice-error"><b>' . __( 'Error:', 'admin-site-enhancements' ) . '</b> ' . __( 'Invalid username/email or incorrect password.', 'admin-site-enhancements' ) . '</div>';
            }
        }
        return $message;
    }

    /**
     * Redirect to custom login URL on successful logout
     *
     * @link https://plugins.trac.wordpress.org/browser/admin-login-url-change/trunk/admin-login-url-change.php#L148
     * @since 1.4.0
     */
    public function redirect_to_custom_login_url_on_logout_success() {
        $options = get_option( ASENHA_SLUG_U );
        $custom_login_slug = $options['custom_login_slug'];
        // Redirect to the login URL with custom login slug in it
        wp_safe_redirect( home_url( 'wp-login.php?' . $custom_login_slug . '&redirect=false' ) );
        exit;
    }

    /**
     * Customize logout URL by adding the custom login slug to it
     * 
     * @since 7.0.2.3
     */
    public function customize_logout_url( $logout_url, $redirect ) {
        $options = get_option( ASENHA_SLUG_U );
        $custom_login_slug = $options['custom_login_slug'];
        if ( !empty( $redirect ) ) {
            $logout_url = add_query_arg( 'redirect_to', urlencode( $redirect ), $logout_url );
        }
        $logout_url .= '&' . $custom_login_slug;
        return $logout_url;
    }

}
