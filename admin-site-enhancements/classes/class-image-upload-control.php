<?php

namespace ASENHA\Classes;

use Imagick;
/**
 * Class for Image Upload Control module
 *
 * @since 6.9.5
 */
class Image_Upload_Control {
    public $png_is_transparent;

    /**
     * Per-request cache of PNG transparency checks, keyed by file path.
     *
     * @since 9.1.2
     *
     * @var array
     */
    private $png_transparency_cache;

    /**
     * Last readable PNG path seen by `image_editor_output_format` in this request.
     *
     * WordPress calls the filter with the source path first, then again with
     * an empty or not-yet-written destination when generating intermediate sizes.
     *
     * @since 9.1.2
     *
     * @var string
     */
    private $png_source_file;

    /**
     * Array storing the file names that were processed, as keys.
     *
     * @since 7.5.0
     * @link https://plugins.trac.wordpress.org/browser/fix-image-rotation/tags/2.2.2/includes/class-fix-image-rotation.php#L30
     *
     * @access private
     *
     * @var array
     */
    private $orientation_fixed;

    /**
     * Array storing the meta data of original files in case it
     * needs to be restored later.
     *
     * @since 7.5.0
     * @link https://plugins.trac.wordpress.org/browser/fix-image-rotation/tags/2.2.2/includes/class-fix-image-rotation.php#L42
     *
     * @access private
     *
     * @var array
     */
    private $previous_meta;

    /**
     * Constructor
     * @since 7.4.3
     */
    function __construct() {
        $this->png_is_transparent = false;
        $this->png_transparency_cache = array();
        $this->png_source_file = '';
        $this->orientation_fixed = array();
        $this->previous_meta = array();
    }

    /**
     * Handler for image uploads. Convert and resize images.
     *
     * @since 4.3.0
     */
    public function image_upload_handler( $upload ) {
        $options = get_option( ASENHA_SLUG_U, array() );
        if ( $this->is_client_side_processing_enabled( $options ) && $this->is_client_side_upload_request() ) {
            $disable_image_conversion = false;
            // wasm-vips does not handle BMP; keep server-side conversion on the original upload.
            if ( !$disable_image_conversion && isset( $upload['type'], $upload['file'] ) && ('image/bmp' === $upload['type'] || 'image/x-ms-bmp' === $upload['type']) && false === strpos( $upload['file'], '-nr.' ) ) {
                return $this->maybe_convert_image( 'bmp', $upload );
            }
            return $upload;
        }
        $applicable_mime_types = array(
            'image/bmp',
            'image/x-ms-bmp',
            'image/png',
            'image/jpeg',
            'image/jpg',
            'image/webp'
        );
        $disable_image_conversion = false;
        if ( in_array( $upload['type'], $applicable_mime_types ) ) {
            // Exlude from conversion and resizing images with filenames ending with '-nr', e.g. birds-nr.png
            if ( false !== strpos( $upload['file'], '-nr.' ) ) {
                return $upload;
            }
            // Image conversion is not disabled
            if ( !$disable_image_conversion ) {
                // Convert BMP
                if ( 'image/bmp' === $upload['type'] || 'image/x-ms-bmp' === $upload['type'] ) {
                    $upload = $this->maybe_convert_image( 'bmp', $upload );
                }
                // Convert PNG without transparency
                if ( 'image/png' === $upload['type'] ) {
                    $upload = $this->maybe_convert_image( 'png', $upload );
                }
            }
            // At this point, BMPs and non-transparent PNGs are already converted to JPGs, unless excluded with '-nr' suffix.
            // Let's perform resize operation as needed, i.e. if image dimension is larger than specified
            $mime_types_to_resize = array(
                'image/jpeg',
                'image/jpg',
                'image/png',
                'image/webp'
            );
            if ( !is_wp_error( $upload ) && in_array( $upload['type'], $mime_types_to_resize ) && filesize( $upload['file'] ) > 0 ) {
                // https://developer.wordpress.org/reference/classes/wp_image_editor/
                $wp_image_editor = wp_get_image_editor( $upload['file'] );
                if ( !is_wp_error( $wp_image_editor ) ) {
                    $image_size = $wp_image_editor->get_size();
                    $max_width = $options['image_max_width'];
                    $max_height = $options['image_max_height'];
                    $convert_to_jpg_quality = 82;
                    $did_resize = false;
                    // Check upload image's dimension and only resize if larger than the defined max dimension
                    if ( isset( $image_size['width'] ) && $image_size['width'] > $max_width || isset( $image_size['height'] ) && $image_size['height'] > $max_height ) {
                        $wp_image_editor->resize( $max_width, $max_height, false );
                        // false is for no cropping
                        $did_resize = true;
                    }
                    // Save only when a resize happened.
                    // Avoid re-encoding (and potentially recompressing) images that are already within max dimensions.
                    if ( $did_resize ) {
                        if ( 'image/jpg' === $upload['type'] || 'image/jpeg' === $upload['type'] ) {
                            $wp_image_editor->set_quality( $convert_to_jpg_quality );
                        }
                        $wp_image_editor->save( $upload['file'] );
                    }
                }
            }
        }
        return $upload;
    }

    /**
     * Convert BMP or PNG without transparency into JPG
     *
     * @since 4.3.0
     */
    public function maybe_convert_image( $file_extension, $upload ) {
        $image_object = false;
        // Get image object from uploaded BMP/PNG. imagecreatefromstring() accepts
        // any raster type GD supports, so a valid image with a mismatched
        // extension (common in demo imports) is still converted.
        if ( 'png' === $file_extension ) {
            $this->png_is_transparent = $this->png_has_transparency( $upload['file'] );
            // Do not convert PNG with alpha/transparency, or a file that could not be decoded.
            if ( $this->png_is_transparent ) {
                return $upload;
            }
        }
        if ( 'bmp' === $file_extension || 'png' === $file_extension ) {
            $image_object = $this->load_gd_image_from_file( $upload['file'] );
        }
        // Let's convert BMP and non-transparent PNG into JPG
        $converted_to_jpg = false;
        $keep_original_image = false;
        if ( is_object( $image_object ) || class_exists( 'Imagick' ) ) {
            $wp_uploads = wp_upload_dir();
            $old_filename = wp_basename( $upload['file'] );
            // Assign new, unique file name for the converted image
            // $new_filename    = wp_basename( str_ireplace( '.' . $file_extension, '.jpg', $old_filename ) );
            $new_filename = str_ireplace( '.' . $file_extension, '.jpg', $old_filename );
            $new_filename = wp_unique_filename( dirname( $upload['file'] ), $new_filename );
            // original image is always deleted in ASE Free
            $keep_original_image = false;
            $converted_to_jpg = false;
        }
        // Prefer GD when JPEG encode is available. Some custom PHP builds ship GD with PNG
        // support but without imagejpeg(); guard that case and fall back to Imagick below.
        if ( is_gd_image( $image_object ) && function_exists( 'imagejpeg' ) ) {
            // When conversion from BMP/PNG to JPG is successful using GD. Last parameter is JPG quality (0-100).
            if ( \imagejpeg( $image_object, $wp_uploads['path'] . '/' . $new_filename, 90 ) ) {
                $converted_to_jpg = true;
            }
        }
        $this->destroy_gd_image( $image_object );
        // Fall back to Imagick when GD image object creation failed, imagejpeg() is unavailable,
        // or GD JPEG encode returned false.
        if ( !$converted_to_jpg && class_exists( 'Imagick' ) ) {
            try {
                $imagick = new Imagick();
                $imagick->readImage( $upload['file'] );
                $imagick->setImageCompressionQuality( 90 );
                $imagick->setImageFormat( 'jpg' );
                // $imagick->setFormat( 'jpg' );
                if ( $imagick->writeImage( $wp_uploads['path'] . '/' . $new_filename ) ) {
                    $converted_to_jpg = true;
                }
                $imagick->clear();
                $imagick->destroy();
            } catch ( \Exception $e ) {
                $converted_to_jpg = false;
                if ( isset( $imagick ) && $imagick instanceof Imagick ) {
                    try {
                        $imagick->clear();
                        $imagick->destroy();
                    } catch ( \Exception $cleanup_exception ) {
                        unset($cleanup_exception);
                    }
                }
                unset($e);
            }
        }
        if ( $converted_to_jpg ) {
            // Delete original BMP / PNG
            if ( !$keep_original_image ) {
                unlink( $upload['file'] );
            }
            // Add converted JPG info into $upload
            $upload['file'] = $wp_uploads['path'] . '/' . $new_filename;
            $upload['url'] = $wp_uploads['url'] . '/' . $new_filename;
            $upload['type'] = 'image/jpeg';
        }
        return $upload;
    }

    /**
     * Load a raster image into a GD object from file bytes.
     *
     * Modeled on WP_Image_Editor_GD::load(). imagecreatefromstring() detects
     * the format from the binary signature, so a valid JPEG, GIF, WebP, or BMP
     * stored with a .png name still becomes an image. The silence matches core:
     * a non-image sideloaded by a demo importer must not emit a warning.
     *
     * @since 9.2.0
     *
     * @param string $file Absolute path to the image file.
     * @return GdImage|resource|false GD image on success, false otherwise.
     */
    private function load_gd_image_from_file( $file ) {
        if ( !is_string( $file ) || '' === $file || !is_file( $file ) || !is_readable( $file ) ) {
            return false;
        }
        if ( !function_exists( 'imagecreatefromstring' ) ) {
            return false;
        }
        if ( function_exists( 'wp_raise_memory_limit' ) ) {
            wp_raise_memory_limit( 'image' );
        }
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
        $contents = file_get_contents( $file );
        if ( !is_string( $contents ) || '' === $contents ) {
            return false;
        }
        // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- See method docblock. Matches WP_Image_Editor_GD::load().
        $image = @imagecreatefromstring( $contents );
        if ( !is_gd_image( $image ) ) {
            return false;
        }
        return $image;
    }

    /**
     * Free a GD image created while inspecting or converting an upload.
     *
     * @since 9.2.0
     *
     * @param mixed $image GD image, or false when loading failed.
     */
    private function destroy_gd_image( $image ) {
        if ( is_gd_image( $image ) && function_exists( 'imagedestroy' ) ) {
            imagedestroy( $image );
        }
    }

    /**
     * Whether a PNG file has at least one transparent / alpha pixel.
     *
     * Results are cached per file path for the current request because
     * `image_editor_output_format` can run once per intermediate size.
     *
     * A file that GD and Imagick cannot decode returns true. Callers treat
     * true as "do not convert", so an undecodable demo file is left as uploaded
     * instead of being treated as an opaque PNG.
     *
     * @since 9.1.2
     *
     * @param string $file Absolute path to the PNG file.
     * @return bool True when a transparent pixel is found, or the file cannot be decoded.
     */
    private function png_has_transparency( $file ) {
        if ( array_key_exists( $file, $this->png_transparency_cache ) ) {
            return $this->png_transparency_cache[$file];
        }
        $is_transparent = false;
        $image_object = $this->load_gd_image_from_file( $file );
        if ( is_gd_image( $image_object ) ) {
            $width = \imagesx( $image_object );
            $height = \imagesy( $image_object );
            // Run through pixels until a transparent pixel is found.
            if ( $width > 0 && $height > 0 ) {
                for ($x = 0; $x < $width; $x++) {
                    for ($y = 0; $y < $height; $y++) {
                        $pixel_color_index = \imagecolorat( $image_object, $x, $y );
                        $pixel_rgba = \imagecolorsforindex( $image_object, $pixel_color_index );
                        if ( $pixel_rgba['alpha'] > 0 ) {
                            // Alpha value range from 0 (completely opaque) to 127 (fully transparent).
                            // Ref: https://www.php.net/manual/en/function.imagecolorallocatealpha.php
                            $is_transparent = true;
                            break 2;
                        }
                    }
                }
            }
            $this->destroy_gd_image( $image_object );
        } elseif ( class_exists( 'Imagick' ) ) {
            try {
                $imagick = new Imagick();
                $imagick->readImage( $file );
                // Ref: https://stackoverflow.com/a/52295997
                // Ref: https://www.php.net/manual/en/imagick.getimagechannelrange.php
                $alpha_range = $imagick->getImageChannelRange( Imagick::CHANNEL_ALPHA );
                $is_transparent = $alpha_range['minima'] < $alpha_range['maxima'];
                $imagick->clear();
                $imagick->destroy();
            } catch ( \Exception $e ) {
                $is_transparent = true;
                if ( isset( $imagick ) && $imagick instanceof Imagick ) {
                    try {
                        $imagick->clear();
                        $imagick->destroy();
                    } catch ( \Exception $cleanup_exception ) {
                        unset($cleanup_exception);
                    }
                }
                unset($e);
            }
        } elseif ( is_file( $file ) ) {
            $is_transparent = true;
        }
        $this->png_transparency_cache[$file] = $is_transparent;
        return $is_transparent;
    }

    /**
     * Whether PNG should be mapped to JPEG in `image_editor_output_format`.
     *
     * WordPress calls this filter with the source path first, then again with
     * an empty filename (`make_subsize`) or a destination path that does not
     * exist yet. Remember the last readable PNG and inspect that file when
     * the current path cannot be read. Fail closed (keep PNG) when no source
     * is available — empty filename must not imply PNG→JPEG.
     *
     * @since 9.1.2
     *
     * @param string $filename  Path passed to the output format filter.
     * @param string $mime_type Source mime type passed to the filter.
     * @return bool
     */
    private function should_convert_png_to_jpeg( $filename, $mime_type ) {
        $inspect = $filename;
        $is_png = 'image/png' === $mime_type || '' !== $filename && 'png' === strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );
        if ( '' !== $filename && is_readable( $filename ) && $is_png ) {
            $this->png_source_file = $filename;
        } elseif ( '' === $filename || !is_readable( $filename ) ) {
            $inspect = $this->png_source_file;
        }
        if ( '' === $inspect || !is_readable( $inspect ) ) {
            return false;
        }
        $inspect_is_png = 'image/png' === $mime_type || 'png' === strtolower( pathinfo( $inspect, PATHINFO_EXTENSION ) );
        if ( $inspect_is_png && $this->png_has_transparency( $inspect ) ) {
            return false;
        }
        return true;
    }

    /**
     * Generate a WebP image from a PNG or JPEG file with GD.
     *
     * @since 6.9.11
     *
     * @param string $file                     Absolute path to the source image.
     * @param string $file_extension           Source extension: png, jpg, or jpeg.
     * @param string $webp_path                Destination path for the WebP file.
     * @param int    $webp_conversion_quality  WebP quality from 0 to 100.
     * @return bool True when the WebP file was written.
     */
    public function gd_generate_webp(
        $file,
        $file_extension,
        $webp_path,
        $webp_conversion_quality
    ) {
        $image_object = $this->load_gd_image_from_file( $file );
        if ( !is_gd_image( $image_object ) ) {
            return false;
        }
        if ( 'png' == $file_extension && $this->png_is_transparent && function_exists( 'imagepalettetotruecolor' ) ) {
            \imagepalettetotruecolor( $image_object );
        }
        $created = false;
        // Second parameter is file path, last parameter is WebP quality (0-100).
        if ( function_exists( 'imagewebp' ) ) {
            $created = \imagewebp( $image_object, $webp_path, $webp_conversion_quality );
        }
        $this->destroy_gd_image( $image_object );
        return (bool) $created;
    }

    /**
     * Checks the filename before it is uploaded to WordPress and
     * runs the fix_image_orientation function in case its needed.
     *
     * @since 7.5.0
     * @link https://plugins.trac.wordpress.org/browser/fix-image-rotation/tags/2.2.2/includes/class-fix-image-rotation.php#L172
     *
     * @access public
     *
     * @hook wp_handle_upload_prefilter
     *
     * @param array $file An array of data for a single file.
     *
     * @return array An array of data for a single file.
     */
    public function prefilter_maybe_fix_image_orientation( $file ) {
        if ( $this->is_client_side_processing_enabled() && $this->is_client_side_upload_request() ) {
            return $file;
        }
        // Get the file extension
        // $suffix = substr( $file['name'], strrpos( $file['name'], '.', -1 ) + 1 );
        $suffix = pathinfo( $file['name'], PATHINFO_EXTENSION );
        if ( in_array( strtolower( $suffix ), array('jpg', 'jpeg', 'tiff'), true ) ) {
            $this->fix_image_orientation( $file['tmp_name'] );
        }
        return $file;
    }

    /**
     * Checks the filename before it is uploaded to WordPress and
     * runs the fix_image_orientation function in case its needed.
     *
     * @since 7.5.0
     * @link https://plugins.trac.wordpress.org/browser/fix-image-rotation/tags/2.2.2/includes/class-fix-image-rotation.php#L150
     *
     * @access public
     *
     * @hook wp_handle_upload
     *
     * @param array $file {
     *    Array of upload data.
     *
     *     @type string $file Filename of the newly-uploaded file.
     *     @type string $url  URL of the uploaded file.
     *     @type string $type File type.
     * }
     *
     * @return array Array of upload data.
     */
    public function maybe_fix_image_orientation( $file ) {
        if ( $this->is_client_side_processing_enabled() && $this->is_client_side_upload_request() ) {
            return $file;
        }
        $suffix = substr( $file['file'], strrpos( $file['file'], '.', -1 ) + 1 );
        if ( in_array( strtolower( $suffix ), array('jpg', 'jpeg', 'tiff'), true ) ) {
            $this->fix_image_orientation( $file['file'] );
        }
        return $file;
    }

    /**
     * Fixes the orientation of the image based on exif data
     *
     * @since 7.5.0
     * @link https://plugins.trac.wordpress.org/browser/fix-image-rotation/tags/2.2.2/includes/class-fix-image-rotation.php#L191
     *
     * @access public
     *
     * @param string $file Path of the file.
     *
     * @return void
     */
    public function fix_image_orientation( $file ) {
        if ( !isset( $this->orientation_fixed[$file] ) ) {
            $exif = @exif_read_data( $file );
            if ( isset( $exif ) && isset( $exif['Orientation'] ) && $exif['Orientation'] > 1 ) {
                // Need it so that image editors are available to us.
                // include_once ABSPATH . 'wp-admin/includes/image-edit.php';
                // Calculate the operations we need to perform on the image.
                $operations = $this->calculate_flip_and_rotate( $file, $exif );
                if ( false !== $operations ) {
                    // Lets flip flop and rotate the image as needed.
                    $this->do_flip_and_rotate( $file, $operations );
                }
            }
        }
    }

    /**
     * Calculate the flips and rotations image will need to do to fix its orientation.
     *
     * @since 7.5.0
     * @link https://plugins.trac.wordpress.org/browser/fix-image-rotation/tags/2.2.2/includes/class-fix-image-rotation.php#L225
     *
     * @access private
     *
     * @param string $file Path of the file.
     *
     * @param array  $exif Exif data of the image.
     *
     * @return array|bool Array of operations to be performed on the image,
     *                    false if no operations are needed.
     */
    private function calculate_flip_and_rotate( $file, $exif ) {
        $rotator = false;
        $flipper = false;
        $orientation = 0;
        // Lets switch to the orientation defined in the exif data.
        switch ( $exif['Orientation'] ) {
            case 1:
                // We don't want to fix an already correct image :).
                $this->orientation_fixed[$file] = true;
                return false;
            case 2:
                $flipper = array(false, true);
                break;
            case 3:
                $orientation = -180;
                $rotator = true;
                break;
            case 4:
                $flipper = array(true, false);
                break;
            case 5:
                $orientation = -90;
                $rotator = true;
                $flipper = array(false, true);
                break;
            case 6:
                $orientation = -90;
                $rotator = true;
                break;
            case 7:
                $orientation = -270;
                $rotator = true;
                $flipper = array(false, true);
                break;
            case 8:
            case 9:
                $orientation = -270;
                $rotator = true;
                break;
            default:
                $orientation = 0;
                $rotator = true;
                break;
        }
        return compact( 'orientation', 'rotator', 'flipper' );
    }

    /**
     * Flips and rotates the image based on the parameters provided.
     *
     * @since 7.5.0
     * @link https://plugins.trac.wordpress.org/browser/fix-image-rotation/tags/2.2.2/includes/class-fix-image-rotation.php#L299
     *
     * @access private
     *
     * @param string $file Path of the file.
     *
     * @param array  $operations {
     *      Array of operations to be performed on the image.
     *
     *      @type bool       $rotator Whether to rotate the image or not.
     *      @type int        $orientation Amount of rotation to be performed in degrees.
     *      @type array|bool $flipper {
     *          Whether to flip the image or not, false if no flipping needed.
     *
     *          @type bool $0 Flip along Horizontal Axis.
     *          @type bool $1 Flip along Vertical Axis.
     *      }
     * }
     *
     * @return bool Returns true if operations were successful, false otherwise.
     */
    private function do_flip_and_rotate( $file, $operations ) {
        $editor = wp_get_image_editor( $file );
        // If GD Library is being used, then we need to store metadata to restore later.
        if ( 'WP_Image_Editor_GD' === get_class( $editor ) ) {
            include_once ABSPATH . 'wp-admin/includes/image.php';
            $this->previous_meta[$file] = wp_read_image_metadata( $file );
        }
        if ( !is_wp_error( $editor ) ) {
            // Lets rotate and flip the image based on exif orientation.
            if ( true === $operations['rotator'] ) {
                $editor->rotate( $operations['orientation'] );
            }
            if ( false !== $operations['flipper'] ) {
                $editor->flip( $operations['flipper'][0], $operations['flipper'][1] );
            }
            $editor->save( $file );
            $this->orientation_fixed[$file] = true;
            add_filter(
                'wp_read_image_metadata',
                array($this, 'restore_meta_data'),
                10,
                2
            );
            return true;
        }
        return false;
    }

    /**
     * Restores the meta data of the image after being processed.
     *
     * WordPress' Imagick Library does not need this, but GD library
     * removes metadata from the image upon rotation or flip so this
     * method restores those values.
     *
     * @since 7.5.0
     * @link https://plugins.trac.wordpress.org/browser/fix-image-rotation/tags/2.2.2/includes/class-fix-image-rotation.php#L341
     *
     * @hook wp_read_image_metadata
     *
     * @param array  $meta Image meta data.
     * @param string $file Path to image file.
     *
     * @return array Image meta data.
     */
    public function restore_meta_data( $meta, $file ) {
        if ( isset( $this->previous_meta[$file] ) ) {
            $meta = $this->previous_meta[$file];
            // Setting the Orientation meta to the new value after fixing the rotation.
            $meta['orientation'] = 1;
            return $meta;
        }
        return $meta;
    }

    /**
     * Whether browser-based libvips processing is enabled for Image Upload Control.
     *
     * Absent option defaults to true on WordPress 7.1+.
     *
     * @since 9.2.0
     *
     * @param array|null $options Optional cached plugin options.
     * @return bool
     */
    public function is_client_side_processing_enabled( $options = null ) {
        if ( !function_exists( 'wp_is_client_side_media_processing_enabled' ) ) {
            return false;
        }
        if ( null === $options ) {
            $options = get_option( ASENHA_SLUG_U, array() );
        }
        if ( !is_array( $options ) || !array_key_exists( 'image_upload_control_client_side_processing', $options ) ) {
            return true;
        }
        return (bool) $options['image_upload_control_client_side_processing'];
    }

    /**
     * Disable Core client-side media processing when the ASE checkbox is off.
     *
     * @since 9.2.0
     *
     * @param bool $enabled Whether Core client-side processing is enabled.
     * @return bool
     */
    public function maybe_disable_client_side_media_processing( $enabled ) {
        if ( !$this->is_client_side_processing_enabled() ) {
            return false;
        }
        return $enabled;
    }

    /**
     * Map ASE max dimensions onto Core's longest-side threshold for wasm-vips.
     *
     * @since 9.2.0
     *
     * @param int|bool $threshold Current big image size threshold.
     * @return int|bool
     */
    public function maybe_set_big_image_size_threshold( $threshold ) {
        if ( !$this->is_client_side_processing_enabled() ) {
            return $threshold;
        }
        if ( !$this->should_map_client_side_filters() ) {
            return $threshold;
        }
        $options = get_option( ASENHA_SLUG_U, array() );
        $max_width = ( isset( $options['image_max_width'] ) ? intval( $options['image_max_width'] ) : 1920 );
        $max_height = ( isset( $options['image_max_height'] ) ? intval( $options['image_max_height'] ) : 1920 );
        if ( $max_width < 1 ) {
            $max_width = 1920;
        }
        if ( $max_height < 1 ) {
            $max_height = 1920;
        }
        return max( $max_width, $max_height );
    }

    /**
     * Map ASE conversion settings onto Core's output format filter for wasm-vips.
     *
     * @since 9.2.0
     *
     * @param array  $formats   Mime-type conversion map.
     * @param string $filename  Path to the image being converted, if known.
     * @param string $mime_type Source mime type, if known.
     * @return array
     */
    public function maybe_set_image_editor_output_format( $formats, $filename = '', $mime_type = '' ) {
        if ( !is_array( $formats ) ) {
            $formats = array();
        }
        if ( !$this->is_client_side_processing_enabled() ) {
            return $formats;
        }
        if ( !$this->should_map_client_side_filters() ) {
            return $formats;
        }
        $options = get_option( ASENHA_SLUG_U, array() );
        $disable_image_conversion = false;
        if ( $disable_image_conversion ) {
            return $formats;
        }
        if ( $this->should_convert_png_to_jpeg( (string) $filename, (string) $mime_type ) ) {
            $formats['image/png'] = 'image/jpeg';
        }
        return $formats;
    }

    /**
     * Map ASE quality settings onto Core's editor quality filter for wasm-vips.
     *
     * @since 9.2.0
     *
     * @param int    $quality    Quality on a 1-100 scale.
     * @param string $mime_type  Output mime type.
     * @param array  $size       Size data from Core.
     * @return int
     */
    public function maybe_set_editor_quality( $quality, $mime_type = '', $size = array() ) {
        if ( !$this->is_client_side_processing_enabled() ) {
            return $quality;
        }
        return $quality;
    }

    /**
     * Map ASE JPEG quality onto the legacy jpeg_quality filter.
     *
     * @since 9.2.0
     *
     * @param int $quality Quality on a 1-100 scale.
     * @return int
     */
    public function maybe_set_jpeg_quality( $quality ) {
        return $this->maybe_set_editor_quality( $quality, 'image/jpeg' );
    }

    /**
     * Delete Core's oversized original after client-side finalize.
     *
     * Matches Image Upload Control's "delete originally uploaded files" behavior.
     *
     * @since 9.2.0
     *
     * @param array  $metadata      Attachment metadata.
     * @param int    $attachment_id Attachment ID.
     * @param string $context       Filter context: create or update.
     * @return array
     */
    public function maybe_delete_original_image_after_client_side_processing( $metadata, $attachment_id, $context = 'create' ) {
        if ( 'update' !== $context ) {
            return $metadata;
        }
        if ( !$this->is_client_side_processing_enabled() ) {
            return $metadata;
        }
        if ( !$this->is_client_side_upload_request() ) {
            return $metadata;
        }
        if ( empty( $metadata['original_image'] ) ) {
            return $metadata;
        }
        $original_basename = $metadata['original_image'];
        if ( false !== strpos( $original_basename, '-nr.' ) ) {
            return $metadata;
        }
        $attached_file = get_attached_file( $attachment_id );
        if ( $attached_file && false !== strpos( $attached_file, '-nr.' ) ) {
            return $metadata;
        }
        $original_path = wp_get_original_image_path( $attachment_id );
        if ( $original_path && file_exists( $original_path ) ) {
            wp_delete_file( $original_path );
        }
        unset($metadata['original_image']);
        return $metadata;
    }

    /**
     * Whether ASE settings should be mapped onto Core client-side filters.
     *
     * @since 9.2.0
     *
     * @param array|null $options Optional cached plugin options.
     * @return bool
     */
    private function should_map_client_side_filters( $options = null ) {
        return true;
    }

    /**
     * Detect a Core client-side media REST request.
     *
     * @since 9.2.0
     *
     * @return bool
     */
    private function is_client_side_upload_request() {
        if ( !defined( 'REST_REQUEST' ) || !REST_REQUEST ) {
            return false;
        }
        $generate_sub_sizes = null;
        if ( isset( $_POST['generate_sub_sizes'] ) ) {
            // phpcs:ignore WordPress.Security.NonceVerification.Missing
            $generate_sub_sizes = wp_unslash( $_POST['generate_sub_sizes'] );
            // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        } elseif ( isset( $_REQUEST['generate_sub_sizes'] ) ) {
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $generate_sub_sizes = wp_unslash( $_REQUEST['generate_sub_sizes'] );
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        }
        if ( null !== $generate_sub_sizes && false === rest_sanitize_boolean( $generate_sub_sizes ) ) {
            return true;
        }
        $route = '';
        if ( isset( $_GET['rest_route'] ) ) {
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $route = (string) wp_unslash( $_GET['rest_route'] );
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        } elseif ( isset( $_SERVER['REQUEST_URI'] ) ) {
            $route = (string) wp_unslash( $_SERVER['REQUEST_URI'] );
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        }
        if ( '' !== $route && preg_match( '#/media/\\d+/(sideload|finalize)(?:/|\\?|$)#', $route ) ) {
            return true;
        }
        return false;
    }

}
