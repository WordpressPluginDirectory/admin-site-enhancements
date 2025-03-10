<?php

namespace ASENHA\Classes;

use WP_Error;
/**
 * Class for Disable REST API module
 *
 * @since 6.9.5
 */
class Disable_REST_API {
    /**
     * Disable REST API for non-authenticated users. This is for WP v4.7 or later.
     *
     * @since 2.9.0
     */
    public function disable_rest_api( $errors ) {
        $allow_rest_api_access = false;
        if ( !is_user_logged_in() ) {
            // Get the REST API route being requested,e.g. wp/v2/posts | altcha/v1/challenge (without preceding slash /)
            // Ref: https://developer.wordpress.org/reference/hooks/rest_authentication_errors/#comment-6463
            $route = ltrim( $GLOBALS['wp']->query_vars['rest_route'], '/' );
            if ( false !== strpos( $route, 'altcha/v1' ) || false !== strpos( $route, 'contact-form-7/v1' ) ) {
                $allow_rest_api_access = true;
            } else {
                $allow_rest_api_access = false;
            }
        } else {
            $allow_rest_api_access = true;
        }
        if ( !$allow_rest_api_access ) {
            return new WP_Error('rest_api_authentication_required', 'The REST API has been restricted to authenticated users.', array(
                'status' => rest_authorization_required_code(),
            ));
        }
    }

}
