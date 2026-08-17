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
     * Whether a file path and/or mime type represents an SVG.
     *
     * @since 9.0.1
     *
     * @param string $file_path Absolute file path or filename.
     * @param string $mime_type Optional mime type.
     * @return bool
     */
    public function is_svg_file( $file_path, $mime_type = '' ) {
        if ( 'image/svg+xml' === $mime_type ) {
            return true;
        }

        if ( empty( $file_path ) ) {
            return false;
        }

        $extension = strtolower( pathinfo( $file_path, PATHINFO_EXTENSION ) );

        return 'svg' === $extension;
    }

    /**
     * Check whether an XML document has an SVG root element.
     *
     * This is a deliberately small preflight check. The bundled sanitizer
     * performs its own XML parsing, but version 0.15.4 throws a LogicException
     * for a valid XML document whose root is not SVG.
     *
     * @since 9.0.1
     *
     * @param string $svg_contents SVG file contents.
     * @return bool
     */
    private function has_svg_root( $svg_contents ) {
        $previous_internal_errors = libxml_use_internal_errors( true );

        try {
            $document = new \DOMDocument();
            $loaded   = $document->loadXML( $svg_contents, LIBXML_NONET );

            if ( ! $loaded || ! $document->documentElement ) {
                return false;
            }

            $root_name = $document->documentElement->localName;
            if ( empty( $root_name ) ) {
                $root_name = $document->documentElement->nodeName;
            }

            return 'svg' === strtolower( $root_name );
        } catch ( \Throwable $e ) {
            return false;
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors( $previous_internal_errors );
        }
    }

    /**
     * Sanitize an SVG file in place.
     *
     * Writes sanitised markup back to the same path on success. Does not write
     * when sanitisation fails, so a malicious original is never replaced with
     * an empty/false value.
     *
     * @since 9.0.1
     *
     * @param string $file_path Absolute path to the SVG file.
     * @return bool True if sanitised and written, false on failure.
     */
    public function sanitize_svg_file( $file_path ) {
        if ( empty( $file_path ) || ! is_readable( $file_path ) ) {
            return false;
        }

        $original_svg = file_get_contents( $file_path );

        if ( false === $original_svg ) {
            return false;
        }

        $previous_internal_errors = libxml_use_internal_errors( true );

        try {
            if ( ! $this->has_svg_root( $original_svg ) ) {
                return false;
            }

            $sanitizer     = $this->get_svg_sanitizer();
            $sanitized_svg = $sanitizer->sanitize( $original_svg );

            if ( false === $sanitized_svg || ! is_string( $sanitized_svg ) || '' === $sanitized_svg ) {
                return false;
            }

            $bytes_written = file_put_contents( $file_path, $sanitized_svg, LOCK_EX );

            return false !== $bytes_written && strlen( $sanitized_svg ) === $bytes_written;
        } catch ( \Throwable $e ) {
            return false;
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors( $previous_internal_errors );
        }
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

        if ( ! $this->is_svg_file( $file_name, $file_type ) ) {
            return $file;
        }

        if ( ! $this->sanitize_svg_file( $file_tmp_name ) ) {
            $file['error'] = 'This SVG file could not be sanitized, so, was not uploaded for security reasons.';
        }

        return $file;
    }

    /**
     * Sanitize SVG files at write time.
     *
     * Covers wp_upload_bits() used by XML-RPC, which does not fire
     * wp_handle_upload_prefilter. Runs before mw_newMediaObject() may
     * return 401 on an unauthorised post_id, so the file on disk is
     * sanitised even when no attachment record is created.
     *
     * @since 9.0.1
     *
     * @param array  $upload  {
     *     Upload data.
     *
     *     @type string $file Filename of the newly-uploaded file.
     *     @type string $url  URL of the uploaded file.
     *     @type string $type Mime type of the newly-uploaded file.
     * }
     * @param string $context Upload context: 'upload' or 'sideload'.
     * @return array Upload data, possibly with an error when sanitisation fails.
     */
    public function sanitize_svg_on_handle_upload( $upload, $context ) {
        if ( empty( $upload['file'] ) ) {
            return $upload;
        }

        $mime_type = isset( $upload['type'] ) ? $upload['type'] : '';

        if ( ! $this->is_svg_file( $upload['file'], $mime_type ) ) {
            return $upload;
        }

        if ( ! $this->sanitize_svg_file( $upload['file'] ) ) {
            wp_delete_file( $upload['file'] );
            $upload['error'] = 'This SVG file could not be sanitized, so, was not uploaded for security reasons.';
        }

        return $upload;
    }
    
    /**
     * Sanitize SVG upload via xmlrpc.php
     * 
     * @link https://developer.wordpress.org/reference/hooks/xmlrpc_prepare_media_item/
     * @since 7.9.8
     */
    public function sanitize_xmlrpc_svg_upload( $_media_item, $media_item ) {
        if ( ! is_object( $media_item ) || ! property_exists( $media_item, 'ID' ) ) {
            return $_media_item;
        }

        $file_path = get_attached_file( $media_item->ID );
        $mime_type = isset( $media_item->post_mime_type ) ? $media_item->post_mime_type : get_post_mime_type( $media_item->ID );

        if ( ! $this->is_svg_file( $file_path, $mime_type ) ) {
            return $_media_item;
        }

        if ( ! $this->sanitize_svg_file( $file_path ) ) {
            wp_delete_file( $file_path );
        }

        return $_media_item;
    }
    
    /**
     * Sanitize a file after it is added to the media library, e.g. via REST API POST request
     * 
     * @since 7.5.2
     */
    public function sanitize_after_upload( $attachment, $request, $creating ) {
        if ( ! $creating || ! ( $attachment instanceof \WP_Post ) ) {
            return;
        }

        $file_path = get_attached_file( $attachment->ID );
        $mime_type = isset( $attachment->post_mime_type ) ? $attachment->post_mime_type : get_post_mime_type( $attachment->ID );

        if ( ! $this->is_svg_file( $file_path, $mime_type ) ) {
            return;
        }

        if ( ! $this->sanitize_svg_file( $file_path ) ) {
            wp_delete_attachment( $attachment->ID, true );
        }
    }
    
    /**
     * Get sanitizer object
     * 
     * @since 7.5.2
     */
    public function get_svg_sanitizer() {
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
        
        return $sanitizer;        
    }

    /**
     * Generate metadata for the svg attachment
     *
     * @link https://developer.wordpress.org/reference/functions/wp_generate_attachment_metadata/
     * @since 2.6.0
     */
    public function generate_svg_metadata( $metadata, $attachment_id, $context ) {

        if ( get_post_mime_type( $attachment_id ) == 'image/svg+xml' ) {

            // Get SVG intrinsic dimensions (prefer viewBox when width/height are %).
            $svg_path       = get_attached_file( $attachment_id );
            $common_methods = new Common_Methods;
            $dims           = $common_methods->get_svg_intrinsic_dimensions_from_file( $svg_path );

            $metadata['width']  = isset( $dims['width'] ) ? absint( $dims['width'] ) : 0;
            $metadata['height'] = isset( $dims['height'] ) ? absint( $dims['height'] ) : 0;

            // Get SVG filename
            $svg_url = wp_get_original_image_url( $attachment_id );
            $svg_url_path = str_replace( wp_upload_dir()['baseurl'] .'/' , '', $svg_url );
            $metadata['file'] = $svg_url_path;

        }

        return $metadata;

    }

    /**
     * Remove responsive image attributes, i.e. srcset attributes, from SVG images HTML
     * This helps ensure SVGs are displayed properly on the frontend
     * 
     * @link https://plugins.trac.wordpress.org/browser/svg-support/tags/2.5.7/functions/attachment.php#L282
     * @since 7.3.0
     */
    public function disable_svg_srcset( $sources ) {
        $first_element = reset( $sources );

        if ( isset( $first_element ) && ! empty( $first_element['url'] ) ) {
            $extension = pathinfo( reset($sources)['url'], PATHINFO_EXTENSION );

            if ( 'svg' === $extension ) {
                $sources = array(); // return empty array
                return $sources;
            } else {
                return $sources;
            }
        } else {
            return $sources;
        }
    }
        
    /**
     * Remove responsive image attributes, i.e. srcset attributes, from SVG images HTML
     * This helps ensure SVGs are displayed properly on the frontend
     * 
     * @link https://gist.github.com/ericvalois/5b1e161c127632a1ace7d65ce1363e69
     * @since 7.3.0
     */
    public function remove_svg_responsive_image_attr( string $sizes, $size, $image_src = null ) {
        $explode = explode( '.', $image_src );
        $extension = end( $explode );
        
        if( 'svg' === $extension ){
            $sizes = '';
        }

        return $sizes;
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