<?php

namespace ASENHA\Classes;

/**
 * Class for Display Active Plugins First module
 *
 * @since 6.9.5
 */
class Display_Active_Plugins_First {
    
    /**
     * Custom sort on the plugins listing to show active plugins first
     * 
     * @link https://plugins.trac.wordpress.org/browser/display-active-plugins-first/tags/1.1/display-active-plugins-first.php
     * @since 6.7.0
     */
    public function show_active_plugins_first() {
        global $wp_list_table, $status;

        if ( ! in_array( $status, array( 'active', 'inactive', 'recently_activated', 'mustuse' ), true ) ) {
            uksort( $wp_list_table->items, array( $this, 'plugins_order_callback' ) );
        }
    }
    
    /**
     * Reorder plugins list to show active ones first
     * 
     * @link https://plugins.trac.wordpress.org/browser/display-active-plugins-first/tags/1.1/display-active-plugins-first.php
     * @since 6.7.0
     */
    public function plugins_order_callback( $a, $b ) {
        global $wp_list_table;

        $a_active = is_plugin_active( $a );
        $b_active = is_plugin_active( $b );

        if ( $a_active && ! $b_active ) {
            return -1;
        } elseif ( ! $a_active && $b_active ) {
            return 1;
        } else {
            return @strcasecmp( $wp_list_table->items[ $a ]['Name'], $wp_list_table->items[ $b ]['Name'] );
        }
    }
        
}