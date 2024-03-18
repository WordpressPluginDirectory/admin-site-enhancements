<?php

// We're using the singleton design pattern
// https://code.tutsplus.com/articles/design-patterns-in-wordpress-the-singleton-pattern--wp-31621
// https://carlalexander.ca/singletons-in-wordpress/
// https://torquemag.io/2016/11/singletons-wordpress-good-evil/
/**
 * Main class of the plugin used to add functionalities
 *
 * @since 1.0.0
 */
class Admin_Site_Enhancements
{
    // Refers to a single instance of this class
    private static  $instance = null ;
    /**
     * Creates or returns a single instance of this class
     *
     * @return Admin_Site_Enhancements a single instance of this class
     * @since 1.0.0
     */
    public static function get_instance()
    {
        if ( null == self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Initialize plugin functionalities
     */
    private function __construct()
    {
        global  $pagenow, $typenow ;
        // Setup admin menu, admin page, settings, settings sections, sections fields, admin scripts, plugin action links, etc.
        // Register admin menu and add the settings page.
        add_action( 'admin_menu', 'asenha_register_admin_menu' );
        // Register plugin settings
        // Instantiate object for registration of settings section and fields
        $settings = new ASENHA\Classes\Settings_Sections_Fields();
        add_action( 'admin_init', [ $settings, 'register_sections_fields' ] );
        // Suppress all notices on the plugin's main page. Then add notification for successful settings update.
        add_action( 'admin_notices', 'asenha_suppress_add_notices', 5 );
        add_action( 'all_admin_notices', 'asenha_suppress_generic_notices', 5 );
        // Enqueue admin scripts and styles
        add_action( 'admin_enqueue_scripts', 'asenha_admin_scripts' );
        // Enqueue public scripts and styles
        add_action( 'wp_enqueue_scripts', 'asenha_public_scripts' );
        // Dequeue scripts that prevents settings page from working
        add_action( 'wp_print_scripts', 'asenha_dequeue_scritps', PHP_INT_MAX );
        add_action( 'admin_print_footer_scripts', 'asenha_dequeue_scritps', PHP_INT_MAX );
        add_action( 'admin_enqueue_scripts-tools_page_admin-site-enhancements', 'asenha_dequeue_scritps', PHP_INT_MAX );
        add_action( 'admin_print_scripts-tools_page_admin-site-enhancements', 'asenha_dequeue_scritps', PHP_INT_MAX );
        // Add admin bar inline styles
        add_action( 'admin_head', 'asenha_admin_bar_item_js_css' );
        add_action( 'wp_head', 'asenha_admin_bar_item_js_css' );
        add_filter( 'plugin_action_links_' . ASENHA_SLUG . '/' . ASENHA_SLUG . '.php', 'asenha_plugin_action_links' );
        // Mark that a user have sponsored ASE (via AJAX)
        add_action( 'wp_ajax_have_sponsored', 'asenha_have_sponsored' );
        // Dismiss upgrade nudge (via AJAX)
        add_action( 'wp_ajax_dismiss_upgrade_nudge', 'asenha_dismiss_upgrade_nudge' );
        // Dismiss sponsorship nudge (via AJAX)
        add_action( 'wp_ajax_dismiss_sponsorship_nudge', 'asenha_dismiss_sponsorship_nudge' );
        if ( function_exists( 'bwasenha_fs' ) ) {
            bwasenha_fs()->add_filter( 'plugin_icon', 'fs_custom_optin_icon__premium_only' );
        }
        // ===== Activate features based on settings =====
        // Get all WP Enhancements options, default to empty array in case it's not been created yet
        $options = get_option( ASENHA_SLUG_U, array() );
        // =================================================================
        // CONTENT MANAGEMENT
        // =================================================================
        // Instantiate object for Content Management features
        $content_management = new ASENHA\Classes\Content_Management();
        // Content Duplication
        
        if ( array_key_exists( 'enable_duplication', $options ) && $options['enable_duplication'] ) {
            add_action( 'admin_action_duplicate_content', [ $content_management, 'duplicate_content' ] );
            add_filter(
                'page_row_actions',
                [ $content_management, 'add_duplication_action_link' ],
                10,
                2
            );
            add_filter(
                'post_row_actions',
                [ $content_management, 'add_duplication_action_link' ],
                10,
                2
            );
            add_action( 'admin_bar_menu', [ $content_management, 'add_admin_bar_duplication_link' ], 100 );
        }
        
        // Content Order
        if ( array_key_exists( 'content_order', $options ) && $options['content_order'] ) {
            
            if ( array_key_exists( 'content_order_for', $options ) && !empty($options['content_order_for']) ) {
                add_action( 'admin_menu', [ $content_management, 'add_content_order_submenu' ] );
                add_action( 'wp_ajax_save_custom_order', [ $content_management, 'save_custom_content_order' ] );
                add_filter( 'pre_get_posts', [ $content_management, 'orderby_menu_order' ] );
                add_filter(
                    'save_post',
                    [ $content_management, 'set_menu_order_for_new_posts' ],
                    10,
                    3
                );
            }
        
        }
        // Media Replacement
        
        if ( array_key_exists( 'enable_media_replacement', $options ) && $options['enable_media_replacement'] ) {
            add_filter(
                'media_row_actions',
                [ $content_management, 'modify_media_list_table_edit_link' ],
                10,
                2
            );
            add_filter(
                'attachment_fields_to_edit',
                [ $content_management, 'add_media_replacement_button' ],
                10,
                2
            );
            add_action( 'edit_attachment', [ $content_management, 'replace_media' ] );
            add_filter( 'post_updated_messages', [ $content_management, 'attachment_updated_custom_message' ] );
            // Bust browser cache of old/replaced images
            add_filter(
                'wp_calculate_image_srcset',
                [ $content_management, 'append_cache_busting_param_to_image_srcset' ],
                10,
                5
            );
            add_filter(
                'wp_get_attachment_image_src',
                [ $content_management, 'append_cache_busting_param_to_attachment_image_src' ],
                10,
                2
            );
            add_filter(
                'wp_prepare_attachment_for_js',
                [ $content_management, 'append_cache_busting_param_to_attachment_for_js' ],
                10,
                2
            );
            add_filter(
                'wp_get_attachment_url',
                [ $content_management, 'append_cache_busting_param_to_attachment_url' ],
                20,
                2
            );
        }
        
        // Media Library Infinite Scrolling
        if ( array_key_exists( 'media_library_infinite_scrolling', $options ) && $options['media_library_infinite_scrolling'] ) {
            add_filter( 'media_library_infinite_scrolling', '__return_true' );
        }
        // Enable SVG Upload
        
        if ( array_key_exists( 'enable_svg_upload', $options ) && $options['enable_svg_upload'] && array_key_exists( 'enable_svg_upload_for', $options ) && isset( $options['enable_svg_upload_for'] ) ) {
            global  $roles_svg_upload_enabled ;
            $enable_svg_upload = $options['enable_svg_upload'];
            $for_roles = $options['enable_svg_upload_for'];
            // User has role(s). Do further checks.
            
            if ( isset( $for_roles ) && count( $for_roles ) > 0 ) {
                // Assemble single-dimensional array of roles for which SVG upload would be enabled
                $roles_svg_upload_enabled = array();
                foreach ( $for_roles as $role_slug => $svg_upload_enabled ) {
                    if ( $svg_upload_enabled ) {
                        $roles_svg_upload_enabled[] = $role_slug;
                    }
                }
            }
            
            add_filter( 'upload_mimes', [ $content_management, 'add_svg_mime' ] );
            add_filter(
                'wp_check_filetype_and_ext',
                [ $content_management, 'confirm_file_type_is_svg' ],
                10,
                4
            );
            add_filter( 'wp_handle_upload_prefilter', [ $content_management, 'sanitize_and_maybe_allow_svg_upload' ] );
            add_filter(
                'wp_generate_attachment_metadata',
                [ $content_management, 'generate_svg_metadata' ],
                10,
                3
            );
            add_action( 'wp_ajax_svg_get_attachment_url', [ $content_management, 'get_svg_attachment_url' ] );
            add_filter( 'wp_prepare_attachment_for_js', [ $content_management, 'get_svg_url_in_media_library' ] );
        }
        
        // Enable External Permalinks
        if ( array_key_exists( 'enable_external_permalinks', $options ) && $options['enable_external_permalinks'] ) {
            
            if ( array_key_exists( 'enable_external_permalinks_for', $options ) && !empty($options['enable_external_permalinks_for']) ) {
                add_action(
                    'add_meta_boxes',
                    [ $content_management, 'add_external_permalink_meta_box' ],
                    10,
                    2
                );
                add_action( 'save_post', [ $content_management, 'save_external_permalink' ] );
                // Filter the permalink for use by get_permalink()
                add_filter(
                    'page_link',
                    [ $content_management, 'use_external_permalink_for_pages' ],
                    20,
                    2
                );
                add_filter(
                    'post_link',
                    [ $content_management, 'use_external_permalink_for_posts' ],
                    20,
                    2
                );
                add_filter(
                    'post_type_link',
                    [ $content_management, 'use_external_permalink_for_posts' ],
                    20,
                    2
                );
                // Enable redirection to external permalink when page/post is opened directly via it's WP permalink
                add_action( 'wp', [ $content_management, 'redirect_to_external_permalink' ] );
            }
        
        }
        // Open All External Links in New Tab
        if ( array_key_exists( 'external_links_new_tab', $options ) && $options['external_links_new_tab'] ) {
            add_filter( 'the_content', [ $content_management, 'add_target_and_rel_atts_to_content_links' ] );
        }
        // Allow Custom Menu Links to Open in New Tab
        
        if ( array_key_exists( 'custom_nav_menu_items_new_tab', $options ) && $options['custom_nav_menu_items_new_tab'] ) {
            add_filter(
                'wp_nav_menu_item_custom_fields',
                [ $content_management, 'add_custom_nav_menu_open_in_new_tab_field' ],
                10,
                4
            );
            add_action(
                'wp_update_nav_menu_item',
                [ $content_management, 'save_custom_nav_menu_open_in_new_tab_status' ],
                10,
                3
            );
            add_action(
                'nav_menu_link_attributes',
                [ $content_management, 'add_attributes_to_custom_nav_menu_item' ],
                10,
                3
            );
        }
        
        // Enable Auto-Publishing of Posts with Missed Schedules
        
        if ( array_key_exists( 'enable_missed_schedule_posts_auto_publish', $options ) && $options['enable_missed_schedule_posts_auto_publish'] ) {
            add_action( 'wp_head', [ $content_management, 'publish_missed_schedule_posts' ] );
            add_action( 'admin_head', [ $content_management, 'publish_missed_schedule_posts' ] );
        }
        
        // =================================================================
        // ADMIN INTERFACE
        // =================================================================
        // Instantiate object for Admin Interface features
        $admin_interface = new ASENHA\Classes\Admin_Interface();
        // Hide or Modify Elements / Clean Up Admin Bar
        
        if ( array_key_exists( 'hide_modify_elements', $options ) && $options['hide_modify_elements'] ) {
            // Priority 5 to execute earlier than the normal 10. This is for removing default items.
            add_filter( 'admin_bar_menu', [ $admin_interface, 'modify_admin_bar_menu' ], 5 );
            if ( array_key_exists( 'hide_help_drawer', $options ) && $options['hide_help_drawer'] ) {
                add_action( 'admin_head', [ $admin_interface, 'hide_help_drawer' ] );
            }
        }
        
        // Hide Admin Notices
        
        if ( array_key_exists( 'hide_admin_notices', $options ) && $options['hide_admin_notices'] ) {
            add_action( 'admin_notices', [ $admin_interface, 'admin_notices_wrapper' ], 9 );
            // add_action( 'all_admin_notices', [ $admin_interface, 'admin_notices_wrapper' ] );
            add_action( 'admin_bar_menu', [ $admin_interface, 'admin_notices_menu' ] );
            add_action( 'admin_print_styles', [ $admin_interface, 'admin_notices_menu_inline_css' ] );
        }
        
        // Disable Dashboard Widgets
        if ( array_key_exists( 'disable_dashboard_widgets', $options ) && $options['disable_dashboard_widgets'] ) {
            add_action( 'wp_dashboard_setup', [ $admin_interface, 'disable_dashboard_widgets' ], 99 );
        }
        // Hide Admin Bar
        // On the frontend
        if ( array_key_exists( 'hide_admin_bar', $options ) && $options['hide_admin_bar'] && array_key_exists( 'hide_admin_bar_for', $options ) && isset( $options['hide_admin_bar_for'] ) ) {
            add_filter( 'show_admin_bar', [ $admin_interface, 'hide_admin_bar_for_roles_on_frontend' ] );
        }
        // Wider Admin Menu
        if ( array_key_exists( 'wider_admin_menu', $options ) && $options['wider_admin_menu'] ) {
            add_action( 'admin_head', [ $admin_interface, 'set_custom_menu_width' ], 99 );
        }
        // Admin Menu Organizer
        
        if ( array_key_exists( 'customize_admin_menu', $options ) && $options['customize_admin_menu'] ) {
            // add_action( 'wp_ajax_save_custom_menu_order', [ $admin_interface, 'save_custom_menu_order' ] );
            // add_action( 'wp_ajax_save_hidden_menu_items', [ $admin_interface, 'save_hidden_menu_items' ] );
            
            if ( array_key_exists( 'custom_menu_order', $options ) ) {
                add_filter( 'custom_menu_order', '__return_true' );
                add_filter( 'menu_order', [ $admin_interface, 'render_custom_menu_order' ], PHP_INT_MAX );
            }
            
            if ( array_key_exists( 'custom_menu_titles', $options ) ) {
                add_action( 'admin_menu', [ $admin_interface, 'apply_custom_menu_item_titles' ], 999995 );
            }
            
            if ( array_key_exists( 'custom_menu_hidden', $options ) || array_key_exists( 'custom_menu_always_hidden', $options ) ) {
                add_action( 'admin_menu', [ $admin_interface, 'hide_menu_items' ], 999996 );
                add_action( 'admin_menu', [ $admin_interface, 'add_hidden_menu_toggle' ], 999997 );
                add_action( 'admin_enqueue_scripts', [ $admin_interface, 'enqueue_toggle_hidden_menu_script' ] );
            }
        
        }
        
        // Enhance List Tables
        
        if ( array_key_exists( 'enhance_list_tables', $options ) && $options['enhance_list_tables'] ) {
            // Show Featured Image Column
            if ( array_key_exists( 'show_featured_image_column', $options ) && $options['show_featured_image_column'] ) {
                add_action( 'admin_init', [ $admin_interface, 'show_featured_image_column' ] );
            }
            // Show Excerpt Column
            if ( array_key_exists( 'show_excerpt_column', $options ) && $options['show_excerpt_column'] ) {
                add_action( 'admin_init', [ $admin_interface, 'show_excerpt_column' ] );
            }
            // Show ID Column
            if ( array_key_exists( 'show_id_column', $options ) && $options['show_id_column'] ) {
                add_action( 'admin_init', [ $admin_interface, 'show_id_column' ] );
            }
            // Show ID in Action Row
            if ( array_key_exists( 'show_id_in_action_row', $options ) && $options['show_id_in_action_row'] ) {
                add_action( 'admin_init', [ $admin_interface, 'show_id_in_action_row' ] );
            }
            // Show Custom Taxonomy Filters
            if ( array_key_exists( 'show_custom_taxonomy_filters', $options ) && $options['show_custom_taxonomy_filters'] ) {
                add_action( 'restrict_manage_posts', [ $admin_interface, 'show_custom_taxonomy_filters' ] );
            }
            // Hide Comments Column
            if ( array_key_exists( 'hide_comments_column', $options ) && $options['hide_comments_column'] ) {
                add_action( 'admin_init', [ $admin_interface, 'hide_comments_column' ] );
            }
            // Hide Post Tags Column
            if ( array_key_exists( 'hide_post_tags_column', $options ) && $options['hide_post_tags_column'] ) {
                add_action( 'admin_init', [ $admin_interface, 'hide_post_tags_column' ] );
            }
        }
        
        // Display Active Plugins First
        if ( array_key_exists( 'display_active_plugins_first', $options ) && $options['display_active_plugins_first'] ) {
            add_action( 'admin_head-plugins.php', [ $admin_interface, 'show_active_plugins_first' ] );
        }
        // Custom Admin Footer Text
        
        if ( array_key_exists( 'custom_admin_footer_text', $options ) && $options['custom_admin_footer_text'] ) {
            // Update footer text
            
            if ( is_asenha() ) {
                add_filter( 'admin_footer_text', 'asenha_footer_text', 20 );
            } else {
                add_filter( 'admin_footer_text', [ $admin_interface, 'custom_admin_footer_text_left' ], 20 );
            }
            
            // Update footer version text
            
            if ( is_asenha() ) {
                add_filter( 'update_footer', 'asenha_footer_version_text', 20 );
            } else {
                add_filter( 'update_footer', [ $admin_interface, 'custom_admin_footer_text_right' ], 20 );
            }
        
        } else {
            // Update footer text
            if ( is_asenha() ) {
                add_filter( 'admin_footer_text', 'asenha_footer_text', 20 );
            }
            // Update footer version text
            if ( is_asenha() ) {
                add_filter( 'update_footer', 'asenha_footer_version_text', 20 );
            }
        }
        
        // =================================================================
        // LOG IN | LOG OUT
        // =================================================================
        // Instantiate object for Log In Log Out features
        $login_logout = new ASENHA\Classes\Login_Logout();
        // Change Login URL
        if ( array_key_exists( 'change_login_url', $options ) && $options['change_login_url'] ) {
            
            if ( array_key_exists( 'custom_login_slug', $options ) && !empty($options['custom_login_slug']) ) {
                add_action( 'init', [ $login_logout, 'redirect_on_custom_login_url' ] );
                // add_filter( 'login_url', [ $login_logout, 'customize_login_url' ] );
                add_filter( 'lostpassword_url', [ $login_logout, 'customize_lost_password_url' ] );
                add_filter( 'register_url', [ $login_logout, 'customize_register_url' ] );
                add_action( 'wp_loaded', [ $login_logout, 'redirect_on_default_login_urls' ] );
                add_action( 'wp_login_failed', [ $login_logout, 'redirect_to_custom_login_url_on_login_fail' ] );
                add_filter( 'login_message', [ $login_logout, 'add_failed_login_message' ] );
                add_action( 'wp_logout', [ $login_logout, 'redirect_to_custom_login_url_on_logout_success' ] );
            }
        
        }
        // Login ID Type
        if ( array_key_exists( 'login_id_type_restriction', $options ) && $options['login_id_type_restriction'] ) {
            if ( array_key_exists( 'login_id_type', $options ) && !empty($options['login_id_type']) ) {
                switch ( $options['login_id_type'] ) {
                    case 'username':
                        remove_filter( 'authenticate', 'wp_authenticate_email_password', 20 );
                        add_filter( 'login_form_defaults', [ $login_logout, 'change_login_form_defaults' ] );
                        add_filter(
                            'gettext',
                            [ $login_logout, 'gettext_login_id_username' ],
                            20,
                            3
                        );
                        break;
                    case 'email':
                        add_filter(
                            'authenticate',
                            [ $login_logout, 'authenticate_email' ],
                            20,
                            2
                        );
                        add_filter(
                            'gettext',
                            [ $login_logout, 'gettext_login_id_email' ],
                            20,
                            3
                        );
                        break;
                }
            }
        }
        // Use Site Identity on the Login Page
        
        if ( array_key_exists( 'site_identity_on_login', $options ) && $options['site_identity_on_login'] ) {
            add_action( 'login_head', [ $login_logout, 'use_site_icon_on_login' ] );
            add_filter( 'login_headerurl', [ $login_logout, 'use_site_url_on_login' ] );
        }
        
        // Enable Login Logout Menu
        
        if ( array_key_exists( 'enable_login_logout_menu', $options ) && $options['enable_login_logout_menu'] ) {
            add_action( 'admin_head-nav-menus.php', [ $login_logout, 'add_login_logout_metabox' ] );
            add_filter( 'wp_setup_nav_menu_item', [ $login_logout, 'set_login_logout_menu_item_dynamic_url' ] );
            add_filter( 'wp_nav_menu_objects', [ $login_logout, 'maybe_remove_login_or_logout_menu_item' ] );
        }
        
        // Enable Last Login Column
        
        if ( array_key_exists( 'enable_last_login_column', $options ) && $options['enable_last_login_column'] ) {
            add_action( 'wp_login', [ $login_logout, 'log_login_datetime' ] );
            add_filter( 'manage_users_columns', [ $login_logout, 'add_last_login_column' ] );
            add_filter(
                'manage_users_custom_column',
                [ $login_logout, 'show_last_login_info' ],
                10,
                3
            );
            add_action( 'admin_print_styles-users.php', [ $login_logout, 'add_column_style' ] );
        }
        
        // Redirect After Login
        if ( array_key_exists( 'redirect_after_login', $options ) && $options['redirect_after_login'] ) {
            if ( array_key_exists( 'redirect_after_login_to_slug', $options ) && !empty($options['redirect_after_login_to_slug']) ) {
                if ( array_key_exists( 'redirect_after_login_for', $options ) && !empty($options['redirect_after_login_for']) ) {
                    add_filter(
                        'wp_login',
                        [ $login_logout, 'redirect_for_roles_after_login' ],
                        5,
                        2
                    );
                }
            }
        }
        // Redirect After Logout
        if ( array_key_exists( 'redirect_after_logout', $options ) && $options['redirect_after_logout'] ) {
            if ( array_key_exists( 'redirect_after_logout_to_slug', $options ) && !empty($options['redirect_after_logout_to_slug']) ) {
                
                if ( array_key_exists( 'redirect_after_logout_for', $options ) && !empty($options['redirect_after_logout_for']) ) {
                    add_action(
                        'wp_logout',
                        [ $login_logout, 'redirect_after_logout' ],
                        5,
                        1
                    );
                    // load earlier than Change Login URL add_action
                }
            
            }
        }
        // =================================================================
        // CUSTOM CODE
        // =================================================================
        // Instantiate object for Custom Code features
        $custom_code = new ASENHA\Classes\Custom_Code();
        // Enable Custom Admin / Frontend CSS
        if ( array_key_exists( 'enable_custom_admin_css', $options ) && $options['enable_custom_admin_css'] ) {
            // add_filter( 'admin_enqueue_scripts', [ $custom_code, 'custom_admin_css' ] );
            add_filter( 'admin_print_footer_scripts', [ $custom_code, 'custom_admin_css' ] );
        }
        if ( array_key_exists( 'enable_custom_frontend_css', $options ) && $options['enable_custom_frontend_css'] ) {
            add_filter( 'wp_head', [ $custom_code, 'custom_frontend_css' ] );
        }
        // Custom Body Class
        if ( array_key_exists( 'enable_custom_body_class', $options ) && $options['enable_custom_body_class'] ) {
            
            if ( array_key_exists( 'enable_custom_body_class_for', $options ) && !empty($options['enable_custom_body_class_for']) ) {
                add_action(
                    'add_meta_boxes',
                    [ $custom_code, 'add_custom_body_class_meta_box' ],
                    10,
                    2
                );
                add_action( 'save_post', [ $custom_code, 'save_custom_body_class' ], 99 );
                add_filter( 'body_class', [ $custom_code, 'append_custom_body_class' ], 99 );
            }
        
        }
        // Manage ads.txt and app-ads.txt
        if ( array_key_exists( 'manage_ads_appads_txt', $options ) && $options['manage_ads_appads_txt'] ) {
            add_action( 'init', [ $custom_code, 'show_ads_appads_txt_content' ] );
        }
        // Manage robots.txt
        if ( array_key_exists( 'manage_robots_txt', $options ) && $options['manage_robots_txt'] ) {
            add_filter(
                'robots_txt',
                [ $custom_code, 'maybe_show_custom_robots_txt_content' ],
                10,
                2
            );
        }
        // Insert <head>, <body> and <footer> code
        
        if ( array_key_exists( 'insert_head_body_footer_code', $options ) && $options['insert_head_body_footer_code'] ) {
            
            if ( isset( $options['head_code_priority'] ) ) {
                add_action( 'wp_head', [ $custom_code, 'insert_head_code' ], $options['head_code_priority'] );
            } else {
                add_action( 'wp_head', [ $custom_code, 'insert_head_code' ], 10 );
            }
            
            if ( function_exists( 'wp_body_open' ) && version_compare( get_bloginfo( 'version' ), '5.2', '>=' ) ) {
                
                if ( isset( $options['body_code_priority'] ) ) {
                    add_action( 'wp_body_open', [ $custom_code, 'insert_body_code' ], $options['body_code_priority'] );
                } else {
                    add_action( 'wp_body_open', [ $custom_code, 'insert_body_code' ], 10 );
                }
            
            }
            
            if ( isset( $options['footer_code_priority'] ) ) {
                add_action( 'wp_footer', [ $custom_code, 'insert_footer_code' ], $options['footer_code_priority'] );
            } else {
                add_action( 'wp_footer', [ $custom_code, 'insert_footer_code' ], 10 );
            }
        
        }
        
        // =================================================================
        // DISABLE COMPONENTS
        // =================================================================
        // Instantiate object for Disable Components features
        $disable_components = new ASENHA\Classes\Disable_Components();
        // Disable Gutenberg
        if ( array_key_exists( 'disable_gutenberg', $options ) && $options['disable_gutenberg'] ) {
            
            if ( array_key_exists( 'disable_gutenberg_for', $options ) && !empty($options['disable_gutenberg_for']) ) {
                add_action( 'admin_init', [ $disable_components, 'disable_gutenberg_for_post_types_admin' ] );
                if ( array_key_exists( 'disable_gutenberg_frontend_styles', $options ) && $options['disable_gutenberg_frontend_styles'] ) {
                    add_action( 'wp_enqueue_scripts', [ $disable_components, 'disable_gutenberg_for_post_types_frontend' ], 100 );
                }
            }
        
        }
        // Disable Block-Based Widgets Screen
        
        if ( array_key_exists( 'disable_block_widgets', $options ) && $options['disable_block_widgets'] ) {
            // Disables the block editor from managing widgets in the Gutenberg plugin.
            add_filter( 'gutenberg_use_widgets_block_editor', '__return_false' );
            // Disables the block editor from managing widgets.
            add_filter( 'use_widgets_block_editor', '__return_false' );
        }
        
        // Disable Comments
        if ( array_key_exists( 'disable_comments', $options ) && $options['disable_comments'] ) {
            
            if ( array_key_exists( 'disable_comments_for', $options ) && !empty($options['disable_comments_for']) ) {
                add_action( 'admin_init', [ $disable_components, 'disable_comments_for_post_types_edit' ] );
                // also work with 'init', 'admin_init', 'wp_loaded', 'do_meta_boxes' hooks
                add_action( 'template_redirect', [ $disable_components, 'show_blank_comment_template' ] );
                add_action( 'wp_loaded', [ $disable_components, 'hide_existing_comments_on_frontend' ] );
                add_filter(
                    'comments_array',
                    [ $disable_components, 'maybe_return_empty_comments' ],
                    20,
                    2
                );
                add_filter(
                    'comments_open',
                    [ $disable_components, 'close_comments_pings_on_frontend' ],
                    20,
                    2
                );
                add_filter(
                    'pings_open',
                    [ $disable_components, 'close_comments_pings_on_frontend' ],
                    20,
                    2
                );
                add_filter(
                    'get_comments_number',
                    [ $disable_components, 'return_zero_comments_count' ],
                    20,
                    2
                );
                // Disable commenting via XML-RPC
                add_filter( 'xmlrpc_allow_anonymous_comments', '__return_false' );
                add_filter( 'xmlrpc_methods', [ $disable_components, 'disable_xmlrpc_comments' ] );
                // Disable commenting via REST API
                add_filter( 'rest_endpoints', [ $disable_components, 'disable_rest_api_comments_endpoints' ] );
                add_filter(
                    'rest_pre_insert_comment',
                    [ $disable_components, 'return_blank_comment' ],
                    10,
                    2
                );
            }
        
        }
        // Disable REST API
        
        if ( array_key_exists( 'disable_rest_api', $options ) && $options['disable_rest_api'] ) {
            
            if ( version_compare( get_bloginfo( 'version' ), '4.7', '>=' ) ) {
                add_filter( 'rest_authentication_errors', [ $disable_components, 'disable_rest_api' ] );
            } else {
                // REST API 1.x
                add_filter( 'json_enabled', '__return_false' );
                add_filter( 'json_jsonp_enabled', '__return_false' );
                // REST API 2.x
                add_filter( 'rest_enabled', '__return_false' );
                add_filter( 'rest_jsonp_enabled', '__return_false' );
            }
            
            remove_action( 'wp_head', 'rest_output_link_wp_head', 10 );
            // Disable REST API links in HTML <head>
            remove_action(
                'template_redirect',
                'rest_output_link_header',
                11,
                0
            );
            // Disable REST API link in HTTP headers
            remove_action( 'xmlrpc_rsd_apis', 'rest_output_rsd' );
            // Remove REST API URL from the WP RSD endpoint.
        }
        
        // Disable Feeds
        
        if ( array_key_exists( 'disable_feeds', $options ) && $options['disable_feeds'] ) {
            remove_action( 'wp_head', 'feed_links', 2 );
            // Remove feed links in <head>
            remove_action( 'wp_head', 'feed_links_extra', 3 );
            // Remove feed links in <head>
            remove_action(
                'do_feed_rdf',
                'do_feed_rdf',
                10,
                0
            );
            remove_action(
                'do_feed_rss',
                'do_feed_rss',
                10,
                0
            );
            remove_action(
                'do_feed_rss2',
                'do_feed_rss2',
                10,
                1
            );
            remove_action(
                'do_feed_atom',
                'do_feed_atom',
                10,
                1
            );
            add_action(
                'template_redirect',
                [ $disable_components, 'redirect_feed_to_403' ],
                10,
                1
            );
        }
        
        // Disable All Updates
        
        if ( array_key_exists( 'disable_all_updates', $options ) && $options['disable_all_updates'] ) {
            add_action( 'admin_init', [ $disable_components, 'disable_update_notices_version_checks' ] );
            // Disable core update
            add_filter( 'pre_transient_update_core', [ $disable_components, 'override_version_check_info' ] );
            add_filter( 'pre_site_transient_update_core', [ $disable_components, 'override_version_check_info' ] );
            // Disable theme updates
            add_filter( 'pre_transient_update_themes', [ $disable_components, 'override_version_check_info' ] );
            add_filter( 'pre_site_transient_update_themes', [ $disable_components, 'override_version_check_info' ] );
            add_action( 'pre_set_site_transient_update_themes', [ $disable_components, 'override_version_check_info' ], 20 );
            // Disable plugin updates
            add_filter( 'pre_transient_update_plugins', [ $disable_components, 'override_version_check_info' ] );
            add_filter( 'pre_site_transient_update_plugins', [ $disable_components, 'override_version_check_info' ] );
            add_action( 'pre_set_site_transient_update_plugins', [ $disable_components, 'override_version_check_info' ], 20 );
            // Disable auto updates
            add_filter( 'automatic_updater_disabled', '__return_true' );
            if ( !defined( 'AUTOMATIC_UPDATER_DISABLED' ) ) {
                define( 'AUTOMATIC_UPDATER_DISABLED', true );
            }
            if ( !defined( 'WP_AUTO_UPDATE_CORE' ) ) {
                define( 'WP_AUTO_UPDATE_CORE', false );
            }
            add_filter( 'auto_update_core', '__return_false' );
            add_filter( 'wp_auto_update_core', '__return_false' );
            add_filter( 'allow_minor_auto_core_updates', '__return_false' );
            add_filter( 'allow_major_auto_core_updates', '__return_false' );
            add_filter( 'allow_dev_auto_core_updates', '__return_false' );
            add_filter( 'auto_update_plugin', '__return_false' );
            add_filter( 'auto_update_theme', '__return_false' );
            add_filter( 'auto_update_translation', '__return_false' );
            remove_action( 'init', 'wp_schedule_update_checks' );
            // Disable update emails
            add_filter( 'auto_core_update_send_email', '__return_false' );
            add_filter( 'send_core_update_notification_email', '__return_false' );
            add_filter( 'automatic_updates_send_debug_email', '__return_false' );
            // Remove Dashboard >> Updates menu
            add_action( 'admin_menu', [ $disable_components, 'remove_updates_menu' ] );
        }
        
        // Disable Smaller Components
        
        if ( array_key_exists( 'disable_smaller_components', $options ) && $options['disable_smaller_components'] ) {
            if ( array_key_exists( 'disable_head_generator_tag', $options ) && $options['disable_head_generator_tag'] ) {
                remove_action( 'wp_head', 'wp_generator' );
            }
            
            if ( array_key_exists( 'disable_resource_version_number', $options ) && $options['disable_resource_version_number'] ) {
                add_filter( 'style_loader_src', [ $disable_components, 'remove_resource_version_number' ], PHP_INT_MAX );
                add_filter( 'script_loader_src', [ $disable_components, 'remove_resource_version_number' ], PHP_INT_MAX );
            }
            
            if ( array_key_exists( 'disable_head_wlwmanifest_tag', $options ) && $options['disable_head_wlwmanifest_tag'] ) {
                remove_action( 'wp_head', 'wlwmanifest_link' );
            }
            if ( array_key_exists( 'disable_head_rsd_tag', $options ) && $options['disable_head_rsd_tag'] ) {
                remove_action( 'wp_head', 'rsd_link' );
            }
            
            if ( array_key_exists( 'disable_head_shortlink_tag', $options ) && $options['disable_head_shortlink_tag'] ) {
                remove_action( 'wp_head', 'wp_shortlink_wp_head' );
                remove_action(
                    'template_redirect',
                    'wp_shortlink_header',
                    100,
                    0
                );
            }
            
            if ( array_key_exists( 'disable_frontend_dashicons', $options ) && $options['disable_frontend_dashicons'] ) {
                add_action( 'init', [ $disable_components, 'disable_dashicons_public_assets' ] );
            }
            if ( array_key_exists( 'disable_emoji_support', $options ) && $options['disable_emoji_support'] ) {
                add_action( 'init', [ $disable_components, 'disable_emoji_support' ] );
            }
            if ( array_key_exists( 'disable_jquery_migrate', $options ) && $options['disable_jquery_migrate'] ) {
                add_action( 'wp_default_scripts', [ $disable_components, 'disable_jquery_migrate' ] );
            }
        }
        
        // =================================================================
        // SECURITY
        // =================================================================
        // Instantiate object for Security features
        $security = new ASENHA\Classes\Security();
        // Limit Login Attempts
        
        if ( array_key_exists( 'limit_login_attempts', $options ) && $options['limit_login_attempts'] ) {
            add_filter(
                'authenticate',
                [ $security, 'maybe_allow_login' ],
                999,
                3
            );
            // Very low priority so it is processed last
            add_action(
                'wp_login_errors',
                [ $security, 'login_error_handler' ],
                999,
                2
            );
            add_action( 'login_enqueue_scripts', [ $security, 'maybe_hide_login_form' ] );
            add_filter( 'login_message', [ $security, 'add_failed_login_message' ] );
            add_action( 'wp_login_failed', [ $security, 'log_failed_login' ], 5 );
            // Higher priority than one in Change Login URL
            add_action( 'wp_login', [ $security, 'clear_failed_login_log' ] );
        }
        
        // Obfuscate Author Slugs
        
        if ( array_key_exists( 'obfuscate_author_slugs', $options ) && $options['obfuscate_author_slugs'] ) {
            add_action( 'pre_get_posts', [ $security, 'alter_author_query' ], 10 );
            add_filter(
                'author_link',
                [ $security, 'alter_author_link' ],
                10,
                3
            );
            add_filter(
                'rest_prepare_user',
                [ $security, 'alter_json_users' ],
                10,
                3
            );
        }
        
        // Obfuscate Email Address
        
        if ( array_key_exists( 'obfuscate_email_address', $options ) && $options['obfuscate_email_address'] ) {
            add_shortcode( 'obfuscate', [ $security, 'obfuscate_string' ] );
            add_filter( 'widget_text', 'shortcode_unautop' );
            add_filter( 'widget_text', 'do_shortcode' );
            if ( array_key_exists( 'obfuscate_email_address_in_content', $options ) && $options['obfuscate_email_address_in_content'] ) {
                add_filter( 'the_content', [ $security, 'obfuscate_emails_in_content__premium_only' ] );
            }
        }
        
        // Disable XML-RPC
        
        if ( array_key_exists( 'disable_xmlrpc', $options ) && $options['disable_xmlrpc'] ) {
            add_filter( 'xmlrpc_enabled', '__return_false' );
            add_action( 'wp', [ $security, 'remove_xmlrpc_link' ], 11 );
            add_filter( 'wp_xmlrpc_server_class', [ $security, 'maybe_disable_xmlrpc' ] );
        }
        
        // =================================================================
        // OPTIMIZATIONS
        // =================================================================
        // Instantiate object for Optimizations features
        $optimizations = new ASENHA\Classes\Optimizations();
        // Image Upload Control
        if ( array_key_exists( 'image_upload_control', $options ) && $options['image_upload_control'] ) {
            add_filter( 'wp_handle_upload', [ $optimizations, 'image_upload_handler' ] );
        }
        // Revisions Control
        if ( array_key_exists( 'enable_revisions_control', $options ) && $options['enable_revisions_control'] ) {
            add_filter(
                'wp_revisions_to_keep',
                [ $optimizations, 'limit_revisions_to_max_number' ],
                10,
                2
            );
        }
        // Heartbeat Control
        
        if ( array_key_exists( 'enable_heartbeat_control', $options ) && $options['enable_heartbeat_control'] ) {
            add_filter(
                'heartbeat_settings',
                [ $optimizations, 'maybe_modify_heartbeat_frequency' ],
                99,
                2
            );
            add_action( 'admin_enqueue_scripts', [ $optimizations, 'maybe_disable_heartbeat' ], 99 );
            add_action( 'wp_enqueue_scripts', [ $optimizations, 'maybe_disable_heartbeat' ], 99 );
        }
        
        // =================================================================
        // UTILITIES
        // =================================================================
        // Instantiate object for Utilities features
        $utilities = new ASENHA\Classes\Utilities();
        // SMTP Email Delivery
        
        if ( array_key_exists( 'smtp_email_delivery', $options ) && $options['smtp_email_delivery'] ) {
            add_action( 'phpmailer_init', [ $utilities, 'deliver_email_via_smtp' ], 99999 );
            add_action( 'wp_ajax_send_test_email', [ $utilities, 'send_test_email' ] );
        }
        
        // Multiple User Roles
        
        if ( array_key_exists( 'multiple_user_roles', $options ) && $options['multiple_user_roles'] ) {
            // Show roles checkboxes
            add_action( 'show_user_profile', [ $utilities, 'add_multiple_roles_ui' ] );
            // for when user edits their own profile
            add_action( 'edit_user_profile', [ $utilities, 'add_multiple_roles_ui' ] );
            // for when editing other user's profile
            add_action( 'user_new_form', [ $utilities, 'add_multiple_roles_ui' ] );
            // new user creation
            // Save roles selections
            add_action( 'personal_options_update', [ $utilities, 'save_roles_assignment' ] );
            // for when user edits their own profile
            add_action( 'edit_user_profile_update', [ $utilities, 'save_roles_assignment' ] );
            // for when editing other user's profile
            add_action( 'user_register', [ $utilities, 'save_roles_assignment' ] );
            // new user creation
        }
        
        // Image Sizes Panel
        if ( array_key_exists( 'image_sizes_panel', $options ) && $options['image_sizes_panel'] ) {
            add_action( 'add_meta_boxes', array( $utilities, 'add_image_sizes_meta_box' ) );
        }
        // View Admin as Role
        
        if ( array_key_exists( 'view_admin_as_role', $options ) && $options['view_admin_as_role'] ) {
            add_action( 'admin_bar_menu', [ $utilities, 'view_admin_as_admin_bar_menu' ], 8 );
            // Priority 8 so it is next to username section
            add_action( 'init', [ $utilities, 'role_switcher_to_view_admin_as' ] );
            // add_action( 'wp_die_handler', [ $utilities, 'custom_error_page_on_switch_failure' ] );
            add_action( 'admin_footer', [ $utilities, 'add_floating_reset_button' ] );
        }
        
        // Password Protection
        
        if ( array_key_exists( 'enable_password_protection', $options ) && $options['enable_password_protection'] ) {
            add_action( 'plugins_loaded', [ $utilities, 'show_password_protection_admin_bar_icon' ] );
            add_action( 'init', [ $utilities, 'maybe_disable_page_caching' ], 1 );
            add_action( 'template_redirect', [ $utilities, 'maybe_show_login_form' ], 0 );
            // load early
            add_action( 'init', [ $utilities, 'maybe_process_login' ], 1 );
            add_action( 'asenha_password_protection_error_messages', [ $utilities, 'add_login_error_messages' ] );
            if ( function_exists( 'wp_site_icon' ) ) {
                // WP v4.3+
                add_action( 'asenha_password_protection_login_head', 'wp_site_icon' );
            }
        }
        
        // Maintenance Mode
        
        if ( array_key_exists( 'maintenance_mode', $options ) && $options['maintenance_mode'] ) {
            add_action( 'send_headers', [ $utilities, 'maintenance_mode_redirect' ] );
            add_action( 'plugins_loaded', [ $utilities, 'show_maintenance_mode_admin_bar_icon' ] );
        }
        
        // Redirect 404 to Homepage
        if ( array_key_exists( 'redirect_404_to_homepage', $options ) && $options['redirect_404_to_homepage'] ) {
            add_filter( 'wp', [ $utilities, 'redirect_404_to_homepage' ] );
        }
        // Display System Summary
        if ( array_key_exists( 'display_system_summary', $options ) && $options['display_system_summary'] ) {
            add_action( 'rightnow_end', [ $utilities, 'display_system_summary' ] );
        }
        // Search Engines Visibility Status
        if ( array_key_exists( 'search_engine_visibility_status', $options ) && $options['search_engine_visibility_status'] ) {
            add_action( 'admin_init', [ $utilities, 'maybe_display_search_engine_visibility_status' ] );
        }
    }

}
Admin_Site_Enhancements::get_instance();