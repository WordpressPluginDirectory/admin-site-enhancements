<?php

namespace ASENHA\Classes;

use  enshrined\svgSanitize\Sanitizer ;
// For Enable SVG Upload
use  WP_Query ;
use  WC_Admin_Duplicate_Product ;
use  WP_Admin_Bar ;
/**
 * Class related to Content Management features
 *
 * @since 1.0.0
 */
class Content_Management
{
    /**
     * Enable duplication of pages, posts and custom posts
     *
     * @since 1.0.0
     */
    public function duplicate_content()
    {
        $allow_duplication = false;
        if ( current_user_can( 'edit_posts' ) ) {
            $allow_duplication = true;
        }
        $original_post_id = intval( sanitize_text_field( $_REQUEST['post'] ) );
        $nonce = sanitize_text_field( $_REQUEST['nonce'] );
        
        if ( wp_verify_nonce( $nonce, 'asenha-duplicate-' . $original_post_id ) && $allow_duplication ) {
            $original_post = get_post( $original_post_id );
            $post_type = $original_post->post_type;
            // Not WooCommerce product
            
            if ( 'product' != $post_type ) {
                // Set some attributes for the duplicate post
                $new_post_title_suffix = ' (DUPLICATE)';
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
                    'post_title'     => $original_post->post_title . $new_post_title_suffix,
                    'post_type'      => $original_post->post_type,
                    'to_ping'        => $original_post->to_ping,
                    'menu_order'     => $original_post->menu_order,
                );
                $new_post_id = wp_insert_post( $args );
                // Copy over the taxonomies
                $original_taxonomies = get_object_taxonomies( $original_post->post_type );
                if ( !empty($original_taxonomies) && is_array( $original_taxonomies ) ) {
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
                if ( !empty($original_post_metas) ) {
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
    public function add_duplication_action_link( $actions, $post )
    {
        $duplication_link_locations = $this->get_duplication_link_locations();
        $allow_duplication = $this->is_user_allowed_to_duplicate_content();
        $post_type = $post->post_type;
        if ( $allow_duplication ) {
            // Not WooCommerce product
            if ( in_array( 'post-action', $duplication_link_locations ) && 'product' != $post_type ) {
                $actions['asenha-duplicate'] = '<a href="admin.php?action=duplicate_content&amp;post=' . $post->ID . '&amp;nonce=' . wp_create_nonce( 'asenha-duplicate-' . $post->ID ) . '" title="Duplicate this as draft">Duplicate</a>';
            }
        }
        return $actions;
    }
    
    /**
     * Add admin bar duplicate link
     * 
     * @since 6.3.0
     */
    public function add_admin_bar_duplication_link( WP_Admin_Bar $wp_admin_bar )
    {
        $duplication_link_locations = $this->get_duplication_link_locations();
        $allow_duplication = $this->is_user_allowed_to_duplicate_content();
        global  $pagenow, $typenow, $post ;
        $inapplicable_post_types = array( 'attachment' );
        if ( $allow_duplication ) {
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
                                'title'  => 'Duplicate ' . $post_type_singular_label,
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
    public function get_duplication_link_locations()
    {
        $options = get_option( ASENHA_SLUG_U, array() );
        $duplication_link_locations = array( 'post-action', 'admin-bar' );
        return $duplication_link_locations;
    }
    
    /**
     * Check if a user role is allowed to duplicate content
     * 
     * @since 6.9.3
     */
    public function is_user_allowed_to_duplicate_content()
    {
        $allow_duplication = false;
        if ( current_user_can( 'edit_posts' ) ) {
            $allow_duplication = true;
        }
        return $allow_duplication;
    }
    
    /**
     * Modify the 'Edit' link to be 'Edit or Replace'
     * 
     */
    public function modify_media_list_table_edit_link( $actions, $post )
    {
        $new_actions = array();
        foreach ( $actions as $key => $value ) {
            
            if ( $key == 'edit' ) {
                $new_actions['edit'] = '<a href="' . get_edit_post_link( $post ) . '" aria-label="Edit or Replace">Edit or Replace</a>';
            } else {
                $new_actions[$key] = $value;
            }
        
        }
        return $new_actions;
    }
    
    /** 
     * Add "Custom Order" sub-menu for post types
     * 
     * @since 5.0.0
     */
    public function add_content_order_submenu( $context )
    {
        $options = get_option( ASENHA_SLUG_U, array() );
        $content_order_for = $options['content_order_for'];
        $content_order_enabled_post_types = array();
        foreach ( $options['content_order_for'] as $post_type_slug => $is_custom_order_enabled ) {
            
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
                            [ $this, 'custom_order_page_output' ]
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
                            [ $this, 'custom_order_page_output' ],
                            // Callback function that outputs page content
                            9999
                        );
                    }
                    
                    add_action( 'admin_print_styles-' . $hook_suffix, [ $this, 'enqueue_content_order_styles' ] );
                    add_action( 'admin_print_scripts-' . $hook_suffix, [ $this, 'enqueue_content_order_scripts' ] );
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
    public function custom_order_page_output()
    {
        $parent_slug = get_admin_page_parent();
        
        if ( 'edit.php' == $parent_slug ) {
            $post_type_slug = 'post';
        } else {
            $post_type_slug = str_replace( 'edit.php?post_type=', '', $parent_slug );
        }
        
        // Object with properties for each post status and the count of posts for each status
        // $post_count_object = wp_count_posts( $post_type_slug );
        // Number of items with the status 'publish(ed)', 'future' (scheduled), 'draft', 'pending' and 'private'
        // $post_count = absint( $post_count_object->publish )
        // 			  + absint( $post_count_object->future )
        // 			  + absint( $post_count_object->draft )
        // 			  + absint( $post_count_object->pending )
        // 			  + absint( $post_count_object->private );
        ?>
		<div class="wrap">
			<div class="page-header">
				<h2>
					<?php 
        echo  get_admin_page_title() ;
        ?>
				</h2>
				<div id="toggles" style="display:none;">
					<input type="checkbox" id="toggle-taxonomy-terms" name="terms" value="" /><label for="toggle-taxonomy-terms">Show taxonomy terms</label>
					<input type="checkbox" id="toggle-excerpt" name="excerpt" value="" /><label for="toggle-excerpt">Show excerpt</label>
				</div>
			</div>
		<?php 
        // Get posts
        $query = new WP_Query( array(
            'post_type'      => $post_type_slug,
            'posts_per_page' => -1,
            'orderby'        => 'menu_order title',
            'order'          => 'ASC',
            'post_status'    => array(
            'publish',
            'future',
            'draft',
            'pending',
            'private'
        ),
            'post_parent'    => 0,
        ) );
        
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
            echo  ASENHA_URL . 'assets/img/oval.svg' ;
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
    private function custom_order_single_item_output( $post )
    {
        
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
        
        if ( empty(wp_trim_excerpt( '', $post )) ) {
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
                if ( is_array( $taxonomy_terms ) && !empty($taxonomy_terms) ) {
                    foreach ( $taxonomy_terms as $term ) {
                        $terms[] = $term->name;
                    }
                }
            }
            
            $terms = implode( ', ', $terms );
            $taxonomies_and_terms .= ' | ' . $taxonomy->label . ': ' . $terms;
        }
        if ( !empty($taxonomies_and_terms) ) {
            $taxonomies_and_terms = '<span class="item-taxonomy-terms">' . $taxonomies_and_terms . '</span>';
        }
        ?>
		<li id="list_<?php 
        echo  $post->ID ;
        ?>" data-id="<?php 
        echo  $post->ID ;
        ?>" data-menu-order="<?php 
        echo  $post->menu_order ;
        ?>" data-parent="<?php 
        echo  $post->post_parent ;
        ?>" data-has-child="<?php 
        echo  $has_child ;
        ?>" data-post-type="<?php 
        echo  $post->post_type ;
        ?>">
			<div class="row">
				<div class="row-content">
					<?php 
        echo  '<div class="content-main">
								<span class="dashicons dashicons-menu"></span><a href="' . get_edit_post_link( $post->ID ) . '" class="item-title">' . $post->post_title . '</a><span class="item-status' . $post_status_label_class . '"> — ' . $post_status_object->label . '</span>' . $has_child_label . wp_kses_post( $taxonomies_and_terms ) . wp_kses_post( $short_excerpt ) . '<div class="fader"></div>
							</div>
							<div class="content-additional">
								<a href="' . get_the_permalink( $post->ID ) . '" target="_blank" class="button item-view-link">View</a>
							</div>' ;
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
    public function enqueue_content_order_styles()
    {
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
    public function enqueue_content_order_scripts()
    {
        global  $typenow ;
        wp_enqueue_script(
            'content-order-jquery-ui-touch-punch',
            ASENHA_URL . 'assets/js/jquery.ui.touch-punch.min.js',
            array( 'jquery-ui-sortable' ),
            '0.2.3',
            true
        );
        wp_register_script(
            'content-order-nested-sortable',
            ASENHA_URL . 'assets/js/jquery.mjs.nestedSortable.js',
            array( 'content-order-jquery-ui-touch-punch' ),
            '2.0.0',
            true
        );
        wp_enqueue_script(
            'content-order-sort',
            ASENHA_URL . 'assets/js/content-order-sort.js',
            array( 'content-order-nested-sortable' ),
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
    public function save_custom_content_order()
    {
        global  $wpdb ;
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
        
        // Get all posts from the post type related to ajax request
        $query_args = array(
            'post_type'              => $post_type,
            'orderby'                => 'menu_order title',
            'order'                  => 'ASC',
            'posts_per_page'         => -1,
            'suppress_filters'       => true,
            'ignore_sticky_posts'    => true,
            'post_status'            => array(
            'publish',
            'future',
            'draft',
            'pending',
            'private'
        ),
            'post_parent'            => $item_parent,
            'post__not_in'           => $items_to_exclude,
            'update_post_term_cache' => false,
            'update_post_meta_cache' => false,
        );
        $posts = new WP_Query( $query_args );
        
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
    public function orderby_menu_order( $query )
    {
        global  $pagenow, $typenow ;
        $options = get_option( ASENHA_SLUG_U, array() );
        $content_order_for = $options['content_order_for'];
        $content_order_enabled_post_types = array();
        foreach ( $options['content_order_for'] as $post_type_slug => $is_custom_order_enabled ) {
            if ( $is_custom_order_enabled ) {
                $content_order_enabled_post_types[] = $post_type_slug;
            }
        }
        // Use custom order in wp-admin listing pages/tables for enabled post types
        if ( is_admin() && 'edit.php' == $pagenow && !isset( $_GET['orderby'] ) ) {
            
            if ( in_array( $typenow, $content_order_enabled_post_types ) && (post_type_supports( $typenow, 'page-attributes' ) || is_post_type_hierarchical( $typenow )) ) {
                $query->set( 'orderby', 'menu_order title' );
                $query->set( 'order', 'ASC' );
            }
        
        }
    }
    
    /**
     * Make sure newly created posts are assigned the highest menu_order so it's added at the bottom of the existing order
     * 
     * @since 6.2.1
     */
    public function set_menu_order_for_new_posts( $post_id, $post, $update )
    {
        $options = get_option( ASENHA_SLUG_U, array() );
        $content_order_for = $options['content_order_for'];
        $content_order_enabled_post_types = array();
        foreach ( $options['content_order_for'] as $post_type_slug => $is_custom_order_enabled ) {
            if ( $is_custom_order_enabled ) {
                $content_order_enabled_post_types[] = $post_type_slug;
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
    
    /**
     * Add media replacement button in the edit screen of media/attachment
     *
     * @since 1.1.0
     */
    public function add_media_replacement_button( $fields, $post )
    {
        global  $post, $pagenow, $typenow ;
        // Do not do this on post creation and editing screen
        // May cause media frame layout / display issues
        
        if ( 'attachment' == $typenow || 'attachment' != $typenow && 'post-new.php' != $pagenow && 'post.php' != $pagenow ) {
            $image_mime_type = '';
            if ( is_object( $post ) ) {
                if ( property_exists( $post, 'post_mime_type' ) ) {
                    $image_mime_type = $post->post_mime_type;
                }
            }
            // Enqueues all scripts, styles, settings, and templates necessary to use all media JS APIs.
            // Reference: https://codex.wordpress.org/Javascript_Reference/wp.media
            wp_enqueue_media();
            // Add new field to attachment fields for the media replace functionality
            $fields['asenha-media-replace'] = array();
            $fields['asenha-media-replace']['label'] = '';
            $fields['asenha-media-replace']['input'] = 'html';
            $fields['asenha-media-replace']['html'] = '
				<div id="media-replace-div" class="postbox">
					<div class="postbox-header">
						<h2 class="hndle ui-sortable-handle">Replace Media</h2>
					</div>
					<div class="inside">
					<button type="button" id="asenha-media-replace" class="button-secondary button-large asenha-media-replace-button" data-old-image-mime-type="' . $image_mime_type . '" onclick="replaceMedia(\'' . $image_mime_type . '\');">Select New Media File</button>
					<input type="hidden" id="new-attachment-id" name="new-attachment-id" />
					<div class="asenha-media-replace-notes"><p>The current file will be replaced with the uploaded / selected file (of the same type) while retaining the current ID, publish date and file name. Thus, no existing links will break.</p></div>
					</div>
				</div>
			';
        }
        
        return $fields;
    }
    
    public function attachment_for_js( $image_url, $attachment_id )
    {
        // vi( $image_url );
        // vi( $attachment_id );
    }
    
    /**
     * Replace existing media with the newly updated file
     *
     * @link https://plugins.trac.wordpress.org/browser/replace-image/tags/1.1.7/hm-replace-image.php#L55
     * @since 1.1.0
     */
    public function replace_media( $old_attachment_id )
    {
        $old_post_meta = get_post( $old_attachment_id, ARRAY_A );
        $old_post_mime = $old_post_meta['post_mime_type'];
        // e.g. 'image/jpeg'
        // Get the new attachment/media ID, meta and mime type
        
        if ( isset( $_POST['new-attachment-id'] ) && !empty($_POST['new-attachment-id']) ) {
            $new_attachment_id = intval( sanitize_text_field( $_POST['new-attachment-id'] ) );
            $new_post_meta = get_post( $new_attachment_id, ARRAY_A );
            $new_post_mime = $new_post_meta['post_mime_type'];
            // e.g. 'image/jpeg'
        }
        
        // Check if the media file ID selected via the media frame and passed on to the #new-attachment-id hidden field
        // Ensure the mime type matches too
        
        if ( !empty($new_attachment_id) && is_numeric( $new_attachment_id ) && $old_post_mime == $new_post_mime ) {
            $new_attachment_meta = wp_get_attachment_metadata( $new_attachment_id );
            // If original file is larger than 2560 pixel
            // https://make.wordpress.org/core/2019/10/09/introducing-handling-of-big-images-in-wordpress-5-3/
            
            if ( array_key_exists( 'original_image', $new_attachment_meta ) ) {
                // Get the original media file path
                $new_media_file_path = wp_get_original_image_path( $new_attachment_id );
            } else {
                // Get the path to newly uploaded media file. An image file name may end with '-scaled'.
                $new_attachment_file = get_post_meta( $new_attachment_id, '_wp_attached_file', true );
                $upload_dir = wp_upload_dir();
                $new_media_file_path = $upload_dir['basedir'] . '/' . $new_attachment_file;
            }
            
            // Check if the new media file exist / was successfully uploaded
            if ( !is_file( $new_media_file_path ) ) {
                return false;
            }
            // Delete existing/old media files. Post and post meta entries for it are still there in the database.
            $this->delete_media_files( $old_attachment_id );
            // If original file is larger than 2560 pixel
            // https://make.wordpress.org/core/2019/10/09/introducing-handling-of-big-images-in-wordpress-5-3/
            
            if ( array_key_exists( 'original_image', $new_attachment_meta ) ) {
                // Get the original media file path
                $old_media_file_path = wp_get_original_image_path( $old_attachment_id );
            } else {
                // Get the path to the old/existing media file that will be replaced and deleted. An image file name may end with '-scaled'.
                $old_attachment_file = get_post_meta( $old_attachment_id, '_wp_attached_file', true );
                $old_media_file_path = $upload_dir['basedir'] . '/' . $old_attachment_file;
            }
            
            // Check if the directory path to the old media file is still intact
            if ( !file_exists( dirname( $old_media_file_path ) ) ) {
                // Recreate the directory path
                mkdir( dirname( $old_media_file_path ), 0755, true );
            }
            // Copy the new media file into the old media file's path
            copy( $new_media_file_path, $old_media_file_path );
            // Regenerate attachment meta data and image sub-sizes from the new media file that was just copied to the old path
            $old_media_post_meta_updated = wp_generate_attachment_metadata( $old_attachment_id, $old_media_file_path );
            // Update new media file's meta data with the ones from the old media. i.e. new media file will carry over the post ID and post meta of the old media file. i.e. only the files are replaced for the old media's ID and post meta in the database.
            wp_update_attachment_metadata( $old_attachment_id, $old_media_post_meta_updated );
            // Delete the newly uploaded media file and it's sub-sizes, and also delete post and post meta entries for it in the database.
            wp_delete_attachment( $new_attachment_id, true );
            // Add old attachment ID to recently replaced media option. This will be used for cache busting to ensure the new replacement images are immediately loaded in the browser / wp-admin
            $options_extra = get_option( ASENHA_SLUG_U . '_extra', array() );
            $recently_replaced_media = ( isset( $options_extra['recently_replaced_media'] ) ? $options_extra['recently_replaced_media'] : array() );
            $max_media_number_to_cache_bust = 5;
            if ( count( $recently_replaced_media ) >= $max_media_number_to_cache_bust ) {
                // Remove first/oldest attachment ID
                array_shift( $recently_replaced_media );
            }
            $recently_replaced_media[] = $old_attachment_id;
            $recently_replaced_media = array_unique( $recently_replaced_media );
            $options_extra['recently_replaced_media'] = $recently_replaced_media;
            update_option( 'admin_site_enhancements_extra', $options_extra );
        }
    
    }
    
    /**
     * Delete the existing/old media files when performing media replacement
     *
     * @link https://plugins.trac.wordpress.org/browser/replace-image/tags/1.1.7/hm-replace-image.php#L80
     * @since 1.1.0
     */
    public function delete_media_files( $post_id )
    {
        $attachment_meta = wp_get_attachment_metadata( $post_id );
        // Will get '-scaled' version if it exists, e.g. /path/to/uploads/year/month/file-name.jpg
        $attachment_file_path = get_attached_file( $post_id );
        // e.g. file-name.jpg
        $attachment_file_basename = basename( $attachment_file_path );
        // Delete intermediate images if there are any
        if ( isset( $attachment_meta['sizes'] ) && is_array( $attachment_meta['sizes'] ) ) {
            foreach ( $attachment_meta['sizes'] as $size => $size_info ) {
                // /path/to/uploads/year/month/file-name.jpg --> /path/to/uploads/year/month/file-name-1024x768.jpg
                $intermediate_file_path = str_replace( $attachment_file_basename, $size_info['file'], $attachment_file_path );
                wp_delete_file( $intermediate_file_path );
            }
        }
        // Delete the attachment file, which maybe the '-scaled' version
        wp_delete_file( $attachment_file_path );
        // If original file is larger than 2560 pixel
        // https://make.wordpress.org/core/2019/10/09/introducing-handling-of-big-images-in-wordpress-5-3/
        
        if ( array_key_exists( 'original_image', $attachment_meta ) ) {
            $attachment_original_file_path = wp_get_original_image_path( $post_id );
            // Delete the original file
            wp_delete_file( $attachment_original_file_path );
        }
    
    }
    
    /**
     * Customize the attachment updated message
     *
     * @link https://github.com/WordPress/wordpress-develop/blob/6.0.2/src/wp-admin/edit-form-advanced.php#L180
     * @since 1.1.0
     */
    public function attachment_updated_custom_message( $messages )
    {
        $new_messages = array();
        foreach ( $messages as $post_type => $messages_array ) {
            if ( $post_type == 'attachment' ) {
                // Message ID for successful edit/update of an attachment is 4. e.g. /wp-admin/post.php?post=a&action=edit&classic-editor&message=4 Customize it here.
                $messages_array[4] = 'Media file updated. You may need to <a href="https://fabricdigital.co.nz/blog/how-to-hard-refresh-your-browser-and-clear-cache" target="_blank">hard refresh</a> your browser to see the updated media preview image below.';
            }
            $new_messages[$post_type] = $messages_array;
        }
        return $new_messages;
    }
    
    /**
     * Append cache busting parameter to the end of image srcset
     * 
     * @since 5.7.0
     */
    public function append_cache_busting_param_to_image_srcset(
        $sources,
        $size_array,
        $image_src,
        $image_meta,
        $attachment_id
    )
    {
        $options_extra = get_option( ASENHA_SLUG_U . '_extra', array() );
        $recently_replaced_media = ( isset( $options_extra['recently_replaced_media'] ) ? $options_extra['recently_replaced_media'] : array() );
        if ( in_array( $attachment_id, $recently_replaced_media ) ) {
            foreach ( $sources as $size => $source ) {
                $source['url'] .= (( false === strpos( $source['url'], '?' ) ? '?' : '&' )) . 't=' . time();
                $sources[$size] = $source;
            }
        }
        return $sources;
    }
    
    /**
     * Append cache busting parameter to the end of image src
     * 
     * @since 5.7.0
     */
    public function append_cache_busting_param_to_attachment_image_src( $image, $attachment_id )
    {
        $options_extra = get_option( ASENHA_SLUG_U . '_extra', array() );
        $recently_replaced_media = ( isset( $options_extra['recently_replaced_media'] ) ? $options_extra['recently_replaced_media'] : array() );
        if ( !empty($image[0]) && in_array( $attachment_id, $recently_replaced_media ) ) {
            $image[0] .= (( false === strpos( $image[0], '?' ) ? '?' : '&' )) . 't=' . time();
        }
        return $image;
    }
    
    /**
     * Append cache busting parameter to image src for js
     * 
     * @since 5.7.0
     */
    public function append_cache_busting_param_to_attachment_for_js( $response, $attachment )
    {
        $options_extra = get_option( ASENHA_SLUG_U . '_extra', array() );
        $recently_replaced_media = ( isset( $options_extra['recently_replaced_media'] ) ? $options_extra['recently_replaced_media'] : array() );
        
        if ( in_array( $attachment->ID, $recently_replaced_media ) ) {
            if ( false !== strpos( $response['url'], '?' ) ) {
                $response['url'] .= (( false === strpos( $response['url'], '?' ) ? '?' : '&' )) . 't=' . time();
            }
            if ( isset( $response['sizes'] ) ) {
                foreach ( $response['sizes'] as $size_name => $size ) {
                    $response['sizes'][$size_name]['url'] .= (( false === strpos( $size['url'], '?' ) ? '?' : '&' )) . 't=' . time();
                }
            }
        }
        
        return $response;
    }
    
    /**
     * Append cache busting parameter to attachment URL
     * 
     * @since 6.8.2
     */
    public function append_cache_busting_param_to_attachment_url( $url, $attachment_id )
    {
        $options_extra = get_option( ASENHA_SLUG_U . '_extra', array() );
        $recently_replaced_media = ( isset( $options_extra['recently_replaced_media'] ) ? $options_extra['recently_replaced_media'] : array() );
        
        if ( in_array( $attachment_id, $recently_replaced_media ) ) {
            // if ( false !== strpos( $url, '?' ) ) {
            $url .= (( false === strpos( $url, '?' ) ? '?' : '&' )) . 't=' . time();
            // }
        }
        
        return $url;
    }
    
    /**
     * Add SVG mime type for media library uploads
     *
     * @link https://developer.wordpress.org/reference/hooks/upload_mimes/
     * @since 2.6.0
     */
    public function add_svg_mime( $mimes )
    {
        global  $roles_svg_upload_enabled ;
        $current_user = wp_get_current_user();
        $current_user_roles = (array) $current_user->roles;
        // single dimensional array of role slugs
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
    public function confirm_file_type_is_svg(
        $filetypes_extensions,
        $file,
        $filename,
        $mimes
    )
    {
        global  $roles_svg_upload_enabled ;
        $current_user = wp_get_current_user();
        $current_user_roles = (array) $current_user->roles;
        // single dimensional array of role slugs
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
    public function sanitize_and_maybe_allow_svg_upload( $file )
    {
        if ( !isset( $file['tmp_name'] ) ) {
            return $file;
        }
        $file_tmp_name = $file['tmp_name'];
        // full path
        $file_name = ( isset( $file['name'] ) ? $file['name'] : '' );
        $file_type_ext = wp_check_filetype_and_ext( $file_tmp_name, $file_name );
        $file_type = ( !empty($file_type_ext['type']) ? $file_type_ext['type'] : '' );
        // Load sanitizer library - https://github.com/darylldoyle/svg-sanitizer
        $sanitizer = new Sanitizer();
        
        if ( 'image/svg+xml' === $file_type ) {
            $original_svg = file_get_contents( $file_tmp_name );
            $sanitized_svg = $sanitizer->sanitize( $original_svg );
            // boolean
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
    public function generate_svg_metadata( $metadata, $attachment_id, $context )
    {
        
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
            $svg_url_path = str_replace( wp_upload_dir()['baseurl'] . '/', '', $svg_url );
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
    public function get_svg_attachment_url()
    {
        $attachment_url = '';
        $attachment_id = ( isset( $_REQUEST['attachmentID'] ) ? $_REQUEST['attachmentID'] : '' );
        // Check response mime type
        
        if ( $attachment_id ) {
            $attachment_url = wp_get_attachment_url( $attachment_id );
            echo  $attachment_url ;
            die;
        }
    
    }
    
    /**
     * Return svg file URL to show preview in media library
     *
     * @link https://developer.wordpress.org/reference/functions/wp_prepare_attachment_for_js/
     * @since 2.6.0
     */
    public function get_svg_url_in_media_library( $response )
    {
        // Check response mime type
        if ( $response['mime'] === 'image/svg+xml' ) {
            $response['image'] = array(
                'src' => $response['url'],
            );
        }
        return $response;
    }
    
    /**
     * Add external permalink meta box for enabled post types
     * 
     * @since 3.9.0
     */
    public function add_external_permalink_meta_box( $post_type, $post )
    {
        $options = get_option( ASENHA_SLUG_U, array() );
        $enable_external_permalinks_for = $options['enable_external_permalinks_for'];
        foreach ( $enable_external_permalinks_for as $post_type_slug => $is_external_permalink_enabled ) {
            if ( get_post_type() == $post_type_slug && $is_external_permalink_enabled ) {
                // Skip adding meta box for post types where Gutenberg is enabled
                // if (
                // 	function_exists( 'use_block_editor_for_post_type' )
                // 	&& use_block_editor_for_post_type( $post_type_slug )
                // ) {
                // 	continue; // go to the beginning of next iteration
                // }
                add_meta_box(
                    'asenha-external-permalink',
                    // ID of meta box
                    'External Permalink',
                    // Title of meta box
                    [ $this, 'output_external_permalink_meta_box' ],
                    // Callback function
                    $post_type_slug,
                    // The screen on which the meta box should be output to
                    'normal',
                    // context
                    'high'
                );
            }
        }
    }
    
    /**
     * Render External Permalink meta box
     *
     * @since 3.9.0
     */
    public function output_external_permalink_meta_box( $post )
    {
        ?>
		<div class="external-permalink-input">
			<input name="<?php 
        echo  esc_attr( 'external_permalink' ) ;
        ?>" class="large-text" id="<?php 
        echo  esc_attr( 'external_permalink' ) ;
        ?>" type="text" value="<?php 
        echo  esc_url( get_post_meta( $post->ID, '_links_to', true ) ) ;
        ?>" placeholder="https://" />
			<div class="external-permalink-input-description">Keep empty to use the default WordPress permalink. External permalink will open in a new browser tab.</div>
			<?php 
        wp_nonce_field(
            'external_permalink_' . $post->ID,
            'external_permalink_nonce',
            false,
            true
        );
        ?>
		</div>
		<?php 
    }
    
    /**
     * Save external permalink input
     *
     * @since 3.9.0
     */
    public function save_external_permalink( $post_id )
    {
        // Only proceed if nonce is verified
        
        if ( isset( $_POST['external_permalink_nonce'] ) && wp_verify_nonce( $_POST['external_permalink_nonce'], 'external_permalink_' . $post_id ) ) {
            // Get the value of external permalink from input field
            $external_permalink = ( isset( $_POST['external_permalink'] ) ? esc_url_raw( trim( $_POST['external_permalink'] ) ) : '' );
            // Update or delete external permalink post meta
            
            if ( !empty($external_permalink) ) {
                update_post_meta( $post_id, '_links_to', $external_permalink );
            } else {
                delete_post_meta( $post_id, '_links_to' );
            }
        
        }
    
    }
    
    /**
     * Change WordPress default permalink into external permalink for pages
     *
     * @since 3.9.0
     */
    public function use_external_permalink_for_pages( $permalink, $post_id )
    {
        $external_permalink = get_post_meta( $post_id, '_links_to', true );
        if ( !empty($external_permalink) ) {
            $permalink = $external_permalink;
        }
        return $permalink;
    }
    
    /**
     * Change WordPress default permalink into external permalink for posts and custom post types
     *
     * @since 3.9.0
     */
    public function use_external_permalink_for_posts( $permalink, $post )
    {
        $external_permalink = get_post_meta( $post->ID, '_links_to', true );
        
        if ( !empty($external_permalink) ) {
            $permalink = $external_permalink;
            if ( !is_admin() ) {
                $permalink = $permalink . '#new_tab';
            }
        }
        
        return $permalink;
    }
    
    /** 
     * Redirect page/post to external permalink if it's loaded directly from the WP default permalink
     *
     * @since 3.9.0
     */
    public function redirect_to_external_permalink()
    {
        global  $post ;
        // If not on/loading the single page/post URL, do nothing
        if ( !is_singular() ) {
            return;
        }
        $external_permalink = get_post_meta( $post->ID, '_links_to', true );
        
        if ( !empty($external_permalink) ) {
            wp_redirect( $external_permalink, 302 );
            // temporary redirect
            exit;
        }
    
    }
    
    /**
     * Parse links in content to add target="_blank" rel="noopener noreferrer nofollow" attributes
     * 
     * @since 4.9.0
     */
    public function add_target_and_rel_atts_to_content_links( $content )
    {
        
        if ( !empty($content) ) {
            // regex pattern for "a href"
            $regexp = "<a\\s[^>]*href=(\"??)([^\" >]*?)\\1[^>]*>";
            
            if ( preg_match_all(
                "/{$regexp}/siU",
                $content,
                $matches,
                PREG_SET_ORDER
            ) ) {
                // $matches might contain parts of $content that has links (a href)
                preg_match_all(
                    "/{$regexp}/siU",
                    $content,
                    $matches,
                    PREG_SET_ORDER
                );
                
                if ( is_array( $matches ) ) {
                    $i = 0;
                    foreach ( $matches as $match ) {
                        $original_tag = $match[0];
                        // e.g. <a title="Link Title" href="http://www.example.com/sit-quaerat">
                        $tag = $match[0];
                        // Same value as $original_tag but for further processing
                        $url = $match[2];
                        // e.g. http://www.example.com/sit-quaerat
                        
                        if ( false !== strpos( $url, get_site_url() ) ) {
                            // Internal link. Do nothing.
                        } elseif ( false === strpos( $url, 'http' ) ) {
                            // Relative link to internal URL. Do nothing.
                        } else {
                            // External link. Let's do something.
                            // Regex pattern for target="_blank|parent|self|top"
                            $pattern = '/target\\s*=\\s*"\\s*_(blank|parent|self|top)\\s*"/';
                            // If there's no 'target="_blank|parent|self|top"' in $tag, add target="blank"
                            if ( 0 === preg_match( $pattern, $tag ) ) {
                                // Replace closing > with ' target="_blank">'
                                $tag = substr_replace( $tag, ' target="_blank">', -1 );
                            }
                            // If there's no 'rel' attribute in $tag, add rel="noopener noreferrer nofollow"
                            $pattern = '/rel\\s*=\\s*\\"[a-zA-Z0-9_\\s]*\\"/';
                            
                            if ( 0 === preg_match( $pattern, $tag ) ) {
                                // Replace closing > with ' rel="noopener noreferrer nofollow">'
                                $tag = substr_replace( $tag, ' rel="noopener noreferrer nofollow">', -1 );
                            } else {
                                // replace rel="noopener" with rel="noopener noreferrer nofollow"
                                if ( false !== strpos( $tag, 'noopener' ) && false === strpos( $tag, 'noreferrer' ) && false === strpos( $tag, 'nofollow' ) ) {
                                    $tag = str_replace( 'noopener', 'noopener noreferrer nofollow', $tag );
                                }
                            }
                            
                            // Replace original a href tag with one containing target and rel attributes above
                            $content = str_replace( $original_tag, $tag, $content );
                        }
                        
                        $i++;
                    }
                }
            
            }
        
        }
        
        return $content;
    }
    
    /**
     * Add "open in new tab" checkbox in custom nav menu item settings
     * 
     * @since 5.4.0
     */
    public function add_custom_nav_menu_open_in_new_tab_field(
        $item_id,
        $menu_item,
        $depth,
        $args
    )
    {
        $target_blank = get_post_meta( $item_id, '_menu_item_target_blank', true );
        
        if ( 'custom' == $menu_item->object ) {
            ?>
			<p class="field-target_blank description-wide">
				<label for="edit-menu-item-target-blank-<?php 
            echo  esc_attr( $item_id ) ;
            ?>">
					<input type="checkbox" id="edit-menu-item-target-blank-<?php 
            echo  esc_attr( $item_id ) ;
            ?>" name="menu-item-target-blank[<?php 
            echo  esc_attr( $item_id ) ;
            ?>]" value="1" <?php 
            checked( $target_blank, '1' );
            ?> />
					Open link in new tab and add rel="noopener noreferrer nofollow" attribute.
				</label>
			</p>
		<?php 
        }
    
    }
    
    /**
     * Save status of "open in new tab" checkbox in custom nav menu item settings
     * 
     * @since 5.4.0
     */
    public function save_custom_nav_menu_open_in_new_tab_status( $menu_id, $menu_item_db_id, $args )
    {
        
        if ( isset( $_POST['menu-item-target-blank'][$menu_item_db_id] ) ) {
            update_post_meta( $menu_item_db_id, '_menu_item_target_blank', '1' );
        } else {
            delete_post_meta( $menu_item_db_id, '_menu_item_target_blank' );
        }
    
    }
    
    /**
     * Add attributes to custom nav menu item on the frontend
     * 
     * @since 5.4.0
     */
    public function add_attributes_to_custom_nav_menu_item( $atts, $menu_item, $args )
    {
        $target_blank = get_post_meta( $menu_item->ID, '_menu_item_target_blank', true );
        
        if ( $target_blank ) {
            $atts['target'] = '_blank';
            $atts['rel'] = 'noopener noreferrer nofollow';
        }
        
        return $atts;
    }
    
    /**
     * Publish posts of any type with missed schedule. 
     * We use the Transients API to reduce straining the site with DB queries on busy sites.
     * So, this function will only query the DB once every 15 minutes at most.
     *
     * @since 3.1.0
     */
    public function publish_missed_schedule_posts()
    {
        
        if ( is_front_page() || is_home() || is_page() || is_single() || is_singular() || is_archive() || is_admin() || is_blog_admin() || is_robots() || is_ssl() ) {
            // Get missed schedule posts data from cache
            $missed_schedule_posts = get_transient( 'asenha_missed_schedule_posts' );
            // Nothing found in cache
            
            if ( false === $missed_schedule_posts ) {
                global  $wpdb ;
                $current_gmt_datetime = gmdate( 'Y-m-d H:i:00' );
                $args = array(
                    'public'   => true,
                    '_builtin' => false,
                );
                $custom_post_types = get_post_types( $args, 'names' );
                // array, e.g. array( 'project', 'book', 'staff' )
                
                if ( count( $custom_post_types ) > 0 ) {
                    $custom_post_types = "'" . implode( "','", $custom_post_types ) . "'";
                    // string, e.g. 'project','book','staff'
                    $post_types = "'page','post'," . $custom_post_types;
                    // 'page','post','project','book','staff'
                } else {
                    $post_types = "'page','post'";
                }
                
                $sql = "SELECT ID FROM {$wpdb->posts} WHERE post_type IN ({$post_types}) AND post_status='future' AND post_date_gmt<'{$current_gmt_datetime}'";
                // The following does not work as backslashes are inserted before single quotes in $post_types
                // $sql = $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_type IN (%s) AND post_status='future' AND post_date_gmt<'%s'", array( $post_types, $current_gmt_datetime ) );
                $missed_schedule_posts = $wpdb->get_results( $sql, ARRAY_A );
                // Save query results as a transient with expiry of 15 minutes
                set_transient( 'asenha_missed_schedule_posts', $missed_schedule_posts, 15 * MINUTE_IN_SECONDS );
            }
            
            if ( empty($missed_schedule_posts) || !is_array( $missed_schedule_posts ) ) {
                return;
            }
            foreach ( $missed_schedule_posts as $post ) {
                wp_publish_post( $post['ID'] );
            }
        }
    
    }

}