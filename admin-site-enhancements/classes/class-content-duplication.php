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
    /**
     * Enable duplication of pages, posts and custom posts
     *
     * @since 1.0.0
     */
    public function duplicate_content() {
        $allow_duplication = false;
        if ( current_user_can( 'edit_posts' ) ) {
            $allow_duplication = true;
        }
        $original_post_id = intval( sanitize_text_field( $_REQUEST['post'] ) );
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
                // Copy over the post meta
                $original_post_metas = get_post_meta( $original_post_id );
                // all meta keys and the corresponding values
                if ( !empty( $original_post_metas ) ) {
                    foreach ( $original_post_metas as $meta_key => $meta_values ) {
                        foreach ( $meta_values as $meta_value ) {
                            update_post_meta( $new_post_id, $meta_key, wp_slash( maybe_unserialize( $meta_value ) ) );
                        }
                    }
                }
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
        $allow_duplication = $this->is_user_allowed_to_duplicate_content();
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
        $duplication_link_locations = $this->get_duplication_link_locations();
        $allow_duplication = $this->is_user_allowed_to_duplicate_content();
        global $pagenow, $typenow, $post;
        $inapplicable_post_types = array('attachment');
        $post_type_is_duplicable = $this->is_post_type_duplicable( $typenow );
        if ( $allow_duplication && $post_type_is_duplicable ) {
            if ( 'post.php' == $pagenow && !in_array( $typenow, $inapplicable_post_types ) || is_singular() ) {
                if ( in_array( 'admin-bar', $duplication_link_locations ) ) {
                    if ( is_object( $post ) ) {
                        $common_methods = new Common_Methods();
                        $post_type_singular_label = $common_methods->get_post_type_singular_label( $post );
                        if ( property_exists( $post, 'ID' ) ) {
                            $wp_admin_bar->add_menu( array(
                                'id'     => 'duplicate-content',
                                'parent' => null,
                                'group'  => null,
                                'title'  => sprintf( 
                                    /* translators: %s is the singular label for the post type */
                                    __( 'Duplicate %s', 'admin-site-enhancements' ),
                                    $post_type_singular_label
                                 ),
                                'href'   => admin_url( 'admin.php?action=duplicate_content&amp;post=' . $post->ID . '&amp;nonce=' . wp_create_nonce( 'asenha-duplicate-' . $post->ID ) ),
                            ) );
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
    public function is_user_allowed_to_duplicate_content() {
        $allow_duplication = false;
        if ( current_user_can( 'edit_posts' ) ) {
            $allow_duplication = true;
        }
        return $allow_duplication;
    }

    /**
     * Check if the post type can be duplicated
     * 
     * @since 6.9.7
     */
    public function is_post_type_duplicable( $post_type ) {
        global $asenha_public_post_types;
        $common_methods = new Common_Methods();
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
        if ( 'only-on' == $enable_duplication_on_post_types_type && in_array( $post_type, $post_types_for_enable_duplication ) || 'except-on' == $enable_duplication_on_post_types_type && !in_array( $post_type, $post_types_for_enable_duplication ) ) {
            if ( 'product' != $post_type || 'product' == $post_type && !$is_woocommerce_active ) {
                return true;
            }
        } else {
            return false;
        }
    }

}
