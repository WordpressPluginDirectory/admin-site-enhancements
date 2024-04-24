<?php

namespace ASENHA\Classes;

use ArrayObject;
use NumberFormatter;
/**
 * Class for Admin Columns Manager module
 *
 * @since 6.9.5
 */
class Admin_Columns_Manager {
    /**
     * Get and set default columns
     * 
     * @since 6.0.9
     */
    public function get_default_columns( $post_type ) {
        global $taxonomy_slugs;
        // indexed array of taxonomy slugs
        $available_columns = array(
            'date_published'     => 'Published',
            'postid'             => 'ID',
            'modified'           => 'Last Modified',
            'password_protected' => 'Password Protected',
            'permalink'          => 'Permalink',
            'slug'               => 'Slug',
            'status'             => 'Status',
            'date'               => 'Date',
            'post_parent'        => 'Post Parent',
            'menu_order'         => 'Menu Order',
        );
        if ( 'post' == $post_type ) {
            $available_columns['sticky'] = 'Sticky';
        }
        if ( post_type_supports( $post_type, 'title' ) ) {
            $available_columns['title'] = 'Title';
            // $available_columns['title_raw'] = 'Title Only';
        }
        if ( post_type_supports( $post_type, 'editor' ) || post_type_supports( $post_type, 'excerpt' ) ) {
            $available_columns['excerpt'] = 'Excerpt';
        }
        if ( post_type_supports( $post_type, 'thumbnail' ) ) {
            $available_columns['featured_image'] = 'Featured Image';
        }
        if ( post_type_supports( $post_type, 'author' ) ) {
            $available_columns['author'] = 'Author';
            // $available_columns['author_name'] = 'Author';
            // $available_columns['last_modified_author'] = 'Last Modified Author';
        }
        if ( post_type_supports( $post_type, 'post-formats' ) ) {
            $available_columns['post_formats'] = 'Post Format';
        }
        if ( post_type_supports( $post_type, 'comments' ) ) {
            $available_columns['comments'] = 'Comments';
            $available_columns['comment_count'] = 'Comment Count';
            $available_columns['comment_status'] = 'Allow Comment';
        }
        if ( post_type_supports( $post_type, 'trackbacks' ) ) {
            $available_columns['ping_status'] = 'Ping Status';
        }
        // Already included by default
        // if ( post_type_supports( $post_type, 'page-attributes' ) || is_post_type_hierarchical( $post_type ) ) {
        //     $available_columns['post_parent'] = 'Post Parent';
        //     $available_columns['menu_order'] = 'Menu Order';
        // }
        if ( 'wp_block' == $post_type ) {
            $available_columns['wp_pattern_sync_status'] = 'Sync Status';
        }
        if ( 'shop_order' == $post_type ) {
            $available_columns['products_ordered'] = 'Products';
        }
        // Add taxonomy columns according to the custom taxonomies assigned to each post type
        $attached_taxonomies = get_object_taxonomies( $post_type );
        if ( !empty( $attached_taxonomies ) ) {
            $taxonomy_slugs = array();
            foreach ( $attached_taxonomies as $taxonomy_slug ) {
                $taxonomy_object = get_taxonomy( $taxonomy_slug );
                $taxonomy_label = $taxonomy_object->labels->name;
                if ( 'category' == $taxonomy_slug ) {
                    $taxonomy_slug = 'categories';
                } elseif ( 'post_tag' == $taxonomy_slug ) {
                    $taxonomy_slug = 'tags';
                }
                $taxonomy_slugs[] = $taxonomy_slug;
                $available_columns[$taxonomy_slug] = $taxonomy_label;
            }
        }
        // Sort by array value in ascending order, so they are displayed in that order in the 'Default' pane
        asort( $available_columns );
        $options = get_option( ASENHA_SLUG_U . '_extra', array() );
        $options['admin_columns_available'][$post_type] = $available_columns;
        update_option( ASENHA_SLUG_U . '_extra', $options );
        return $available_columns;
    }

    /**
     * Get ACF field object data
     * 
     * @since 6.3.0
     */
    public function get_acf_field_object(
        $column_name = '',
        $post_id = false,
        $in_collection = false,
        $parent_field = false,
        $parent_type = ''
    ) {
        $field_data = array();
        if ( !$in_collection ) {
            $field_data = get_field_object( $column_name, $post_id );
        } else {
            $parent_field_data = get_field_object( $parent_field, $post_id );
            if ( 'group' == $parent_type || 'repeater' == $parent_type ) {
                $sub_fields = $parent_field_data['sub_fields'];
                foreach ( $sub_fields as $sub_field ) {
                    if ( $column_name == $sub_field['name'] ) {
                        $field_data = $sub_field;
                    }
                }
            }
            if ( 'flexible_content' == $parent_type ) {
                $layouts = $parent_field_data['layouts'];
                foreach ( $layouts as $layout ) {
                    $sub_fields = $layout['sub_fields'];
                    foreach ( $sub_fields as $sub_field ) {
                        if ( $column_name == $sub_field['name'] ) {
                            $field_data = $sub_field;
                        }
                    }
                }
            }
        }
        return $field_data;
    }

    /**
     * Reload list table after performing Quick Edit on post types with custom admin columns
     * This is modified from wp_ajax_inline_save() by adding a script before wp_die() at the end
     * 
     * @link https://github.com/WordPress/wordpress-develop/blob/6.3/src/wp-admin/includes/ajax-actions.php#L2049-L2159
     * @since 6.0.6
     */
    public function wp_ajax_inline_save_with_page_reload() {
        global $mode;
        check_ajax_referer( 'inlineeditnonce', '_inline_edit' );
        if ( !isset( $_POST['post_ID'] ) || !(int) $_POST['post_ID'] ) {
            wp_die();
        }
        $post_id = (int) $_POST['post_ID'];
        if ( 'page' === $_POST['post_type'] ) {
            if ( !current_user_can( 'edit_page', $post_id ) ) {
                wp_die( esc_html( __( 'Sorry, you are not allowed to edit this page.' ) ) );
            }
        } else {
            if ( !current_user_can( 'edit_post', $post_id ) ) {
                wp_die( esc_html( __( 'Sorry, you are not allowed to edit this post.' ) ) );
            }
        }
        $last = wp_check_post_lock( $post_id );
        if ( $last ) {
            $last_user = get_userdata( $last );
            $last_user_name = ( $last_user ? $last_user->display_name : __( 'Someone' ) );
            /* translators: %s: User's display name. */
            $msg_template = __( 'Saving is disabled: %s is currently editing this post.' );
            if ( 'page' === $_POST['post_type'] ) {
                /* translators: %s: User's display name. */
                $msg_template = __( 'Saving is disabled: %s is currently editing this page.' );
            }
            printf( esc_html( $msg_template ), esc_html( $last_user_name ) );
            wp_die();
        }
        $data =& $_POST;
        $post = get_post( $post_id, ARRAY_A );
        // Since it's coming from the database.
        $post = wp_slash( $post );
        $data['content'] = $post['post_content'];
        $data['excerpt'] = $post['post_excerpt'];
        // Rename.
        $data['user_ID'] = get_current_user_id();
        if ( isset( $data['post_parent'] ) ) {
            $data['parent_id'] = $data['post_parent'];
        }
        // Status.
        if ( isset( $data['keep_private'] ) && 'private' === $data['keep_private'] ) {
            $data['visibility'] = 'private';
            $data['post_status'] = 'private';
        } else {
            $data['post_status'] = $data['_status'];
        }
        if ( empty( $data['comment_status'] ) ) {
            $data['comment_status'] = 'closed';
        }
        if ( empty( $data['ping_status'] ) ) {
            $data['ping_status'] = 'closed';
        }
        // Exclude terms from taxonomies that are not supposed to appear in Quick Edit.
        if ( !empty( $data['tax_input'] ) ) {
            foreach ( $data['tax_input'] as $taxonomy => $terms ) {
                $tax_object = get_taxonomy( $taxonomy );
                /** This filter is documented in wp-admin/includes/class-wp-posts-list-table.php */
                if ( !apply_filters(
                    'quick_edit_show_taxonomy',
                    $tax_object->show_in_quick_edit,
                    $taxonomy,
                    $post['post_type']
                ) ) {
                    unset($data['tax_input'][$taxonomy]);
                }
            }
        }
        // Hack: wp_unique_post_slug() doesn't work for drafts, so we will fake that our post is published.
        if ( !empty( $data['post_name'] ) && in_array( $post['post_status'], array('draft', 'pending'), true ) ) {
            $post['post_status'] = 'publish';
            $data['post_name'] = wp_unique_post_slug(
                $data['post_name'],
                $post['ID'],
                $post['post_status'],
                $post['post_type'],
                $post['post_parent']
            );
        }
        // Update the post.
        edit_post();
        $wp_list_table = _get_list_table( 'WP_Posts_List_Table', array(
            'screen' => $_POST['screen'],
        ) );
        $mode = ( 'excerpt' === $_POST['post_view'] ? 'excerpt' : 'list' );
        $level = 0;
        if ( is_post_type_hierarchical( $wp_list_table->screen->post_type ) ) {
            $request_post = array(get_post( $_POST['post_ID'] ));
            $parent = $request_post[0]->post_parent;
            while ( $parent > 0 ) {
                $parent_post = get_post( $parent );
                $parent = $parent_post->post_parent;
                $level++;
            }
        }
        $wp_list_table->display_rows( array(get_post( $_POST['post_ID'] )), $level );
        // INTERCEPT: Add a script to reload the list table page
        ?>
            <script type="text/javascript">
                jQuery('#post-<?php 
        echo esc_attr( $_POST['post_ID'] );
        ?>').css('opacity','0.3');
                document.location.reload(true);
            </script>
        <?php 
        // end INTERCEPT
        wp_die();
    }

}
