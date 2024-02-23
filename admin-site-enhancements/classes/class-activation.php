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

}