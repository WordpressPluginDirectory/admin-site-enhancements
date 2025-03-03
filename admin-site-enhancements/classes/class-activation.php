<?php

namespace ASENHA\Classes;

/**
 * Plugin Activation
 *
 * @since 1.0.0
 */
class Activation {

	/**
	 * Create failed login log table for Limit Login Attempts feature
	 *
	 * @since 2.5.0
	 */
	public function create_failed_logins_log_table() {
        global $wpdb;

        // Limit Login Attempts Log Table

        $table_name = $wpdb->prefix . 'asenha_failed_logins';

        if ( ! empty( $wpdb->charset ) ) {
            $charset_collation_sql = "DEFAULT CHARACTER SET $wpdb->charset";         
        }

        if ( ! empty( $wpdb->collate ) ) {
            $charset_collation_sql .= " COLLATE $wpdb->collate";         
        }

        // Drop table if already exists
        $wpdb->query("DROP TABLE IF EXISTS `". $table_name ."`");

        // Create database table. This procedure may also be called
        $sql = 
        "CREATE TABLE {$table_name} (
            id int(6) unsigned NOT NULL auto_increment,
            ip_address varchar(40) NOT NULL DEFAULT '',
            username varchar(24) NOT NULL DEFAULT '',
            fail_count int(10) NOT NULL DEFAULT '0',
            lockout_count int(10) NOT NULL DEFAULT '0',
            request_uri varchar(24) NOT NULL DEFAULT '',
            unixtime int(10) NOT NULL DEFAULT '0',
            datetime_wp varchar(36) NOT NULL DEFAULT '',
            -- datetime_utc datetime NULL DEFAULT CURRENT_TIMESTAMP,
            info varchar(64) NOT NULL DEFAULT '',
            UNIQUE (ip_address),
            PRIMARY KEY (id)
        ) {$charset_collation_sql}";
		
		require_once ABSPATH . '/wp-admin/includes/upgrade.php';

        dbDelta( $sql );

        return true;
	}

    /**
     * Create email delivery log table for Email Delivery module
     *
     * @since 7.1.0
     */
    public function create_email_delivery_log_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'asenha_email_delivery';

        if ( ! empty( $wpdb->charset ) ) {
            $charset_collation_sql = "DEFAULT CHARACTER SET $wpdb->charset";         
        }

        if ( ! empty( $wpdb->collate ) ) {
            $charset_collation_sql .= " COLLATE $wpdb->collate";         
        }

        // Drop table if already exists
        $wpdb->query("DROP TABLE IF EXISTS `". $table_name ."`");

        // Create database table. This procedure may also be called
        $sql = 
        "CREATE TABLE {$table_name} (
            id int(6) unsigned NOT NULL auto_increment,
            status enum('successful','failed','unknown') NOT NULL DEFAULT 'unknown',
            error varchar(250) NOT NULL DEFAULT '',
            subject varchar(250) NOT NULL DEFAULT '',
            message longtext NOT NULL DEFAULT '',
            send_to varchar(256) NOT NULL DEFAULT '',
            sender varchar(256) NOT NULL DEFAULT '',
            reply_to varchar(256) NOT NULL DEFAULT '',            
            headers text NOT NULL DEFAULT '',
            content_type text NOT NULL DEFAULT '',
            attachments text NOT NULL DEFAULT '',
            backtrace text NOT NULL DEFAULT '',
            processor text NOT NULL DEFAULT '',
            sent_on datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            sent_on_unixtime int(10) NOT NULL DEFAULT '0',
            extra longtext NOT NULL DEFAULT '',
            PRIMARY KEY (id)
        ) {$charset_collation_sql}";
        
        require_once ABSPATH . '/wp-admin/includes/upgrade.php';

        dbDelta( $sql );

        return true;
    }

    /**
     * Create tables for the Form Builder module
     *
     * @since 7.8.0
     */
    public function create_form_builder_tables() {
        global $wpdb;

        if ( ! empty( $wpdb->charset ) ) {
            $charset_collation_sql = "DEFAULT CHARACTER SET $wpdb->charset";         
        }

        if ( ! empty( $wpdb->collate ) ) {
            $charset_collation_sql .= " COLLATE $wpdb->collate";         
        }
        
        flush_rewrite_rules();

        require_once ABSPATH . '/wp-admin/includes/upgrade.php';
        
        $fields_table_name = $wpdb->prefix . 'asenha_formbuilder_fields';
        $forms_table_name = $wpdb->prefix . 'asenha_formbuilder_forms';
        $entries_table_name = $wpdb->prefix . 'asenha_formbuilder_entries';
        $entry_meta_table_name = $wpdb->prefix . 'asenha_formbuilder_entry_meta';
        
        $sql = "CREATE TABLE {$fields_table_name} (
        id BIGINT(20) NOT NULL auto_increment,
        field_key varchar(100) default NULL,
                name text default NULL,
                description longtext default NULL,
                type text default NULL,
                default_value longtext default NULL,
                options longtext default NULL,
                field_order int(11) default 0,
                required int(1) default NULL,
                field_options longtext default NULL,
                form_id int(11) default NULL,
                created_at datetime NOT NULL,
                PRIMARY KEY (id),
                KEY form_id (form_id),
                UNIQUE KEY field_key (field_key)
        ) {$charset_collation_sql}";

        dbDelta( $sql );
        
        $sql = "CREATE TABLE {$forms_table_name} (
                id int(11) NOT NULL auto_increment,
        form_key varchar(100) default NULL,
                name varchar(255) default NULL,
                description text default NULL,
                status varchar(255) default NULL,
                options longtext default NULL,
                settings longtext default NULL,
                styles longtext default NULL,
                created_at datetime NOT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY form_key (form_key)
        ) {$charset_collation_sql}";

        dbDelta( $sql );

        $sql = "CREATE TABLE {$entries_table_name} (
        id BIGINT(20) NOT NULL auto_increment,
                ip text default NULL,
        form_id BIGINT(20) default NULL,
        user_id BIGINT(20) default NULL,
        delivery_status tinyint(1) default 0,
                status varchar(255) default NULL,
                created_at datetime NOT NULL,
                PRIMARY KEY (id),
                KEY form_id (form_id),
                KEY user_id (user_id)
        ) {$charset_collation_sql}";

        dbDelta( $sql );

        $sql = "CREATE TABLE {$entry_meta_table_name} (
        id BIGINT(20) NOT NULL auto_increment,
        meta_value longtext default NULL,
        field_id BIGINT(20) NOT NULL,
        item_id BIGINT(20) NOT NULL,
                created_at datetime NOT NULL,
                PRIMARY KEY  (id),
                KEY field_id (field_id),
                KEY item_id (item_id)
        ) {$charset_collation_sql}";

        dbDelta( $sql );

        return true;
    }

}