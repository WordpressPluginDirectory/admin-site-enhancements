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

}