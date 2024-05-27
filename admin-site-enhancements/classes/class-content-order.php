<?php

namespace ASENHA\Classes;

use WP_Query;
/**
 * Class for Content Order module
 *
 * @since 6.9.5
 */
class Content_Order {
    /** 
     * Add "Custom Order" sub-menu for post types
     * 
     * @since 5.0.0
     */
    public function add_content_order_submenu( $context ) {
        $options = get_option( ASENHA_SLUG_U, array() );
        $content_order_for = ( isset( $options['content_order_for'] ) ? $options['content_order_for'] : array() );
        $content_order_enabled_post_types = array();
        if ( is_array( $content_order_for ) && count( $content_order_for ) > 0 ) {
            foreach ( $content_order_for as $post_type_slug => $is_custom_order_enabled ) {
                if ( $is_custom_order_enabled ) {
                    $post_type_object = get_post_type_object( $post_type_slug );
                    if ( is_object( $post_type_object ) && property_exists( $post_type_object, 'labels' ) ) {
                        $post_type_name_plural = $post_type_object->labels->name;
                        if ( 'post' == $post_type_slug ) {
                            $hook_suffix = add_posts_page(
                                $post_type_name_plural . ' Order',
                                // Page title
                                'Order',
                                // Menu title
                                'edit_pages',
                                // Capability required
                                'custom-order-posts',
                                // Menu and page slug
                                [$this, 'custom_order_page_output']
                            );
                        } else {
                            $hook_suffix = add_submenu_page(
                                'edit.php?post_type=' . $post_type_slug,
                                // Parent (menu) slug. Ref: https://developer.wordpress.org/reference/functions/add_submenu_page/#comment-1404
                                $post_type_name_plural . ' Order',
                                // Page title
                                'Order',
                                // Menu title
                                'edit_pages',
                                // Capability required
                                'custom-order-' . $post_type_slug,
                                // Menu and page slug
                                [$this, 'custom_order_page_output'],
                                // Callback function that outputs page content
                                9999
                            );
                        }
                        add_action( 'admin_print_styles-' . $hook_suffix, [$this, 'enqueue_content_order_styles'] );
                        add_action( 'admin_print_scripts-' . $hook_suffix, [$this, 'enqueue_content_order_scripts'] );
                    }
                }
            }
        }
    }

    /**
     * Output content for the custom order page for each enabled post types
     * Not using settings API because all done via AJAX
     * 
     * @since 5.0.0
     */
    public function custom_order_page_output() {
        $post_status = array(
            'publish',
            'future',
            'draft',
            'pending',
            'private'
        );
        $parent_slug = get_admin_page_parent();
        if ( 'edit.php' == $parent_slug ) {
            $post_type_slug = 'post';
        } elseif ( 'upload.php' == $parent_slug ) {
            $post_type_slug = 'attachment';
            $post_status = array('inherit', 'private');
        } else {
            $post_type_slug = str_replace( 'edit.php?post_type=', '', $parent_slug );
        }
        // Object with properties for each post status and the count of posts for each status
        // $post_count_object = wp_count_posts( $post_type_slug );
        // Number of items with the status 'publish(ed)', 'future' (scheduled), 'draft', 'pending' and 'private'
        // $post_count = absint( $post_count_object->publish )
        //            + absint( $post_count_object->future )
        //            + absint( $post_count_object->draft )
        //            + absint( $post_count_object->pending )
        //            + absint( $post_count_object->private );
        ?>
        <div class="wrap">
            <div class="page-header">
                <h2>
                    <?php 
        echo esc_html( get_admin_page_title() );
        ?>
                </h2>
                <div id="toggles" style="display:none;">
                    <input type="checkbox" id="toggle-taxonomy-terms" name="terms" value="" /><label for="toggle-taxonomy-terms">Show taxonomy terms</label>
                    <input type="checkbox" id="toggle-excerpt" name="excerpt" value="" /><label for="toggle-excerpt">Show excerpt</label>
                </div>
            </div>
        <?php 
        // Get posts
        $args = array(
            'post_type'      => $post_type_slug,
            'posts_per_page' => -1,
            'orderby'        => 'menu_order title',
            'order'          => 'ASC',
            'post_status'    => $post_status,
        );
        // Add the following to non-attachment post types
        if ( 'attachment' != $post_type_slug ) {
            // In hierarchical post types, only return non-child posts as we currently only sort parent posts
            $args['post_parent'] = 0;
        }
        $query = new WP_Query($args);
        if ( $query->have_posts() ) {
            ?>
            <ul id="item-list">
                <?php 
            while ( $query->have_posts() ) {
                $query->the_post();
                $post = get_post( get_the_ID() );
                $this->custom_order_single_item_output( $post );
            }
            ?>
            </ul>
            <div id="updating-order-notice" class="updating-order-notice" style="display: none;"><img src="<?php 
            echo esc_attr( ASENHA_URL ) . 'assets/img/oval.svg';
            ?>" id="spinner-img" class="spinner-img" /><span class="dashicons dashicons-saved" style="display:none;"></span>Updating order...</div>
            <?php 
        } else {
            ?>
            <h3>There is nothing to sort for this post type.</h3>
            <?php 
        }
        ?>
        </div> <!-- End of div.wrap -->
        <?php 
        wp_reset_postdata();
    }

    /**
     * Output single item sortable for custom content order
     * 
     * @since 5.0.0
     */
    private function custom_order_single_item_output( $post ) {
        if ( is_post_type_hierarchical( $post->post_type ) ) {
            $post_type_object = get_post_type_object( $post->post_type );
            $children = get_pages( array(
                'child_of'  => $post->ID,
                'post_type' => $post->post_type,
            ) );
            if ( count( $children ) > 0 ) {
                $has_child_label = '<span class="has-child-label"> <span class="dashicons dashicons-arrow-right"></span> Has child ' . strtolower( $post_type_object->label ) . '</span>';
                $has_child = 'true';
            } else {
                $has_child_label = '';
                $has_child = 'false';
            }
        } else {
            $has_child_label = '';
            $has_child = 'false';
        }
        $post_status_label_class = ( $post->post_status == 'publish' ? ' item-status-hidden' : '' );
        $post_status_object = get_post_status_object( $post->post_status );
        if ( 'attachment' == $post->post_type ) {
            $post_status_label_separator = '';
            $post_status_label = '';
            // Attachments / media only has the post status 'inherit'. Let's not show it.
        } else {
            $post_status_label_separator = ' — ';
            $post_status_label = $post_status_object->label;
        }
        if ( empty( wp_trim_excerpt( '', $post ) ) ) {
            $short_excerpt = '';
        } else {
            $excerpt_trimmed = implode( " ", array_slice( explode( " ", wp_trim_excerpt( '', $post ) ), 0, 30 ) );
            $short_excerpt = '<span class="item-excerpt"> | ' . $excerpt_trimmed . '</span>';
        }
        $taxonomies = get_object_taxonomies( $post->post_type, 'objects' );
        // vi( $taxonomies );
        $taxonomies_and_terms = '';
        foreach ( $taxonomies as $taxonomy ) {
            $terms = array();
            if ( $taxonomy->hierarchical ) {
                $taxonomy_terms = get_the_terms( $post->ID, $taxonomy->name );
                if ( is_array( $taxonomy_terms ) && !empty( $taxonomy_terms ) ) {
                    foreach ( $taxonomy_terms as $term ) {
                        $terms[] = $term->name;
                    }
                }
            }
            $terms = implode( ', ', $terms );
            $taxonomies_and_terms .= ' | ' . $taxonomy->label . ': ' . $terms;
        }
        if ( !empty( $taxonomies_and_terms ) ) {
            $taxonomies_and_terms = '<span class="item-taxonomy-terms">' . $taxonomies_and_terms . '</span>';
        }
        ?>
        <li id="list_<?php 
        echo esc_attr( $post->ID );
        ?>" data-id="<?php 
        echo esc_attr( $post->ID );
        ?>" data-menu-order="<?php 
        echo esc_attr( $post->menu_order );
        ?>" data-parent="<?php 
        echo esc_attr( $post->post_parent );
        ?>" data-has-child="<?php 
        echo esc_attr( $has_child );
        ?>" data-post-type="<?php 
        echo esc_attr( $post->post_type );
        ?>">
            <div class="row">
                <div class="row-content">
                    <?php 
        echo '<div class="content-main">
                                <span class="dashicons dashicons-menu"></span><a href="' . esc_attr( get_edit_post_link( $post->ID ) ) . '" class="item-title">' . esc_html( $post->post_title ) . '</a><span class="item-status' . esc_attr( $post_status_label_class ) . '">' . esc_html( $post_status_label_separator ) . esc_html( $post_status_label ) . '</span>' . wp_kses_post( $has_child_label ) . wp_kses_post( $taxonomies_and_terms ) . wp_kses_post( $short_excerpt ) . '<div class="fader"></div>
                            </div>
                            <div class="content-additional">
                                <a href="' . esc_attr( get_the_permalink( $post->ID ) ) . '" target="_blank" class="button item-view-link">View</a>
                            </div>';
        ?>
                </div>
            </div>
        </li>
        <?php 
    }

    /**
     * Enqueue styles for content order pages
     * 
     * @since 5.0.0
     */
    public function enqueue_content_order_styles() {
        wp_enqueue_style(
            'content-order-style',
            ASENHA_URL . 'assets/css/content-order.css',
            array(),
            ASENHA_VERSION
        );
    }

    /**
     * Enqueue scripts for content order pages
     * 
     * @since 5.0.0
     */
    public function enqueue_content_order_scripts() {
        global $typenow;
        wp_enqueue_script(
            'content-order-jquery-ui-touch-punch',
            ASENHA_URL . 'assets/js/jquery.ui.touch-punch.min.js',
            array('jquery-ui-sortable'),
            '0.2.3',
            true
        );
        wp_register_script(
            'content-order-nested-sortable',
            ASENHA_URL . 'assets/js/jquery.mjs.nestedSortable.js',
            array('content-order-jquery-ui-touch-punch'),
            '2.0.0',
            true
        );
        wp_enqueue_script(
            'content-order-sort',
            ASENHA_URL . 'assets/js/content-order-sort.js',
            array('content-order-nested-sortable'),
            ASENHA_VERSION,
            true
        );
        wp_localize_script( 'content-order-sort', 'contentOrderSort', array(
            'action'      => 'save_custom_order',
            'nonce'       => wp_create_nonce( 'order_sorting_nonce' ),
            'hirarchical' => ( is_post_type_hierarchical( $typenow ) ? 'true' : 'false' ),
        ) );
    }

    /**
     * Save custom content order coming from ajax call
     * 
     * @since 5.0.0
     */
    public function save_custom_content_order() {
        global $wpdb;
        // Check user capabilities
        if ( !current_user_can( 'edit_pages' ) ) {
            wp_send_json( 'Something went wrong.' );
        }
        // Verify nonce
        if ( !wp_verify_nonce( $_POST['nonce'], 'order_sorting_nonce' ) ) {
            wp_send_json( 'Something went wrong.' );
        }
        // Get ajax variables
        $action = ( isset( $_POST['action'] ) ? $_POST['action'] : '' );
        // Item parent is currently 0, as we only handle sorting of non-child posts
        $item_parent = ( isset( $_POST['item_parent'] ) ? absint( $_POST['item_parent'] ) : 0 );
        $menu_order_start = ( isset( $_POST['start'] ) ? absint( $_POST['start'] ) : 0 );
        $post_id = ( isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0 );
        $item_menu_order = ( isset( $_POST['menu_order'] ) ? absint( $_POST['menu_order'] ) : 0 );
        $items_to_exclude = ( isset( $_POST['excluded_items'] ) ? absint( $_POST['excluded_items'] ) : array() );
        $post_type = ( isset( $_POST['post_type'] ) ? $_POST['post_type'] : false );
        // Make processing faster by removing certain actions
        remove_action( 'pre_post_update', 'wp_save_post_revision' );
        // $response array for ajax response
        $response = array();
        // Update the item whose order/position was moved
        if ( $post_id > 0 && !isset( $_POST['more_posts'] ) ) {
            // https://developer.wordpress.org/reference/classes/wpdb/update/
            $wpdb->update( 
                $wpdb->posts,
                // The table
                array(
                    'menu_order' => $item_menu_order,
                ),
                array(
                    'ID' => $post_id,
                )
             );
            clean_post_cache( $post_id );
            $items_to_exclude[] = $post_id;
        }
        if ( 'attachment' == $post_type ) {
            $post_status = array('inherit', 'private');
        } else {
            $post_status = array(
                'publish',
                'future',
                'draft',
                'pending',
                'private'
            );
        }
        // Get all posts from the post type related to ajax request
        $query_args = array(
            'post_type'              => $post_type,
            'orderby'                => 'menu_order title',
            'order'                  => 'ASC',
            'posts_per_page'         => -1,
            'suppress_filters'       => true,
            'ignore_sticky_posts'    => true,
            'post_status'            => $post_status,
            'post__not_in'           => $items_to_exclude,
            'update_post_term_cache' => false,
            'update_post_meta_cache' => false,
        );
        if ( 'attachment' == $post_type ) {
            // do nothing, we do not add post_parent parameter as media items can be attached to other posts, making them the parent.
        } else {
            // Item parent is currently 0, as we only handle sorting of non-child posts
            $query_args['post_parent'] = $item_parent;
        }
        $posts = new WP_Query($query_args);
        if ( $posts->have_posts() ) {
            // Iterate through posts and update menu order and post parent
            foreach ( $posts->posts as $post ) {
                // If the $post is the one being displaced (shited downward) by the moved item, increment it's menu_order by one
                if ( $menu_order_start == $item_menu_order && $post_id > 0 ) {
                    $menu_order_start++;
                }
                // Only process posts other than the moved item, which has been processed earlier outside this loop
                if ( $post_id != $post->ID ) {
                    // Update menu_order
                    $wpdb->update( $wpdb->posts, array(
                        'menu_order' => $menu_order_start,
                    ), array(
                        'ID' => $post->ID,
                    ) );
                    clean_post_cache( $post->ID );
                }
                $items_to_exclude[] = $post->ID;
                $menu_order_start++;
            }
            die( json_encode( $response ) );
        } else {
            die( json_encode( $response ) );
        }
    }

    /**
     * Set default ordering of list tables of sortable post types by 'menu_order'
     * 
     * @link https://developer.wordpress.org/reference/classes/wp_query/#methods
     * @since 5.0.0
     */
    public function orderby_menu_order( $query ) {
        global $pagenow, $typenow;
        $options = get_option( ASENHA_SLUG_U, array() );
        $content_order_for = ( isset( $options['content_order_for'] ) ? $options['content_order_for'] : array() );
        $content_order_enabled_post_types = array();
        if ( is_array( $content_order_for ) && count( $content_order_for ) > 0 ) {
            foreach ( $content_order_for as $post_type_slug => $is_custom_order_enabled ) {
                if ( $is_custom_order_enabled ) {
                    $content_order_enabled_post_types[] = $post_type_slug;
                }
            }
        }
        $should_be_custom_sorted = false;
        if ( in_array( $typenow, $content_order_enabled_post_types ) ) {
            $should_be_custom_sorted = true;
        }
        // Use custom order in wp-admin listing pages/tables for enabled post types
        if ( is_admin() && ('edit.php' == $pagenow || 'upload.php' == $pagenow) && !isset( $_GET['orderby'] ) ) {
            if ( $should_be_custom_sorted ) {
                $query->set( 'orderby', 'menu_order title' );
                $query->set( 'order', 'ASC' );
                // vi( $query, '', 'for ' . $pagenow );
            }
        }
    }

    /**
     * Make sure newly created posts are assigned the highest menu_order so it's added at the bottom of the existing order
     * 
     * @since 6.2.1
     */
    public function set_menu_order_for_new_posts( $post_id, $post, $update ) {
        $options = get_option( ASENHA_SLUG_U, array() );
        $content_order_for = ( isset( $options['content_order_for'] ) ? $options['content_order_for'] : array() );
        $content_order_enabled_post_types = array();
        if ( is_array( $content_order_for ) && count( $content_order_for ) > 0 ) {
            foreach ( $content_order_for as $post_type_slug => $is_custom_order_enabled ) {
                if ( $is_custom_order_enabled ) {
                    $content_order_enabled_post_types[] = $post_type_slug;
                }
            }
        }
        // Only assign menu_order if there are none assigned when creating the post, i.e. menu_order is 0
        if ( in_array( $post->post_type, $content_order_enabled_post_types ) && ('auto-draft' == $post->post_status || 'publish' == $post->post_status) && $post->menu_order == '0' && false === $update ) {
            $post_with_highest_menu_order = get_posts( array(
                'post_type'      => $post->post_type,
                'posts_per_page' => 1,
                'orderby'        => 'menu_order',
                'order'          => 'DESC',
            ) );
            if ( $post_with_highest_menu_order ) {
                $new_menu_order = (int) $post_with_highest_menu_order[0]->menu_order + 1;
                // Assign the one higher menu_order to the new post
                $args = array(
                    'ID'         => $post_id,
                    'menu_order' => $new_menu_order,
                );
                wp_update_post( $args );
            }
        }
    }

}
