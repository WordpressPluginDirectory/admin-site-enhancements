<?php

namespace ASENHA\Classes;

use WP_Admin_Bar;
use WC_Admin_Duplicate_Product;
/**
 * Class for Content Duplication module
 *
 * @since 6.9.5
 */
class Content_Duplication {
    public $inapplicable_post_types = array(
        'attachment',
        // public
        'asenha_code_snippet',
        // public, ASE
        'asenha_cpt',
        // non-public, ASE
        'asenha_ctax',
        // non-public, ASE
        'asenha_cfgroup',
        // non-public, ASE
        'asenha_options_page',
        // non-public, ASE
        'options_page_config',
        // non-public, ASE
        'revision',
        // non-public
        'nav_menu_item',
        // non-public
        'custom_css',
        // non-public
        'customize_changeset',
        // non-public
        'oembed_cache',
        // non-public
        'user_request',
        // non-public
        'wp_block',
        // non-public
        'wp_template',
        // non-public
        'wp_template_part',
        // non-public
        'wp_global_styles',
        // non-public
        'wp_navigation',
        // non-public
        'wp_font_family',
        // non-public
        'wp_font_face',
        // non-public
        'patterns_ai_data',
        // non-public
        'product_variation',
        // non-public, WooCommerce
        'shop_order',
        // non-public, WooCommerce
        'shop_order_refund',
        // non-public, WooCommerce
        'shop_coupon',
        // non-public, WooCommerce
        'shop_order_placehold',
        // non-public, WooCommerce
        // 'elementor_library', // public, Elementor -- not excluded as data is stored only in wp_posts and wp_postmeta
        // 'e-landing-page', // public, Elementor -- not excluded as data is stored only in wp_posts and wp_postmeta
        'elementor_snippet',
        // non-public, Elementor
        'elementor_font',
        // non-public, Elementor
        'elementor_icons',
        // non-public, Elementor
        'sfwd-assignment',
        // public, LearnDash
        'sfwd-certificates',
        // public, LearnDash
        'sfwd-courses',
        // public, LearnDash
        'sfwd-lessons',
        // public, LearnDash
        'sfwd-quiz',
        // public, LearnDash
        'sfwd-essays',
        // public, LearnDash
        'sfwd-topic',
        // public, LearnDash
        'sfwd-transactions',
        // public, LearnDash
        'sfwd-question',
        // non-public, LearnDash
        'ld-exam',
        // non-public, LearnDash
        'wfacp_checkout',
        // public, FunnelKit Automation
        'wffn_oty',
        // public, FunnelKit Funnel Builder
        'wffn_optin',
        // public, FunnelKit Funnel Builder
        'wffn_landing',
        // public, FunnelKit Funnel Builder
        'wffn_ty',
        // public, FunnelKit Funnel Builder
        'kadence_form',
        // non-public, Kadence Blocks
        'kadence_header',
        // non-public, Kadence Blocks
        'kadence_navigation',
        // non-public, Kadence Blocks
        'kadence_lottie',
        // non-public, Kadence Blocks
        'kadence_vector',
        // non-public, Kadence Blocks
        'kb_icon',
    );

    /**
     * Enable duplication of pages, posts and custom posts
     *
     * @since 1.0.0
     */
    public function duplicate_content() {
        $original_post_id = intval( sanitize_text_field( $_REQUEST['post'] ) );
        $allow_duplication = false;
        if ( current_user_can( 'edit_post', $original_post_id ) ) {
            $allow_duplication = true;
        }
        $nonce = sanitize_text_field( $_REQUEST['nonce'] );
        if ( wp_verify_nonce( $nonce, 'asenha-duplicate-' . $original_post_id ) && $allow_duplication ) {
            $original_post = get_post( $original_post_id );
            $post_type = $original_post->post_type;
            $common_methods = new Common_Methods();
            $is_woocommerce_active = $common_methods->is_woocommerce_active();
            if ( 'product' != $post_type || 'product' == $post_type && !$is_woocommerce_active ) {
                // Set some attributes for the duplicate post
                $new_post_title_suffix = __( 'DUPLICATE', 'admin-site-enhancements' );
                $new_post_status = 'draft';
                $current_user = wp_get_current_user();
                $new_post_author_id = $current_user->ID;
                // Create the duplicate post and store the ID
                $args = array(
                    'comment_status' => $original_post->comment_status,
                    'ping_status'    => $original_post->ping_status,
                    'post_author'    => $new_post_author_id,
                    'post_content'   => str_replace( '\\', "\\\\", $original_post->post_content ),
                    'post_excerpt'   => $original_post->post_excerpt,
                    'post_parent'    => $original_post->post_parent,
                    'post_password'  => $original_post->post_password,
                    'post_status'    => $new_post_status,
                    'post_title'     => $original_post->post_title . ' (' . $new_post_title_suffix . ')',
                    'post_type'      => $original_post->post_type,
                    'to_ping'        => $original_post->to_ping,
                    'menu_order'     => $original_post->menu_order,
                );
                $new_post_id = wp_insert_post( $args );
                // Copy over the taxonomies
                $original_taxonomies = get_object_taxonomies( $original_post->post_type );
                if ( !empty( $original_taxonomies ) && is_array( $original_taxonomies ) ) {
                    foreach ( $original_taxonomies as $taxonomy ) {
                        $original_post_terms = wp_get_object_terms( $original_post_id, $taxonomy, array(
                            'fields' => 'slugs',
                        ) );
                        wp_set_object_terms(
                            $new_post_id,
                            $original_post_terms,
                            $taxonomy,
                            false
                        );
                    }
                }
                $excluded_post_meta_keys = array();
                // Copy over the post meta
                $original_post_metas = get_post_meta( $original_post_id );
                // all meta keys and the corresponding values
                if ( !empty( $original_post_metas ) ) {
                    foreach ( $original_post_metas as $meta_key => $meta_values ) {
                        if ( !in_array( $meta_key, $excluded_post_meta_keys ) ) {
                            // Only copy over post meta that are not ASE custom fields. We will handle that later.
                            foreach ( $meta_values as $meta_value ) {
                                update_post_meta( $new_post_id, $meta_key, wp_slash( maybe_unserialize( $meta_value ) ) );
                            }
                        }
                    }
                }
                // Prevent Elementor plugin placeholder.png URLs from being sideloaded into the Media Library.
                $this->normalize_elementor_placeholder_media_in_duplicate( $new_post_id );
            }
            $options = get_option( ASENHA_SLUG_U, array() );
            $duplication_redirect_destination = ( isset( $options['duplication_redirect_destination'] ) ? $options['duplication_redirect_destination'] : 'edit' );
            switch ( $duplication_redirect_destination ) {
                case 'edit':
                    // Redirect to edit screen of the duplicate post
                    wp_redirect( admin_url( 'post.php?action=edit&post=' . $new_post_id ) );
                    break;
                case 'list':
                    // Redirect to list table of the corresponding post type of original post
                    if ( 'post' == $post_type ) {
                        wp_redirect( admin_url( 'edit.php' ) );
                    } else {
                        wp_redirect( admin_url( 'edit.php?post_type=' . $post_type ) );
                    }
                    break;
            }
        } else {
            wp_die( 'You do not have permission to perform this action.' );
        }
    }

    /** 
     * Add row action link to perform duplication in page/post list tables
     *
     * @since 1.0.0
     */
    public function add_duplication_action_link( $actions, $post ) {
        $duplication_link_locations = $this->get_duplication_link_locations();
        $allow_duplication = $this->is_user_allowed_to_duplicate_content( $post );
        $post_type = $post->post_type;
        $post_type_is_duplicable = $this->is_post_type_duplicable( $post_type );
        if ( $allow_duplication && $post_type_is_duplicable ) {
            // Not WooCommerce product
            if ( in_array( 'post-action', $duplication_link_locations ) ) {
                $actions['asenha-duplicate'] = '<a href="admin.php?action=duplicate_content&amp;post=' . $post->ID . '&amp;nonce=' . wp_create_nonce( 'asenha-duplicate-' . $post->ID ) . '" title="' . __( 'Duplicate this as draft', 'admin-site-enhancements' ) . '">' . __( 'Duplicate', 'admin-site-enhancements' ) . '</a>';
            }
        }
        return $actions;
    }

    /**
     * Add admin bar duplicate link
     * 
     * @since 6.3.0
     */
    public function add_admin_bar_duplication_link( WP_Admin_Bar $wp_admin_bar ) {
        global $pagenow, $post;
        $duplication_link_locations = $this->get_duplication_link_locations();
        $allow_duplication = $this->is_user_allowed_to_duplicate_content( $post );
        if ( is_object( $post ) ) {
            if ( property_exists( $post, 'post_type' ) ) {
                $post_type = $post->post_type;
                $inapplicable_post_types = array('attachment');
                $post_type_is_duplicable = $this->is_post_type_duplicable( $post_type );
                if ( $allow_duplication && $post_type_is_duplicable ) {
                    if ( 'post.php' == $pagenow && !in_array( $post_type, $inapplicable_post_types ) || is_singular() || is_front_page() && !is_home() ) {
                        if ( in_array( 'admin-bar', $duplication_link_locations ) ) {
                            $common_methods = new Common_Methods();
                            $post_type_singular_label = $common_methods->get_post_type_singular_label( $post );
                            $post_id = 0;
                            if ( is_front_page() && !is_home() ) {
                                $post_id = get_option( 'page_on_front' );
                            } else {
                                // if ( property_exists( $post, 'ID' ) ) {
                                $post_id = (int) $post->ID;
                                // } else {
                                //     $post_id = 0;
                                // }
                            }
                            if ( $post_id > 0 ) {
                                $wp_admin_bar->add_menu( array(
                                    'id'     => 'duplicate-content',
                                    'parent' => null,
                                    'group'  => null,
                                    'title'  => sprintf( 
                                        /* translators: %s is the singular label for the post type */
                                        __( 'Duplicate %s', 'admin-site-enhancements' ),
                                        $post_type_singular_label
                                     ),
                                    'href'   => admin_url( 'admin.php?action=duplicate_content&amp;post=' . $post_id . '&amp;nonce=' . wp_create_nonce( 'asenha-duplicate-' . $post_id ) ),
                                ) );
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Check at which locations duplication link should enabled
     * 
     * @since 6.9.3
     */
    public function get_duplication_link_locations() {
        $options = get_option( ASENHA_SLUG_U, array() );
        $duplication_link_locations = array('post-action', 'admin-bar');
        return $duplication_link_locations;
    }

    /**
     * Check if a user role is allowed to duplicate content
     * 
     * @since 6.9.3
     */
    public function is_user_allowed_to_duplicate_content( $post = null ) {
        $allow_duplication = false;
        if ( is_object( $post ) ) {
            if ( property_exists( $post, 'ID' ) ) {
                if ( current_user_can( 'edit_post', $post->ID ) ) {
                    $allow_duplication = true;
                }
            }
        }
        return $allow_duplication;
    }

    /**
     * Check if the post type can be duplicated
     * 
     * @since 6.9.7
     */
    public function is_post_type_duplicable( $post_type ) {
        $common_methods = new Common_Methods();
        $asenha_public_post_types = $common_methods->get_public_post_type_slugs();
        $inapplicable_post_types = $this->inapplicable_post_types;
        $is_woocommerce_active = $common_methods->is_woocommerce_active();
        $options = get_option( ASENHA_SLUG_U, array() );
        $enable_duplication_on_post_types_type = 'only-on';
        $asenha_public_post_types_slugs = array();
        if ( is_array( $asenha_public_post_types ) ) {
            foreach ( $asenha_public_post_types as $post_type_slug => $post_type_label ) {
                // e.g. $post_type_slug is post,
                $asenha_public_post_types_slugs[] = $post_type_slug;
            }
        }
        $enable_duplication_on_post_types = ( isset( $options['enable_duplication_on_post_types'] ) ? $options['enable_duplication_on_post_types'] : array() );
        $post_types_for_enable_duplication = array();
        if ( !empty( $enable_duplication_on_post_types ) && count( $enable_duplication_on_post_types ) > 0 ) {
            foreach ( $enable_duplication_on_post_types as $post_type_slug => $is_duplication_enabled ) {
                if ( $is_duplication_enabled ) {
                    $post_types_for_enable_duplication[] = $post_type_slug;
                }
            }
        } else {
            $post_types_for_enable_duplication = $asenha_public_post_types_slugs;
        }
        if ( 'only-on' == $enable_duplication_on_post_types_type && in_array( $post_type, $post_types_for_enable_duplication ) && !in_array( $post_type, $inapplicable_post_types ) || 'except-on' == $enable_duplication_on_post_types_type && !in_array( $post_type, $post_types_for_enable_duplication ) && !in_array( $post_type, $inapplicable_post_types ) ) {
            if ( 'product' != $post_type || 'product' == $post_type && !$is_woocommerce_active ) {
                return true;
            }
        } else {
            return false;
        }
    }

    /**
     * Normalize Elementor plugin placeholder media in a duplicated post.
     *
     * Rewrites placeholder image objects in `_elementor_data` and
     * `_elementor_page_settings` to an empty attachment ID and Elementor's
     * canonical plugin placeholder URL. Uploaded Media Library files named
     * placeholder.png are left unchanged.
     *
     * @since 9.1.3
     *
     * @param int $post_id Duplicate post ID.
     */
    public function normalize_elementor_placeholder_media_in_duplicate( $post_id ) {
        $post_id = absint( $post_id );
        if ( $post_id < 1 ) {
            return;
        }
        $elementor_data = get_post_meta( $post_id, '_elementor_data', true );
        if ( !empty( $elementor_data ) ) {
            $decoded = ( is_string( $elementor_data ) ? json_decode( $elementor_data, true ) : $elementor_data );
            if ( is_array( $decoded ) ) {
                $changed = false;
                $sanitized = $this->normalize_elementor_placeholder_media_recursive( $decoded, $changed );
                if ( $changed ) {
                    $encoded = wp_json_encode( $sanitized );
                    if ( false !== $encoded ) {
                        update_post_meta( $post_id, '_elementor_data', wp_slash( $encoded ) );
                    }
                }
            }
        }
        $page_settings = get_post_meta( $post_id, '_elementor_page_settings', true );
        if ( is_array( $page_settings ) && !empty( $page_settings ) ) {
            $changed = false;
            $sanitized = $this->normalize_elementor_placeholder_media_recursive( $page_settings, $changed );
            if ( $changed ) {
                update_post_meta( $post_id, '_elementor_page_settings', wp_slash( $sanitized ) );
            }
        }
    }

    /**
     * Recursively normalize Elementor plugin placeholder media objects.
     *
     * @since 9.1.3
     *
     * @param mixed $data    Nested Elementor data.
     * @param bool  $changed Set true when a placeholder object is rewritten.
     * @return mixed
     */
    public function normalize_elementor_placeholder_media_recursive( $data, &$changed ) {
        if ( !is_array( $data ) ) {
            return $data;
        }
        if ( isset( $data['url'] ) && is_string( $data['url'] ) && $this->is_elementor_plugin_placeholder_url( $data['url'] ) ) {
            $canonical_url = $this->get_elementor_placeholder_image_src();
            $new_url = ( '' !== $canonical_url ? $canonical_url : $data['url'] );
            $current_id = ( isset( $data['id'] ) ? $data['id'] : '' );
            if ( '' !== $current_id || $data['url'] !== $new_url ) {
                $data['id'] = '';
                $data['url'] = $new_url;
                $changed = true;
            }
        }
        foreach ( $data as $key => $value ) {
            if ( is_array( $value ) ) {
                $data[$key] = $this->normalize_elementor_placeholder_media_recursive( $value, $changed );
            }
        }
        return $data;
    }

    /**
     * Whether a URL is Elementor's plugin placeholder image, not an uploads file.
     *
     * @since 9.1.3
     *
     * @param string $url Image URL.
     * @return bool
     */
    public function is_elementor_plugin_placeholder_url( $url ) {
        if ( !is_string( $url ) || '' === $url ) {
            return false;
        }
        $path = wp_parse_url( $url, PHP_URL_PATH );
        if ( !is_string( $path ) || '' === $path ) {
            $path = $url;
        }
        if ( false !== strpos( $path, '/plugins/elementor/assets/images/placeholder.' ) ) {
            return true;
        }
        $placeholder_src = $this->get_elementor_placeholder_image_src();
        if ( '' === $placeholder_src ) {
            return false;
        }
        if ( $url === $placeholder_src ) {
            return true;
        }
        $placeholder_path = wp_parse_url( $placeholder_src, PHP_URL_PATH );
        if ( is_string( $placeholder_path ) && '' !== $placeholder_path && $path === $placeholder_path ) {
            return true;
        }
        return false;
    }

    /**
     * Get Elementor's canonical placeholder image URL when Elementor is available.
     *
     * @since 9.1.3
     *
     * @return string
     */
    public function get_elementor_placeholder_image_src() {
        if ( class_exists( '\\Elementor\\Utils' ) && method_exists( '\\Elementor\\Utils', 'get_placeholder_image_src' ) ) {
            $src = \Elementor\Utils::get_placeholder_image_src();
            if ( is_string( $src ) && '' !== $src ) {
                return $src;
            }
        }
        return '';
    }

    /**
     * Prevent Elementor Import_Images from downloading the plugin placeholder via HTTP.
     *
     * Fired by `pre_http_request`.
     *
     * @since 9.1.3
     *
     * @param false|array|\WP_Error $preempt     Preempted response.
     * @param array                 $parsed_args Request args.
     * @param string                $url         Request URL.
     * @return false|array|\WP_Error
     */
    public function skip_elementor_placeholder_http_import( $preempt, $parsed_args, $url ) {
        unset($parsed_args);
        if ( false !== $preempt ) {
            return $preempt;
        }
        if ( $this->is_elementor_plugin_placeholder_url( $url ) ) {
            return new \WP_Error('asenha_skip_elementor_placeholder', __( 'Skipping import of Elementor placeholder image.', 'admin-site-enhancements' ));
        }
        return $preempt;
    }

    /**
     * Prevent Elementor from sideloading its own placeholder.png into the Media Library.
     *
     * Fired by `wp_handle_sideload_prefilter`. User-uploaded files named
     * placeholder.png are allowed when the file hash does not match the plugin asset.
     *
     * @since 9.1.3
     *
     * @param array $file Sideload file array.
     * @return array
     */
    public function skip_elementor_placeholder_sideload( $file ) {
        if ( empty( $file['tmp_name'] ) || empty( $file['name'] ) ) {
            return $file;
        }
        $basename = strtolower( wp_basename( $file['name'] ) );
        if ( !preg_match( '/^placeholder\\.(png|jpe?g|webp)$/', $basename ) ) {
            return $file;
        }
        if ( !defined( 'ELEMENTOR_PATH' ) ) {
            return $file;
        }
        $plugin_placeholder = ELEMENTOR_PATH . 'assets/images/placeholder.png';
        if ( !file_exists( $plugin_placeholder ) || !file_exists( $file['tmp_name'] ) ) {
            return $file;
        }
        $plugin_hash = md5_file( $plugin_placeholder );
        $tmp_hash = md5_file( $file['tmp_name'] );
        if ( false !== $plugin_hash && $plugin_hash === $tmp_hash ) {
            $file['error'] = __( 'Skipping import of Elementor placeholder image.', 'admin-site-enhancements' );
        }
        return $file;
    }

}
