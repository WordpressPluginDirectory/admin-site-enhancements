<?php

namespace ASENHA\Classes;

/**
 * Class for Admin Menu Organizer module
 *
 * @since 6.9.5
 */
class Admin_Menu_Organizer {
    /**
     * Render custom menu order
     *
     * @param $menu_order array an ordered array of menu items
     * @link https://developer.wordpress.org/reference/hooks/menu_order/
     * @since 2.0.0
     */
    public function render_custom_menu_order( $menu_order ) {
        global $menu;
        $options = get_option( ASENHA_SLUG_U );
        // Get current menu order. We're not using the default $menu_order which uses index.php, edit.php as array values.
        $current_menu_order = array();
        foreach ( $menu as $menu_key => $menu_info ) {
            if ( false !== strpos( $menu_info[4], 'wp-menu-separator' ) ) {
                $menu_item_id = $menu_info[2];
            } else {
                $menu_item_id = $menu_info[5];
            }
            $current_menu_order[] = array($menu_item_id, $menu_info[2]);
        }
        // Get custom menu order
        $custom_menu_order = $options['custom_menu_order'];
        // comma separated
        $custom_menu_order = explode( ",", $custom_menu_order );
        // array of menu ID, e.g. menu-dashboard
        // Return menu order for rendering
        $rendered_menu_order = array();
        // Render menu based on items saved in custom menu order
        foreach ( $custom_menu_order as $custom_menu_item_id ) {
            foreach ( $current_menu_order as $current_menu_item_id => $current_menu_item ) {
                if ( $custom_menu_item_id == $current_menu_item[0] ) {
                    $rendered_menu_order[] = $current_menu_item[1];
                }
            }
        }
        // Add items from current menu not already part of custom menu order, e.g. new plugin activated and adds new menu item
        foreach ( $current_menu_order as $current_menu_item_id => $current_menu_item ) {
            if ( !in_array( $current_menu_item[0], $custom_menu_order ) ) {
                $rendered_menu_order[] = $current_menu_item[1];
            }
        }
        return $rendered_menu_order;
    }

    /**
     * Apply custom menu item titles
     *
     * @since 2.9.0
     */
    public function apply_custom_menu_item_titles() {
        global $menu;
        $options = get_option( ASENHA_SLUG_U );
        // Get custom menu item titles
        $custom_menu_titles = $options['custom_menu_titles'];
        $custom_menu_titles = explode( ',', $custom_menu_titles );
        foreach ( $menu as $menu_key => $menu_info ) {
            if ( false !== strpos( $menu_info[4], 'wp-menu-separator' ) ) {
                $menu_item_id = $menu_info[2];
            } else {
                $menu_item_id = $menu_info[5];
            }
            // Get defaul/custom menu item title
            foreach ( $custom_menu_titles as $custom_menu_title ) {
                // At this point, $custom_menu_title value looks like toplevel_page_snippets__Code Snippets
                $custom_menu_title = explode( '__', $custom_menu_title );
                if ( $custom_menu_title[0] == $menu_item_id ) {
                    $menu_item_title = $custom_menu_title[1];
                    // e.g. Code Snippets
                    break;
                    // stop foreach loop so $menu_item_title is not overwritten in the next iteration
                } else {
                    $menu_item_title = $menu_info[0];
                }
            }
            $menu[$menu_key][0] = $menu_item_title;
        }
    }

    /**
     * Get custom title for 'Posts' menu item
     * 
     * @since 6.9.13
     */
    public function get_posts_custom_title() {
        $post_object = get_post_type_object( 'post' );
        // object
        if ( property_exists( $post_object, 'label' ) ) {
            $posts_default_title = $post_object->label;
        } else {
            $posts_default_title = $post_object->labels->name;
        }
        $posts_custom_title = $posts_default_title;
        $options = get_option( ASENHA_SLUG_U );
        $custom_menu_titles = ( isset( $options['custom_menu_titles'] ) ? explode( ',', $options['custom_menu_titles'] ) : array() );
        if ( !empty( $custom_menu_titles ) ) {
            foreach ( $custom_menu_titles as $custom_menu_title ) {
                if ( false !== strpos( $custom_menu_title, 'menu-posts__' ) ) {
                    $custom_menu_title = explode( '__', $custom_menu_title );
                    $posts_custom_title = $custom_menu_title[1];
                }
            }
        }
        return $posts_custom_title;
    }

    /**
     * For 'Posts', apply custom label
     * 
     * @link https://developer.wordpress.org/reference/hooks/post_type_labels_post_type/
     * @since 6.9.13
     */
    public function change_post_labels( $labels ) {
        $post_object = get_post_type_object( 'post' );
        // object
        if ( property_exists( $post_object, 'label' ) ) {
            $posts_default_title_plural = $post_object->label;
        } else {
            $posts_default_title_plural = $post_object->labels->name;
        }
        $posts_default_title_singular = $post_object->labels->singular_name;
        $posts_custom_title = $this->get_posts_custom_title();
        foreach ( $labels as $key => $label ) {
            if ( null === $label ) {
                continue;
            }
            $labels->{$key} = str_replace( [$posts_default_title_plural, $posts_default_title_singular], $posts_custom_title, $label );
        }
        return $labels;
    }

    /**
     * For 'Posts', apply custom label in post object
     * 
     * @since 6.9.12
     */
    public function change_post_object_label() {
        global $wp_post_types;
        $posts_custom_title = $this->get_posts_custom_title();
        $labels =& $wp_post_types['post']->labels;
        $labels->name = $posts_custom_title;
        $labels->singular_name = $posts_custom_title;
        $labels->add_new = __( 'Add New', 'admin-site-enhancements' );
        $labels->add_new_item = __( 'Add New', 'admin-site-enhancements' );
        $labels->edit_item = __( 'Edit', 'admin-site-enhancements' );
        $labels->new_item = $posts_custom_title;
        $labels->view_item = __( 'View', 'admin-site-enhancements' );
        $labels->search_items = sprintf( 
            /* translators: %s is the post type label */
            'Search %s',
            $posts_custom_title
         );
        $labels->not_found = sprintf( 
            /* translators: %s is the post type label */
            'No %s found',
            strtolower( $posts_custom_title )
         );
        $labels->not_found_in_trash = sprintf( 
            /* translators: %s is the post type label */
            'No %s found in Trash',
            strtolower( $posts_custom_title )
         );
    }

    /**
     * For 'Posts', apply custom label in menu and submenu
     * 
     * @since 6.9.12
     */
    public function change_post_menu_label() {
        global $submenu;
        $posts_custom_title = $this->get_posts_custom_title();
        if ( !empty( $posts_custom_title ) ) {
            $submenu['edit.php'][5][0] = sprintf( 
                /* translators: %s is the post type label */
                'All %s',
                $posts_custom_title
             );
        } else {
            $submenu['edit.php'][5][0] = sprintf( 
                /* translators: %s is the post type label */
                'All %s',
                $posts_default_title
             );
        }
    }

    /**
     * For 'Posts', apply custom label in admin bar
     * 
     * @since 6.9.12
     */
    public function change_wp_admin_bar( $wp_admin_bar ) {
        $posts_custom_title = $this->get_posts_custom_title();
        $new_post_node = $wp_admin_bar->get_node( 'new-post' );
        if ( $new_post_node ) {
            $new_post_node->title = $posts_custom_title;
            $wp_admin_bar->add_node( $new_post_node );
        }
    }

    /**
     * Hide parent menu items by adding class(es) to hide them
     *
     * @since 2.0.0
     */
    public function hide_menu_items() {
        global $menu;
        $common_methods = new Common_Methods();
        $menu_hidden_by_toggle = $common_methods->get_menu_hidden_by_toggle();
        // indexed array
        foreach ( $menu as $menu_key => $menu_info ) {
            if ( false !== strpos( $menu_info[4], 'wp-menu-separator' ) ) {
                $menu_item_id = $menu_info[2];
            } else {
                $menu_item_id = $menu_info[5];
            }
            // Append 'hidden' class to hide menu item until toggled
            if ( in_array( $menu_item_id, $menu_hidden_by_toggle ) ) {
                $menu[$menu_key][4] = $menu_info[4] . ' hidden asenha_hidden_menu';
            }
        }
    }

    /**
     * Add toggle to show hidden menu items
     *
     * @since 2.0.0
     */
    public function add_hidden_menu_toggle() {
        global $current_user;
        // Get menu items hidden by toggle
        $common_methods = new Common_Methods();
        $menu_hidden_by_toggle = $common_methods->get_menu_hidden_by_toggle();
        $submenu_hidden_by_toggle = array();
        // Get user capabilities the "Show All/Less" toggle should be shown for
        $user_capabilities_to_show_menu_toggle_for = $common_methods->get_user_capabilities_to_show_menu_toggle_for();
        // Get current user's capabilities from the user's role(s)
        $current_user_capabilities = '';
        $current_user_roles = $current_user->roles;
        // indexed array of role slugs
        foreach ( $current_user_roles as $current_user_role ) {
            $current_user_role_capabilities = get_role( $current_user_role )->capabilities;
            $current_user_role_capabilities = array_keys( $current_user_role_capabilities );
            // indexed array
            $current_user_role_capabilities = implode( ",", $current_user_role_capabilities );
            $current_user_capabilities .= $current_user_role_capabilities;
        }
        $current_user_capabilities = array_unique( explode( ",", $current_user_capabilities ) );
        // Maybe show "Show All/Less" toggle
        $show_toggle_menu = false;
        foreach ( $user_capabilities_to_show_menu_toggle_for as $user_capability_to_show_menu_toggle_for ) {
            if ( in_array( $user_capability_to_show_menu_toggle_for, $current_user_capabilities ) ) {
                $show_toggle_menu = true;
                break;
            }
        }
        if ( (!empty( $menu_hidden_by_toggle ) || !empty( $submenu_hidden_by_toggle )) && $show_toggle_menu ) {
            add_menu_page(
                __( 'Show All', 'admin-site-enhancements' ),
                __( 'Show All', 'admin-site-enhancements' ),
                'read',
                'asenha_show_hidden_menu',
                function () {
                    return false;
                },
                "dashicons-arrow-down-alt2",
                300
            );
            add_menu_page(
                __( 'Show Less', 'admin-site-enhancements' ),
                __( 'Show Less', 'admin-site-enhancements' ),
                'read',
                'asenha_hide_hidden_menu',
                function () {
                    return false;
                },
                "dashicons-arrow-up-alt2",
                301
            );
        }
    }

    /**
     * Script to toggle hidden menu itesm
     *
     * @since 2.0.0
     */
    public function enqueue_toggle_hidden_menu_script() {
        // Get menu items hidden by toggle
        $common_methods = new Common_Methods();
        $menu_hidden_by_toggle = $common_methods->get_menu_hidden_by_toggle();
        $submenu_hidden_by_toggle = array();
        if ( !empty( $menu_hidden_by_toggle ) || !empty( $submenu_hidden_by_toggle ) ) {
            // Script to set behaviour and actions of the sortable menu
            wp_enqueue_script(
                'asenha-toggle-hidden-menu',
                ASENHA_URL . 'assets/js/toggle-hidden-menu.js',
                array(),
                ASENHA_VERSION,
                false
            );
        }
    }

}
