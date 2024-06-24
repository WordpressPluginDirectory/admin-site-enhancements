<?php

namespace ASENHA\Classes;

/**
 * Class related to registration of settings fields
 *
 * @since 2.2.0
 */
class Settings_Sections_Fields {
    /**
     * Register plugin settings and the corresponding fields
     *
     * @link https://wpshout.com/making-an-admin-options-page-with-the-wordpress-settings-api/
     * @link https://rudrastyh.com/wordpress/creating-options-pages.html
     * @since 1.0.0
     */
    function register_sections_fields() {
        add_settings_section(
            'main-section',
            '',
            // Section title. Can be blank.
            '',
            // Callback function to output section intro. Can be blank.
            ASENHA_SLUG
        );
        $common_methods = new Common_Methods();
        // Register main setttings
        // Instantiate object for sanitization of settings fields values
        $sanitization = new Settings_Sanitization();
        // Instantiate object for rendering of settings fields for the admin page
        $render_field = new Settings_Fields_Render();
        register_setting( 
            ASENHA_ID,
            // Option group or option_page
            ASENHA_SLUG_U,
            array(
                'type'              => 'array',
                'description'       => '',
                'sanitize_callback' => [$sanitization, 'sanitize_for_options'],
                'show_in_rest'      => false,
                'default'           => array(),
            )
         );
        // =================================================================
        // Call WordPress globals and set new globals required for the fields
        // =================================================================
        global 
            $wp_version,
            $wp_roles,
            $wpdb,
            $asenha_public_post_types,
            $asenha_nonpublic_post_types,
            $asenha_gutenberg_post_types,
            $asenha_revisions_post_types,
            $active_plugin_slugs,
            $workable_nodes
        ;
        $options = get_option( ASENHA_SLUG_U, array() );
        $options_extra = get_option( ASENHA_SLUG_U . '_extra', array() );
        $roles = $wp_roles->get_names();
        // Get array of slugs and plural labels for public post types, e.g. array( 'post' => 'Posts', 'page' => 'Pages' )
        $asenha_public_post_types = array();
        $public_post_type_names = get_post_types( array(
            'public' => true,
        ), 'names' );
        foreach ( $public_post_type_names as $post_type_name ) {
            $post_type_object = get_post_type_object( $post_type_name );
            $asenha_public_post_types[$post_type_name] = $post_type_object->label;
        }
        asort( $asenha_public_post_types );
        // sort by value, ascending
        // Get array of slugs and plural labels for non-public post types, e.g. array( 'post' => 'Posts', 'page' => 'Pages' )
        $asenha_nonpublic_post_types = array();
        $public_post_type_names = get_post_types( array(
            'public' => false,
        ), 'names' );
        foreach ( $public_post_type_names as $post_type_name ) {
            $post_type_object = get_post_type_object( $post_type_name );
            $asenha_nonpublic_post_types[$post_type_name] = $post_type_object->label;
        }
        asort( $asenha_nonpublic_post_types );
        // sort by value, ascending
        // Get array of slugs and plural labels for post types that can be edited with the Gutenberg block editor, e.g. array( 'post' => 'Posts', 'page' => 'Pages' )
        $asenha_gutenberg_post_types = array();
        $gutenberg_not_applicable_types = array(
            'attachment',
            'revision',
            'nav_menu_item',
            'custom_css',
            'customize_changeset',
            'oembed_cache',
            'user_request',
            'wp_block',
            'wp_template',
            'wp_template_part',
            'wp_global_styles',
            'wp_navigation'
        );
        $all_post_types = get_post_types( array(), 'objects' );
        foreach ( $all_post_types as $post_type_slug => $post_type_info ) {
            $asenha_gutenberg_post_types[$post_type_slug] = $post_type_info->label;
            if ( in_array( $post_type_slug, $gutenberg_not_applicable_types ) ) {
                unset($asenha_gutenberg_post_types[$post_type_slug]);
            }
        }
        // Get array of slugs and plural labels for post types supporting revisions, e.g. array( 'post' => 'Posts', 'page' => 'Pages' )
        $asenha_revisions_post_types = array();
        foreach ( get_post_types( array(), 'names' ) as $post_type_slug ) {
            // post type slug/name
            if ( post_type_supports( $post_type_slug, 'revisions' ) ) {
                $post_type_object = get_post_type_object( $post_type_slug );
                if ( property_exists( $post_type_object, 'label' ) ) {
                    $asenha_revisions_post_types[$post_type_slug] = $post_type_object->label;
                }
            }
        }
        // Get array of active plugins slugs
        $active_plugins = get_option( 'active_plugins', array() );
        $active_plugin_slugs = array();
        foreach ( $active_plugins as $active_plugin ) {
            // e.g. debug-log-manager/debug-log-manager.php
            $active_plugin = explode( "/", $active_plugin );
            $active_plugin_slugs[] = $active_plugin[0];
        }
        $background_pattern_options = array();
        // Enable Content Duplication
        $field_id = 'enable_duplication';
        $field_slug = 'enable-duplication';
        add_settings_field(
            $field_id,
            __( 'Content Duplication', 'admin-site-enhancements' ),
            // Field title
            [$render_field, 'render_checkbox_toggle'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name'            => ASENHA_SLUG_U,
                'field_id'               => $field_id,
                'field_slug'             => $field_slug,
                'field_name'             => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_description'      => __( 'Enable one-click duplication of pages, posts and custom posts. The corresponding taxonomy terms and post meta will also be duplicated.', 'admin-site-enhancements' ),
                'field_options_wrapper'  => true,
                'field_options_moreless' => true,
                'class'                  => 'asenha-toggle content-management ' . $field_slug,
            )
        );
        $field_id = 'duplication_redirect_destination';
        $field_slug = 'duplication-redirect-destination';
        add_settings_field(
            $field_id,
            __( 'After duplication, redirect to:', 'admin-site-enhancements' ),
            // Field title
            [$render_field, 'render_radio_buttons_subfield'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name'   => ASENHA_SLUG_U,
                'field_id'      => $field_id,
                'field_name'    => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_radios'  => array(
                    __( 'Edit screen', 'admin-site-enhancements' ) => 'edit',
                    __( 'List view', 'admin-site-enhancements' )   => 'list',
                ),
                'field_default' => 'edit',
                'class'         => 'asenha-radio-buttons shift-up content-management ' . $field_slug,
            )
        );
        // Content Order
        $field_id = 'content_order';
        $field_slug = 'content-order';
        add_settings_field(
            $field_id,
            __( 'Content Order', 'admin-site-enhancements' ),
            // Field title
            [$render_field, 'render_checkbox_toggle'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name'            => ASENHA_SLUG_U,
                'field_id'               => $field_id,
                'field_slug'             => $field_slug,
                'field_name'             => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_description'      => __( 'Enable custom ordering of various "hierarchical" content types or those supporting "page attributes". A new \'Order\' sub-menu will appear for enabled content type(s). The "All {Posts}" list page for enabled post types in wp-admin will automatically use the custom order.', 'admin-site-enhancements' ),
                'field_options_wrapper'  => true,
                'field_options_moreless' => true,
                'class'                  => 'asenha-toggle content-management ' . $field_slug,
            )
        );
        $field_id = 'content_order_for';
        $field_slug = 'content-order-for';
        if ( is_array( $asenha_public_post_types ) ) {
            $inapplicable_post_types = array();
            foreach ( $asenha_public_post_types as $post_type_slug => $post_type_label ) {
                // e.g. $post_type_slug is post, $post_type_label is Posts
                $is_hierarchical_label = ( is_post_type_hierarchical( $post_type_slug ) ? ' <span class="faded">- Hierarchical</span>' : '' );
                if ( (post_type_supports( $post_type_slug, 'page-attributes' ) || is_post_type_hierarchical( $post_type_slug )) && !in_array( $post_type_slug, $inapplicable_post_types ) ) {
                    add_settings_field(
                        $field_id . '_' . $post_type_slug,
                        '',
                        // Field title
                        [$render_field, 'render_checkbox_subfield'],
                        ASENHA_SLUG,
                        'main-section',
                        array(
                            'option_name'     => ASENHA_SLUG_U,
                            'parent_field_id' => $field_id,
                            'field_id'        => $post_type_slug,
                            'field_name'      => ASENHA_SLUG_U . '[' . $field_id . '][' . $post_type_slug . ']',
                            'field_label'     => $post_type_label . ' <span class="faded">(' . $post_type_slug . ')</span>' . $is_hierarchical_label,
                            'class'           => 'asenha-checkbox asenha-hide-th asenha-half content-management ' . $field_slug . ' ' . $post_type_slug,
                        )
                    );
                }
            }
        }
        // Media Replacement
        $field_id = 'enable_media_replacement';
        $field_slug = 'enable-media-replacement';
        add_settings_field(
            $field_id,
            __( 'Media Replacement', 'admin-site-enhancements' ),
            // Field title
            [$render_field, 'render_checkbox_toggle'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name'       => ASENHA_SLUG_U,
                'field_id'          => $field_id,
                'field_slug'        => $field_slug,
                'field_name'        => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_description' => __( 'Easily replace any type of media file with a new one while retaining the existing media ID, publish date and file name. So, no existing links will break.', 'admin-site-enhancements' ),
                'class'             => 'asenha-toggle content-management ' . $field_slug,
            )
        );
        // Enable SVG Upload
        $field_id = 'enable_svg_upload';
        $field_slug = 'enable-svg-upload';
        add_settings_field(
            $field_id,
            __( 'SVG Upload', 'admin-site-enhancements' ),
            // Field title
            [$render_field, 'render_checkbox_toggle'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name'            => ASENHA_SLUG_U,
                'field_id'               => $field_id,
                'field_slug'             => $field_slug,
                'field_name'             => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_description'      => __( 'Allow some or all user roles to upload SVG files, which will then be sanitized to keep things secure.', 'admin-site-enhancements' ),
                'field_options_wrapper'  => true,
                'field_options_moreless' => true,
                'class'                  => 'asenha-toggle content-management ' . $field_slug,
            )
        );
        $field_id = 'enable_svg_upload_for';
        $field_slug = 'enable-svg-upload-for';
        if ( is_array( $roles ) ) {
            foreach ( $roles as $role_slug => $role_label ) {
                // e.g. $role_slug is administrator, $role_label is Administrator
                add_settings_field(
                    $field_id . '_' . $role_slug,
                    '',
                    // Field title
                    [$render_field, 'render_checkbox_subfield'],
                    ASENHA_SLUG,
                    'main-section',
                    array(
                        'option_name'     => ASENHA_SLUG_U,
                        'parent_field_id' => $field_id,
                        'field_id'        => $role_slug,
                        'field_name'      => ASENHA_SLUG_U . '[' . $field_id . '][' . $role_slug . ']',
                        'field_label'     => $role_label,
                        'class'           => 'asenha-checkbox asenha-hide-th asenha-half admin-interface ' . $field_slug . ' ' . $role_slug,
                    )
                );
            }
        }
        // Enable AVIF Upload
        $field_id = 'enable_avif_upload';
        $field_slug = 'enable-avif-upload';
        add_settings_field(
            $field_id,
            __( 'AVIF Upload', 'admin-site-enhancements' ),
            // Field title
            [$render_field, 'render_checkbox_toggle'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name'            => ASENHA_SLUG_U,
                'field_id'               => $field_id,
                'field_slug'             => $field_slug,
                'field_name'             => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_description'      => __( 'Enable uploading <a href="https://www.smashingmagazine.com/2021/09/modern-image-formats-avif-webp/" target="_blank">AVIF</a> files in the Media Library. You can convert your existing PNG, JPG and GIF files using a tool like <a href="https://squoosh.app/" target="_blank">Squoosh</a>.', 'admin-site-enhancements' ),
                'field_options_wrapper'  => true,
                'field_options_moreless' => true,
                'class'                  => 'asenha-toggle content-management ' . $field_slug,
            )
        );
        $field_id = 'avif_support_status';
        $field_slug = 'avif-support-status';
        add_settings_field(
            $field_id,
            '',
            // Field title
            [$render_field, 'render_avif_support_status'],
            ASENHA_SLUG,
            'main-section',
            array(
                'class' => 'asenha-toggle content-management ' . $field_slug,
            )
        );
        // Enable External Permalinks
        $field_id = 'enable_external_permalinks';
        $field_slug = 'enable-external-permalinks';
        add_settings_field(
            $field_id,
            __( 'External Permalinks', 'admin-site-enhancements' ),
            // Field title
            [$render_field, 'render_checkbox_toggle'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name'            => ASENHA_SLUG_U,
                'field_id'               => $field_id,
                'field_slug'             => $field_slug,
                'field_name'             => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_description'      => __( 'Enable pages, posts and/or custom post types to have permalinks that point to external URLs. The rel="noopener noreferrer nofollow" attribute will also be added for enhanced security and SEO benefits. Compatible with links added using <a href="https://wordpress.org/plugins/page-links-to/" target="_blank">Page Links To</a>.', 'admin-site-enhancements' ),
                'field_options_wrapper'  => true,
                'field_options_moreless' => true,
                'class'                  => 'asenha-toggle content-management ' . $field_slug,
            )
        );
        $field_id = 'enable_external_permalinks_for';
        $field_slug = 'enable-external-permalinks-for';
        if ( is_array( $asenha_public_post_types ) ) {
            foreach ( $asenha_public_post_types as $post_type_slug => $post_type_label ) {
                // e.g. $post_type_slug is post, $post_type_label is Posts
                if ( 'attachment' != $post_type_slug ) {
                    add_settings_field(
                        $field_id . '_' . $post_type_slug,
                        '',
                        // Field title
                        [$render_field, 'render_checkbox_subfield'],
                        ASENHA_SLUG,
                        'main-section',
                        array(
                            'option_name'     => ASENHA_SLUG_U,
                            'parent_field_id' => $field_id,
                            'field_id'        => $post_type_slug,
                            'field_name'      => ASENHA_SLUG_U . '[' . $field_id . '][' . $post_type_slug . ']',
                            'field_label'     => $post_type_label . ' <span class="faded">(' . $post_type_slug . ')</span>',
                            'class'           => 'asenha-checkbox asenha-hide-th asenha-half content-management ' . $field_slug . ' ' . $post_type_slug,
                        )
                    );
                }
            }
        }
        // Open All External Links in New Tab
        $field_id = 'external_links_new_tab';
        $field_slug = 'external-links-new-tab';
        add_settings_field(
            $field_id,
            __( 'Open All External Links in New Tab', 'admin-site-enhancements' ),
            // Field title
            [$render_field, 'render_checkbox_toggle'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name'            => ASENHA_SLUG_U,
                'field_id'               => $field_id,
                'field_slug'             => $field_slug,
                'field_name'             => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_description'      => __( 'Force all links to external sites in post content, where <a href="https://developer.wordpress.org/reference/hooks/the_content/" target="_blank">the_content</a> hook is used, to open in new browser tab via target="_blank" attribute. The rel="noopener noreferrer nofollow" attribute will also be added for enhanced security and SEO benefits.', 'admin-site-enhancements' ),
                'field_options_wrapper'  => false,
                'field_options_moreless' => false,
                'class'                  => 'asenha-toggle content-management ' . $field_slug,
            )
        );
        // Allow Custom Nav Menu Items to Open in New Tab
        $field_id = 'custom_nav_menu_items_new_tab';
        $field_slug = 'custom-nav-menu-items-new-tab';
        add_settings_field(
            $field_id,
            __( 'Allow Custom Navigation Menu Items to Open in New Tab', 'admin-site-enhancements' ),
            // Field title
            [$render_field, 'render_checkbox_toggle'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name'            => ASENHA_SLUG_U,
                'field_id'               => $field_id,
                'field_slug'             => $field_slug,
                'field_name'             => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_description'      => __( 'Allow custom navigation menu items to have links that open in new browser tab via target="_blank" attribute. The rel="noopener noreferrer nofollow" attribute will also be added for enhanced security and SEO benefits.', 'admin-site-enhancements' ),
                'field_options_wrapper'  => false,
                'field_options_moreless' => false,
                'class'                  => 'asenha-toggle content-management ' . $field_slug,
            )
        );
        // Enable Auto-Publishing of Posts with Missed Schedules
        $field_id = 'enable_missed_schedule_posts_auto_publish';
        $field_slug = 'enable-missed-schedule-posts-auto-publish';
        add_settings_field(
            $field_id,
            __( 'Auto-Publish Posts with Missed Schedule', 'admin-site-enhancements' ),
            // Field title
            [$render_field, 'render_checkbox_toggle'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name'       => ASENHA_SLUG_U,
                'field_id'          => $field_id,
                'field_slug'        => $field_slug,
                'field_name'        => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_description' => __( 'Trigger publishing of scheduled posts of all types marked with "missed schedule", anytime the site is visited.', 'admin-site-enhancements' ),
                'class'             => 'asenha-toggle content-management ' . $field_slug,
            )
        );
        // =================================================================
        // ADMIN INTERFACE
        // =================================================================
        // Hide or Modify Elements / Clean Up Admin Bar
        $field_id = 'hide_modify_elements';
        $field_slug = 'hide-modify-elements';
        add_settings_field(
            $field_id,
            __( 'Clean Up Admin Bar', 'admin-site-enhancements' ),
            // Field title
            [$render_field, 'render_checkbox_toggle'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name'            => ASENHA_SLUG_U,
                'field_id'               => $field_id,
                'field_slug'             => $field_slug,
                'field_name'             => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_description'      => __( 'Remove various elements from the admin bar.', 'admin-site-enhancements' ),
                'field_options_wrapper'  => true,
                'field_options_moreless' => true,
                'class'                  => 'asenha-toggle admin-interface ' . $field_slug,
            )
        );
        $field_id = 'hide_ab_wp_logo_menu';
        $field_slug = 'hide-ab-wp-logo-menu';
        add_settings_field(
            $field_id,
            '',
            // Field title
            [$render_field, 'render_checkbox_plain'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name' => ASENHA_SLUG_U,
                'field_id'    => $field_id,
                'field_name'  => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_label' => __( 'Remove WordPress logo/menu', 'admin-site-enhancements' ),
                'class'       => 'asenha-checkbox asenha-hide-th admin-interface ' . $field_slug,
            )
        );
        $field_id = 'hide_ab_customize_menu';
        $field_slug = 'hide-ab-customize-menu';
        add_settings_field(
            $field_id,
            '',
            // Field title
            [$render_field, 'render_checkbox_plain'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name' => ASENHA_SLUG_U,
                'field_id'    => $field_id,
                'field_name'  => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_label' => __( 'Remove customize menu', 'admin-site-enhancements' ),
                'class'       => 'asenha-checkbox asenha-hide-th admin-interface ' . $field_slug,
            )
        );
        $field_id = 'hide_ab_updates_menu';
        $field_slug = 'hide-ab-updates-menu';
        add_settings_field(
            $field_id,
            '',
            // Field title
            [$render_field, 'render_checkbox_plain'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name' => ASENHA_SLUG_U,
                'field_id'    => $field_id,
                'field_name'  => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_label' => __( 'Remove updates counter/link', 'admin-site-enhancements' ),
                'class'       => 'asenha-checkbox asenha-hide-th admin-interface ' . $field_slug,
            )
        );
        $field_id = 'hide_ab_comments_menu';
        $field_slug = 'hide-ab-comments-menu';
        add_settings_field(
            $field_id,
            '',
            // Field title
            [$render_field, 'render_checkbox_plain'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name' => ASENHA_SLUG_U,
                'field_id'    => $field_id,
                'field_name'  => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_label' => __( 'Remove comments counter/link', 'admin-site-enhancements' ),
                'class'       => 'asenha-checkbox asenha-hide-th admin-interface ' . $field_slug,
            )
        );
        $field_id = 'hide_ab_new_content_menu';
        $field_slug = 'hide-ab-new-content-menu';
        add_settings_field(
            $field_id,
            '',
            // Field title
            [$render_field, 'render_checkbox_plain'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name' => ASENHA_SLUG_U,
                'field_id'    => $field_id,
                'field_name'  => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_label' => __( 'Remove new content menu', 'admin-site-enhancements' ),
                'class'       => 'asenha-checkbox asenha-hide-th admin-interface ' . $field_slug,
            )
        );
        $field_id = 'hide_ab_howdy';
        $field_slug = 'hide-ab-howdy';
        add_settings_field(
            $field_id,
            '',
            // Field title
            [$render_field, 'render_checkbox_plain'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name' => ASENHA_SLUG_U,
                'field_id'    => $field_id,
                'field_name'  => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_label' => __( 'Remove \'Howdy\'', 'admin-site-enhancements' ),
                'class'       => 'asenha-checkbox asenha-hide-th admin-interface ' . $field_slug,
            )
        );
        $field_id = 'hide_help_drawer';
        $field_slug = 'hide-help-drawer';
        add_settings_field(
            $field_id,
            '',
            // Field title
            [$render_field, 'render_checkbox_plain'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name' => ASENHA_SLUG_U,
                'field_id'    => $field_id,
                'field_name'  => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_label' => __( 'Remove the Help tab and drawer', 'admin-site-enhancements' ),
                'class'       => 'asenha-checkbox asenha-hide-th admin-interface ' . $field_slug,
            )
        );
        // Hide Admin Notices
        $field_id = 'hide_admin_notices';
        $field_slug = 'hide-admin-notices';
        $field_options_wrapper = false;
        $field_options_moreless = false;
        add_settings_field(
            $field_id,
            __( 'Hide Admin Notices', 'admin-site-enhancements' ),
            // Field title
            [$render_field, 'render_checkbox_toggle'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name'            => ASENHA_SLUG_U,
                'field_id'               => $field_id,
                'field_slug'             => $field_slug,
                'field_name'             => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_description'      => __( 'Clean up admin pages by moving notices into a separate panel easily accessible via the admin bar.', 'admin-site-enhancements' ),
                'field_options_wrapper'  => $field_options_wrapper,
                'field_options_moreless' => $field_options_moreless,
                'class'                  => 'asenha-toggle admin-interface ' . $field_slug,
            )
        );
        // Disable Dashboard Widgets
        $field_id = 'disable_dashboard_widgets';
        $field_slug = 'disable-dashboard-widgets';
        add_settings_field(
            $field_id,
            __( 'Disable Dashboard Widgets', 'admin-site-enhancements' ),
            // Field title
            [$render_field, 'render_checkbox_toggle'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name'            => ASENHA_SLUG_U,
                'field_id'               => $field_id,
                'field_slug'             => $field_slug,
                'field_name'             => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_description'      => __( 'Clean up and speed up the dashboard by completely disabling some or all widgets. Disabled widgets won\'t load any assets nor show up under Screen Options.', 'admin-site-enhancements' ),
                'field_options_wrapper'  => true,
                'field_options_moreless' => true,
                'class'                  => 'asenha-toggle admin-interface ' . $field_slug,
            )
        );
        $field_id = 'disable_welcome_panel_in_dashboard';
        $field_slug = 'disable-welcome-panel-in-dashboard';
        add_settings_field(
            $field_id,
            '',
            // Field title
            [$render_field, 'render_checkbox_plain'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name' => ASENHA_SLUG_U,
                'field_id'    => $field_id,
                'field_name'  => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_label' => __( 'Welcome to WordPress', 'admin-site-enhancements' ),
                'class'       => 'asenha-checkbox asenha-hide-th admin-interface ' . $field_slug,
            )
        );
        $field_id = 'disabled_dashboard_widgets';
        $field_slug = 'disabled-dashboard-widgets';
        if ( array_key_exists( 'dashboard_widgets', $options_extra ) ) {
            $dashboard_widgets = $options_extra['dashboard_widgets'];
        } else {
            $disable_dashboard_widgets = new Disable_Dashboard_Widgets();
            $dashboard_widgets = $disable_dashboard_widgets->get_dashboard_widgets();
            $options_extra['dashboard_widgets'] = $dashboard_widgets;
            update_option( 'admin_site_enhancements_extra', $options_extra );
        }
        foreach ( $dashboard_widgets as $widget ) {
            add_settings_field(
                $field_id . '_' . $widget['id'],
                '',
                // Field title
                [$render_field, 'render_checkbox_subfield'],
                ASENHA_SLUG,
                'main-section',
                array(
                    'option_name'     => ASENHA_SLUG_U,
                    'parent_field_id' => $field_id,
                    'field_id'        => $widget['id'] . '__' . $widget['context'] . '__' . $widget['priority'],
                    'field_name'      => ASENHA_SLUG_U . '[' . $field_id . '][' . $widget['id'] . '__' . $widget['context'] . '__' . $widget['priority'] . ']',
                    'field_label'     => $widget['title'] . ' <span class="faded">(' . $widget['id'] . ')</span>',
                    'class'           => 'asenha-checkbox asenha-hide-th admin-interface ' . $field_slug . ' ' . $widget['id'],
                )
            );
        }
        // Hide Admin Bar
        $field_id = 'hide_admin_bar';
        $field_slug = 'hide-admin-bar';
        $field_description = __( 'Hide admin bar on the frontend for all or some user roles.', 'admin-site-enhancements' );
        add_settings_field(
            $field_id,
            __( 'Hide Admin Bar', 'admin-site-enhancements' ),
            // Field title
            [$render_field, 'render_checkbox_toggle'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name'            => ASENHA_SLUG_U,
                'field_id'               => $field_id,
                'field_slug'             => $field_slug,
                'field_name'             => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_description'      => $field_description,
                'field_options_wrapper'  => true,
                'field_options_moreless' => true,
                'class'                  => 'asenha-toggle admin-interface ' . $field_slug,
            )
        );
        $field_id = 'hide_admin_bar_for';
        $field_slug = 'hide-admin-bar-for';
        if ( is_array( $roles ) ) {
            foreach ( $roles as $role_slug => $role_label ) {
                // e.g. $role_slug is administrator, $role_label is Administrator
                add_settings_field(
                    $field_id . '_' . $role_slug,
                    '',
                    // Field title
                    [$render_field, 'render_checkbox_subfield'],
                    ASENHA_SLUG,
                    'main-section',
                    array(
                        'option_name'     => ASENHA_SLUG_U,
                        'parent_field_id' => $field_id,
                        'field_id'        => $role_slug,
                        'field_name'      => ASENHA_SLUG_U . '[' . $field_id . '][' . $role_slug . ']',
                        'field_label'     => $role_label,
                        'class'           => 'asenha-checkbox asenha-hide-th asenha-half admin-interface ' . $field_slug . ' ' . $role_slug,
                    )
                );
            }
        }
        // Wider Admin Menu
        $field_id = 'wider_admin_menu';
        $field_slug = 'wider-admin-menu';
        add_settings_field(
            $field_id,
            __( 'Wider Admin Menu', 'admin-site-enhancements' ),
            // Field title
            [$render_field, 'render_checkbox_toggle'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name'           => ASENHA_SLUG_U,
                'field_id'              => $field_id,
                'field_slug'            => $field_slug,
                'field_name'            => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_description'     => __( 'Give the admin menu more room to better accommodate wider items.', 'admin-site-enhancements' ),
                'field_options_wrapper' => true,
                'class'                 => 'asenha-toggle admin-interface ' . $field_slug,
            )
        );
        $field_id = 'admin_menu_width';
        $field_slug = 'admin-menu-width';
        add_settings_field(
            $field_id,
            '',
            // Field title
            [$render_field, 'render_select_subfield'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name'          => ASENHA_SLUG_U,
                'field_id'             => $field_id,
                'field_name'           => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_type'           => 'with-prefix-suffix',
                'field_prefix'         => __( 'Set width to', 'admin-site-enhancements' ),
                'field_suffix'         => '<span class="faded">' . __( '(Default is 160px)', 'admin-site-enhancements' ) . '</span>',
                'field_select_options' => array(
                    '180px' => '180px',
                    '200px' => '200px',
                    '220px' => '220px',
                    '240px' => '240px',
                    '260px' => '260px',
                    '280px' => '280px',
                    '300px' => '300px',
                ),
                'field_select_default' => 200,
                'field_intro'          => '',
                'field_description'    => '',
                'class'                => 'asenha-number asenha-hide-th extra-narrow shift-up admin-interface ' . $field_slug,
                'display_none_on_load' => true,
            )
        );
        // Admin Menu Organizer
        $field_id = 'customize_admin_menu';
        $field_slug = 'customize-admin-menu';
        add_settings_field(
            $field_id,
            __( 'Admin Menu Organizer', 'admin-site-enhancements' ),
            // Field title
            [$render_field, 'render_checkbox_toggle'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name'            => ASENHA_SLUG_U,
                'field_id'               => $field_id,
                'field_slug'             => $field_slug,
                'field_name'             => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_description'      => __( 'Customize the order of the admin menu and optionally change menu item title or hide some items.', 'admin-site-enhancements' ),
                'field_options_wrapper'  => true,
                'field_options_moreless' => true,
                'class'                  => 'asenha-toggle admin-interface ' . $field_slug,
            )
        );
        $field_id = 'custom_menu_order';
        $field_slug = 'custom-menu-order';
        add_settings_field(
            $field_id,
            '',
            // Field title
            [$render_field, 'render_sortable_menu'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name'       => ASENHA_SLUG_U,
                'field_id'          => $field_id,
                'field_name'        => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_type'        => 'sortable-menu',
                'field_description' => '',
                'class'             => 'asenha-sortable asenha-hide-th admin-interface ' . $field_slug,
            )
        );
        // Show Custom Taxonomy Filters
        $field_id = 'show_custom_taxonomy_filters';
        $field_slug = 'show-custom-taxonomy-filters';
        $field_options_wrapper = false;
        $field_options_moreless = false;
        add_settings_field(
            $field_id,
            __( 'Show Custom Taxonomy Filters', 'admin-site-enhancements' ),
            // Field title
            [$render_field, 'render_checkbox_toggle'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name'            => ASENHA_SLUG_U,
                'field_id'               => $field_id,
                'field_slug'             => $field_slug,
                'field_name'             => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_description'      => __( 'Show additional filter(s) on list tables for hierarchical, custom taxonomies.', 'admin-site-enhancements' ),
                'field_options_wrapper'  => $field_options_wrapper,
                'field_options_moreless' => $field_options_moreless,
                'class'                  => 'asenha-toggle admin-interface ' . $field_slug,
            )
        );
        // Enhance List Tables
        $field_id = 'enhance_list_tables';
        $field_slug = 'enhance-list-tables';
        add_settings_field(
            $field_id,
            __( 'Enhance List Tables', 'admin-site-enhancements' ),
            // Field title
            [$render_field, 'render_checkbox_toggle'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name'            => ASENHA_SLUG_U,
                'field_id'               => $field_id,
                'field_slug'             => $field_slug,
                'field_name'             => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_description'      => __( 'Improve the usefulness of listing pages for various post types and taxonomies, media, comments and users by adding / removing columns and elements.', 'admin-site-enhancements' ),
                'field_options_wrapper'  => true,
                'field_options_moreless' => true,
                'class'                  => 'asenha-toggle content-management ' . $field_slug,
            )
        );
        // Show Featured Image Column
        $field_id = 'show_featured_image_column';
        $field_slug = 'show-featured-image-column';
        add_settings_field(
            $field_id,
            '',
            // Field title
            [$render_field, 'render_checkbox_plain'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name' => ASENHA_SLUG_U,
                'field_id'    => $field_id,
                'field_name'  => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_label' => __( 'Show featured image column', 'admin-site-enhancements' ),
                'class'       => 'asenha-checkbox asenha-hide-th content-management ' . $field_slug,
            )
        );
        // Show Excerpt Column
        $field_id = 'show_excerpt_column';
        $field_slug = 'show-excerpt-column';
        add_settings_field(
            $field_id,
            '',
            // Field title
            [$render_field, 'render_checkbox_plain'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name' => ASENHA_SLUG_U,
                'field_id'    => $field_id,
                'field_name'  => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_label' => __( 'Show excerpt column', 'admin-site-enhancements' ),
                'class'       => 'asenha-checkbox asenha-hide-th content-management ' . $field_slug,
            )
        );
        // Show ID Column
        $field_id = 'show_id_column';
        $field_slug = 'show-id-column';
        add_settings_field(
            $field_id,
            '',
            // Field title
            [$render_field, 'render_checkbox_plain'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name' => ASENHA_SLUG_U,
                'field_id'    => $field_id,
                'field_name'  => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_label' => __( 'Show ID column', 'admin-site-enhancements' ),
                'class'       => 'asenha-checkbox asenha-hide-th content-management ' . $field_slug,
            )
        );
        // Show File Size Column in Media Library
        $field_id = 'show_file_size_column';
        $field_slug = 'show-file-size-column';
        add_settings_field(
            $field_id,
            '',
            // Field title
            [$render_field, 'render_checkbox_plain'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name' => ASENHA_SLUG_U,
                'field_id'    => $field_id,
                'field_name'  => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_label' => __( 'Show file size column in media library', 'admin-site-enhancements' ),
                'class'       => 'asenha-checkbox asenha-hide-th content-management ' . $field_slug,
            )
        );
        // Show ID in Action Row
        $field_id = 'show_id_in_action_row';
        $field_slug = 'show-id-in-action_row';
        add_settings_field(
            $field_id,
            '',
            // Field title
            [$render_field, 'render_checkbox_plain'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name' => ASENHA_SLUG_U,
                'field_id'    => $field_id,
                'field_name'  => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_label' => __( 'Show ID in action rows along with links for Edit, View, etc.', 'admin-site-enhancements' ),
                'class'       => 'asenha-checkbox asenha-hide-th content-management ' . $field_slug,
            )
        );
        // Hide Comments Column
        $field_id = 'hide_comments_column';
        $field_slug = 'hide-comments-column';
        add_settings_field(
            $field_id,
            '',
            // Field title
            [$render_field, 'render_checkbox_plain'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name' => ASENHA_SLUG_U,
                'field_id'    => $field_id,
                'field_name'  => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_label' => __( 'Remove comments column', 'admin-site-enhancements' ),
                'class'       => 'asenha-checkbox asenha-hide-th content-management ' . $field_slug,
            )
        );
        // Hide Post Tags Column
        $field_id = 'hide_post_tags_column';
        $field_slug = 'hide-post-tags-column';
        add_settings_field(
            $field_id,
            '',
            // Field title
            [$render_field, 'render_checkbox_plain'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name' => ASENHA_SLUG_U,
                'field_id'    => $field_id,
                'field_name'  => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_label' => __( 'Remove tags column (for posts)', 'admin-site-enhancements' ),
                'class'       => 'asenha-checkbox asenha-hide-th content-management ' . $field_slug,
            )
        );
        // Various Admin UI Enhancements
        $field_id = 'various_admin_ui_enhancements';
        $field_slug = 'various-admin-ui-enhancements';
        add_settings_field(
            $field_id,
            __( 'Various Admin UI Enhancements', 'admin-site-enhancements' ),
            // Field title
            [$render_field, 'render_checkbox_toggle'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name'            => ASENHA_SLUG_U,
                'field_id'               => $field_id,
                'field_slug'             => $field_slug,
                'field_name'             => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_description'      => __( 'Various, smaller enhancements for different parts of the admin interface.', 'admin-site-enhancements' ),
                'field_options_wrapper'  => true,
                'field_options_moreless' => true,
                'class'                  => 'asenha-toggle admin-interface ' . $field_slug,
            )
        );
        // Media Library Infinite Scrolling
        $field_id = 'media_library_infinite_scrolling';
        $field_slug = 'media-library-infinite-scrolling';
        add_settings_field(
            $field_id,
            '',
            // Field title
            [$render_field, 'render_checkbox_plain'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name' => ASENHA_SLUG_U,
                'field_id'    => $field_id,
                'field_name'  => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_label' => '<strong>' . __( 'Media Library Infinite Scrolling', 'admin-site-enhancements' ) . '</strong><br />' . __( 'Re-enable infinite scrolling in the grid view of the media library. Useful for scrolling through a large library.', 'admin-site-enhancements' ),
                'class'       => 'asenha-toggle admin-interface ' . $field_slug,
            )
        );
        // Display Active Plugins First
        $field_id = 'display_active_plugins_first';
        $field_slug = 'display-active-plugins-first';
        add_settings_field(
            $field_id,
            '',
            // Field title
            [$render_field, 'render_checkbox_plain'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name' => ASENHA_SLUG_U,
                'field_id'    => $field_id,
                'field_name'  => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_label' => '<strong>' . __( 'Display Active Plugins First', 'admin-site-enhancements' ) . '</strong><br />' . __( 'Display active / activated plugins at the top of the Installed Plugins list. Useful when your site has many deactivated plugins for testing or development purposes.', 'admin-site-enhancements' ),
                'class'       => 'asenha-toggle admin-interface ' . $field_slug,
            )
        );
        // Custom Admin Footer Text
        $field_id = 'custom_admin_footer_text';
        $field_slug = 'custom-admin-footer-text';
        add_settings_field(
            $field_id,
            __( 'Custom Admin Footer Text', 'admin-site-enhancements' ),
            // Field title
            [$render_field, 'render_checkbox_toggle'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name'            => ASENHA_SLUG_U,
                'field_id'               => $field_id,
                'field_slug'             => $field_slug,
                'field_name'             => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_description'      => __( 'Customize the text you see on the footer of wp-admin pages other than this ASE settings page.', 'admin-site-enhancements' ),
                'field_options_wrapper'  => true,
                'field_options_moreless' => true,
                'class'                  => 'asenha-toggle utilities ' . $field_slug,
            )
        );
        $media_buttons = false;
        $quicktags = false;
        $toolbar1 = 'bold,italic,underline';
        $field_id = 'custom_admin_footer_left';
        $field_slug = 'custom-admin-footer-left';
        // https://developer.wordpress.org/reference/classes/_wp_editors/parse_settings/
        // https://www.tiny.cloud/docs/advanced/available-toolbar-buttons/
        $editor_settings = array(
            'media_buttons'  => $media_buttons,
            'textarea_name'  => ASENHA_SLUG_U . '[' . $field_id . ']',
            'textarea_rows'  => 3,
            'tiny_mce'       => true,
            'tinymce'        => array(
                'toolbar1'    => $toolbar1,
                'content_css' => ASENHA_URL . 'assets/css/settings-wpeditor.css',
            ),
            'editor_css'     => '',
            'quicktags'      => $quicktags,
            'default_editor' => 'tinymce',
        );
        add_settings_field(
            $field_id,
            __( 'Left Side', 'admin-site-enhancements' ),
            // Field title
            [$render_field, 'render_wpeditor_subfield'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name'       => ASENHA_SLUG_U,
                'field_id'          => $field_id,
                'field_slug'        => $field_slug,
                'field_name'        => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_type'        => 'textarea',
                'field_intro'       => '',
                'field_description' => __( 'Default text is: <em>Thank you for creating with <a href="https://wordpress.org/">WordPress</a></em>.', 'admin-site-enhancements' ),
                'field_placeholder' => '',
                'editor_settings'   => $editor_settings,
                'class'             => 'asenha-textarea admin-interface has-wpeditor ' . $field_slug,
            )
        );
        $field_id = 'custom_admin_footer_right';
        $field_slug = 'custom-admin-footer-right';
        // https://developer.wordpress.org/reference/classes/_wp_editors/parse_settings/
        // https://www.tiny.cloud/docs/advanced/available-toolbar-buttons/
        $editor_settings = array(
            'media_buttons'  => $media_buttons,
            'textarea_name'  => ASENHA_SLUG_U . '[' . $field_id . ']',
            'textarea_rows'  => 3,
            'tiny_mce'       => true,
            'tinymce'        => array(
                'toolbar1'    => $toolbar1,
                'content_css' => ASENHA_URL . 'assets/css/settings-wpeditor.css',
            ),
            'editor_css'     => '',
            'quicktags'      => $quicktags,
            'default_editor' => 'tinymce',
        );
        add_settings_field(
            $field_id,
            __( 'Right Side', 'admin-site-enhancements' ),
            // Field title
            [$render_field, 'render_wpeditor_subfield'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name'       => ASENHA_SLUG_U,
                'field_id'          => $field_id,
                'field_slug'        => $field_slug,
                'field_name'        => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_type'        => 'textarea',
                'field_rows'        => 1,
                'field_intro'       => '',
                'field_description' => sprintf( 
                    /* translators: %s is the WordPress version number */
                    __( 'Default text is: <em>Version %s</em>', 'admin-site-enhancements' ),
                    $wp_version
                 ),
                'field_placeholder' => '',
                'editor_settings'   => $editor_settings,
                'class'             => 'asenha-textarea admin-interface has-wpeditor ' . $field_slug,
            )
        );
        // =================================================================
        // LOG IN | LOG OUT
        // =================================================================
        // Change Login URL
        $field_id = 'change_login_url';
        $field_slug = 'change-login-url';
        add_settings_field(
            $field_id,
            __( 'Change Login URL', 'admin-site-enhancements' ),
            // Field title
            [$render_field, 'render_checkbox_toggle'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name'            => ASENHA_SLUG_U,
                'field_id'               => $field_id,
                'field_slug'             => $field_slug,
                'field_name'             => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_description'      => sprintf( 
                    /* translators: %s is the default login URL */
                    __( 'Default is %s', 'admin-site-enhancements' ),
                    site_url( '/wp-login.php' )
                 ),
                'field_options_moreless' => true,
                'field_options_wrapper'  => true,
                'class'                  => 'asenha-toggle login-logout ' . $field_slug,
            )
        );
        $field_id = 'custom_login_slug';
        $field_slug = 'custom-login-slug';
        add_settings_field(
            $field_id,
            __( 'New login URL:', 'admin-site-enhancements' ),
            // Field title
            [$render_field, 'render_text_subfield'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name'       => ASENHA_SLUG_U,
                'field_id'          => $field_id,
                'field_name'        => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_type'        => 'with-prefix-suffix',
                'field_prefix'      => site_url() . '/',
                'field_suffix'      => '/',
                'field_placeholder' => __( 'e.g. backend', 'admin-site-enhancements' ),
                'field_description' => '',
                'class'             => 'asenha-text with-prefix-suffix login-logout ' . $field_slug,
            )
        );
        $field_id = 'change_login_url_description';
        $field_slug = 'change-login-url-description';
        add_settings_field(
            $field_id,
            '',
            // Field title
            [$render_field, 'render_description_subfield'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name'       => ASENHA_SLUG_U,
                'field_description' => __( '<div class="asenha-warning">This feature <strong>only works for/with the default WordPress login page</strong>. It does not support using custom login page you manually created with a page builder or with another plugin.<br /><br />It\'s also <strong>not yet compatible with two-factor authentication (2FA) methods</strong>. If you use a 2FA plugin, please use the change login URL feature bundled in that plugin, or use another plugin that is compatible with it.<br /><br />And obviously, to improve security, please <strong>use something other than \'login\'</strong> for the custom login slug.</div>', 'admin-site-enhancements' ),
                'class'             => 'asenha-description login-logout ' . $field_slug,
            )
        );
        // Login ID Type
        $field_id = 'login_id_type_restriction';
        $field_slug = 'login-id-type-restriction';
        add_settings_field(
            $field_id,
            __( 'Login ID Type', 'admin-site-enhancements' ),
            // Field title
            [$render_field, 'render_checkbox_toggle'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name'           => ASENHA_SLUG_U,
                'field_id'              => $field_id,
                'field_slug'            => $field_slug,
                'field_name'            => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_description'     => __( 'Restrict login ID to username or email address only.', 'admin-site-enhancements' ),
                'field_options_wrapper' => true,
                'class'                 => 'asenha-toggle login-logout ' . $field_slug,
            )
        );
        $field_id = 'login_id_type';
        $field_slug = 'login-id-type';
        add_settings_field(
            $field_id,
            '',
            // Field title
            [$render_field, 'render_radio_buttons_subfield'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name'   => ASENHA_SLUG_U,
                'field_id'      => $field_id,
                'field_name'    => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_radios'  => array(
                    __( 'Username only', 'admin-site-enhancements' )      => 'username',
                    __( 'Email address only', 'admin-site-enhancements' ) => 'email',
                ),
                'field_default' => 'username',
                'class'         => 'asenha-radio-buttons shift-up login-logout ' . $field_slug,
            )
        );
        // Use Site Identity on the Login Page
        $field_id = 'site_identity_on_login';
        $field_slug = 'site-identity-on-login';
        add_settings_field(
            $field_id,
            __( 'Site Identity on Login Page', 'admin-site-enhancements' ),
            // Field title
            [$render_field, 'render_checkbox_toggle'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name'           => ASENHA_SLUG_U,
                'field_id'              => $field_id,
                'field_slug'            => $field_slug,
                'field_name'            => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_description'     => sprintf( 
                    /* translators: %s is URL to the Customizer */
                    __( 'Use the site icon and URL to replace the default WordPress logo with link to wordpress.org on the login page. Go to <a href="%1$s">General Settings</a> or the <a href="%2$s">Customizer</a> to set or change your site icon.', 'admin-site-enhancements' ),
                    admin_url( 'options-general.php' ),
                    admin_url( 'customize.php' )
                 ),
                'field_options_wrapper' => true,
                'class'                 => 'asenha-toggle login-logout ' . $field_slug,
            )
        );
        // Enable Log In/Out Menu
        $field_id = 'enable_login_logout_menu';
        $field_slug = 'enable-login-logout-menu';
        add_settings_field(
            $field_id,
            __( 'Log In/Out Menu', 'admin-site-enhancements' ),
            // Field title
            [$render_field, 'render_checkbox_toggle'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name'            => ASENHA_SLUG_U,
                'field_id'               => $field_id,
                'field_slug'             => $field_slug,
                'field_name'             => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_description'      => __( 'Enable log in, log out and dynamic log in/out menu item for addition to any menu.', 'admin-site-enhancements' ),
                'field_options_wrapper'  => true,
                'field_options_moreless' => true,
                'class'                  => 'asenha-toggle login-logout ' . $field_slug,
            )
        );
        // Enable Last Login Column
        $field_id = 'enable_last_login_column';
        $field_slug = 'enable-last-login-column';
        add_settings_field(
            $field_id,
            __( 'Last Login Column', 'admin-site-enhancements' ),
            // Field title
            [$render_field, 'render_checkbox_toggle'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name'            => ASENHA_SLUG_U,
                'field_id'               => $field_id,
                'field_slug'             => $field_slug,
                'field_name'             => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_description'      => __( 'Log when users on the site last logged in and display the date and time in the users list table.', 'admin-site-enhancements' ),
                'field_options_wrapper'  => true,
                'field_options_moreless' => true,
                'class'                  => 'asenha-toggle login-logout ' . $field_slug,
            )
        );
        // Redirect After Login
        $field_id = 'redirect_after_login';
        $field_slug = 'redirect-after-login';
        add_settings_field(
            $field_id,
            __( 'Redirect After Login', 'admin-site-enhancements' ),
            // Field title
            [$render_field, 'render_checkbox_toggle'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name'            => ASENHA_SLUG_U,
                'field_id'               => $field_id,
                'field_slug'             => $field_slug,
                'field_name'             => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_description'      => __( 'Set custom redirect URL for all or some user roles after login.', 'admin-site-enhancements' ),
                'field_options_wrapper'  => true,
                'field_options_moreless' => true,
                'class'                  => 'asenha-toggle login-logout ' . $field_slug,
            )
        );
        $field_id = 'redirect_after_login_to_slug';
        $field_slug = 'redirect-after-login-to-slug';
        add_settings_field(
            $field_id,
            __( 'Redirect to:', 'admin-site-enhancements' ),
            // Field title
            [$render_field, 'render_text_subfield'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name'       => ASENHA_SLUG_U,
                'field_id'          => $field_id,
                'field_name'        => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_type'        => 'with-prefix-suffix',
                'field_prefix'      => site_url() . '/',
                'field_suffix'      => __( 'for:', 'admin-site-enhancements' ),
                'field_placeholder' => '',
                'field_description' => '',
                'class'             => 'asenha-text with-prefix-suffix login-logout ' . $field_slug,
            )
        );
        $field_id = 'redirect_after_login_for';
        $field_slug = 'redirect-after-login-for';
        if ( is_array( $roles ) ) {
            foreach ( $roles as $role_slug => $role_label ) {
                // e.g. $role_slug is administrator, $role_label is Administrator
                add_settings_field(
                    $field_id . '_' . $role_slug,
                    '',
                    // Field title
                    [$render_field, 'render_checkbox_subfield'],
                    ASENHA_SLUG,
                    'main-section',
                    array(
                        'option_name'     => ASENHA_SLUG_U,
                        'parent_field_id' => $field_id,
                        'field_id'        => $role_slug,
                        'field_name'      => ASENHA_SLUG_U . '[' . $field_id . '][' . $role_slug . ']',
                        'field_label'     => $role_label,
                        'class'           => 'asenha-checkbox asenha-hide-th asenha-half login-logout ' . $field_slug . ' ' . $role_slug,
                    )
                );
            }
        }
        // Redirect After Logout
        $field_id = 'redirect_after_logout';
        $field_slug = 'redirect-after-logout';
        add_settings_field(
            $field_id,
            __( 'Redirect After Logout', 'admin-site-enhancements' ),
            // Field title
            [$render_field, 'render_checkbox_toggle'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name'            => ASENHA_SLUG_U,
                'field_id'               => $field_id,
                'field_slug'             => $field_slug,
                'field_name'             => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_description'      => __( 'Set custom redirect URL for all or some user roles after logout.', 'admin-site-enhancements' ),
                'field_options_wrapper'  => true,
                'field_options_moreless' => true,
                'class'                  => 'asenha-toggle login-logout ' . $field_slug,
            )
        );
        $field_id = 'redirect_after_logout_to_slug';
        $field_slug = 'redirect-after-logout-to-slug';
        add_settings_field(
            $field_id,
            __( 'Redirect to:', 'admin-site-enhancements' ),
            // Field title
            [$render_field, 'render_text_subfield'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name'       => ASENHA_SLUG_U,
                'field_id'          => $field_id,
                'field_name'        => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_type'        => 'with-prefix-suffix',
                'field_prefix'      => site_url() . '/',
                'field_suffix'      => __( 'for:', 'admin-site-enhancements' ),
                'field_description' => '',
                'class'             => 'asenha-text with-prefix-suffix login-logout ' . $field_slug,
            )
        );
        $field_id = 'redirect_after_logout_for';
        $field_slug = 'redirect-after-logout-for';
        if ( is_array( $roles ) ) {
            foreach ( $roles as $role_slug => $role_label ) {
                // e.g. $role_slug is administrator, $role_label is Administrator
                add_settings_field(
                    $field_id . '_' . $role_slug,
                    '',
                    // Field title
                    [$render_field, 'render_checkbox_subfield'],
                    ASENHA_SLUG,
                    'main-section',
                    array(
                        'option_name'     => ASENHA_SLUG_U,
                        'parent_field_id' => $field_id,
                        'field_id'        => $role_slug,
                        'field_name'      => ASENHA_SLUG_U . '[' . $field_id . '][' . $role_slug . ']',
                        'field_label'     => $role_label,
                        'class'           => 'asenha-checkbox asenha-hide-th asenha-half login-logout ' . $field_slug . ' ' . $role_slug,
                    )
                );
            }
        }
        // Enable Custom Admin CSS
        $field_id = 'enable_custom_admin_css';
        $field_slug = 'enable-custom-admin-css';
        add_settings_field(
            $field_id,
            __( 'Custom Admin CSS', 'admin-site-enhancements' ),
            // Field title
            [$render_field, 'render_checkbox_toggle'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name'            => ASENHA_SLUG_U,
                'field_id'               => $field_id,
                'field_slug'             => $field_slug,
                'field_name'             => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_description'      => __( 'Add custom CSS on all admin pages for all user roles.', 'admin-site-enhancements' ),
                'field_options_wrapper'  => true,
                'field_options_moreless' => true,
                'class'                  => 'asenha-toggle custom-code ' . $field_slug,
            )
        );
        $field_id = 'custom_admin_css';
        $field_slug = 'custom-admin-css';
        add_settings_field(
            $field_id,
            '',
            // Field title
            [$render_field, 'render_textarea_subfield'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name'       => ASENHA_SLUG_U,
                'field_id'          => $field_id,
                'field_slug'        => $field_slug,
                'field_name'        => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_type'        => 'textarea',
                'field_rows'        => 30,
                'field_intro'       => '',
                'field_description' => '',
                'class'             => 'asenha-textarea asenha-hide-th syntax-highlighted custom-code ' . $field_slug,
            )
        );
        // Enable Custom Frontend CSS
        $field_id = 'enable_custom_frontend_css';
        $field_slug = 'enable-custom-frontend-css';
        add_settings_field(
            $field_id,
            __( 'Custom Frontend CSS', 'admin-site-enhancements' ),
            // Field title
            [$render_field, 'render_checkbox_toggle'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name'            => ASENHA_SLUG_U,
                'field_id'               => $field_id,
                'field_slug'             => $field_slug,
                'field_name'             => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_description'      => __( 'Add custom CSS on all frontend pages for all user roles.', 'admin-site-enhancements' ),
                'field_options_wrapper'  => true,
                'field_options_moreless' => true,
                'class'                  => 'asenha-toggle custom-code ' . $field_slug,
            )
        );
        $field_id = 'custom_frontend_css';
        $field_slug = 'custom-frontend-css';
        add_settings_field(
            $field_id,
            '',
            // Field title
            [$render_field, 'render_textarea_subfield'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name'       => ASENHA_SLUG_U,
                'field_id'          => $field_id,
                'field_slug'        => $field_slug,
                'field_name'        => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_type'        => 'textarea',
                'field_rows'        => 30,
                'field_intro'       => '',
                'field_description' => '',
                'class'             => 'asenha-textarea asenha-hide-th syntax-highlighted custom-code ' . $field_slug,
            )
        );
        // Insert <head>, <body> and <footer> code
        $field_id = 'insert_head_body_footer_code';
        $field_slug = 'insert-head-body-footer-code';
        add_settings_field(
            $field_id,
            /* translators: keep &lt;head&gt; &lt;body&gt; &lt;footer&gt; as is in the translation */
            __( 'Insert &lt;head&gt;, &lt;body&gt; and &lt;footer&gt; Code', 'admin-site-enhancements' ),
            // Field title
            [$render_field, 'render_checkbox_toggle'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name'            => ASENHA_SLUG_U,
                'field_id'               => $field_id,
                'field_slug'             => $field_slug,
                'field_name'             => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_description'      => __( 'Easily insert &lt;meta&gt;, &lt;link&gt;, &lt;script&gt; and &lt;style&gt; tags, Google Analytics, Tag Manager, AdSense, Ads Conversion and Optimize code, Facebook, TikTok and Twitter pixels, etc.', 'admin-site-enhancements' ),
                'field_options_wrapper'  => true,
                'field_options_moreless' => true,
                'class'                  => 'asenha-toggle custom-code ' . $field_slug,
            )
        );
        $field_id = 'head_code_priority';
        $field_slug = 'head-code-priority';
        add_settings_field(
            $field_id,
            '',
            // Field title
            [$render_field, 'render_number_subfield'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name'       => ASENHA_SLUG_U,
                'field_id'          => $field_id,
                'field_name'        => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_type'        => 'with-prefix-suffix',
                'field_prefix'      => __( '<strong>Code to insert before &lt;/head&gt; with the priority of</strong>', 'admin-site-enhancements' ),
                'field_suffix'      => '',
                'field_intro'       => '',
                'field_placeholder' => '10',
                'field_description' => __( 'Default is 10. Larger number insert code closer to &lt;/head&gt;', 'admin-site-enhancements' ),
                'class'             => 'asenha-number asenha-hide-th narrow custom-code ' . $field_slug,
            )
        );
        $field_id = 'head_code';
        $field_slug = 'head-code';
        add_settings_field(
            $field_id,
            '',
            // Field title
            [$render_field, 'render_textarea_subfield'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name'       => ASENHA_SLUG_U,
                'field_id'          => $field_id,
                'field_slug'        => $field_slug,
                'field_name'        => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_type'        => 'textarea',
                'field_rows'        => 15,
                'field_intro'       => '',
                'field_description' => '',
                'class'             => 'asenha-textarea asenha-hide-th syntax-highlighted custom-code ' . $field_slug,
            )
        );
        $field_id = 'body_code_priority';
        $field_slug = 'body-code-priority';
        add_settings_field(
            $field_id,
            '',
            // Field title
            [$render_field, 'render_number_subfield'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name'       => ASENHA_SLUG_U,
                'field_id'          => $field_id,
                'field_name'        => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_type'        => 'with-prefix-suffix',
                'field_prefix'      => __( '<strong>Code to insert after &lt;body&gt; with the priority of</strong>', 'admin-site-enhancements' ),
                'field_suffix'      => '',
                'field_intro'       => '',
                'field_placeholder' => '10',
                'field_description' => __( 'Default is 10. Smaller number insert code closer to &lt;body&gt;', 'admin-site-enhancements' ),
                'class'             => 'asenha-number asenha-hide-th narrow custom-code ' . $field_slug,
            )
        );
        $field_id = 'body_code';
        $field_slug = 'body-code';
        add_settings_field(
            $field_id,
            '',
            // Field title
            [$render_field, 'render_textarea_subfield'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name'       => ASENHA_SLUG_U,
                'field_id'          => $field_id,
                'field_slug'        => $field_slug,
                'field_name'        => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_type'        => 'textarea',
                'field_rows'        => 15,
                'field_intro'       => '',
                'field_description' => '',
                'class'             => 'asenha-textarea asenha-hide-th syntax-highlighted custom-code ' . $field_slug,
            )
        );
        $field_id = 'footer_code_priority';
        $field_slug = 'footer-code-priority';
        add_settings_field(
            $field_id,
            '',
            // Field title
            [$render_field, 'render_number_subfield'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name'       => ASENHA_SLUG_U,
                'field_id'          => $field_id,
                'field_name'        => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_type'        => 'with-prefix-suffix',
                'field_prefix'      => __( '<strong>Code to insert in footer section before &lt;/body&gt; with the priority of</strong>', 'admin-site-enhancements' ),
                'field_suffix'      => '',
                'field_intro'       => '',
                'field_placeholder' => '10',
                'field_description' => __( 'Default is 10. Larger number insert code closer to &lt;/body&gt;', 'admin-site-enhancements' ),
                'class'             => 'asenha-number asenha-hide-th narrow custom-code ' . $field_slug,
            )
        );
        $field_id = 'footer_code';
        $field_slug = 'footer-code';
        add_settings_field(
            $field_id,
            '',
            // Field title
            [$render_field, 'render_textarea_subfield'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name'       => ASENHA_SLUG_U,
                'field_id'          => $field_id,
                'field_slug'        => $field_slug,
                'field_name'        => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_type'        => 'textarea',
                'field_rows'        => 15,
                'field_intro'       => '',
                'field_description' => '',
                'class'             => 'asenha-textarea asenha-hide-th syntax-highlighted custom-code ' . $field_slug,
            )
        );
        // Custom Body Class
        $field_id = 'enable_custom_body_class';
        $field_slug = 'enable-custom-body-class';
        add_settings_field(
            $field_id,
            __( 'Custom Body Class', 'admin-site-enhancements' ),
            // Field title
            [$render_field, 'render_checkbox_toggle'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name'            => ASENHA_SLUG_U,
                'field_id'               => $field_id,
                'field_slug'             => $field_slug,
                'field_name'             => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_description'      => __( 'Add custom &lt;body&gt; class(es) on the singular view of some or all public post types. Compatible with classes already added using <a href="https://wordpress.org/plugins/wp-custom-body-class" target="_blank">Custom Body Class plugin</a>.', 'admin-site-enhancements' ),
                'field_options_wrapper'  => true,
                'field_options_moreless' => true,
                'class'                  => 'asenha-toggle custom-code ' . $field_slug,
            )
        );
        $field_id = 'enable_custom_body_class_for';
        $field_slug = 'enable-custom-body-class-for';
        if ( is_array( $asenha_public_post_types ) ) {
            foreach ( $asenha_public_post_types as $post_type_slug => $post_type_label ) {
                // e.g. $post_type_slug is post, $post_type_label is Posts
                if ( 'attachment' != $post_type_slug ) {
                    add_settings_field(
                        $field_id . '_' . $post_type_slug,
                        '',
                        // Field title
                        [$render_field, 'render_checkbox_subfield'],
                        ASENHA_SLUG,
                        'main-section',
                        array(
                            'option_name'     => ASENHA_SLUG_U,
                            'parent_field_id' => $field_id,
                            'field_id'        => $post_type_slug,
                            'field_name'      => ASENHA_SLUG_U . '[' . $field_id . '][' . $post_type_slug . ']',
                            'field_label'     => $post_type_label . ' <span class="faded">(' . $post_type_slug . ')</span>',
                            'class'           => 'asenha-checkbox asenha-hide-th asenha-half custom-code ' . $field_slug . ' ' . $post_type_slug,
                        )
                    );
                }
            }
        }
        // Manage ads.txt and app-ads.txt
        $field_id = 'manage_ads_appads_txt';
        $field_slug = 'manage-ads-appads-txt';
        add_settings_field(
            $field_id,
            __( 'Manage ads.txt and app-ads.txt', 'admin-site-enhancements' ),
            // Field title
            [$render_field, 'render_checkbox_toggle'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name'            => ASENHA_SLUG_U,
                'field_id'               => $field_id,
                'field_slug'             => $field_slug,
                'field_name'             => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_description'      => __( 'Easily edit and validate your <a href="/ads.txt" target="_blank">ads.txt</a> and <a href="/app-ads.txt" target="_blank">app-ads.txt</a> content.', 'admin-site-enhancements' ),
                'field_options_wrapper'  => true,
                'field_options_moreless' => true,
                'class'                  => 'asenha-toggle custom-code ' . $field_slug,
            )
        );
        $field_id = 'ads_txt_content';
        $field_slug = 'ads-txt-content';
        $ads_txt_url_urlencoded = urlencode( site_url( 'ads.txt' ) );
        $ads_txt_str_replaced = str_replace( '.', '-', sanitize_text_field( $_SERVER['SERVER_NAME'] ) );
        add_settings_field(
            $field_id,
            '',
            // Field title
            [$render_field, 'render_textarea_subfield'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name'       => ASENHA_SLUG_U,
                'field_id'          => $field_id,
                'field_slug'        => $field_slug,
                'field_name'        => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_type'        => 'textarea',
                'field_rows'        => 15,
                'field_intro'       => __( '<strong>Your ads.txt content:</strong>', 'admin-site-enhancements' ),
                'field_description' => __( 'Validate with:', 'admin-site-enhancements' ) . ' <a href="https://adstxt.guru/validator/url/?url=' . $ads_txt_url_urlencoded . '" target="_blank">adstxt.guru</a> | <a href="https://www.adstxtvalidator.com/ads_txt/' . $ads_txt_str_replaced . '" target="_blank">adstxtvalidator.com</a><div class="vspacer"></div>',
                'admin-site-enhancements',
                'class'             => 'asenha-textarea asenha-hide-th syntax-highlighted custom-code ' . $field_slug,
            )
        );
        $field_id = 'app_ads_txt_content';
        $field_slug = 'app-ads-txt-content';
        $appads_txt_url_urlencoded = urlencode( site_url( 'app-ads.txt' ) );
        add_settings_field(
            $field_id,
            '',
            // Field title
            [$render_field, 'render_textarea_subfield'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name'       => ASENHA_SLUG_U,
                'field_id'          => $field_id,
                'field_slug'        => $field_slug,
                'field_name'        => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_type'        => 'textarea',
                'field_rows'        => 15,
                'field_intro'       => __( '<strong>Your app-ads.txt content:</strong>', 'admin-site-enhancements' ),
                'field_description' => __( 'Validate with:', 'admin-site-enhancements' ) . ' <a href="https://adstxt.guru/validator/url/?url=' . $appads_txt_url_urlencoded . '" target="_blank">adstxt.guru</a>',
                'class'             => 'asenha-textarea asenha-hide-th syntax-highlighted custom-code ' . $field_slug,
            )
        );
        // Manage robots.txt
        $field_id = 'manage_robots_txt';
        $field_slug = 'manage-robots-txt';
        add_settings_field(
            $field_id,
            __( 'Manage robots.txt', 'admin-site-enhancements' ),
            // Field title
            [$render_field, 'render_checkbox_toggle'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name'            => ASENHA_SLUG_U,
                'field_id'               => $field_id,
                'field_slug'             => $field_slug,
                'field_name'             => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_description'      => __( 'Easily edit and validate your <a href="/robots.txt" target="_blank">robots.txt</a> content.', 'admin-site-enhancements' ),
                'field_options_wrapper'  => true,
                'field_options_moreless' => true,
                'class'                  => 'asenha-toggle custom-code ' . $field_slug,
            )
        );
        $field_id = 'robots_txt_content';
        $field_slug = 'robots-txt-content';
        $robots_txt_url_urlencoded = urlencode( site_url( 'robots.txt' ) );
        add_settings_field(
            $field_id,
            '',
            // Field title
            [$render_field, 'render_textarea_subfield'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name'       => ASENHA_SLUG_U,
                'field_id'          => $field_id,
                'field_slug'        => $field_slug,
                'field_name'        => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_type'        => 'textarea',
                'field_rows'        => 20,
                'field_intro'       => '',
                'field_description' => __( 'Validate with:', 'admin-site-enhancements' ) . ' <a href="https://en.ryte.com/free-tools/robots-txt/?refresh=1&url=' . $robots_txt_url_urlencoded . '&useragent=Googlebot&submit=Evaluate" target="_blank">ryte.com</a> | <a href="https://serp.tools/tools/robots-txt" target="_blank">serp.tools</a><div class="vspacer"></div>',
                'class'             => 'asenha-textarea asenha-hide-th syntax-highlighted custom-code ' . $field_slug,
            )
        );
        // =================================================================
        // DISABLE COMPONENTS
        // =================================================================
        // Disable Gutenberg
        $field_id = 'disable_gutenberg';
        $field_slug = 'disable-gutenberg';
        add_settings_field(
            $field_id,
            __( 'Disable Gutenberg', 'admin-site-enhancements' ),
            // Field title
            [$render_field, 'render_checkbox_toggle'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name'            => ASENHA_SLUG_U,
                'field_id'               => $field_id,
                'field_slug'             => $field_slug,
                'field_name'             => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_description'      => __( 'Disable the Gutenberg block editor for some or all applicable post types.', 'admin-site-enhancements' ),
                'field_options_wrapper'  => true,
                'field_options_moreless' => true,
                'class'                  => 'asenha-toggle disable-components ' . $field_slug,
            )
        );
        $field_id = 'disable_gutenberg_for';
        $field_slug = 'disable-gutenberg-for';
        if ( is_array( $asenha_gutenberg_post_types ) ) {
            foreach ( $asenha_gutenberg_post_types as $post_type_slug => $post_type_label ) {
                // e.g. $post_type_slug is post, $post_type_label is Posts
                add_settings_field(
                    $field_id . '_' . $post_type_slug,
                    '',
                    // Field title
                    [$render_field, 'render_checkbox_subfield'],
                    ASENHA_SLUG,
                    'main-section',
                    array(
                        'option_name'     => ASENHA_SLUG_U,
                        'parent_field_id' => $field_id,
                        'field_id'        => $post_type_slug,
                        'field_name'      => ASENHA_SLUG_U . '[' . $field_id . '][' . $post_type_slug . ']',
                        'field_label'     => $post_type_label . ' <span class="faded">(' . $post_type_slug . ')</span>',
                        'class'           => 'asenha-checkbox asenha-checkbox-item asenha-hide-th asenha-half disable-components ' . $field_slug . ' ' . $post_type_slug,
                    )
                );
            }
        }
        $field_id = 'disable_gutenberg_frontend_styles';
        $field_slug = 'disable-gutenberg-frontend-styles';
        add_settings_field(
            $field_id,
            '',
            // Field title
            [$render_field, 'render_checkbox_plain'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name' => ASENHA_SLUG_U,
                'field_id'    => $field_id,
                'field_name'  => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_label' => __( 'Also disable frontend block styles / CSS files for the selected post types.', 'admin-site-enhancements' ),
                'class'       => 'asenha-checkbox asenha-hide-th asenha-th-border-top disable-components ' . $field_slug,
            )
        );
        // Disable Comments
        $field_id = 'disable_comments';
        $field_slug = 'disable-comments';
        add_settings_field(
            $field_id,
            __( 'Disable Comments', 'admin-site-enhancements' ),
            // Field title
            [$render_field, 'render_checkbox_toggle'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name'            => ASENHA_SLUG_U,
                'field_id'               => $field_id,
                'field_slug'             => $field_slug,
                'field_name'             => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_description'      => __( 'Disable comments for some or all public post types. When disabled, existing comments will also be hidden on the frontend.', 'admin-site-enhancements' ),
                'field_options_wrapper'  => true,
                'field_options_moreless' => true,
                'class'                  => 'asenha-toggle disable-components ' . $field_slug,
            )
        );
        $field_id = 'disable_comments_for';
        $field_slug = 'disable-comments-for';
        if ( is_array( $asenha_public_post_types ) ) {
            foreach ( $asenha_public_post_types as $post_type_slug => $post_type_label ) {
                // e.g. $post_type_slug is post, $post_type_label is Posts
                add_settings_field(
                    $field_id . '_' . $post_type_slug,
                    '',
                    // Field title
                    [$render_field, 'render_checkbox_subfield'],
                    ASENHA_SLUG,
                    'main-section',
                    array(
                        'option_name'     => ASENHA_SLUG_U,
                        'parent_field_id' => $field_id,
                        'field_id'        => $post_type_slug,
                        'field_name'      => ASENHA_SLUG_U . '[' . $field_id . '][' . $post_type_slug . ']',
                        'field_label'     => $post_type_label . ' <span class="faded">(' . $post_type_slug . ')</span>',
                        'class'           => 'asenha-checkbox asenha-checkbox-item asenha-hide-th asenha-half disable-components ' . $field_slug . ' ' . $post_type_slug,
                    )
                );
            }
        }
        // Disable REST API
        $field_id = 'disable_rest_api';
        $field_slug = 'disable-rest-api';
        add_settings_field(
            $field_id,
            __( 'Disable REST API', 'admin-site-enhancements' ),
            // Field title
            [$render_field, 'render_checkbox_toggle'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name'            => ASENHA_SLUG_U,
                'field_id'               => $field_id,
                'field_slug'             => $field_slug,
                'field_name'             => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_description'      => __( 'Disable REST API access for non-authenticated users and remove URL traces from &lt;head&gt;, HTTP headers and WP RSD endpoint.', 'admin-site-enhancements' ),
                'field_options_wrapper'  => true,
                'field_options_moreless' => true,
                'class'                  => 'asenha-toggle disable-components ' . $field_slug,
            )
        );
        // Disable Feeds
        $field_id = 'disable_feeds';
        $field_slug = 'disable-feeds';
        add_settings_field(
            $field_id,
            __( 'Disable Feeds', 'admin-site-enhancements' ),
            // Field title
            [$render_field, 'render_checkbox_toggle'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name'            => ASENHA_SLUG_U,
                'field_id'               => $field_id,
                'field_slug'             => $field_slug,
                'field_name'             => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_description'      => __( 'Disable all RSS, Atom and RDF feeds. This includes feeds for posts, categories, tags, comments, authors and search. Also removes traces of feed URLs from &lt;head&gt;.', 'admin-site-enhancements' ),
                'field_options_wrapper'  => true,
                'field_options_moreless' => true,
                'class'                  => 'asenha-toggle disable-components ' . $field_slug,
            )
        );
        // Disable Auto Updates
        $field_id = 'disable_all_updates';
        $field_slug = 'disable-all-updates';
        add_settings_field(
            $field_id,
            __( 'Disable All Updates', 'admin-site-enhancements' ),
            // Field title
            [$render_field, 'render_checkbox_toggle'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name'            => ASENHA_SLUG_U,
                'field_id'               => $field_id,
                'field_slug'             => $field_slug,
                'field_name'             => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_description'      => __( 'Completely disable core, theme and plugin updates and auto-updates. Will also disable update checks, notices and emails.', 'admin-site-enhancements' ),
                'field_options_wrapper'  => true,
                'field_options_moreless' => true,
                'class'                  => 'asenha-toggle disable-components ' . $field_slug,
            )
        );
        // Disable Smaller Components
        $field_id = 'disable_smaller_components';
        $field_slug = 'disable-smaller-components';
        add_settings_field(
            $field_id,
            __( 'Disable Smaller Components', 'admin-site-enhancements' ),
            // Field title
            [$render_field, 'render_checkbox_toggle'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name'            => ASENHA_SLUG_U,
                'field_id'               => $field_id,
                'field_slug'             => $field_slug,
                'field_name'             => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_description'      => __( 'Prevent smaller components from running or loading. Make the site more secure, load slightly faster and be more optimized for crawling by search engines.', 'admin-site-enhancements' ),
                'field_options_wrapper'  => true,
                'field_options_moreless' => true,
                'class'                  => 'asenha-toggle disable-components ' . $field_slug,
            )
        );
        $field_id = 'disable_head_generator_tag';
        $field_slug = 'disable-head-generator-tag';
        add_settings_field(
            $field_id,
            '',
            // Field title
            [$render_field, 'render_checkbox_plain'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name' => ASENHA_SLUG_U,
                'field_id'    => $field_id,
                'field_name'  => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_label' => __( 'Disable the <strong>generator &lt;meta&gt; tag</strong> in &lt;head&gt;, which discloses the WordPress version number. Older versions(s) might contain unpatched security loophole(s).', 'admin-site-enhancements' ),
                'class'       => 'asenha-checkbox asenha-hide-th disable-components ' . $field_slug,
            )
        );
        $field_id = 'disable_resource_version_number';
        $field_slug = 'disable-resource-version-number';
        add_settings_field(
            $field_id,
            '',
            // Field title
            [$render_field, 'render_checkbox_plain'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name' => ASENHA_SLUG_U,
                'field_id'    => $field_id,
                'field_name'  => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_label' => __( 'Disable <strong>version number</strong> on static resource URLs referenced in &lt;head&gt;, which can disclose WordPress version number. Older versions(s) might contain unpatched security loophole(s). Applies to non-logged-in view of pages. This will also increase cacheability of static assets, but may have unintended consequences. Make sure you know what you are doing.', 'admin-site-enhancements' ),
                'class'       => 'asenha-checkbox asenha-hide-th disable-components ' . $field_slug,
            )
        );
        $field_id = 'disable_head_wlwmanifest_tag';
        $field_slug = 'disable-head-wlwmanifest-tag';
        add_settings_field(
            $field_id,
            '',
            // Field title
            [$render_field, 'render_checkbox_plain'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name' => ASENHA_SLUG_U,
                'field_id'    => $field_id,
                'field_name'  => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_label' => __( 'Disable the <strong>Windows Live Writer (WLW) manifest &lt;link&gt; tag</strong> in &lt;head&gt;. The WLW app was discontinued in 2017.', 'admin-site-enhancements' ),
                'class'       => 'asenha-checkbox asenha-hide-th disable-components ' . $field_slug,
            )
        );
        $field_id = 'disable_head_rsd_tag';
        $field_slug = 'disable-head-rsd-tag';
        add_settings_field(
            $field_id,
            '',
            // Field title
            [$render_field, 'render_checkbox_plain'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name' => ASENHA_SLUG_U,
                'field_id'    => $field_id,
                'field_name'  => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_label' => __( 'Disable the <strong>Really Simple Discovery (RSD) &lt;link&gt; tag</strong> in &lt;head&gt;. It\'s not needed if your site is not using pingback or remote (XML-RPC) client to manage posts.', 'admin-site-enhancements' ),
                'class'       => 'asenha-checkbox asenha-hide-th disable-components ' . $field_slug,
            )
        );
        $field_id = 'disable_head_shortlink_tag';
        $field_slug = 'disable-head-shortlink-tag';
        add_settings_field(
            $field_id,
            '',
            // Field title
            [$render_field, 'render_checkbox_plain'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name' => ASENHA_SLUG_U,
                'field_id'    => $field_id,
                'field_name'  => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_label' => __( 'Disable the default <strong>WordPress shortlink &lt;link&gt; tag</strong> in &lt;head&gt;. Ignored by search engines and has minimal practical use case. Usually, a dedicated shortlink plugin or service is preferred that allows for nice names in the short links and tracking of clicks when sharing the link on social media.', 'admin-site-enhancements' ),
                'class'       => 'asenha-checkbox asenha-hide-th disable-components ' . $field_slug,
            )
        );
        $field_id = 'disable_frontend_dashicons';
        $field_slug = 'disable-frontend-dashicons';
        add_settings_field(
            $field_id,
            '',
            // Field title
            [$render_field, 'render_checkbox_plain'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name' => ASENHA_SLUG_U,
                'field_id'    => $field_id,
                'field_name'  => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_label' => __( 'Disable loading of <strong>Dashicons CSS and JS files</strong> on the front-end for public site visitors. This might break the layout or design of custom forms, including custom login forms, if they depend on Dashicons. Make sure to check those forms after disabling.', 'admin-site-enhancements' ),
                'class'       => 'asenha-checkbox asenha-hide-th disable-components ' . $field_slug,
            )
        );
        $field_id = 'disable_emoji_support';
        $field_slug = 'disable-emoji-support';
        add_settings_field(
            $field_id,
            '',
            // Field title
            [$render_field, 'render_checkbox_plain'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name' => ASENHA_SLUG_U,
                'field_id'    => $field_id,
                'field_name'  => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_label' => __( 'Disable <strong>emoji support for pages, posts and custom post types</strong> on the admin and frontend. The support is primarily useful for older browsers that do not have native support for it. Most modern browsers across different OSes and devices now have native support for it.', 'admin-site-enhancements' ),
                'class'       => 'asenha-checkbox asenha-hide-th disable-components ' . $field_slug,
            )
        );
        $field_id = 'disable_jquery_migrate';
        $field_slug = 'disable-jquery-migrate';
        add_settings_field(
            $field_id,
            '',
            // Field title
            [$render_field, 'render_checkbox_plain'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name' => ASENHA_SLUG_U,
                'field_id'    => $field_id,
                'field_name'  => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_label' => __( 'Disable <strong>jQuery Migrate</strong> script on the frontend, which should no longer be needed if your site uses modern theme and plugins.', 'admin-site-enhancements' ),
                'class'       => 'asenha-checkbox asenha-hide-th disable-components ' . $field_slug,
            )
        );
        $field_id = 'disable_block_widgets';
        $field_slug = 'disable-block-widgets';
        add_settings_field(
            $field_id,
            '',
            // Field title
            [$render_field, 'render_checkbox_plain'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name' => ASENHA_SLUG_U,
                'field_id'    => $field_id,
                'field_name'  => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_label' => __( 'Disable <strong>block-based widgets settings screen</strong>. Restores the classic widgets settings screen when using a classic (non-block) theme. This has no effect on block themes.', 'admin-site-enhancements' ),
                'class'       => 'asenha-checkbox asenha-hide-th disable-components ' . $field_slug,
            )
        );
        // =================================================================
        // SECURITY
        // =================================================================
        // Limit Login Attempts
        $field_id = 'limit_login_attempts';
        $field_slug = 'limit-login-attempts';
        add_settings_field(
            $field_id,
            __( 'Limit Login Attempts', 'admin-site-enhancements' ),
            // Field title
            [$render_field, 'render_checkbox_toggle'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name'            => ASENHA_SLUG_U,
                'field_id'               => $field_id,
                'field_slug'             => $field_slug,
                'field_name'             => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_description'      => __( 'Prevent brute force attacks by limiting the number of failed login attempts allowed per IP address.', 'admin-site-enhancements' ),
                'field_options_wrapper'  => true,
                'field_options_moreless' => true,
                'class'                  => 'asenha-toggle security ' . $field_slug,
            )
        );
        $field_id = 'login_fails_allowed';
        $field_slug = 'login-fails-allowed';
        add_settings_field(
            $field_id,
            '',
            // Field title
            [$render_field, 'render_number_subfield'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name'       => ASENHA_SLUG_U,
                'field_id'          => $field_id,
                'field_name'        => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_type'        => 'with-prefix-suffix',
                'field_prefix'      => '',
                'field_suffix'      => __( 'failed login attempts allowed before 15 minutes lockout', 'admin-site-enhancements' ),
                'field_intro'       => '',
                'field_placeholder' => '3',
                'field_description' => '',
                'class'             => 'asenha-text with-prefix-suffix extra-narrow no-margin security ' . $field_slug,
            )
        );
        $field_id = 'login_lockout_maxcount';
        $field_slug = 'login-lockout-maxcount';
        add_settings_field(
            $field_id,
            '',
            // Field title
            [$render_field, 'render_number_subfield'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name'       => ASENHA_SLUG_U,
                'field_id'          => $field_id,
                'field_name'        => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_type'        => 'with-prefix-suffix',
                'field_prefix'      => '',
                'field_suffix'      => __( 'lockout(s) will block further login attempts for 24 hours', 'admin-site-enhancements' ),
                'field_intro'       => '',
                'field_placeholder' => '3',
                'field_description' => '',
                'class'             => 'asenha-text with-prefix-suffix extra-narrow no-margin security ' . $field_slug,
            )
        );
        $field_id = 'login_attempts_log_table';
        $field_slug = 'login-attempts-log-table';
        add_settings_field(
            $field_id,
            __( 'Failed login attempts:', 'admin-site-enhancements' ),
            // Field title
            [$render_field, 'render_datatable'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name'       => ASENHA_SLUG_U,
                'field_id'          => $field_id,
                'field_slug'        => $field_slug,
                'field_name'        => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_type'        => 'datatable',
                'field_description' => '',
                'class'             => 'asenha-text datatable margin-top-16 security ' . $field_slug,
                'table_title'       => __( 'Failed Login Attempts Log', 'admin-site-enhancements' ),
                'table_name'        => $wpdb->prefix . 'asenha_failed_logins',
            )
        );
        // Obfuscate Author Slugs
        $field_id = 'obfuscate_author_slugs';
        $field_slug = 'obfuscate-author-slugs';
        add_settings_field(
            $field_id,
            __( 'Obfuscate Author Slugs', 'admin-site-enhancements' ),
            // Field title
            [$render_field, 'render_checkbox_toggle'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name'           => ASENHA_SLUG_U,
                'field_id'              => $field_id,
                'field_slug'            => $field_slug,
                'field_name'            => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_description'     => __( 'Obfuscate publicly exposed author page URLs that shows the user slugs / usernames, e.g. <em>sitename.com/author/username1/</em> into <em>sitename.com/author/a6r5b8ytu9gp34bv/</em>, and output 404 errors for the original URLs. Also obfuscates in /wp-json/wp/v2/users/ REST API endpoint.', 'admin-site-enhancements' ),
                'field_options_wrapper' => false,
                'class'                 => 'asenha-toggle security ' . $field_slug,
            )
        );
        // Obfuscate Email Address
        $field_id = 'obfuscate_email_address';
        $field_slug = 'obfuscate-email-address';
        add_settings_field(
            $field_id,
            __( 'Email Address Obfuscator', 'admin-site-enhancements' ),
            // Field title
            [$render_field, 'render_checkbox_toggle'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name'            => ASENHA_SLUG_U,
                'field_id'               => $field_id,
                'field_slug'             => $field_slug,
                'field_name'             => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_description'      => __( 'Obfuscate email address to prevent spam bots from harvesting them, but make it readable like a regular email address for human visitors.', 'admin-site-enhancements' ),
                'field_options_wrapper'  => true,
                'field_options_moreless' => true,
                'class'                  => 'asenha-toggle security ' . $field_slug,
            )
        );
        $field_id = 'obfuscate_email_address_description';
        $field_slug = 'obfuscate-email-address-description';
        $field_description = __( 'Use a shortcode like the following examples to display an email address on the frontend of your site: 
        		<ul>
        			<li><strong>[obfuscate email="john@example.com"]</strong> to display the email on it\'s own line</li>
        			<li><strong>[obfuscate email="john@example.com" display="inline"]</strong> to show the email inline</li>
        		</ul>', 'admin-site-enhancements' );
        add_settings_field(
            $field_id,
            '',
            // Field title
            [$render_field, 'render_description_subfield'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name'       => ASENHA_SLUG_U,
                'field_description' => $field_description,
                'class'             => 'asenha-description security ' . $field_slug,
            )
        );
        // Disable XML-RPC
        $field_id = 'disable_xmlrpc';
        $field_slug = 'disable-xmlrpc';
        add_settings_field(
            $field_id,
            __( 'Disable XML-RPC', 'admin-site-enhancements' ),
            // Field title
            [$render_field, 'render_checkbox_toggle'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name'           => ASENHA_SLUG_U,
                'field_id'              => $field_id,
                'field_slug'            => $field_slug,
                'field_name'            => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_description'     => __( 'Protect your site from brute force, DOS and DDOS attacks via <a href="https://kinsta.com/blog/xmlrpc-php/#what-is-xmlrpcphp" target="_blank">XML-RPC</a>. Also disables trackbacks and pingbacks.', 'admin-site-enhancements' ),
                'field_options_wrapper' => false,
                'class'                 => 'asenha-toggle security ' . $field_slug,
            )
        );
        // =================================================================
        // OPTIMIZATIONS
        // =================================================================
        // Image Upload Control
        $field_id = 'image_upload_control';
        $field_slug = 'image-upload-control';
        add_settings_field(
            $field_id,
            __( 'Image Upload Control', 'admin-site-enhancements' ),
            // Field title
            [$render_field, 'render_checkbox_toggle'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name'            => ASENHA_SLUG_U,
                'field_id'               => $field_id,
                'field_slug'             => $field_slug,
                'field_name'             => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_description'      => __( 'Resize newly uploaded, large images to a smaller dimension and delete originally uploaded files. BMPs and non-transparent PNGs will be converted to JPGs and resized.', 'admin-site-enhancements' ),
                'field_options_wrapper'  => true,
                'field_options_moreless' => true,
                'class'                  => 'asenha-toggle optimizations ' . $field_slug,
            )
        );
        $field_id = 'image_max_width';
        $field_slug = 'image-max-width';
        add_settings_field(
            $field_id,
            '',
            // Field title
            [$render_field, 'render_number_subfield'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name'       => ASENHA_SLUG_U,
                'field_id'          => $field_id,
                'field_name'        => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_type'        => 'with-prefix-suffix',
                'field_prefix'      => __( 'Max width:', 'admin-site-enhancements' ),
                'field_suffix'      => __( 'pixels. <span class="faded">(Default is 1920 pixels)</span>', 'admin-site-enhancements' ),
                'field_intro'       => '',
                'field_placeholder' => '1920',
                'field_description' => '',
                'class'             => 'asenha-number asenha-hide-th narrow optimizations ' . $field_slug,
            )
        );
        $field_id = 'image_max_height';
        $field_slug = 'image-max-height';
        add_settings_field(
            $field_id,
            '',
            // Field title
            [$render_field, 'render_number_subfield'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name'       => ASENHA_SLUG_U,
                'field_id'          => $field_id,
                'field_name'        => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_type'        => 'with-prefix-suffix',
                'field_prefix'      => __( 'Max height:', 'admin-site-enhancements' ),
                'field_suffix'      => __( 'pixels <span class="faded">(Default is 1920 pixels)</span>', 'admin-site-enhancements' ),
                'field_intro'       => '',
                'field_placeholder' => '1920',
                'class'             => 'asenha-number asenha-hide-th narrow margin-bottom-4 optimizations ' . $field_slug,
            )
        );
        $field_id = 'image_upload_control_description';
        $field_slug = 'image-upload-control-description';
        $field_description = __( 'To exclude an image from conversion and resizing, append \'-nr\' suffix to the file name, e.g. bird-photo-4k-nr.jpg', 'admin-site-enhancements' );
        add_settings_field(
            $field_id,
            '',
            // Field title
            [$render_field, 'render_description_subfield'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name'       => ASENHA_SLUG_U,
                'field_description' => $field_description,
                'class'             => 'asenha-description top-border optimizations ' . $field_slug,
            )
        );
        // Enable Revisions Control
        $field_id = 'enable_revisions_control';
        $field_slug = 'enable-revisions-control';
        add_settings_field(
            $field_id,
            __( 'Revisions Control', 'admin-site-enhancements' ),
            // Field title
            [$render_field, 'render_checkbox_toggle'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name'            => ASENHA_SLUG_U,
                'field_id'               => $field_id,
                'field_slug'             => $field_slug,
                'field_name'             => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_description'      => __( 'Prevent bloating the database by limiting the number of revisions to keep for some or all post types supporting revisions.', 'admin-site-enhancements' ),
                'field_options_wrapper'  => true,
                'field_options_moreless' => true,
                'class'                  => 'asenha-toggle optimizations ' . $field_slug,
            )
        );
        $field_id = 'revisions_max_number';
        $field_slug = 'revisions-max-number';
        add_settings_field(
            $field_id,
            '',
            // Field title
            [$render_field, 'render_number_subfield'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name'       => ASENHA_SLUG_U,
                'field_id'          => $field_id,
                'field_name'        => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_type'        => 'with-prefix-suffix',
                'field_prefix'      => __( 'Limit to', 'admin-site-enhancements' ),
                'field_suffix'      => __( 'revisions for:', 'admin-site-enhancements' ),
                'field_intro'       => '',
                'field_placeholder' => '10',
                'field_description' => '',
                'class'             => 'asenha-number asenha-hide-th extra-narrow optimizations ' . $field_slug,
            )
        );
        $field_id = 'enable_revisions_control_for';
        $field_slug = 'enable-revisions-control-for';
        if ( is_array( $asenha_revisions_post_types ) ) {
            // Exclude Bricks builder template CPT as revisions are handled via a constant
            // Ref: https://academy.bricksbuilder.io/article/revisions/
            unset($asenha_revisions_post_types['bricks_template']);
            foreach ( $asenha_revisions_post_types as $post_type_slug => $post_type_label ) {
                // e.g. $post_type_slug is post, $post_type_label is Posts
                add_settings_field(
                    $field_id . '_' . $post_type_slug,
                    '',
                    // Field title
                    [$render_field, 'render_checkbox_subfield'],
                    ASENHA_SLUG,
                    'main-section',
                    array(
                        'option_name'     => ASENHA_SLUG_U,
                        'parent_field_id' => $field_id,
                        'field_id'        => $post_type_slug,
                        'field_name'      => ASENHA_SLUG_U . '[' . $field_id . '][' . $post_type_slug . ']',
                        'field_label'     => $post_type_label . ' <span class="faded">(' . $post_type_slug . ')</span>',
                        'class'           => 'asenha-checkbox asenha-hide-th asenha-half optimizations ' . $field_slug . ' ' . $post_type_slug,
                    )
                );
            }
        }
        // Enable Heartbeat Control
        $field_id = 'enable_heartbeat_control';
        $field_slug = 'enable-heartbeat-control';
        add_settings_field(
            $field_id,
            __( 'Heartbeat Control', 'admin-site-enhancements' ),
            // Field title
            [$render_field, 'render_checkbox_toggle'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name'            => ASENHA_SLUG_U,
                'field_id'               => $field_id,
                'field_slug'             => $field_slug,
                'field_name'             => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_description'      => __( 'Modify the interval of the WordPress heartbeat API or disable it on admin pages, post creation/edit screens and/or the frontend. This will help reduce CPU load on the server.', 'admin-site-enhancements' ),
                'field_options_wrapper'  => true,
                'field_options_moreless' => true,
                'class'                  => 'asenha-toggle optimizations ' . $field_slug,
            )
        );
        $field_id = 'heartbeat_control_for_admin_pages';
        $field_slug = 'heartbeat-control-for-admin-pages';
        add_settings_field(
            $field_id,
            __( 'On admin pages', 'admin-site-enhancements' ),
            // Field title
            [$render_field, 'render_radio_buttons_subfield'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name'   => ASENHA_SLUG_U,
                'field_id'      => $field_id,
                'field_name'    => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_radios'  => array(
                    __( 'Keep as is', 'admin-site-enhancements' ) => 'default',
                    __( 'Modify', 'admin-site-enhancements' )     => 'modify',
                    __( 'Disable', 'admin-site-enhancements' )    => 'disable',
                ),
                'field_default' => 'default',
                'class'         => 'asenha-radio-buttons optimizations ' . $field_slug,
            )
        );
        $field_id = 'heartbeat_interval_for_admin_pages';
        $field_slug = 'heartbeat-interval-for-admin-pages';
        add_settings_field(
            $field_id,
            '',
            // Field title
            [$render_field, 'render_select_subfield'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name'          => ASENHA_SLUG_U,
                'field_id'             => $field_id,
                'field_name'           => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_type'           => 'with-prefix-suffix',
                'field_prefix'         => __( 'Set interval to once every', 'admin-site-enhancements' ),
                'field_suffix'         => __( '<span class="faded">(Default is 1 minute)</span>', 'admin-site-enhancements' ),
                'field_select_options' => array(
                    __( '15 seconds', 'admin-site-enhancements' ) => 15,
                    __( '30 seconds', 'admin-site-enhancements' ) => 30,
                    __( '1 minute', 'admin-site-enhancements' )   => 60,
                    __( '2 minutes', 'admin-site-enhancements' )  => 120,
                    __( '3 minutes', 'admin-site-enhancements' )  => 180,
                    __( '5 minutes', 'admin-site-enhancements' )  => 300,
                    __( '10 minutes', 'admin-site-enhancements' ) => 600,
                ),
                'field_select_default' => 60,
                'field_intro'          => '',
                'field_description'    => '',
                'class'                => 'asenha-number asenha-hide-th extra-narrow shift-up optimizations ' . $field_slug,
                'display_none_on_load' => true,
            )
        );
        $field_id = 'heartbeat_control_for_post_edit';
        $field_slug = 'heartbeat-control-for-post-edit';
        add_settings_field(
            $field_id,
            __( 'On post creation and edit screens', 'admin-site-enhancements' ),
            // Field title
            [$render_field, 'render_radio_buttons_subfield'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name'   => ASENHA_SLUG_U,
                'field_id'      => $field_id,
                'field_name'    => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_radios'  => array(
                    __( 'Keep as is', 'admin-site-enhancements' ) => 'default',
                    __( 'Modify', 'admin-site-enhancements' )     => 'modify',
                    __( 'Disable', 'admin-site-enhancements' )    => 'disable',
                ),
                'field_default' => 'default',
                'class'         => 'asenha-radio-buttons optimizations top-border ' . $field_slug,
            )
        );
        $field_id = 'heartbeat_interval_for_post_edit';
        $field_slug = 'heartbeat-interval-for-post-edit';
        add_settings_field(
            $field_id,
            '',
            // Field title
            [$render_field, 'render_select_subfield'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name'          => ASENHA_SLUG_U,
                'field_id'             => $field_id,
                'field_name'           => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_type'           => 'with-prefix-suffix',
                'field_prefix'         => __( 'Set interval to once every', 'admin-site-enhancements' ),
                'field_suffix'         => __( '<span class="faded">(Default is 15 seconds)</span>', 'admin-site-enhancements' ),
                'field_select_options' => array(
                    __( '15 seconds', 'admin-site-enhancements' )  => 15,
                    __( '30 seconds', 'admin-site-enhancements' )  => 30,
                    __( '45 seconds', 'admin-site-enhancements' )  => 45,
                    __( '60 seconds', 'admin-site-enhancements' )  => 60,
                    __( '90 seconds', 'admin-site-enhancements' )  => 90,
                    __( '120 seconds', 'admin-site-enhancements' ) => 120,
                ),
                'field_select_default' => 15,
                'field_intro'          => '',
                'field_description'    => '',
                'class'                => 'asenha-number asenha-hide-th extra-narrow shift-up optimizations ' . $field_slug,
                'display_none_on_load' => true,
            )
        );
        $field_id = 'heartbeat_control_for_frontend';
        $field_slug = 'heartbeat-control-for-frontend';
        add_settings_field(
            $field_id,
            __( 'On the frontend', 'admin-site-enhancements' ),
            // Field title
            [$render_field, 'render_radio_buttons_subfield'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name'   => ASENHA_SLUG_U,
                'field_id'      => $field_id,
                'field_name'    => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_radios'  => array(
                    __( 'Keep as is', 'admin-site-enhancements' ) => 'default',
                    __( 'Modify', 'admin-site-enhancements' )     => 'modify',
                    __( 'Disable', 'admin-site-enhancements' )    => 'disable',
                ),
                'field_default' => 'default',
                'class'         => 'asenha-radio-buttons optimizations top-border ' . $field_slug,
            )
        );
        $field_id = 'heartbeat_interval_for_frontend';
        $field_slug = 'heartbeat-interval-for-frontend';
        add_settings_field(
            $field_id,
            '',
            // Field title
            [$render_field, 'render_select_subfield'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name'          => ASENHA_SLUG_U,
                'field_id'             => $field_id,
                'field_name'           => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_type'           => 'with-prefix-suffix',
                'field_prefix'         => __( 'Set interval to once every', 'admin-site-enhancements' ),
                'field_suffix'         => '',
                'field_select_options' => array(
                    __( '15 seconds', 'admin-site-enhancements' ) => 15,
                    __( '30 seconds', 'admin-site-enhancements' ) => 30,
                    __( '1 minute', 'admin-site-enhancements' )   => 60,
                    __( '2 minutes', 'admin-site-enhancements' )  => 120,
                    __( '3 minutes', 'admin-site-enhancements' )  => 180,
                    __( '5 minutes', 'admin-site-enhancements' )  => 300,
                    __( '10 minutes', 'admin-site-enhancements' ) => 600,
                ),
                'field_select_default' => 60,
                'field_intro'          => '',
                'field_description'    => '',
                'class'                => 'asenha-number asenha-hide-th extra-narrow shift-up optimizations ' . $field_slug,
                'display_none_on_load' => true,
            )
        );
        // =================================================================
        // UTILITIES
        // =================================================================
        // SMTP Email Delivery
        $field_id = 'smtp_email_delivery';
        $field_slug = 'smtp-email-delivery';
        add_settings_field(
            $field_id,
            __( 'Email Delivery', 'admin-site-enhancements' ),
            // Field title
            [$render_field, 'render_checkbox_toggle'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name'            => ASENHA_SLUG_U,
                'field_id'               => $field_id,
                'field_slug'             => $field_slug,
                'field_name'             => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_description'      => __( 'Set custom sender name and email. Optionally use external SMTP service to ensure notification and transactional emails from your site are being delivered to inboxes.', 'admin-site-enhancements' ),
                'field_options_wrapper'  => true,
                'field_options_moreless' => true,
                'class'                  => 'asenha-toggle utilities ' . $field_slug,
            )
        );
        $field_id = 'smtp_default_from_description';
        $field_slug = 'smtp-default-from-description';
        add_settings_field(
            $field_id,
            '',
            // Field title
            [$render_field, 'render_description_subfield'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name'       => ASENHA_SLUG_U,
                'field_description' => __( 'If set, the following sender name/email overrides WordPress core defaults but can still be overridden by other plugins that enables custom sender name/email, e.g. form plugins.', 'admin-site-enhancements' ),
                'class'             => 'asenha-description utilities ' . $field_slug,
            )
        );
        $field_id = 'smtp_default_from_name';
        $field_slug = 'smtp-default-from-name';
        add_settings_field(
            $field_id,
            __( '<span class="field-sublabel sublabel-wide">Sender name</span>', 'admin-site-enhancements' ),
            // Field title
            [$render_field, 'render_text_subfield'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name'       => ASENHA_SLUG_U,
                'field_id'          => $field_id,
                'field_name'        => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_type'        => '',
                'field_prefix'      => '',
                'field_suffix'      => '',
                'field_description' => '',
                'class'             => 'asenha-text with-prefix-suffix wide utilities ' . $field_slug,
            )
        );
        $field_id = 'smtp_default_from_email';
        $field_slug = 'smtp-default-from-email';
        add_settings_field(
            $field_id,
            __( '<span class="field-sublabel sublabel-wide">Sender email</span>', 'admin-site-enhancements' ),
            // Field title
            [$render_field, 'render_text_subfield'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name'       => ASENHA_SLUG_U,
                'field_id'          => $field_id,
                'field_name'        => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_type'        => '',
                'field_prefix'      => '',
                'field_suffix'      => '',
                'field_description' => '',
                'class'             => 'asenha-text with-prefix-suffix wide utilities ' . $field_slug,
            )
        );
        $field_id = 'smtp_force_from';
        $field_slug = 'smtp-force-from';
        add_settings_field(
            $field_id,
            '',
            // Field title
            [$render_field, 'render_checkbox_plain'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name' => ASENHA_SLUG_U,
                'field_id'    => $field_id,
                'field_name'  => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_label' => __( 'Force the usage of the sender name/email defined above. It will override those set by other plugins.', 'admin-site-enhancements' ),
                'class'       => 'asenha-checkbox asenha-hide-th utilities ' . $field_slug,
            )
        );
        $field_id = 'smtp_description';
        $field_slug = 'smtp--description';
        add_settings_field(
            $field_id,
            '',
            // Field title
            [$render_field, 'render_description_subfield'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name'       => ASENHA_SLUG_U,
                'field_description' => __( 'If set, the following SMTP service/account wil be used to deliver your emails.', 'admin-site-enhancements' ),
                'class'             => 'asenha-description top-border utilities ' . $field_slug,
            )
        );
        $field_id = 'smtp_host';
        $field_slug = 'smtp-host';
        add_settings_field(
            $field_id,
            __( '<span class="field-sublabel sublabel-wide">Host</span>', 'admin-site-enhancements' ),
            // Field title
            [$render_field, 'render_text_subfield'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name'       => ASENHA_SLUG_U,
                'field_id'          => $field_id,
                'field_name'        => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_type'        => '',
                'field_prefix'      => '',
                'field_suffix'      => '',
                'field_description' => '',
                'class'             => 'asenha-text with-prefix-suffix wide utilities ' . $field_slug,
            )
        );
        $field_id = 'smtp_port';
        $field_slug = 'smtp-port';
        add_settings_field(
            $field_id,
            __( '<span class="field-sublabel sublabel-wide">Port</span>', 'admin-site-enhancements' ),
            // Field title
            [$render_field, 'render_number_subfield'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name'       => ASENHA_SLUG_U,
                'field_id'          => $field_id,
                'field_name'        => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_type'        => '',
                'field_prefix'      => '',
                'field_suffix'      => '',
                'field_intro'       => '',
                'field_description' => '',
                'class'             => 'asenha-text with-prefix-suffix narrow utilities ' . $field_slug,
            )
        );
        $field_id = 'smtp_security';
        $field_slug = 'smtp-security';
        add_settings_field(
            $field_id,
            __( '<span class="field-sublabel sublabel-wide">Security</span>', 'admin-site-enhancements' ),
            // Field title
            [$render_field, 'render_radio_buttons_subfield'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name'   => ASENHA_SLUG_U,
                'field_id'      => $field_id,
                'field_name'    => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_radios'  => array(
                    __( 'None', 'admin-site-enhancements' ) => 'none',
                    __( 'SSL', 'admin-site-enhancements' )  => 'ssl',
                    __( 'TLS', 'admin-site-enhancements' )  => 'tls',
                ),
                'field_default' => 'default',
                'class'         => 'asenha-radio-buttons with-prefix-suffix utilities ' . $field_slug,
            )
        );
        $field_id = 'smtp_username';
        $field_slug = 'smtp-username';
        add_settings_field(
            $field_id,
            __( '<span class="field-sublabel sublabel-wide">Username</span>', 'admin-site-enhancements' ),
            // Field title
            [$render_field, 'render_text_subfield'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name'       => ASENHA_SLUG_U,
                'field_id'          => $field_id,
                'field_name'        => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_type'        => '',
                'field_prefix'      => '',
                'field_suffix'      => '',
                'field_description' => '',
                'class'             => 'asenha-text with-prefix-suffix wide utilities ' . $field_slug,
            )
        );
        $field_id = 'smtp_password';
        $field_slug = 'smtp-password';
        add_settings_field(
            $field_id,
            __( '<span class="field-sublabel sublabel-wide">Password</span>', 'admin-site-enhancements' ),
            // Field title
            [$render_field, 'render_password_subfield'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name'       => ASENHA_SLUG_U,
                'field_id'          => $field_id,
                'field_name'        => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_type'        => '',
                'field_prefix'      => '',
                'field_suffix'      => '',
                'field_description' => '',
                'class'             => 'asenha-text with-prefix-suffix wide utilities ' . $field_slug,
            )
        );
        $field_id = 'smtp_bypass_ssl_verification';
        $field_slug = 'smtp-bypass-ssl-verification';
        add_settings_field(
            $field_id,
            '',
            // Field title
            [$render_field, 'render_checkbox_plain'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name' => ASENHA_SLUG_U,
                'field_id'    => $field_id,
                'field_name'  => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_label' => __( 'Bypass verification of SSL certificate. This would be insecure if mail is delivered across the internet but could help in certain local and/or containerized WordPress scenarios.', 'admin-site-enhancements' ),
                'class'       => 'asenha-checkbox asenha-hide-th margin-top-8 utilities ' . $field_slug,
            )
        );
        $field_id = 'smtp_debug';
        $field_slug = 'smtp-debug';
        add_settings_field(
            $field_id,
            '',
            // Field title
            [$render_field, 'render_checkbox_plain'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name' => ASENHA_SLUG_U,
                'field_id'    => $field_id,
                'field_name'  => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_label' => __( 'Enable debug mode and output the debug info into WordPress debug.log file.', 'admin-site-enhancements' ),
                'class'       => 'asenha-checkbox asenha-hide-th bottom-border utilities ' . $field_slug,
            )
        );
        $field_id = 'smtp_send_test_email_description';
        $field_slug = 'smtp-send-test-email-description';
        add_settings_field(
            $field_id,
            '',
            // Field title
            [$render_field, 'render_description_subfield'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name'       => ASENHA_SLUG_U,
                'field_description' => __( 'After saving the settings above, check if everything is configured properly below.', 'admin-site-enhancements' ),
                'class'             => 'asenha-description utilities ' . $field_slug,
            )
        );
        $field_id = 'smtp_send_test_email_to';
        $field_slug = 'smtp-send-test-email-to';
        add_settings_field(
            $field_id,
            __( '<span class="field-sublabel sublabel-wide">Send a test email to</span>', 'admin-site-enhancements' ),
            // Field title
            [$render_field, 'render_custom_html'],
            ASENHA_SLUG,
            'main-section',
            array(
                'html'  => '<input type="text" id="test-email-to" class="asenha-subfield-text" name="" placeholder="" value=""><a id="send-test-email" class="button send-test-email">' . __( 'Send Now', 'admin-site-enhancements' ) . '</a>',
                'class' => 'asenha-html wide utilities ' . $field_slug,
            )
        );
        $field_id = 'smtp_send_test_email_result';
        $field_slug = 'smtp-send-test-email-result';
        add_settings_field(
            $field_id,
            '',
            // Field title
            [$render_field, 'render_description_subfield'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name'       => ASENHA_SLUG_U,
                'field_description' => '<div id="ajax-result" class="ajax-result-div" style="display:none;">
				<div class="sending-test-email"><img src="' . ASENHA_URL . 'assets/img/oval.svg" id="sending-test-email-spinner" class="spinner-img">' . __( 'Sending test email...', 'admin-site-enhancements' ) . '</div>
				<div id="test-email-success" class="test-email-success" style="display:none;"><span class="dashicons dashicons-yes"></span> <span>' . __( 'Test email was successfully processed</span>.<br />Please check the destination email\'s inbox to verify successful delivery.', 'admin-site-enhancements' ) . '</div>
				<div id="test-email-failed" class="test-email-failed" style="display:none;"><span class="dashicons dashicons-no-alt"></span> <span>' . __( 'Oops, something went wrong</span>.<br />Please double check your settings and the destination email address.', 'admin-site-enhancements' ) . '</div></div>',
                'class'             => 'asenha-description utilities ' . $field_slug,
            )
        );
        // Multiple User Roles
        $field_id = 'multiple_user_roles';
        $field_slug = 'multiple-user-roles';
        add_settings_field(
            $field_id,
            __( 'Multiple User Roles', 'admin-site-enhancements' ),
            // Field title
            [$render_field, 'render_checkbox_toggle'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name'           => ASENHA_SLUG_U,
                'field_id'              => $field_id,
                'field_slug'            => $field_slug,
                'field_name'            => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_description'     => __( 'Enable assignment of multiple roles during user account creation and editing. This maybe useful for working with roles not defined in WordPress core, e.g. from e-commerce or LMS plugins.', 'admin-site-enhancements' ),
                'field_options_wrapper' => true,
                'class'                 => 'asenha-toggle utilities ' . $field_slug,
            )
        );
        // Image Sizes Panel
        $field_id = 'image_sizes_panel';
        $field_slug = 'image-sizes-panel';
        add_settings_field(
            $field_id,
            __( 'Image Sizes Panel', 'admin-site-enhancements' ),
            // Field title
            [$render_field, 'render_checkbox_toggle'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name'           => ASENHA_SLUG_U,
                'field_id'              => $field_id,
                'field_slug'            => $field_slug,
                'field_name'            => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_description'     => __( 'Display a panel showing and linking to all available sizes when viewing an image in the media library. Especially useful to quickly get the URL of a particular image size.', 'admin-site-enhancements' ),
                'field_options_wrapper' => true,
                'class'                 => 'asenha-toggle utilities ' . $field_slug,
            )
        );
        // View Admin as Role
        $field_id = 'view_admin_as_role';
        $field_slug = 'view-admin-as-role';
        add_settings_field(
            $field_id,
            __( 'View Admin as Role', 'admin-site-enhancements' ),
            // Field title
            [$render_field, 'render_checkbox_toggle'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name'            => ASENHA_SLUG_U,
                'field_id'               => $field_id,
                'field_slug'             => $field_slug,
                'field_name'             => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_description'      => __( 'View admin pages and the site (logged-in) as one of the non-administrator user roles.', 'admin-site-enhancements' ),
                'field_options_moreless' => true,
                'field_options_wrapper'  => true,
                'class'                  => 'asenha-toggle utilities ' . $field_slug,
            )
        );
        $current_user = wp_get_current_user();
        $current_user_username = $current_user->user_login;
        $field_id = 'view_admin_as_role_description';
        $field_slug = 'view-admin-as-role-description';
        $role_reset_link = site_url( '/?reset-for=' ) . $current_user_username;
        add_settings_field(
            $field_id,
            '',
            // Field title
            [$render_field, 'render_description_subfield'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name'       => ASENHA_SLUG_U,
                'field_description' => '<div class="asenha-warning"><strong>' . sprintf( 
                    /* translators: %s is URL of the role reset link */
                    __( 'If something goes wrong</strong> and you need to regain access to your account as an administrator, please visit the following URL: <br /><strong>%s</strong><br /><br />If you use <strong>Ninja Firewall</strong>, please uncheck "Block attempts to gain administrative privileges" in the Firewall Policies settings before you try to view as a non-admin user role to <strong>prevent being locked out</strong> of your admin account.', 'admin-site-enhancements' ),
                    $role_reset_link
                 ) . '</div>',
                'class'             => 'asenha-description utilities ' . $field_slug,
            )
        );
        // Enable Password Protection
        $field_id = 'enable_password_protection';
        $field_slug = 'enable-password-protection';
        add_settings_field(
            $field_id,
            __( 'Password Protection', 'admin-site-enhancements' ),
            // Field title
            [$render_field, 'render_checkbox_toggle'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name'            => ASENHA_SLUG_U,
                'field_id'               => $field_id,
                'field_slug'             => $field_slug,
                'field_name'             => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_description'      => __( 'Password-protect the entire site to hide the content from public view and search engine bots / crawlers. Logged-in administrators can still access the site as usual.', 'admin-site-enhancements' ),
                'field_options_moreless' => true,
                'field_options_wrapper'  => true,
                'class'                  => 'asenha-toggle utilities ' . $field_slug,
            )
        );
        $field_id = 'password_protection_password';
        $field_slug = 'password-protection-password';
        add_settings_field(
            $field_id,
            __( 'Set the password:', 'admin-site-enhancements' ),
            // Field title
            [$render_field, 'render_password_subfield'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name'       => ASENHA_SLUG_U,
                'field_id'          => $field_id,
                'field_name'        => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_type'        => 'with-prefix-suffix',
                'field_prefix'      => '',
                'field_suffix'      => '<span class="faded">' . __( '(Default is \'secret\')', 'admin-site-enhancements' ) . '</span>',
                'field_description' => '',
                'class'             => 'asenha-text with-prefix-suffix utilities ' . $field_slug,
            )
        );
        // Maintenance Mode
        $field_id = 'maintenance_mode';
        $field_slug = 'maintenance-mode';
        add_settings_field(
            $field_id,
            __( 'Maintenance Mode', 'admin-site-enhancements' ),
            // Field title
            [$render_field, 'render_checkbox_toggle'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name'            => ASENHA_SLUG_U,
                'field_id'               => $field_id,
                'field_slug'             => $field_slug,
                'field_name'             => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_description'      => __( 'Show a customizable maintenance page on the frontend while performing a brief maintenance to your site. Logged-in administrators can still view the site as usual.', 'admin-site-enhancements' ),
                'field_options_wrapper'  => true,
                'field_options_moreless' => true,
                'class'                  => 'asenha-toggle utilities ' . $field_slug,
            )
        );
        $field_id = 'maintenance_page_type_custom';
        $field_slug = 'maintenance-page-type-custom';
        add_settings_field(
            $field_id,
            '',
            // Field title
            [$render_field, 'render_custom_html'],
            ASENHA_SLUG,
            'main-section',
            array(
                'html' => '<div class="subfields-container subfields-in-column maintenance-page-type-custom"></div>',
            )
        );
        $field_id = 'maintenance_page_heading';
        $field_slug = 'maintenance-page-heading';
        add_settings_field(
            $field_id,
            __( 'Heading', 'admin-site-enhancements' ),
            // Field title
            [$render_field, 'render_text_subfield'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name'       => ASENHA_SLUG_U,
                'field_id'          => $field_id,
                'field_name'        => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_type'        => '',
                'field_prefix'      => '',
                'field_suffix'      => '',
                'field_description' => '',
                'field_placeholder' => __( 'We\'ll be back soon.', 'admin-site-enhancements' ),
                'class'             => 'asenha-text utilities full-width margin-bottom-20 ' . $field_slug,
            )
        );
        $field_id = 'maintenance_page_description';
        $field_slug = 'maintenance-page-description';
        add_settings_field(
            $field_id,
            __( 'Description', 'admin-site-enhancements' ),
            // Field title
            [$render_field, 'render_textarea_subfield'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name'       => ASENHA_SLUG_U,
                'field_id'          => $field_id,
                'field_slug'        => $field_slug,
                'field_name'        => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_type'        => 'textarea',
                'field_rows'        => 5,
                'field_intro'       => '',
                'field_description' => '',
                'field_placeholder' => __( 'This site is undergoing maintenance for an extended period today. Thanks for your patience.', 'admin-site-enhancements' ),
                'class'             => 'asenha-textarea utilities ' . $field_slug,
            )
        );
        $field_id = 'maintenance_page_background';
        $field_slug = 'maintenance-page-background';
        $field_radios = array(
            __( 'Stripes', 'admin-site-enhancements' ) => 'stripes',
            __( 'Curves', 'admin-site-enhancements' )  => 'curves',
            __( 'Lines', 'admin-site-enhancements' )   => 'lines',
        );
        add_settings_field(
            $field_id,
            __( 'Background', 'admin-site-enhancements' ),
            // Field title
            [$render_field, 'render_radio_buttons_subfield'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name'   => ASENHA_SLUG_U,
                'field_id'      => $field_id,
                'field_name'    => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_radios'  => $field_radios,
                'field_default' => 'default',
                'class'         => 'asenha-radio-buttons margin-top-12 utilities ' . $field_slug,
            )
        );
        $field_id = 'maintenance_mode_description';
        $field_slug = 'maintenance-mode-description';
        add_settings_field(
            $field_id,
            '',
            // Field title
            [$render_field, 'render_description_subfield'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name'       => ASENHA_SLUG_U,
                'field_description' => '<div class="asenha-warning"><strong>' . __( 'Please clear your cache</strong> after enabling or disabling maintenance mode. This ensures site visitors see either the maintenance page or the actual content of each page.', 'admin-site-enhancements' ) . '</div>',
                'class'             => 'asenha-description utilities ' . $field_slug,
            )
        );
        // Redirect 404 to Homepage
        $field_id = 'redirect_404_to_homepage';
        $field_slug = 'redirect-404-to-homepage';
        $module_title = __( 'Redirect 404 to Homepage', 'admin-site-enhancements' );
        $module_description = __( 'Perform 301 (permanent) redirect to the homepage for all 404 (not found) pages.', 'admin-site-enhancements' );
        $field_options_wrapper = false;
        $field_options_moreless = false;
        add_settings_field(
            $field_id,
            $module_title,
            // Field title
            [$render_field, 'render_checkbox_toggle'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name'            => ASENHA_SLUG_U,
                'field_id'               => $field_id,
                'field_slug'             => $field_slug,
                'field_name'             => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_description'      => $module_description,
                'field_options_wrapper'  => $field_options_wrapper,
                'field_options_moreless' => $field_options_moreless,
                'class'                  => 'asenha-toggle utilities ' . $field_slug,
            )
        );
        // Display System Summary
        $field_id = 'display_system_summary';
        $field_slug = 'display-system-summary';
        $module_description = __( 'Show quick summary of the system the site is running on to admins, in the "At a Glance" dashboard widget. This includes the web server software, the PHP version, the database software and server IP address.', 'admin-site-enhancements' );
        add_settings_field(
            $field_id,
            __( 'Display System Summary', 'admin-site-enhancements' ),
            // Field title
            [$render_field, 'render_checkbox_toggle'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name'           => ASENHA_SLUG_U,
                'field_id'              => $field_id,
                'field_slug'            => $field_slug,
                'field_name'            => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_description'     => $module_description,
                'field_options_wrapper' => true,
                'class'                 => 'asenha-toggle utilities ' . $field_slug,
            )
        );
        // Search Engines Visibility Status
        $field_id = 'search_engine_visibility_status';
        $field_slug = 'search-engine-visibility-status';
        $field_options_moreless = false;
        $field_options_wrapper = false;
        add_settings_field(
            $field_id,
            __( 'Search Engines Visibility Status', 'admin-site-enhancements' ),
            // Field title
            [$render_field, 'render_checkbox_toggle'],
            ASENHA_SLUG,
            'main-section',
            array(
                'option_name'            => ASENHA_SLUG_U,
                'field_id'               => $field_id,
                'field_slug'             => $field_slug,
                'field_name'             => ASENHA_SLUG_U . '[' . $field_id . ']',
                'field_description'      => __( 'Show admin bar status when search engines are set to be discouraged from indexing the site. This is set through a "Search engine visibility" checkbox in Settings >> Reading.', 'admin-site-enhancements' ),
                'field_options_moreless' => $field_options_moreless,
                'field_options_wrapper'  => $field_options_wrapper,
                'class'                  => 'asenha-toggle utilities ' . $field_slug,
            )
        );
    }

}
