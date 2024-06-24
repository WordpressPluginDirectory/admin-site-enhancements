<?php

namespace ASENHA\Classes;

/**
 * Class for SVG Upload module
 *
 * @since 6.9.5
 */
class SVG_Upload {

    /**
     * Add SVG mime type for media library uploads
     *
     * @link https://developer.wordpress.org/reference/hooks/upload_mimes/
     * @since 2.6.0
     */
    public function add_svg_mime( $mimes ) {

        global $roles_svg_upload_enabled;

        $current_user = wp_get_current_user();
        $current_user_roles = (array) $current_user->roles; // single dimensional array of role slugs

        if ( count( $roles_svg_upload_enabled ) > 0 ) {

            // Add mime type for user roles set to enable SVG upload
            foreach ( $current_user_roles as $role ) {
                if ( in_array( $role, $roles_svg_upload_enabled ) ) {
                    $mimes['svg'] = 'image/svg+xml';
                }
            }   

        }

        return $mimes;

    }

    /**
     * Check and confirm if the real file type is indeed SVG
     *
     * @link https://developer.wordpress.org/reference/functions/wp_check_filetype_and_ext/
     * @since 2.6.0
     */
    public function confirm_file_type_is_svg( $filetypes_extensions, $file, $filename, $mimes ) {

        global $roles_svg_upload_enabled;

        $current_user = wp_get_current_user();
        $current_user_roles = (array) $current_user->roles; // single dimensional array of role slugs

        if ( count( $roles_svg_upload_enabled ) > 0 ) {

            // Check file extension
            if ( substr( $filename, -4 ) == '.svg' ) {

                // Add mime type for user roles set to enable SVG upload
                foreach ( $current_user_roles as $role ) {
                    if ( in_array( $role, $roles_svg_upload_enabled ) ) {
                        $filetypes_extensions['type'] = 'image/svg+xml';
                        $filetypes_extensions['ext'] = 'svg';
                    }
                }   

            }

        }

        return $filetypes_extensions;

    }

    /** 
     * Sanitize the SVG file and maybe allow upload based on the result
     *
     * @since 2.6.0
     */
    public function sanitize_and_maybe_allow_svg_upload( $file ) {

        if ( ! isset( $file['tmp_name'] ) ) {
            return $file;
        }

        $file_tmp_name = $file['tmp_name']; // full path
        $file_name = isset( $file['name'] ) ? $file['name'] : '';
        $file_type_ext = wp_check_filetype_and_ext( $file_tmp_name, $file_name );
        $file_type = ! empty( $file_type_ext['type'] ) ? $file_type_ext['type'] : '';

        if ( 'image/svg+xml' === $file_type ) {

            if ( ! class_exists( '\enshrined\svgSanitize\Sanitizer' ) ) {
                // Load sanitizer library - https://github.com/darylldoyle/svg-sanitizer
                require_once ASENHA_PATH . 'vendor/enshrined/svg-sanitize/src/data/AttributeInterface.php';
                require_once ASENHA_PATH . 'vendor/enshrined/svg-sanitize/src/data/TagInterface.php';
                require_once ASENHA_PATH . 'vendor/enshrined/svg-sanitize/src/data/AllowedAttributes.php';
                require_once ASENHA_PATH . 'vendor/enshrined/svg-sanitize/src/data/AllowedTags.php';
                require_once ASENHA_PATH . 'vendor/enshrined/svg-sanitize/src/data/XPath.php';
                require_once ASENHA_PATH . 'vendor/enshrined/svg-sanitize/src/ElementReference/Resolver.php';
                require_once ASENHA_PATH . 'vendor/enshrined/svg-sanitize/src/ElementReference/Subject.php';
                require_once ASENHA_PATH . 'vendor/enshrined/svg-sanitize/src/ElementReference/Usage.php';
                require_once ASENHA_PATH . 'vendor/enshrined/svg-sanitize/src/Exceptions/NestingException.php';
                require_once ASENHA_PATH . 'vendor/enshrined/svg-sanitize/src/Helper.php';
                require_once ASENHA_PATH . 'vendor/enshrined/svg-sanitize/src/Sanitizer.php';
            }

            // $sanitizer = new Sanitizer();
            $sanitizer = new \enshrined\svgSanitize\Sanitizer();

            $original_svg = file_get_contents( $file_tmp_name );
            $sanitized_svg = $sanitizer->sanitize( $original_svg ); // boolean

            if ( false === $sanitized_svg ) {

                $file['error'] = 'This SVG file could not be sanitized, so, was not uploaded for security reasons.';

            }

            file_put_contents( $file_tmp_name, $sanitized_svg );

        }

        return $file;

    }

    /**
     * Generate metadata for the svg attachment
     *
     * @link https://developer.wordpress.org/reference/functions/wp_generate_attachment_metadata/
     * @since 2.6.0
     */
    public function generate_svg_metadata( $metadata, $attachment_id, $context ) {

        if ( get_post_mime_type( $attachment_id ) == 'image/svg+xml' ) {

            // Get SVG dimensions
            $svg_path = get_attached_file( $attachment_id );
            $svg = simplexml_load_file( $svg_path );
            $width = 0;
            $height = 0;

            if ( $svg ) {

                $attributes = $svg->attributes();
                if ( isset( $attributes->width, $attributes->height ) ) {
                    $width = intval( floatval( $attributes->width ) );
                    $height = intval( floatval( $attributes->height ) );
                } elseif ( isset( $attributes->viewBox ) ) {
                    $sizes = explode( ' ', $attributes->viewBox );
                    if ( isset( $sizes[2], $sizes[3] ) ) {
                        $width = intval( floatval( $sizes[2] ) );
                        $height = intval( floatval( $sizes[3] ) );
                    }
                }

            }

            $metadata['width'] = $width;
            $metadata['height'] = $height;

            // Get SVG filename
            $svg_url = wp_get_original_image_url( $attachment_id );
            $svg_url_path = str_replace( wp_upload_dir()['baseurl'] .'/' , '', $svg_url );
            $metadata['file'] = $svg_url_path;

        }

        return $metadata;

    }

    /**
     * Return svg file URL to show preview in media library
     *
     * @link https://developer.wordpress.org/reference/hooks/wp_ajax_action/
     * @link https://developer.wordpress.org/reference/functions/wp_get_attachment_url/
     * @since 2.6.0
     */
    public function get_svg_attachment_url() {

        $attachment_url = '';
        $attachment_id = isset( $_REQUEST['attachmentID'] ) ? $_REQUEST['attachmentID'] : '';

        // Check response mime type
        if ( $attachment_id ) {

            echo esc_url( wp_get_attachment_url( $attachment_id ) );

            die();

        }

    }

    /**
     * Return svg file URL to show preview in media library
     *
     * @link https://developer.wordpress.org/reference/functions/wp_prepare_attachment_for_js/
     * @since 2.6.0
     */
    public function get_svg_url_in_media_library( $response ) {

        // Check response mime type
        if ( $response['mime'] === 'image/svg+xml' ) {

            $response['image'] = array(
                'src'   => $response['url'],
            );

        }

        return $response;

    }
        
}