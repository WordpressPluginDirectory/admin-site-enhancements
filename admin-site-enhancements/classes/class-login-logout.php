<?php

namespace ASENHA\Classes;

use  Walker_Nav_Menu_Checklist ;
use  WP_Error ;
/**
 * Class related to Log In Log Out features
 *
 * @since 3.6.0
 */
class Login_Logout
{
    /**
     * Redirect to valid login URL when custom login slug is part of the request URL
     *
     * @link https://plugins.trac.wordpress.org/browser/admin-login-url-change/trunk/admin-login-url-change.php#L134
     * @since 1.4.0
     */
    public function redirect_on_custom_login_url()
    {
        $options = get_option( ASENHA_SLUG_U );
        $custom_login_slug = $options['custom_login_slug'];
        $url_input = sanitize_text_field( $_SERVER['REQUEST_URI'] );
        // Make sure $url_input ends with /
        if ( false !== strpos( $url_input, $custom_login_slug ) ) {
            if ( substr( $url_input, -1 ) != '/' ) {
                $url_input = $url_input . '/';
            }
        }
        // If URL contains the custom login slug, redirect to the dashboard
        
        if ( false !== strpos( $url_input, '/' . $custom_login_slug . '/' ) ) {
            
            if ( is_user_logged_in() ) {
                // Redirect to dashboard
                wp_safe_redirect( get_admin_url() );
            } else {
                // Redirect to the login URL with custom login slug in the query parameters
                wp_safe_redirect( site_url( '/wp-login.php?' . $custom_login_slug . '&redirect=false' ) );
            }
            
            exit;
        }
    
    }
    
    /**
     * Customize login URL returned when calling wp_login_url(). Add the custom login slug.
     * 
     * @since 5.8.0
     */
    public function customize_login_url( $lostpassword_url )
    {
        $options = get_option( ASENHA_SLUG_U );
        $custom_login_slug = $options['custom_login_slug'];
        return home_url( '/' . $custom_login_slug . '/' );
    }
    
    /**
     * Customize lost password URL. Add the custom login slug.
     * 
     * @since 5.8.0
     */
    public function customize_lost_password_url( $lostpassword_url )
    {
        $options = get_option( ASENHA_SLUG_U );
        $custom_login_slug = $options['custom_login_slug'];
        // return home_url( '/wp-login.php?manage&action=lostpassword' );
        return $lostpassword_url . '&' . $custom_login_slug;
    }
    
    /**
     * Customize registration URL. Add the custom login slug.
     * 
     * @since 6.2.5
     */
    public function customize_register_url( $registration_url )
    {
        $options = get_option( ASENHA_SLUG_U );
        $custom_login_slug = $options['custom_login_slug'];
        // return home_url( '/wp-login.php?manage&action=lostpassword' );
        return $registration_url . '&' . $custom_login_slug;
    }
    
    /**
     * Redirect to /not_found when login URL does not contain the custom login slug
     * This will redirect /wp-login.php and /wp-admin/ to /not_found/
     *
     * @link https://plugins.trac.wordpress.org/browser/admin-login-url-change/trunk/admin-login-url-change.php#L121
     * @since 1.4.0
     */
    public function redirect_on_default_login_urls()
    {
        global  $interim_login ;
        $options = get_option( ASENHA_SLUG_U );
        $custom_login_slug = $options['custom_login_slug'];
        // e.g. manage
        $url_input = sanitize_text_field( $_SERVER['REQUEST_URI'] );
        $redirect_slug = 'not_found';
        // When logging in
        
        if ( isset( $_POST['log'] ) && isset( $_POST['pwd'] ) || isset( $_POST['post_password'] ) || is_user_logged_in() ) {
            // Do nothing. i.e. do not redirect to /not_found/ as this contains a login POST request
            // or the user is already logged in
            // upon successful login, redirection to logged-in view of /wp-admin/ happens.
            // Without this condition, login attempt will redirect to /not_found/
        } else {
            // When landing on the login page
            // At this point /admin, /wp-admin/, /login redirects to /wp-login.php
            if ( false !== strpos( $url_input, 'wp-login.php' ) ) {
                
                if ( isset( $_GET['action'] ) && ('logout' == $_GET['action'] || 'rp' == $_GET['action'] || 'resetpass' == $_GET['action']) || isset( $_GET['checkemail'] ) && ('confirm' == $_GET['checkemail'] || 'registered' == $_GET['checkemail']) || isset( $_GET['interim-login'] ) && '1' == $_GET['interim-login'] || 'success' == $interim_login ) {
                    // When we're logging out, inside the reset password flow, inside the registration flow or within the interim login flow
                    // e.g. https://www.example.com/wp-login.php?action=logout&_wpnonce=49bb818269
                    // e.g. https://www.example.com/wp-login.php?action=rp --> reset password
                    // e.g. https://www.example.com/wp-login.php?action=resetpass --> reset password
                    // e.g. https://www.example.com/wp-login.php?checkmail=confirm --> reset password
                    // e.g. https://www.example.com/wp-login.php?checkmail=registered --> register account
                    // e.g. https://www.example.com/wp-login.php?interim-login=1&wp_lang=en_US
                    // Do nothing.. proceed...
                } elseif ( isset( $_GET['action'] ) && ('lostpassword' == $_GET['action'] || 'register' == $_GET['action']) ) {
                    // When resetting password or registering an account
                    
                    if ( isset( $_POST['user_login'] ) ) {
                        // Sending the form to reset password or register an account...
                        // Do nothing.. proceed with password reset or account registration
                    } else {
                        // When landing on the password reset or registration form
                        // ...and custom login slug is not in the URL
                        
                        if ( false === strpos( $url_input, $custom_login_slug ) ) {
                            // Redirect to /not_found/
                            wp_safe_redirect( home_url( $redirect_slug . '/' ), 302 );
                            exit;
                        }
                        
                        // or, custom login slug is in the url
                        // e.g. https://www.example.com/wp-login.php?action=lostpassword&customloginslug
                        // e.g. https://www.example.com/wp-login.php?action=register&customloginslug
                        // Do nothing... allow reset password or registration
                    }
                
                } else {
                    // When landing on the login form
                    // ...and custom login slug is not in the URL
                    
                    if ( false === strpos( $url_input, $custom_login_slug ) ) {
                        // Redirect to /not_found/
                        wp_safe_redirect( home_url( $redirect_slug . '/' ), 302 );
                        exit;
                    }
                    
                    // ...or, custom login slug is in the URL
                    // e.g. https://www.example.com/wp-login.php?customloginslug&redirect=false
                    // Do nothing... allow login
                }
            
            }
        }
    
    }
    
    /**
     * Redirect to custom login URL on failed login
     *
     * @link https://plugins.trac.wordpress.org/browser/admin-login-url-change/trunk/admin-login-url-change.php#L148
     * @since 1.4.0
     */
    public function redirect_to_custom_login_url_on_login_fail()
    {
        global  $asenha_limit_login ;
        $options = get_option( ASENHA_SLUG_U );
        $custom_login_slug = $options['custom_login_slug'];
        
        if ( isset( $asenha_limit_login ) && is_array( $asenha_limit_login ) && $asenha_limit_login['within_lockout_period'] ) {
            // Do nothing. This prevents redirection loop.
        } else {
            $should_redirect = true;
            
            if ( $should_redirect ) {
                // Append 'failed_login=true' so we can output custom error message above the login form
                wp_safe_redirect( home_url( 'wp-login.php?' . $custom_login_slug . '&redirect=false&failed_login=true' ) );
                exit;
            }
        
        }
    
    }
    
    /**
     * Add login error message on top of the login form. 
     * Only shown if there's a failed_login URL parameter, and Limit Login Attempts module is not enabled. 
     * If LLA module is enabled, the same custom login error message is handled there.
     *
     * @since 6.9.1
     */
    public function add_failed_login_message( $message )
    {
        global  $asenha_limit_login ;
        if ( isset( $_REQUEST['failed_login'] ) && $_REQUEST['failed_login'] == 'true' ) {
            if ( is_null( $asenha_limit_login ) ) {
                $message = '<div id="login_error" class="notice notice-error"><b>Error:</b> Invalid username/email or incorrect password.</div>';
            }
        }
        return $message;
    }
    
    /**
     * Redirect to custom login URL on successful logout
     *
     * @link https://plugins.trac.wordpress.org/browser/admin-login-url-change/trunk/admin-login-url-change.php#L148
     * @since 1.4.0
     */
    public function redirect_to_custom_login_url_on_logout_success()
    {
        $options = get_option( ASENHA_SLUG_U );
        $custom_login_slug = $options['custom_login_slug'];
        // Redirect to the login URL with custom login slug in it
        wp_safe_redirect( home_url( 'wp-login.php?' . $custom_login_slug . '&redirect=false' ) );
        exit;
    }
    
    /**
     * Change default label on login form
     * 
     * @param array $defaults an array of default login form arguments
     * @link https://plugins.trac.wordpress.org/browser/xo-security/tags/3.7.1/inc/class-xo-security.php
     * @since 6.8.0
     */
    public function change_login_form_defaults( $defaults )
    {
        $defaults['label_username'] = 'Username';
        return $defaults;
    }
    
    /**
     * Filter for gettext.
     *
     * @param string $translation Translated text.
     * @param string $text        Text to translate.
     * @param string $domain      Text domain. Unique identifier for retrieving translated strings.
     * @link https://plugins.trac.wordpress.org/browser/xo-security/tags/3.7.1/inc/class-xo-security.php
     * @since 6.8.0
     */
    public function gettext_login_id_username( $translation, $text, $domain )
    {
        global  $pagenow ;
        if ( 'wp-login.php' === $pagenow ) {
            if ( 'default' === $domain && 'Username or Email Address' === $text ) {
                $translation = 'Username';
            }
        }
        return $translation;
    }
    
    /**
     * Filter for authenticate.
     *
     * @param WP_User|Mixed $user user object if authenticated.
     * @param String        $username username.
     * @return WP_User|Mixed authenticated user or error.
     * @link https://plugins.trac.wordpress.org/browser/xo-security/tags/3.7.1/inc/class-xo-security.php
     * @since 6.8.0
     */
    public function authenticate_email( $user, $username )
    {
        if ( null !== $user && !is_wp_error( $user ) && $user->user_email !== $username ) {
            $user = new WP_Error( 'invalid_username', '<strong>Error:</strong> There is no account with that email address.' );
        }
        return $user;
    }
    
    /**
     * Filter for gettext.
     *
     * @param string $translation Translated text.
     * @param string $text        Text to translate.
     * @param string $domain      Text domain. Unique identifier for retrieving translated strings.
     * @return WP_User|Mixed authenticated user or error.
     * @link https://plugins.trac.wordpress.org/browser/xo-security/tags/3.7.1/inc/class-xo-security.php
     */
    public function gettext_login_id_email( $translation, $text, $domain )
    {
        global  $pagenow ;
        if ( 'wp-login.php' === $pagenow ) {
            if ( 'default' === $domain && 'Username or Email Address' === $text ) {
                $translation = 'Email';
            }
        }
        return $translation;
    }
    
    /**
     * Use site icon as the login page icon, the one on top of the login form
     * 
     * @link https://plugins.trac.wordpress.org/browser/login-site-icon/trunk/login-site-icon.php
     * @since 6.0.0
     */
    public function use_site_icon_on_login()
    {
        
        if ( has_site_icon() ) {
            ?>
	        <style type="text/css">
	                .login h1 a {
	                        background-image: url('<?php 
            site_icon_url( 180 );
            ?>');
	                }
	        </style>
	        <?php 
        }
    
    }
    
    /**
     * Use site icon URL as a link on the login page icon
     * 
     * @link https://plugins.trac.wordpress.org/browser/login-site-icon/trunk/login-site-icon.php
     * @since 6.0.0
     */
    public function use_site_url_on_login()
    {
        return get_site_url();
    }
    
    /**
     * Add metabox to Appearance >> Menus page for the login logout menu items
     *
     * @since 3.4.0
     */
    public function add_login_logout_metabox()
    {
        add_meta_box(
            'add-login-logout',
            'Log In / Log Out',
            array( $this, 'add_login_logout_menu_items' ),
            'nav-menus',
            'side',
            'default'
        );
    }
    
    /**
     * Add menu items for the login logout metabox
     *
     * @since 3.4.0
     */
    public function add_login_logout_menu_items()
    {
        // The ID of the currently selected menu
        global  $nav_menu_selected_id ;
        $menu_items = array(
            'asenha-login'        => array(
            'title'   => 'Log In',
            'url'     => '#asenha-login',
            'classes' => array( 'asenha-login-menu-item' ),
        ),
            'asenha-logout'       => array(
            'title'   => 'Log Out',
            'url'     => '#asenha-logout',
            'classes' => array( 'asenha-logout-menu-item' ),
        ),
            'asenha-login-logout' => array(
            'title'   => 'Log In / Log Out',
            'url'     => '#asenha-login-logout',
            'classes' => array( 'asenha-login-logout-menu-item' ),
        ),
        );
        $item_details = array(
            'db_id'            => 0,
            'object'           => 'asenha',
            'object_id'        => '',
            'menu_item_parent' => 0,
            'type'             => 'custom',
            'title'            => '',
            'url'              => '',
            'target'           => '',
            'attr_title'       => '',
            'classes'          => array(),
            'xfn'              => '',
        );
        $menu_items_object = array();
        foreach ( $menu_items as $item_id => $details ) {
            $menu_items_object[$details['title']] = (object) $item_details;
            $menu_items_object[$details['title']]->object_id = $item_id;
            $menu_items_object[$details['title']]->title = $details['title'];
            $menu_items_object[$details['title']]->classes = $details['classes'];
            $menu_items_object[$details['title']]->url = $details['url'];
        }
        $walker = new Walker_Nav_Menu_Checklist( array() );
        ?>
		<div id="login-logout-links" class="loginlinksdiv">
			<div id="tabs-panel-login-logout-links-all" class="tabs-panel tabs-panel-view-all tabs-panel-active">
			<ul id="login-logout-links-checklist" class="list:login-logout-links categorychecklist form-no-clear">
				<?php 
        echo  walk_nav_menu_tree( array_map( 'wp_setup_nav_menu_item', $menu_items_object ), 0, (object) array(
            'walker' => $walker,
        ) ) ;
        ?>
			</ul>
			</div>
			<p class="button-controls">
				<span class="add-to-menu">
					<input type="submit"<?php 
        disabled( $nav_menu_selected_id, 0 );
        ?> class="button-secondary submit-add-to-menu right" value="<?php 
        echo  esc_attr( 'Add to Menu' ) ;
        ?>" name="add-login-logout-links-menu-item" id="submit-login-logout-links" />
					<span class="spinner"></span>
				</span>
			</p>
		</div>
		<?php 
    }
    
    /** 
     * Setup login logout URL based on login state
     * 
     * @since 3.4.0
     */
    public function set_login_logout_menu_item_dynamic_url( $menu_item )
    {
        global  $pagenow ;
        $options = get_option( ASENHA_SLUG_U, array() );
        
        if ( $pagenow != 'nav-menus.php' && !defined( 'DOING_AJAX' ) && isset( $menu_item->url ) && false !== strpos( $menu_item->url, 'asenha' ) ) {
            // Define login URL based on whether
            
            if ( array_key_exists( 'change_login_url', $options ) && $options['change_login_url'] ) {
                if ( array_key_exists( 'custom_login_slug', $options ) && !empty($options['custom_login_slug']) ) {
                    $login_page_url = get_site_url() . '/' . $options['custom_login_slug'];
                }
            } else {
                $login_page_url = wp_login_url();
            }
            
            $logout_redirect_url = home_url();
            switch ( $menu_item->url ) {
                case '#asenha-login':
                    $menu_item->url = $login_page_url;
                    break;
                case '#asenha-logout':
                    $menu_item->url = wp_logout_url();
                    break;
                case '#asenha-login-logout':
                    $menu_item->url = ( is_user_logged_in() ? wp_logout_url() : $login_page_url );
                    $menu_item->title = ( is_user_logged_in() ? 'Log Out' : 'Log In' );
                    break;
            }
        }
        
        return $menu_item;
    }
    
    /**
     * Conditionally remove login or logout menu item based on is_user_logged_in()
     *
     * @since 3.4.0
     */
    public function maybe_remove_login_or_logout_menu_item( $sorted_menu_items )
    {
        foreach ( $sorted_menu_items as $menu => $item ) {
            $item_classes = $item->classes;
            // Maybe remove Log In menu item
            if ( in_array( 'asenha-login-menu-item', $item_classes ) ) {
                if ( is_user_logged_in() ) {
                    unset( $sorted_menu_items[$menu] );
                }
            }
            // Maybe remove Log Out menu item
            if ( in_array( 'asenha-logout-menu-item', $item_classes ) ) {
                if ( !is_user_logged_in() ) {
                    unset( $sorted_menu_items[$menu] );
                }
            }
        }
        return $sorted_menu_items;
    }
    
    /**
     * Redirect to custom internal URL after login for user roles
     *
     * @param string $redirect_to_url URL to redirect to. Default is admin dashboard URL.
     * @param string $origin_url URL the user is coming from.
     * @param object $user logged-in user's data.
     * @since 1.5.0
     */
    public function redirect_for_roles_after_login( $username, $user )
    {
        $options = get_option( ASENHA_SLUG_U, array() );
        $redirect_after_login_to_slug = trim( trim( $options['redirect_after_login_to_slug'] ), '/' );
        
        if ( false !== strpos( $redirect_after_login_to_slug, '.php' ) ) {
            $slug_suffix = '';
        } else {
            $slug_suffix = '/';
        }
        
        $redirect_after_login_for = $options['redirect_after_login_for'];
        
        if ( isset( $redirect_after_login_for ) && count( $redirect_after_login_for ) > 0 ) {
            // Assemble single-dimensional array of roles for which custom URL redirection should happen
            $roles_for_custom_redirect = array();
            foreach ( $redirect_after_login_for as $role_slug => $custom_redirect ) {
                if ( $custom_redirect ) {
                    $roles_for_custom_redirect[] = $role_slug;
                }
            }
            // Does the user have roles data in array form?
            if ( isset( $user->roles ) && is_array( $user->roles ) ) {
                $current_user_roles = $user->roles;
            }
            // Set custom redirect URL for roles set in the settings. Otherwise, leave redirect URL to the default, i.e. admin dashboard.
            foreach ( $current_user_roles as $role ) {
                
                if ( in_array( $role, $roles_for_custom_redirect ) ) {
                    wp_safe_redirect( home_url( $redirect_after_login_to_slug . $slug_suffix ) );
                    exit;
                }
            
            }
        }
    
    }
    
    /**
     * Redirect to custom internal URL after login for user roles
     *
     * @param string $redirect_to_url URL to redirect to. Default is admin dashboard URL.
     * @param string $origin_url URL the user is coming from.
     * @param object $user logged-in user's data.
     * @since 1.6.0
     */
    public function redirect_after_logout( $user_id )
    {
        $options = get_option( ASENHA_SLUG_U, array() );
        $redirect_after_logout_to_slug = $options['redirect_after_logout_to_slug'];
        $redirect_after_logout_for = $options['redirect_after_logout_for'];
        $user = get_userdata( $user_id );
        
        if ( isset( $redirect_after_logout_for ) && count( $redirect_after_logout_for ) > 0 ) {
            // Assemble single-dimensional array of roles for which custom URL redirection should happen
            $roles_for_custom_redirect = array();
            foreach ( $redirect_after_logout_for as $role_slug => $custom_redirect ) {
                if ( $custom_redirect ) {
                    $roles_for_custom_redirect[] = $role_slug;
                }
            }
            // Does the user have roles data in array form?
            if ( isset( $user->roles ) && is_array( $user->roles ) ) {
                $current_user_roles = $user->roles;
            }
            // Redirect for roles set in the settings. Otherwise, leave redirect URL to the default, i.e. admin dashboard.
            foreach ( $current_user_roles as $role ) {
                
                if ( in_array( $role, $roles_for_custom_redirect ) ) {
                    wp_safe_redirect( home_url( $redirect_after_logout_to_slug . '/' ) );
                    exit;
                }
            
            }
        }
    
    }
    
    /**
     * Log date time when a user last logged in successfully
     *
     * @since 3.6.0
     */
    public function log_login_datetime( $user_login )
    {
        $user = get_user_by( 'login', $user_login );
        // by username
        if ( is_object( $user ) ) {
            if ( property_exists( $user, 'ID' ) ) {
                update_user_meta( $user->ID, 'asenha_last_login_on', time() );
            }
        }
    }
    
    /**
     * Add Last Login column to users list table
     *
     * @since 3.6.0
     */
    public function add_last_login_column( $columns )
    {
        $columns['asenha_last_login'] = 'Last Login';
        return $columns;
    }
    
    /**
     * Show last login info in the last login column
     *
     * @since 3.6.0
     */
    public function show_last_login_info( $output, $column_name, $user_id )
    {
        if ( 'asenha_last_login' === $column_name ) {
            
            if ( !empty(get_user_meta( $user_id, 'asenha_last_login_on', true )) ) {
                $last_login_unixtime = (int) get_user_meta( $user_id, 'asenha_last_login_on', true );
                
                if ( function_exists( 'wp_date' ) ) {
                    $output = wp_date( 'M j, Y H:i', $last_login_unixtime );
                } else {
                    $output = date_i18n( 'M j, Y H:i', $last_login_unixtime );
                }
            
            } else {
                $output = 'No data yet';
            }
        
        }
        return $output;
    }
    
    /**
     * Add custom CSS for the Last Login column
     *
     * @since 3.6.0
     */
    public function add_column_style()
    {
        ?>
			<style>
				.column-asenha_last_login {
					width: 90px;
				}
			</style>
		<?php 
    }

}