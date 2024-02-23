<?php

namespace ASENHA\Classes;

use  WP_Error ;
/**
 * Class related to Utilities features
 *
 * @since 1.5.0
 */
class Utilities
{
    /**
     * Get user ID from $id_or_email
     * 
     * @since 6.4.1
     */
    public function get_user_id_from_idoremail( $id_or_email )
    {
        $user_id = false;
        // Get user ID, if is numeric
        
        if ( is_numeric( $id_or_email ) ) {
            $user_id = (int) $id_or_email;
            // If is string, maybe the user email
        } elseif ( is_string( $id_or_email ) ) {
            // Find user by email
            $user = get_user_by( 'email', $id_or_email );
            if ( is_object( $user ) ) {
                if ( property_exists( $user, 'ID' ) || is_numeric( $user->ID ) ) {
                    $user_id = (int) $user->ID;
                }
            }
            // If is an object
        } elseif ( is_object( $id_or_email ) ) {
            // If is an ID
            
            if ( property_exists( $id_or_email, 'ID' ) && is_numeric( $id_or_email->ID ) ) {
                $user_id = (int) $id_or_email->ID;
                // If this is a Comment Object
            } elseif ( property_exists( $id_or_email, 'comment_author_email' ) ) {
                $user = get_user_by( 'email', $id_or_email->comment_author_email );
                if ( is_object( $user ) ) {
                    if ( property_exists( $user, 'ID' ) || is_numeric( $user->ID ) ) {
                        $user_id = (int) $user->ID;
                    }
                }
            } else {
            }
        
        }
        
        
        if ( is_numeric( $user_id ) ) {
            return $user_id;
        } else {
            return false;
        }
    
    }
    
    /**
     * Add UI to enable multiple user roles selection
     *
     * @since 4.8.0
     */
    public function add_multiple_roles_ui( $user )
    {
        // Get user roles that the current user is allowed to edit
        $roles = get_editable_roles();
        // Get the roles of the user being shown / edited / created
        
        if ( !empty($user->roles) ) {
            $user_roles = array_intersect( array_values( $user->roles ), array_keys( $roles ) );
            // indexed array of role slugs
        } else {
            $user_roles = array();
        }
        
        // Only show roles checkboxes for users that can assign roles to other users
        
        if ( current_user_can( 'promote_users', get_current_user_id() ) ) {
            ?>
			<div class="asenha-roles-temporary-container">
				<table class="form-table">
					<tr>
						<th>
							<label>Roles</label>
						</th>
						<td>
							<?php 
            foreach ( $roles as $role_slug => $role_info ) {
                $checkbox_id = $role_slug . '_role';
                $role_name = translate_user_role( $role_info['name'] );
                
                if ( !empty($user_roles) && in_array( $role_slug, $user_roles ) ) {
                    $checked = 'checked="checked"';
                } else {
                    $checked = '';
                }
                
                // Output roles checkboxes
                ?>
								<label for="<?php 
                esc_attr_e( $checkbox_id );
                ?>"><input type="checkbox" id="<?php 
                esc_attr_e( $checkbox_id );
                ?>" value="<?php 
                esc_attr_e( $role_slug );
                ?>" name="asenha_assigned_roles[]" <?php 
                esc_attr_e( $checked );
                ?> /> <?php 
                esc_html_e( $role_name );
                ?></label><br />
								<?php 
            }
            wp_nonce_field( 'asenha_set_multiple_roles', 'asenha_multiple_roles_nonce' );
            ?>
						</td>
					</tr>
				</table>
			</div>
			<?php 
        }
    
    }
    
    /**
     * Save changes in roles assignment
     *
     * @since 4.8.0
     */
    public function save_roles_assignment( $user_id )
    {
        if ( !current_user_can( 'promote_users', get_current_user_id() ) || !wp_verify_nonce( $_POST['asenha_multiple_roles_nonce'], 'asenha_set_multiple_roles' ) ) {
            return;
        }
        // Get user roles that the current user is allowed to edit
        $roles = get_editable_roles();
        // Get the roles of the user being shown / edited / created
        $user = get_user_by( 'id', (int) $user_id );
        // WP_User object
        $user_roles = array_intersect( array_values( $user->roles ), array_keys( $roles ) );
        // Current/existing roles
        
        if ( !empty($_POST['asenha_assigned_roles']) ) {
            $assigned_roles = array_map( 'sanitize_text_field', $_POST['asenha_assigned_roles'] );
            // Make sure only valid roles are processed
            $assigned_roles = array_intersect( $assigned_roles, array_keys( $roles ) );
            $roles_to_remove = array();
            $roles_to_add = array();
            
            if ( empty($assigned_roles) ) {
                // Remove all current/existing roles
                $roles_to_remove = $user_roles;
            } else {
                // Identify and remove roles not present in the newly assigned roles
                $roles_to_remove = array_diff( $user_roles, $assigned_roles );
                if ( !empty($roles_to_remove) ) {
                    foreach ( $roles_to_remove as $role_to_remove ) {
                        $user->remove_role( $role_to_remove );
                    }
                }
                // Identify and add roles not present in the existing roles
                $roles_to_add = array_diff( $assigned_roles, $user_roles );
                if ( !empty($roles_to_add) ) {
                    foreach ( $roles_to_add as $role_to_add ) {
                        $user->add_role( $role_to_add );
                    }
                }
            }
        
        }
    
    }
    
    /**
     * Add image sizes meta box in image view/edit screen
     * 
     * @since 6.3.0
     */
    public function add_image_sizes_meta_box()
    {
        global  $post ;
        // Only add meta box if the attachment is an image
        if ( is_object( $post ) && property_exists( $post, 'post_mime_type' ) && false !== strpos( $post->post_mime_type, 'image' ) ) {
            add_meta_box(
                'image_sizes',
                'Image Sizes',
                [ $this, 'image_sizes_table' ],
                'attachment',
                'side'
            );
        }
    }
    
    /**
     * Output table of image sizes
     * 
     * @link https://plugins.trac.wordpress.org/browser/image-sizes-panel/tags/0.4/admin/admin.php
     * @since 6.3.0
     */
    public function image_sizes_table( $post )
    {
        global  $_wp_additional_image_sizes ;
        $image_sizes = get_intermediate_image_sizes();
        $metadata = wp_get_attachment_metadata( $post->ID );
        $generated_sizes = array();
        // Merge defined image sizes with generated image sizes
        
        if ( isset( $metadata['sizes'] ) && count( $metadata['sizes'] ) > 0 ) {
            $generated_sizes = array_keys( $metadata['sizes'] );
            $image_sizes = array_unique( array_merge( $image_sizes, $generated_sizes ) );
        }
        
        $image_sizes[] = 'full';
        $full = wp_get_attachment_image_src( $post->ID, 'full' );
        sort( $image_sizes );
        
        if ( count( $image_sizes ) > 0 ) {
            echo  '<table>' ;
            foreach ( $image_sizes as $size ) {
                $src = wp_get_attachment_image_src( $post->ID, $size );
                
                if ( isset( $metadata['sizes'][$size] ) ) {
                    $width = $metadata['sizes'][$size]['width'];
                    $height = $metadata['sizes'][$size]['height'];
                } else {
                    
                    if ( 'full' == $size ) {
                        $width = $full[1];
                        $height = $full[2];
                    } else {
                        $width = $src[1];
                        $height = $src[2];
                    }
                
                }
                
                
                if ( in_array( $size, $generated_sizes ) || 'full' == $size ) {
                    echo  '<tr id="image-sizes-panel-' . sanitize_html_class( $size ) . '" class="image-size-row">' ;
                    echo  '<td class="size"><span class="name"><a href="' . $src[0] . '" target="_blank" class="image-url">' . $size . '</a></span></td>' ;
                    echo  '<td class="dim">' . $width . ' &times ' . $height . '</td>' ;
                    echo  '</tr>' ;
                }
            
            }
            echo  '</table>' ;
        } else {
            echo  '<p>No image sizes</p>' ;
        }
    
    }
    
    /**
     * Add menu bar item to view admin as one of the user roles
     *
     * @param $wp_admin_bar The WP_Admin_Bar instance
     * @link https://developer.wordpress.org/reference/hooks/admin_bar_menu/
     * @link https://developer.wordpress.org/reference/classes/wp_admin_bar/
     * @since 1.8.0
     */
    public function view_admin_as_admin_bar_menu( $wp_admin_bar )
    {
        $options = get_option( ASENHA_SLUG_U, array() );
        $usernames = ( isset( $options['viewing_admin_as_role_are'] ) ? $options['viewing_admin_as_role_are'] : array() );
        $current_user = wp_get_current_user();
        $current_user_roles = array_values( $current_user->roles );
        // indexed array
        $current_user_username = $current_user->user_login;
        // Get which role slug is currently set to "View as"
        $viewing_admin_as = get_user_meta( get_current_user_id(), '_asenha_viewing_admin_as', true );
        if ( empty($viewing_admin_as) ) {
            update_user_meta( get_current_user_id(), '_asenha_viewing_admin_as', 'administrator' );
        }
        // Get the role name, translated if available, from the role slug
        $wp_roles = wp_roles()->roles;
        foreach ( $wp_roles as $wp_role_slug => $wp_role_info ) {
            if ( $wp_role_slug == $viewing_admin_as ) {
                $viewing_admin_as_role_name = $wp_role_info['name'];
            }
        }
        if ( !isset( $viewing_admin_as_role_name ) ) {
            $viewing_admin_as_role_name = $viewing_admin_as;
        }
        $translated_name_for_viewing_admin_as = ucfirst( $viewing_admin_as_role_name );
        // Add parent menu based on the role being set to "View as"
        
        if ( 'administrator' == $viewing_admin_as ) {
            if ( in_array( 'administrator', $current_user_roles ) ) {
                // Add parent menu for administrators
                $wp_admin_bar->add_menu( array(
                    'id'     => 'asenha-view-admin-as-role',
                    'parent' => 'top-secondary',
                    'title'  => 'View as <span style="font-size:0.8125em;">&#9660;</span>',
                    'href'   => '#',
                    'meta'   => array(
                    'title' => 'View admin pages and the site (logged-in) as one of the following user roles.',
                ),
                ) );
            }
        } else {
            // Limit to users performing role switching only. i.e. Don't show role switcher to regularly logging in users.
            if ( in_array( $current_user_username, $usernames ) ) {
                // Add parent menu
                $wp_admin_bar->add_menu( array(
                    'id'     => 'asenha-view-admin-as-role',
                    'parent' => 'top-secondary',
                    'title'  => 'Viewing as ' . $translated_name_for_viewing_admin_as . ' <span style="font-size:0.8125em;">&#9660;</span>',
                    'href'   => '#',
                ) );
            }
        }
        
        // Get available role(s) to switch to
        $roles_to_switch_to = $this->get_roles_to_switch_to();
        // Add role(s) to switch to as sub-menu
        
        if ( 'administrator' == $viewing_admin_as ) {
            
            if ( in_array( 'administrator', $current_user_roles ) ) {
                // Add submenu for each role other than Administrator
                $i = 1;
                foreach ( $roles_to_switch_to as $role_slug => $data ) {
                    $wp_admin_bar->add_menu( array(
                        'id'     => 'role' . $i . '_' . $role_slug,
                        'parent' => 'asenha-view-admin-as-role',
                        'title'  => $data['role_name'],
                        'href'   => $data['nonce_url'],
                    ) );
                    $i++;
                }
            }
        
        } else {
            // Add submenu to switch back to Administrator role
            // Limit to users performing role switching only. i.e. Don't show role switcher to regularly logging in users.
            if ( in_array( $current_user_username, $usernames ) ) {
                foreach ( $roles_to_switch_to as $role_slug => $data ) {
                    $wp_admin_bar->add_menu( array(
                        'id'     => 'role_' . $role_slug,
                        'parent' => 'asenha-view-admin-as-role',
                        'title'  => 'Switch back to ' . $data['role_name'],
                        'href'   => $data['nonce_url'],
                    ) );
                }
            }
        }
    
    }
    
    /** 
     * Get roles availble to switch to
     *
     * @since 1.8.0
     */
    private function get_roles_to_switch_to()
    {
        $current_user = wp_get_current_user();
        $current_user_role_slugs = $current_user->roles;
        // indexed array of current user role slug(s)
        // Get full list of roles defined in WordPress
        $wp_roles = wp_roles()->roles;
        $roles_to_switch_to = array();
        // Get which role slug is currently active for viewing
        $viewing_admin_as = get_user_meta( get_current_user_id(), '_asenha_viewing_admin_as', true );
        
        if ( 'administrator' == $viewing_admin_as ) {
            // Exclude 'Administrator' from the "View as" menu
            foreach ( $wp_roles as $wp_role_slug => $wp_role_info ) {
                if ( !in_array( $wp_role_slug, $current_user_role_slugs ) ) {
                    $roles_to_switch_to[$wp_role_slug] = array(
                        'role_name' => $wp_role_info['name'],
                        'nonce_url' => wp_nonce_url(
                        add_query_arg( array(
                            'action' => 'switch_role_to',
                            'role'   => $wp_role_slug,
                        ) ),
                        // add query parameters to current URl, this is the $actionurl that will be appended with the nonce action
                        'asenha_view_admin_as_' . $wp_role_slug,
                        // the nonce $action name
                        'nonce'
                    ),
                    );
                }
            }
        } else {
            // Only show switch back to Administrator in the "View as" menu
            $roles_to_switch_to['administrator'] = array(
                'role_name' => 'Administrator',
                'nonce_url' => wp_nonce_url(
                add_query_arg( array(
                    'action' => 'switch_back_to_administrator',
                    'role'   => 'administrator',
                ) ),
                // add query parameters to current URl, this is the $actionurl that will be appended with the nonce action
                'asenha_view_admin_as_administrator',
                // the nonce $action name
                'nonce'
            ),
            );
        }
        
        return $roles_to_switch_to;
        // array of $role_slug => $nonce_url
    }
    
    /**
     * Switch user role to view admin and site
     *
     * @since 1.8.0
     */
    public function role_switcher_to_view_admin_as()
    {
        $current_user = wp_get_current_user();
        $current_user_role_slugs = $current_user->roles;
        // indexed array of current user role slug(s)
        $current_user_username = $current_user->user_login;
        $options = get_option( ASENHA_SLUG_U, array() );
        $options['viewing_admin_as_role_are'] = array();
        
        if ( isset( $_REQUEST['action'] ) && isset( $_REQUEST['role'] ) && isset( $_REQUEST['nonce'] ) ) {
            $action = sanitize_text_field( $_REQUEST['action'] );
            $new_role = sanitize_text_field( $_REQUEST['role'] );
            $nonce = sanitize_text_field( $_REQUEST['nonce'] );
            
            if ( 'switch_role_to' === $action ) {
                // Check nonce validity and role existence
                $wp_roles = array_keys( wp_roles()->roles );
                // indexed array of all WP roles
                
                if ( !wp_verify_nonce( $nonce, 'asenha_view_admin_as_' . $new_role ) || !in_array( $new_role, $wp_roles ) ) {
                    return;
                    // cancel role switching
                }
                
                // Get original roles (before role switching) of the current user
                $original_role_slugs = get_user_meta( get_current_user_id(), '_asenha_view_admin_as_original_roles', true );
                // Store original user role(s) before switching it to another role
                if ( empty($original_role_slugs) ) {
                    update_user_meta( get_current_user_id(), '_asenha_view_admin_as_original_roles', $current_user_role_slugs );
                }
                // Store current user's username in options
                $options['viewing_admin_as_role_are'][] = $current_user_username;
                update_option( ASENHA_SLUG_U, $options );
                // Remove all current roles from current user.
                foreach ( $current_user_role_slugs as $current_user_role_slug ) {
                    $current_user->remove_role( $current_user_role_slug );
                }
                // Add new role to current user
                $current_user->add_role( $new_role );
                // Mark that the user has switched to a non-administrator role
                update_user_meta( get_current_user_id(), '_asenha_viewing_admin_as', $new_role );
                // if ( ! in_array( $new_role, array( 'administrator', 'editor', 'author', 'contributor' ) ) ) {
                // Redirect to profile edit page
                // wp_safe_redirect( get_edit_profile_url() );
                // } else {
                // Redirect to admin dashboard
                wp_safe_redirect( get_admin_url() );
                // }
                exit;
            }
            
            
            if ( 'switch_back_to_administrator' === $action ) {
                // Check nonce validity
                
                if ( !wp_verify_nonce( $nonce, 'asenha_view_admin_as_administrator' ) || $new_role != 'administrator' ) {
                    return;
                    // cancel role switching
                }
                
                // Remove all current roles from current user.
                foreach ( $current_user_role_slugs as $current_role_slug ) {
                    $current_user->remove_role( $current_role_slug );
                }
                // Get original roles (before role switching) of the current user
                $original_role_slugs = get_user_meta( get_current_user_id(), '_asenha_view_admin_as_original_roles', true );
                // Add the original roles to the current user
                foreach ( $original_role_slugs as $original_role_slug ) {
                    $current_user->add_role( $original_role_slug );
                }
                // Remove current user's username from stored usernames.
                $usernames = $options['viewing_admin_as_role_are'];
                foreach ( $usernames as $key => $username ) {
                    if ( $current_user_username == $username ) {
                        unset( $usernames[$key] );
                    }
                }
                $options['viewing_admin_as_role_are'] = $usernames;
                update_option( ASENHA_SLUG_U, $options );
                // Mark that the user has switched back to an administrator role
                update_user_meta( get_current_user_id(), '_asenha_viewing_admin_as', 'administrator' );
            }
        
        } elseif ( isset( $_REQUEST['reset-for'] ) ) {
            $reset_for_username = sanitize_text_field( $_REQUEST['reset-for'] );
            $options = get_option( ASENHA_SLUG_U, array() );
            $usernames = $options['viewing_admin_as_role_are'];
            if ( !empty($reset_for_username) ) {
                
                if ( in_array( $reset_for_username, $usernames ) ) {
                    $current_user = get_user_by( 'login', $reset_for_username );
                    $current_user_role_slugs = $current_user->roles;
                    // indexed array of current user role slug(s)
                    // Remove all current roles from current user.
                    foreach ( $current_user_role_slugs as $current_role_slug ) {
                        $current_user->remove_role( $current_role_slug );
                    }
                    // Get original roles (before role switching) of the current user
                    $original_role_slugs = get_user_meta( $current_user->ID, '_asenha_view_admin_as_original_roles', true );
                    // Add the original roles to the current user
                    foreach ( $original_role_slugs as $original_role_slug ) {
                        $current_user->add_role( $original_role_slug );
                    }
                    // Mark that the user has switched back to an administrator role
                    update_user_meta( $current_user->ID, '_asenha_viewing_admin_as', 'administrator' );
                    // Remove current user's username from stored usernames.
                    foreach ( $usernames as $key => $username ) {
                        if ( $reset_for_username == $username ) {
                            unset( $usernames[$key] );
                        }
                    }
                    $options['viewing_admin_as_role_are'] = $usernames;
                    update_option( ASENHA_SLUG_U, $options );
                    // Redirect to login URL, including when custom login slug is set and active
                    
                    if ( array_key_exists( 'change_login_url', $options ) && $options['change_login_url'] ) {
                        if ( array_key_exists( 'custom_login_slug', $options ) && !empty($options['custom_login_slug']) ) {
                            $login_url = get_site_url( null, $options['custom_login_slug'] );
                        }
                    } else {
                        $login_url = wp_login_url();
                    }
                    
                    // Redirect to admin dashboard
                    // wp_safe_redirect( $login_url );
                    // exit;
                    // Use JS redirect, which works more reliably on the frontend
                    ?>
					<script>
						window.location.href='<?php 
                    echo  $login_url ;
                    ?>';
					</script>
					<?php 
                }
            
            }
        }
    
    }
    
    /**
     * Add floating button to reset the view/account back to the administrator
     * 
     * @since 6.1.3
     */
    public function add_floating_reset_button()
    {
        $options = get_option( ASENHA_SLUG_U, array() );
        $admin_usernames_viewing_as_role = ( isset( $options['viewing_admin_as_role_are'] ) ? $options['viewing_admin_as_role_are'] : array() );
        $current_user = wp_get_current_user();
        $username = $current_user->user_login;
        // Show for non-admins
        
        if ( !current_user_can( 'manage_options' ) && in_array( $username, $admin_usernames_viewing_as_role ) ) {
            ?>
			<div id="role-view-reset">
				<a href="<?php 
            echo  get_site_url() ;
            ?>/?reset-for=<?php 
            echo  $username ;
            ?>" class="button button-primary">Switch back to Administrator</a>
			</div>
			<?php 
        }
    
    }
    
    /**
     * Show custom error page on switch failure, which causes inability to view admin dashboard/pages
     *
     * @since 1.8.0
     */
    public function custom_error_page_on_switch_failure( $callback )
    {
        ?>
<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8; ?>" />
	<meta name="viewport" content="width=device-width">
	<title>WordPress Error</title>
	<style type="text/css">
		html {
			background: #f1f1f1;
		}
		body {
			background: #fff;
			border: 1px solid #ccd0d4;
			color: #444;
			font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
			margin: 2em auto;
			padding: 1em 2em;
			max-width: 700px;
			-webkit-box-shadow: 0 1px 1px rgba(0, 0, 0, .04);
			box-shadow: 0 1px 1px rgba(0, 0, 0, .04);
		}
		h1 {
			border-bottom: 1px solid #dadada;
			clear: both;
			color: #666;
			font-size: 24px;
			margin: 30px 0 0 0;
			padding: 0;
			padding-bottom: 7px;
		}
		#error-page {
			margin-top: 50px;
		}
		#error-page p,
		#error-page .wp-die-message {
			font-size: 14px;
			line-height: 1.5;
			margin: 20px 0;
		}
		#error-page code {
			font-family: Consolas, Monaco, monospace;
		}
		a {
			color: #0073aa;
		}
		a:hover,
		a:active {
			color: #006799;
		}
		a:focus {
			color: #124964;
			-webkit-box-shadow:
				0 0 0 1px #5b9dd9,
				0 0 2px 1px rgba(30, 140, 190, 0.8);
			box-shadow:
				0 0 0 1px #5b9dd9,
				0 0 2px 1px rgba(30, 140, 190, 0.8);
			outline: none;
		}
	</style>
</head>
<body id="error-page">
	<div class="wp-die-message">Something went wrong. Please try logging in.</div>
</body>
</html>
		<?php 
    }
    
    /**
     * Show Password Protection admin bar status icon
     *
     * @since 4.1.0
     */
    public function show_password_protection_admin_bar_icon()
    {
        add_action( 'wp_before_admin_bar_render', [ $this, 'add_password_protection_admin_bar_item' ] );
        add_action( 'admin_head', [ $this, 'add_password_protection_admin_bar_item_styles' ] );
        add_action( 'wp_head', [ $this, 'add_password_protection_admin_bar_item_styles' ] );
    }
    
    /**
     * Add WP Admin Bar item
     *
     * @since 4.1.0
     */
    public function add_password_protection_admin_bar_item()
    {
        global  $wp_admin_bar ;
        if ( is_user_logged_in() ) {
            if ( current_user_can( 'manage_options' ) ) {
                $wp_admin_bar->add_menu( array(
                    'id'    => 'password_protection',
                    'title' => '',
                    'href'  => admin_url( 'tools.php?page=admin-site-enhancements#utilities' ),
                    'meta'  => array(
                    'title' => 'Password protection is currently enabled for this site.',
                ),
                ) );
            }
        }
    }
    
    /**
     * Add icon and CSS for admin bar item
     *
     * @since 4.1.0
     */
    public function add_password_protection_admin_bar_item_styles()
    {
        if ( is_user_logged_in() ) {
            if ( current_user_can( 'manage_options' ) ) {
                ?>
				<style>
					#wp-admin-bar-password_protection { 
						background-color: #c32121 !important;
						transition: .25s;
					}
					#wp-admin-bar-password_protection > .ab-item { 
						color: #fff !important;  
					}
					#wp-admin-bar-password_protection > .ab-item:before { 
						content: "\f160"; 
						top: 2px; 
						color: #fff !important; 
						margin-right: 0px; 
					}
					#wp-admin-bar-password_protection:hover > .ab-item { 
						background-color: #af1d1d !important; 
						color: #fff; 
					}
				</style>
				<?php 
            }
        }
    }
    
    /**
     * Disable page caching
     *
     * @since 4.1.0
     */
    public function maybe_disable_page_caching()
    {
        if ( !defined( 'DONOTCACHEPAGE' ) ) {
            define( 'DONOTCACHEPAGE', true );
        }
    }
    
    /**
     * Maybe show login form
     *
     * @since 4.1.0
     */
    public function maybe_show_login_form()
    {
        // When user is logged-in as in an administrator
        if ( is_user_logged_in() ) {
            
            if ( current_user_can( 'manage_options' ) ) {
                return;
                // Do not load login form or perform redirection to the login form
            }
        
        }
        // When site visitor has entered correct password, get the auth cookie
        $auth_cookie = ( isset( $_COOKIE['asenha_password_protection'] ) ? $_COOKIE['asenha_password_protection'] : '' );
        // Compared against random string set in maybe_process_login()
        
        if ( wp_check_password( 'MOeldTVhGnL18VfbDtXM7znSYXIUQn3z', $auth_cookie ) ) {
            return;
            // Do not load login form or perform redirection to the login form
        }
        
        
        if ( isset( $_REQUEST['protected-page'] ) && 'view' == $_REQUEST['protected-page'] ) {
            // Show login form
            $password_protected_login_page_template = ASENHA_PATH . 'includes/password-protected-login.php';
            load_template( $password_protected_login_page_template );
            exit;
        } else {
            // Redirect to login form
            $current_url = (( is_ssl() ? 'https://' : 'http://' )) . sanitize_text_field( $_SERVER['HTTP_HOST'] ) . sanitize_text_field( $_SERVER['REQUEST_URI'] );
            $args = array(
                'protected-page' => 'view',
                'source'         => urlencode( $current_url ),
            );
            $pwd_protect_login_url = add_query_arg( $args, home_url() );
            nocache_headers();
            wp_safe_redirect( $pwd_protect_login_url );
            exit;
        }
    
    }
    
    /**
     * Maybe process login to access protected page content
     *
     * @since 4.1.0
     */
    public function maybe_process_login()
    {
        global  $password_protected_errors ;
        $password_protected_errors = new WP_Error();
        
        if ( isset( $_REQUEST['protected_page_pwd'] ) ) {
            $password_input = $_REQUEST['protected_page_pwd'];
            $options = get_option( ASENHA_SLUG_U, array() );
            $stored_password = $options['password_protection_password'];
            
            if ( !empty($password_input) ) {
                
                if ( $password_input == $stored_password ) {
                    // Password is correct
                    // Set auth cookie
                    // $expiration = time() + DAY_IN_SECONDS; // in 24 hours
                    $expiration = 0;
                    // by the end of browsing session
                    $hashed_cookie_value = wp_hash_password( 'MOeldTVhGnL18VfbDtXM7znSYXIUQn3z' );
                    // random string
                    setcookie(
                        'asenha_password_protection',
                        $hashed_cookie_value,
                        $expiration,
                        COOKIEPATH,
                        COOKIE_DOMAIN,
                        false,
                        true
                    );
                    // Redirect
                    $redirect_to_url = ( isset( $_REQUEST['source'] ) ? $_REQUEST['source'] : '' );
                    wp_safe_redirect( $redirect_to_url );
                    exit;
                } else {
                    // Password is incorrect
                    // Add error message
                    $password_protected_errors->add( 'incorrect_password', 'Incorrect password.' );
                }
            
            } else {
                // Password input is empty
                // Add error message
                $password_protected_errors->add( 'empty_password', 'Password can not be empty.' );
            }
        
        }
    
    }
    
    /**
     * Add custom login error messages
     *
     * @since 4.1.0
     */
    public function add_login_error_messages()
    {
        global  $password_protected_errors ;
        
        if ( $password_protected_errors->get_error_code() ) {
            $messages = '';
            $errors = '';
            // Extract the error message
            foreach ( $password_protected_errors->get_error_codes() as $code ) {
                $severity = $password_protected_errors->get_error_data( $code );
                foreach ( $password_protected_errors->get_error_messages( $code ) as $error ) {
                    
                    if ( 'message' == $severity ) {
                        $messages .= $error . '<br />';
                    } else {
                        $errors .= $error . '<br />';
                    }
                
                }
            }
            // Output the error message
            if ( !empty($messages) ) {
                echo  '<p class="message">' . $messages . '</p>' ;
            }
            if ( !empty($errors) ) {
                echo  '<div id="login_error">' . $errors . '</div>' ;
            }
        }
    
    }
    
    /**
     * Redirect 404 to homepage
     *
     * @since 1.7.0
     */
    public function redirect_404_to_homepage()
    {
        
        if ( !is_404() || is_admin() || defined( 'DOING_CRON' ) && DOING_CRON || defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) {
            return;
        } else {
            // wp_safe_redirect( home_url(), 301 );
            header( 'HTTP/1.1 301 Moved Permanently' );
            header( 'Location: ' . home_url() );
            exit;
        }
    
    }
    
    /**
     * Send emails using external SMTP service
     *
     * @since 4.6.0
     */
    public function deliver_email_via_smtp( $phpmailer )
    {
        $options = get_option( ASENHA_SLUG_U, array() );
        $smtp_host = $options['smtp_host'];
        $smtp_port = $options['smtp_port'];
        $smtp_security = $options['smtp_security'];
        $smtp_username = $options['smtp_username'];
        $smtp_password = $options['smtp_password'];
        $smtp_default_from_name = $options['smtp_default_from_name'];
        $smtp_default_from_email = $options['smtp_default_from_email'];
        $smtp_force_from = $options['smtp_force_from'];
        $smtp_bypass_ssl_verification = $options['smtp_bypass_ssl_verification'];
        $smtp_debug = $options['smtp_debug'];
        // Do nothing if host or password is empty
        // if ( empty( $smtp_host ) || empty( $smtp_password ) ) {
        // 	return;
        // }
        // Maybe override FROM email and/or name if the sender is "WordPress <wordpress@sitedomain.com>", the default from WordPress core and not yet overridden by another plugin.
        $from_name = $phpmailer->FromName;
        $from_email_beginning = substr( $phpmailer->From, 0, 9 );
        // Get the first 9 characters of the current FROM email
        
        if ( $smtp_force_from ) {
            $phpmailer->FromName = $smtp_default_from_name;
            $phpmailer->From = $smtp_default_from_email;
        } else {
            if ( 'WordPress' === $from_name && !empty($smtp_default_from_name) ) {
                $phpmailer->FromName = $smtp_default_from_name;
            }
            if ( 'wordpress' === $from_email_beginning && !empty($smtp_default_from_email) ) {
                $phpmailer->From = $smtp_default_from_email;
            }
        }
        
        // Only attempt to send via SMTP if all the required info is present. Otherwise, use default PHP Mailer settings as set by wp_mail()
        
        if ( !empty($smtp_host) && !empty($smtp_port) && !empty($smtp_security) && !empty($smtp_username) && !empty($smtp_password) ) {
            // Send using SMTP
            $phpmailer->isSMTP();
            // phpcs:ignore
            // Enanble SMTP authentication
            $phpmailer->SMTPAuth = true;
            // phpcs:ignore
            // Set some other defaults
            // $phpmailer->CharSet 	= 'utf-8'; // phpcs:ignore
            $phpmailer->XMailer = 'Admin and Site Enhancements v' . ASENHA_VERSION . ' - a WordPress plugin';
            // phpcs:ignore
            $phpmailer->Host = $smtp_host;
            // phpcs:ignore
            $phpmailer->Port = $smtp_port;
            // phpcs:ignore
            $phpmailer->SMTPSecure = $smtp_security;
            // phpcs:ignore
            $phpmailer->Username = trim( $smtp_username );
            // phpcs:ignore
            $phpmailer->Password = trim( $smtp_password );
            // phpcs:ignore
        }
        
        // If verification of SSL certificate is bypassed
        // Reference: https://www.php.net/manual/en/context.ssl.php & https://stackoverflow.com/a/30803024
        if ( $smtp_bypass_ssl_verification ) {
            $phpmailer->SMTPOptions = [
                'ssl' => [
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => true,
            ],
            ];
        }
        // If debug mode is enabled, send debug info (SMTP::DEBUG_CONNECTION) to WordPress debug.log file set in wp-config.php
        // Reference: https://github.com/PHPMailer/PHPMailer/wiki/SMTP-Debugging
        
        if ( $smtp_debug ) {
            $phpmailer->SMTPDebug = 4;
            //phpcs:ignore
            $phpmailer->Debugoutput = 'error_log';
            //phpcs:ignore
        }
    
    }
    
    /**
     * Send a test email and use SMTP host if defined in settings
     * 
     * @since 5.3.0
     */
    public function send_test_email()
    {
        
        if ( isset( $_REQUEST ) ) {
            $content = array(
                array(
                'title' => 'Hey... are you getting this?',
                'body'  => '<p><strong>Looks like you did!</strong></p>',
            ),
                array(
                'title' => 'There\'s a message for you...',
                'body'  => '<p><strong>Here it is:</strong></p>',
            ),
                array(
                'title' => 'Is it working?',
                'body'  => '<p><strong>Yes, it\'s working!</strong></p>',
            ),
                array(
                'title' => 'Hope you\'re getting this...',
                'body'  => '<p><strong>Looks like this was sent out just fine and you got it.</strong></p>',
            ),
                array(
                'title' => 'Testing delivery configuration...',
                'body'  => '<p><strong>Everything looks good!</strong></p>',
            ),
                array(
                'title' => 'Testing email delivery',
                'body'  => '<p><strong>Looks good!</strong></p>',
            ),
                array(
                'title' => 'Config is looking good',
                'body'  => '<p><strong>Seems like everything has been set up properly!</strong></p>',
            ),
                array(
                'title' => 'All set up',
                'body'  => '<p><strong>Your configuration is working properly.</strong></p>',
            ),
                array(
                'title' => 'Good to go',
                'body'  => '<p><strong>Config is working great.</strong></p>',
            ),
                array(
                'title' => 'Good job',
                'body'  => '<p><strong>Everything is set.</strong></p>',
            )
            );
            $random_number = rand( 0, count( $content ) - 1 );
            $to = $_REQUEST['email_to'];
            $title = $content[$random_number]['title'];
            $body = $content[$random_number]['body'] . '<p>This message was sent from <a href="' . get_bloginfo( 'url' ) . '">' . get_bloginfo( 'url' ) . '</a> on ' . wp_date( 'F j, Y' ) . ' at ' . wp_date( 'H:i:s' ) . ' via ASE.</p>';
            $headers = array( 'Content-Type: text/html; charset=UTF-8' );
            $success = wp_mail(
                $to,
                $title,
                $body,
                $headers
            );
            
            if ( $success ) {
                $response = array(
                    'status' => 'success',
                );
            } else {
                $response = array(
                    'status' => 'failed',
                );
            }
            
            echo  json_encode( $response ) ;
        }
    
    }
    
    /**
     * Redirect for when maintenance mode is enabled
     *
     * @since 4.7.0
     */
    public function maintenance_mode_redirect()
    {
        $current_url = sanitize_text_field( $_SERVER['REQUEST_URI'] );
        $current_url_parts = explode( '/', $current_url );
        // Bypass wp-admin pages and logged-in administrator on the frontend
        if ( !in_array( 'wp-admin', $current_url_parts ) || false !== strpos( 'wp-login.php', $current_url ) ) {
            
            if ( !current_user_can( 'manage_options' ) ) {
                header( 'HTTP/1.1 503 Service Unavailable', true, 503 );
                header( 'Status: 503 Service Unavailable' );
                header( 'Retry-After: 3600' );
                // Tell search engine bots to return after 3600 seconds, i.e. 1 hour
                $options = get_option( ASENHA_SLUG_U, array() );
                $heading = $options['maintenance_page_heading'];
                $description = $options['maintenance_page_description'];
                $background = $options['maintenance_page_background'];
                
                if ( 'lines' === $background ) {
                    // https://bgjar.com/curve-line
                    $background_image = "url(\"data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' version='1.1' xmlns:xlink='http://www.w3.org/1999/xlink' xmlns:svgjs='http://svgjs.com/svgjs' width='1920' height='1280' preserveAspectRatio='none' viewBox='0 0 1920 1280'%3e%3cg mask='url(%26quot%3b%23SvgjsMask1804%26quot%3b)' fill='none'%3e%3crect width='1920' height='1280' x='0' y='0' fill='url(%23SvgjsLinearGradient1805)'%3e%3c/rect%3e%3cpath d='M2294.46 927.36C2128.65 934.22 2078.52 1270.56 1693.36 1208.96 1308.19 1147.36 1373.24 145.96 1092.25-67.11' stroke='rgba(158%2c 160%2c 161%2c 0.57)' stroke-width='2'%3e%3c/path%3e%3cpath d='M2225.25 303.97C1963.34 332.56 1808.36 909.76 1359.97 905.57 911.59 901.38 820.47-55.06 494.7-167.42' stroke='rgba(158%2c 160%2c 161%2c 0.57)' stroke-width='2'%3e%3c/path%3e%3cpath d='M2247.58 281.19C2070.08 293.95 1967.68 651 1632.53 639.59 1297.39 628.18 1265.17-143.39 1017.49-253.69' stroke='rgba(158%2c 160%2c 161%2c 0.57)' stroke-width='2'%3e%3c/path%3e%3cpath d='M1924.29 917.21C1696.21 904.78 1584.63 530.74 1114.13 494.81 643.63 458.88 546.92-26.2 303.97-50.85' stroke='rgba(158%2c 160%2c 161%2c 0.57)' stroke-width='2'%3e%3c/path%3e%3cpath d='M2009.59 400.31C1847.79 399.06 1696.02 240.31 1382.45 240.31 1068.87 240.31 1083.3 404.62 755.3 400.31 427.31 396 332.72-108.61 128.16-144.89' stroke='rgba(158%2c 160%2c 161%2c 0.57)' stroke-width='2'%3e%3c/path%3e%3c/g%3e%3cdefs%3e%3cmask id='SvgjsMask1804'%3e%3crect width='1920' height='1280' fill='white'%3e%3c/rect%3e%3c/mask%3e%3clinearGradient x1='8.33%25' y1='-12.5%25' x2='91.67%25' y2='112.5%25' gradientUnits='userSpaceOnUse' id='SvgjsLinearGradient1805'%3e%3cstop stop-color='rgba(255%2c 255%2c 255%2c 1)' offset='0'%3e%3c/stop%3e%3cstop stop-color='rgba(193%2c 192%2c 192%2c 1)' offset='1'%3e%3c/stop%3e%3c/linearGradient%3e%3c/defs%3e%3c/svg%3e\")";
                    $background_style = 'background-image: ' . $background_image;
                } elseif ( 'stripes' === $background ) {
                    // https://bgjar.com/shiny-overlay
                    $background_image = "url(\"data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' version='1.1' xmlns:xlink='http://www.w3.org/1999/xlink' xmlns:svgjs='http://svgjs.com/svgjs' width='2560' height='2560' preserveAspectRatio='none' viewBox='0 0 2560 2560'%3e%3cg mask='url(%26quot%3b%23SvgjsMask1276%26quot%3b)' fill='none'%3e%3crect width='2560' height='2560' x='0' y='0' fill='url(%23SvgjsLinearGradient1277)'%3e%3c/rect%3e%3cpath d='M0 0L524.59 0L0 986.23z' fill='rgba(255%2c 255%2c 255%2c .1)'%3e%3c/path%3e%3cpath d='M0 986.23L524.59 0L684.6500000000001 0L0 1251.4z' fill='rgba(255%2c 255%2c 255%2c .075)'%3e%3c/path%3e%3cpath d='M0 1251.4L684.6500000000001 0L1140.02 0L0 1816.94z' fill='rgba(255%2c 255%2c 255%2c .05)'%3e%3c/path%3e%3cpath d='M0 1816.94L1140.02 0L1666.1399999999999 0L0 1973.71z' fill='rgba(255%2c 255%2c 255%2c .025)'%3e%3c/path%3e%3cpath d='M2560 2560L1477.86 2560L2560 2129.39z' fill='rgba(0%2c 0%2c 0%2c .1)'%3e%3c/path%3e%3cpath d='M2560 2129.39L1477.86 2560L669.0099999999999 2560L2560 1244.5099999999998z' fill='rgba(0%2c 0%2c 0%2c .075)'%3e%3c/path%3e%3cpath d='M2560 1244.51L669.0099999999998 2560L531.5999999999998 2560L2560 928.88z' fill='rgba(0%2c 0%2c 0%2c .05)'%3e%3c/path%3e%3cpath d='M2560 928.8800000000001L531.5999999999997 2560L354.62999999999965 2560L2560 697.8700000000001z' fill='rgba(0%2c 0%2c 0%2c .025)'%3e%3c/path%3e%3c/g%3e%3cdefs%3e%3cmask id='SvgjsMask1276'%3e%3crect width='2560' height='2560' fill='white'%3e%3c/rect%3e%3c/mask%3e%3clinearGradient x1='0%25' y1='0%25' x2='100%25' y2='100%25' gradientUnits='userSpaceOnUse' id='SvgjsLinearGradient1277'%3e%3cstop stop-color='rgba(255%2c 255%2c 255%2c 1)' offset='0'%3e%3c/stop%3e%3cstop stop-color='rgba(172%2c 172%2c 172%2c 1)' offset='1'%3e%3c/stop%3e%3c/linearGradient%3e%3c/defs%3e%3c/svg%3e\")";
                    $background_style = 'background-image: ' . $background_image;
                } elseif ( 'curves' === $background ) {
                    // https://www.svgbackgrounds.com/
                    $background_image = "url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='100%25' height='100%25' viewBox='0 0 1600 800'%3E%3Cg %3E%3Cpath fill='%23e0e0e0' d='M486 705.8c-109.3-21.8-223.4-32.2-335.3-19.4C99.5 692.1 49 703 0 719.8V800h843.8c-115.9-33.2-230.8-68.1-347.6-92.2C492.8 707.1 489.4 706.5 486 705.8z'/%3E%3Cpath fill='%23e2e2e2' d='M1600 0H0v719.8c49-16.8 99.5-27.8 150.7-33.5c111.9-12.7 226-2.4 335.3 19.4c3.4 0.7 6.8 1.4 10.2 2c116.8 24 231.7 59 347.6 92.2H1600V0z'/%3E%3Cpath fill='%23e5e5e5' d='M478.4 581c3.2 0.8 6.4 1.7 9.5 2.5c196.2 52.5 388.7 133.5 593.5 176.6c174.2 36.6 349.5 29.2 518.6-10.2V0H0v574.9c52.3-17.6 106.5-27.7 161.1-30.9C268.4 537.4 375.7 554.2 478.4 581z'/%3E%3Cpath fill='%23e7e7e7' d='M0 0v429.4c55.6-18.4 113.5-27.3 171.4-27.7c102.8-0.8 203.2 22.7 299.3 54.5c3 1 5.9 2 8.9 3c183.6 62 365.7 146.1 562.4 192.1c186.7 43.7 376.3 34.4 557.9-12.6V0H0z'/%3E%3Cpath fill='%23EAEAEA' d='M181.8 259.4c98.2 6 191.9 35.2 281.3 72.1c2.8 1.1 5.5 2.3 8.3 3.4c171 71.6 342.7 158.5 531.3 207.7c198.8 51.8 403.4 40.8 597.3-14.8V0H0v283.2C59 263.6 120.6 255.7 181.8 259.4z'/%3E%3Cpath fill='%23ededed' d='M1600 0H0v136.3c62.3-20.9 127.7-27.5 192.2-19.2c93.6 12.1 180.5 47.7 263.3 89.6c2.6 1.3 5.1 2.6 7.7 3.9c158.4 81.1 319.7 170.9 500.3 223.2c210.5 61 430.8 49 636.6-16.6V0z'/%3E%3Cpath fill='%23f0f0f0' d='M454.9 86.3C600.7 177 751.6 269.3 924.1 325c208.6 67.4 431.3 60.8 637.9-5.3c12.8-4.1 25.4-8.4 38.1-12.9V0H288.1c56 21.3 108.7 50.6 159.7 82C450.2 83.4 452.5 84.9 454.9 86.3z'/%3E%3Cpath fill='%23f2f2f2' d='M1600 0H498c118.1 85.8 243.5 164.5 386.8 216.2c191.8 69.2 400 74.7 595 21.1c40.8-11.2 81.1-25.2 120.3-41.7V0z'/%3E%3Cpath fill='%23f5f5f5' d='M1397.5 154.8c47.2-10.6 93.6-25.3 138.6-43.8c21.7-8.9 43-18.8 63.9-29.5V0H643.4c62.9 41.7 129.7 78.2 202.1 107.4C1020.4 178.1 1214.2 196.1 1397.5 154.8z'/%3E%3Cpath fill='%23F8F8F8' d='M1315.3 72.4c75.3-12.6 148.9-37.1 216.8-72.4h-723C966.8 71 1144.7 101 1315.3 72.4z'/%3E%3C/g%3E%3C/svg%3E\")";
                    $background_style = 'background-image: ' . $background_image;
                } elseif ( 'image' === $background ) {
                    $background_style = 'background-image: none;';
                } elseif ( 'solid_color' === $background ) {
                    $background_style = 'background-color: #ffffff;';
                } else {
                }
                
                ?>
				<html>
					<head>
						<title>Under maintenance</title>
						<link rel="stylesheet" id="asenha-maintenance" href="<?php 
                echo  ASENHA_URL . 'assets/css/maintenance.css' ;
                ?>" media="all">
						<meta name="viewport" content="width=device-width">
						<style>
							body {
								<?php 
                echo  $background_style ;
                ?>;
								background-size: cover;
								background-position: center center;
							}
						</style>
					</head>
					<body>
						<div class="page-wrapper">
							<div class="message-box">
								<h1><?php 
                echo  $heading ;
                ?></h1>
								<div class="description"><?php 
                echo  $description ;
                ?></div>
							</div>
						</div>
					</body>
				</html>
				<?php 
                exit;
            }
        
        }
    }
    
    /**
     * Show Password Protection admin bar status icon
     *
     * @since 4.1.0
     */
    public function show_maintenance_mode_admin_bar_icon()
    {
        add_action( 'wp_before_admin_bar_render', [ $this, 'add_maintenance_mode_admin_bar_item' ] );
        add_action( 'admin_head', [ $this, 'add_maintenance_mode_admin_bar_item_styles' ] );
        add_action( 'wp_head', [ $this, 'add_maintenance_mode_admin_bar_item_styles' ] );
    }
    
    /**
     * Add WP Admin Bar item
     *
     * @since 4.1.0
     */
    public function add_maintenance_mode_admin_bar_item()
    {
        global  $wp_admin_bar ;
        if ( is_user_logged_in() ) {
            if ( current_user_can( 'manage_options' ) ) {
                $wp_admin_bar->add_menu( array(
                    'id'    => 'maintenance_mode',
                    'title' => '',
                    'href'  => admin_url( 'tools.php?page=admin-site-enhancements#utilities' ),
                    'meta'  => array(
                    'title' => 'Maintenance mode is currently enabled for this site.',
                ),
                ) );
            }
        }
    }
    
    /**
     * Add icon and CSS for admin bar item
     *
     * @since 4.1.0
     */
    public function add_maintenance_mode_admin_bar_item_styles()
    {
        if ( is_user_logged_in() ) {
            if ( current_user_can( 'manage_options' ) ) {
                ?>
				<style>
					#wp-admin-bar-maintenance_mode { 
						background-color: #ff800c !important;
						transition: .25s;
					}
					#wp-admin-bar-maintenance_mode > .ab-item { 
						color: #fff !important;  
					}
					#wp-admin-bar-maintenance_mode > .ab-item:before { 
						content: "\f308"; 
						top: 2px; 
						color: #fff !important; 
						margin-right: 0px; 
					}
					#wp-admin-bar-maintenance_mode:hover > .ab-item { 
						background-color: #e5730a !important; 
						color: #fff; 
					}
				</style>
				<?php 
            }
        }
    }
    
    /**
     * Display system summary in the "At a Glance" dashboard widget
     * 
     * @since 5.6.0
     */
    public function display_system_summary()
    {
        // When user is logged-in as in an administrator
        if ( is_user_logged_in() ) {
            
            if ( current_user_can( 'manage_options' ) ) {
                
                if ( isset( $_SERVER['SERVER_SOFTWARE'] ) ) {
                    $server_software_raw = str_replace( "/", " ", $_SERVER['SERVER_SOFTWARE'] );
                    $server_software_parts = explode( " (", $server_software_raw );
                    $server_software = ucfirst( $server_software_parts[0] );
                } else {
                    $server_software = 'Unknown';
                }
                
                $php_version = phpversion();
                // From WP core /wp-admin/includes/class-wp-debug-data.php
                global  $wpdb ;
                $db_server = $wpdb->get_var( 'SELECT VERSION()' );
                $db_server_parts = explode( ':', $db_server );
                $db_server = $db_server_parts[0];
                $db_separator = ' | ';
                $ip = 'localhost';
                
                if ( isset( $_SERVER['HTTP_X_SERVER_ADDR'] ) ) {
                    $ip = sanitize_text_field( $_SERVER['HTTP_X_SERVER_ADDR'] );
                } elseif ( isset( $_SERVER['SERVER_ADDR'] ) ) {
                    $ip = sanitize_text_field( $_SERVER['SERVER_ADDR'] );
                } else {
                }
                
                echo  '<div class="system-summary"><a href="' . admin_url( 'site-health.php?tab=debug' ) . '">System</a>: ' . esc_html( $server_software ) . ' | PHP ' . esc_html( $php_version ) . ' (' . php_sapi_name() . ')' . esc_html( $db_separator ) . esc_html( $db_server ) . ' | IP: ' . esc_html( $ip ) . '</div>' ;
            }
        
        }
    }
    
    /**
     * Display search engine visibility status indicator and notice
     * 
     * @since 6.6.0
     */
    public function maybe_display_search_engine_visibility_status()
    {
        // Check if the user is an admin
        if ( !current_user_can( 'manage_options' ) ) {
            return;
        }
        // Get the option 'blog_public' to check search engine visibility
        // If 'blog_public' is '0', it means 'Discourage search engines from indexing this site' is checked
        
        if ( get_option( 'blog_public' ) === '0' ) {
            add_action( 'admin_notices', array( $this, 'display_admin_notice_for_search_visibility' ) );
            add_action( 'admin_bar_menu', array( $this, 'add_notice_in_admin_bar' ), 100 );
        }
    
    }
    
    public function display_admin_notice_for_search_visibility()
    {
        echo  '<div class="notice notice-warning is-dismissible">' ;
        echo  '<p><strong>Search Engine Visibility is OFF</strong>. Search engines are discouraged from indexing this site. <a href="' . admin_url( 'options-reading.php' ) . '"><strong>Change the setting »</strong></a></p>' ;
        echo  '</div>' ;
    }
    
    public function add_notice_in_admin_bar( $wp_admin_bar )
    {
        $node_id = 'search_visibility_notice';
        // Add inline style for warning background color
        echo  "<style>#wpadminbar #wp-admin-bar-{$node_id} > .ab-item { background-color: #ff9a00; color: #fff; font-weight: 600; }</style>" ;
        $args = array(
            'id'     => $node_id,
            'parent' => 'top-secondary',
            'title'  => 'SE Visibility: OFF',
            'href'   => admin_url( 'options-reading.php' ),
            'meta'   => array(
            'title' => 'Search engines are discouraged from indexing this site. Click to change the settings.',
        ),
        );
        $wp_admin_bar->add_node( $args );
    }

}