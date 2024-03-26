<?php

namespace ASENHA\Classes;

/**
 * Class for Redirect 404 module
 *
 * @since 6.9.5
 */
class Redirect_Fourofour {

    /**
     * Redirect 404 to homepage
     *
     * @since 1.7.0
     */
    public function redirect_404() {

        if ( ! is_404() || is_admin() || ( defined( 'DOING_CRON' ) && DOING_CRON ) || ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) ) {

            return;

        } else {

            // wp_safe_redirect( home_url(), 301 );

            header( 'HTTP/1.1 301 Moved Permanently');
            header( 'Location: ' . home_url() );
            exit();

        }

    }

}