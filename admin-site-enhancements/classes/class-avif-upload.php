<?php

namespace ASENHA\Classes;

/**
 * Class for AVIF Upload module
 *
 * @since 6.9.5
 */
class AVIF_Upload {

    /**
     * Add AVIF mime type to list of mime types
     *
     * @since 5.7.0
     */
    public function add_avif_mime_type( $wp_get_mime_types ) {

        $wp_get_mime_types['avif'] = 'image/avif';
        return $wp_get_mime_types;

    }

    /**
     * Add AVIF mime type to allowed mime types
     *
     * @since 5.7.0
     */
    public function allow_avif_mime_type_upload( $mimes ) {

        $mimes['avif'] = 'image/avif';
        return $mimes;

    }

    /**
     * Add AVIF to mapping of mime types to their respective extensions
     *
     * @since 5.7.0
     */
    public function add_avif_mime_type_to_exts( $mime_to_ext ) {

        $mime_to_ext['image/avif'] = 'avif';
        return $mime_to_ext;

    }
    
    /**
     * Add correct dimension for AVIF images
     * 
     * @link https://plugins.trac.wordpress.org/browser/avif-support/trunk/includes/AvifSupport.php#L104
     * @since 5.7.0
     */
    public function add_avif_image_dimension( $metadata, $attachment_id, $context ) {
                
        if ( empty( $metadata ) ) {
            return $metadata;
        }
        
        $attachment_post = get_post( $attachment_id );
        
        if ( ! $attachment_post || is_wp_error( $attachment_post ) ) {
            return $metadata;
        }
        
        if ( 'image/avif' !== $attachment_post->post_mime_type ) {
            return $metadata;
        }
        
        // Fix width and height

        if ( 
            ( ! empty( $metadata['width'] ) 
              && ( 0 !== $metadata['width'] ) ) 
              && ( ! empty( $metadata['height'] ) 
              && 0 !== $metadata['height'] ) 
            ) {
            return $metadata;
        }
        
        $file = get_attached_file( $attachment_id );
        
        if ( ! $file ) {
            return $metadata;   
        }
        
        if ( empty( $metadata['width'] ) ) {
            $metadata['width'] = 0;
        }

        if ( empty( $metadata['height'] ) ) {
            $metadata['height'] = 0;
        }
        
        if ( empty( $metadata['file'] ) ) {
            $metadata['file'] = _wp_relative_upload_path( $file );
        }
        
        if ( empty( $metadata['sizes'] ) ) {
            $metadata['sizes'] = array();
        }
        
        $img_size = wp_getimagesize( $file );

        // Legacy PHP Version, return false, fake it till manual.
        if ( empty( $img_size ) ) {
            $img_size = array(
                0      => 0,
                1      => 0,
                2      => 19,
                3      => 'width="0" height="0"',
                'mime' => 'image/avif',
            );
        }

        if ( is_array( $img_size ) && ( 0 !== $img_size[0] ) && ( 0 !== $img_size[1] ) ) {
            // Do nothing, we have what we need
        } else {

            // Only hand-parse dimensions from structurally valid AVIF files.
            if ( ! $this->has_valid_avif_ftyp( $file ) ) {
                return $metadata;
            }

            // Manually get width and height
            $binary_string = file_get_contents( $file );
            $ispe_pos      = strpos( $binary_string, 'ispe' );

            if ( false === $ispe_pos ) {
                // Corrupted Image.
                return false;
            }

            $dim_start_pos = $ispe_pos + 8;
            $dim_bin       = substr( $binary_string, $dim_start_pos, 8 );
            $width         = hexdec( bin2hex( substr( $dim_bin, 0, 4 ) ) );
            $height        = hexdec( bin2hex( substr( $dim_bin, 4, 8 ) ) );

            if ( $width && $height && is_numeric( $width ) && is_numeric( $height ) ) {
                $img_size[0] = absint( $width );
                $img_size[1] = absint( $height );
            }

            // wp_getimagesize() failed, try with Imagick
            // if ( extension_loaded( 'imagick' ) && class_exists( 'Imagick' ) ) {
            //  try {
            //      $imagick      = new \Imagick( $file );
            //      $img_dim     = $imagick->getImageGeometry();
            //      $img_size[0] = $img_dim['width'];
            //      $img_size[1] = $img_dim['height'];

            //      $imagick->clear();
            //  } catch ( \Exception $e ) {
            //      // Do nothing for now.
            //  }
            // }

        }
        
        if ( ! $img_size ) {
            $avif_specs = false;
        } else {
            $file_size = filesize( $file );
            $avif_specs = array(
                'width'       => $img_size[0],
                'height'      => $img_size[1],
                'mime'        => $img_size['mime'],
                'dimension'   => $img_size[0] . 'x' . $img_size[1],
                'ext'         => str_replace( 'image/', '', $img_size['mime'] ),
                'size'        => $file_size,
                'size_format' => size_format( $file_size ),
            );
        }
        
        if ( is_wp_error( $avif_specs ) || ! $avif_specs ) {
            return $metadata;
        }
        
        $metadata['width'] = $avif_specs['width'];
        $metadata['height'] = $avif_specs['height'];
        
        return $metadata;

        // Fix scaled version of the image
                
    }
    
    /**
     * Make sure AVIF files are displayable in the browser
     * 
     * @since 5.7.0
     */
    public function make_avif_displayable( $result, $path ) {
        if ( str_ends_with( $path, '.avif' ) ) {
            return true;
        }
        
        return $result;
    }

    /**
     * Allow AVIF uploads even when GD/Imagick cannot generate sub-sizes.
     *
     * WordPress 6.8+ blocks those uploads by default. This module's purpose
     * is still to accept the original file (browsers can display it).
     *
     * @since 9.1.2
     *
     * @param bool        $prevent   Whether to prevent uploads of unsupported image types.
     * @param string|null $mime_type The mime type of the file being uploaded (if available).
     * @return bool
     */
    public function allow_avif_without_editor_support( $prevent, $mime_type ) {
        if ( 'image/avif' === $mime_type ) {
            return false;
        }
        return $prevent;
    }

    /**
     * Clear Plupload's client-side AVIF block (classic Media Library + media-new.php).
     *
     * media_upload_form() calls wp_prevent_unsupported_mime_type_uploads with
     * $mime_type = null, so the REST-oriented filter above is not enough there.
     *
     * @since 9.1.2
     *
     * @param array $settings Plupload settings.
     * @return array
     */
    public function allow_avif_in_plupload( $settings ) {
        $settings['avif_upload_error'] = false;
        return $settings;
    }

    /**
     * Check whether a file is a structurally valid AVIF (ISO-BMFF) via its ftyp box.
     *
     * A valid file must begin with a box whose size (bytes 0-3, big-endian)
     * fits the file, whose type (bytes 4-7) is 'ftyp', and which declares the
     * 'avif' or 'avis' brand as major brand (bytes 8-11) or a compatible brand
     * (bytes 16+, within the box). Only the first 64 bytes are read, so spoof
     * files are rejected cheaply and no whole file is loaded into memory.
     *
     * @since 9.1.2
     *
     * @param string $file Absolute path to the file to check.
     * @return bool True when the file declares a valid AVIF ftyp box.
     */
    private function has_valid_avif_ftyp( $file ) {

        if ( empty( $file ) || ! is_readable( $file ) ) {
            return false;
        }

        $file_size = filesize( $file );
        $header    = file_get_contents( $file, false, null, 0, 64 ); // bounded read

        if ( false === $header || false === $file_size || strlen( $header ) < 16 ) {
            return false;
        }

        // First box must be ftyp, and its declared size must be plausible.
        // The size check also kills HTML-comment polyglots such as
        // "<!--ftypavif--><script>..." ("<!--" as uint32 dwarfs any upload).
        $box_size = unpack( 'N', substr( $header, 0, 4 ) )[1];
        if ( $box_size < 16 || $box_size > $file_size ) {
            return false;
        }

        if ( 'ftyp' !== substr( $header, 4, 4 ) ) {
            return false;
        }

        // Major brand, then compatible brands within the ftyp box (bounded by header).
        $brands = array( substr( $header, 8, 4 ) );
        for ( $i = 16; $i + 4 <= min( $box_size, 64 ); $i += 4 ) {
            $brands[] = substr( $header, $i, 4 );
        }

        return (bool) array_intersect( $brands, array( 'avif', 'avis' ) );

    }

    /**
     * Handle rare scenarios where exif and fileinfo fail to detect AVIF
     * 
     * @since 5.7.0
     */
    public function handle_exif_and_fileinfo_fail( $wp_check_filetype_and_ext, $file, $filename, $mimes, $real_mime ) {

        // AVIF is properly handled, no need to do anything else
        if ( $wp_check_filetype_and_ext['ext'] && $wp_check_filetype_and_ext['type'] ) {
            return $wp_check_filetype_and_ext;
        }

        // Not an .avif file, no need to do anything else
        if ( ! str_ends_with( $filename, '.avif' ) ) {
            return $wp_check_filetype_and_ext;
        }

        // Override core's rejection only for structurally valid AVIF files.
        if ( ! $this->has_valid_avif_ftyp( $file ) ) {
            return $wp_check_filetype_and_ext; // leave core's failure untouched
        }

        $wp_check_filetype_and_ext['type'] = 'image/avif';
        $wp_check_filetype_and_ext['ext']  = 'avif';

        return $wp_check_filetype_and_ext;

    }
        
}