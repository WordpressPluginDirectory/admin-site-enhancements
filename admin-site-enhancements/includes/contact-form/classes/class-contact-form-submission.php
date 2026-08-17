<?php
/**
 * Contact Form submission storage.
 *
 * @package Admin_Site_Enhancements
 */

namespace ASENHA\Classes;

defined( 'ABSPATH' ) || exit;

/**
 * Handles database operations for contact form submissions.
 */
class Contact_Form_Submission {

	/**
	 * Get the submissions table name.
	 *
	 * @return string
	 */
	public static function get_table_name() {
		global $wpdb;

		return $wpdb->prefix . 'asenha_contact_form_submissions';
	}

	/**
	 * Insert a new submission.
	 *
	 * @param array<string, string> $data Submission data.
	 * @return int|false Insert ID or false on failure.
	 */
	public static function insert( $data ) {
		global $wpdb;

		$inserted = $wpdb->insert(
			self::get_table_name(),
			array(
				'subject'    => $data['subject'],
				'message'    => $data['message'],
				'name'       => $data['name'],
				'email'      => $data['email'],
				'created_at' => current_time( 'mysql' ),
			),
			array( '%s', '%s', '%s', '%s', '%s' )
		);

		if ( false === $inserted ) {
			return false;
		}

		return (int) $wpdb->insert_id;
	}

	/**
	 * Format a submission timestamp for display.
	 *
	 * @param string $mysql_datetime MySQL datetime string.
	 * @return string
	 */
	public static function format_submission_date( $mysql_datetime ) {
		if ( empty( $mysql_datetime ) ) {
			return '';
		}

		$timestamp = mysql2date( 'U', $mysql_datetime, false );

		if ( empty( $timestamp ) ) {
			return '';
		}

		return wp_date( 'l, F j, Y \- H:i', (int) $timestamp, wp_timezone() );
	}

	/**
	 * Get a submission by ID.
	 *
	 * @param int $submission_id Submission ID.
	 * @return array<string, mixed>|null
	 */
	public static function get( $submission_id ) {
		global $wpdb;

		$table_name = self::get_table_name();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$row = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT id, subject, message, name, email, created_at FROM ' . $table_name . ' WHERE id = %d',
				$submission_id
			),
			ARRAY_A
		);

		return is_array( $row ) ? $row : null;
	}

	/**
	 * Delete a submission by ID.
	 *
	 * @param int $submission_id Submission ID.
	 * @return bool
	 */
	public static function delete( $submission_id ) {
		global $wpdb;

		$deleted = $wpdb->delete(
			self::get_table_name(),
			array( 'id' => $submission_id ),
			array( '%d' )
		);

		return false !== $deleted && $deleted > 0;
	}

	/**
	 * Get paginated submissions for the admin list table.
	 *
	 * @param array<string, mixed> $args Query arguments.
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_list( $args = array() ) {
		global $wpdb;

		$defaults = array(
			'per_page' => 10,
			'page'     => 1,
			'orderby'  => 'created_at',
			'order'    => 'DESC',
			'search'   => '',
		);

		$args = wp_parse_args( $args, $defaults );

		$table_name = self::get_table_name();
		$allowed_orderby = array( 'created_at', 'subject', 'name', 'email' );
		$orderby = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'created_at';
		$order   = 'ASC' === strtoupper( $args['order'] ) ? 'ASC' : 'DESC';
		$offset  = max( 0, ( (int) $args['page'] - 1 ) * (int) $args['per_page'] );
		$limit   = max( 1, (int) $args['per_page'] );

		$where  = 'WHERE 1=1';
		$params = array();

		if ( ! empty( $args['search'] ) ) {
			$like     = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$where   .= ' AND (subject LIKE %s OR message LIKE %s OR name LIKE %s OR email LIKE %s)';
			$params[] = $like;
			$params[] = $like;
			$params[] = $like;
			$params[] = $like;
		}

		$sql = "SELECT id, subject, message, name, email, created_at FROM {$table_name} {$where} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";
		$params[] = $limit;
		$params[] = $offset;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A );

		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Count submissions for pagination.
	 *
	 * @param string $search Optional search string.
	 * @return int
	 */
	public static function count( $search = '' ) {
		global $wpdb;

		$table_name = self::get_table_name();
		$where      = 'WHERE 1=1';
		$params     = array();

		if ( ! empty( $search ) ) {
			$like     = '%' . $wpdb->esc_like( $search ) . '%';
			$where   .= ' AND (subject LIKE %s OR message LIKE %s OR name LIKE %s OR email LIKE %s)';
			$params[] = $like;
			$params[] = $like;
			$params[] = $like;
			$params[] = $like;
		}

		$sql = "SELECT COUNT(*) FROM {$table_name} {$where}";

		if ( ! empty( $params ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
			return (int) $wpdb->get_var( $wpdb->prepare( $sql, $params ) );
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		return (int) $wpdb->get_var( $sql );
	}
}
