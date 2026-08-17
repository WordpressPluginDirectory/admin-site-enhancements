<?php
/**
 * Contact Form submissions list table.
 *
 * @package Admin_Site_Enhancements
 */

namespace ASENHA\Classes;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Admin list table for contact form submissions.
 */
class Contact_Form_Submissions_Table extends \WP_List_Table {

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => 'submission',
				'plural'   => 'submissions',
				'ajax'     => false,
			)
		);
	}

	/**
	 * Message shown when no items exist.
	 *
	 * @return void
	 */
	public function no_items() {
		esc_html_e( 'No submissions found.', 'admin-site-enhancements' );
	}

	/**
	 * Get table columns.
	 *
	 * @return array<string, string>
	 */
	public function get_columns() {
		return array(
			'cb'      => '<input type="checkbox" />',
			'subject' => __( 'Subject', 'admin-site-enhancements' ),
			'message' => __( 'Message', 'admin-site-enhancements' ),
			'name'    => __( 'Name', 'admin-site-enhancements' ),
			'email'   => __( 'Email', 'admin-site-enhancements' ),
			'date'    => __( 'Date', 'admin-site-enhancements' ),
			'actions' => __( 'Action', 'admin-site-enhancements' ),
		);
	}

	/**
	 * Get sortable columns.
	 *
	 * @return array<string, array<int, bool|int|string>>
	 */
	public function get_sortable_columns() {
		return array(
			'date' => array( 'created_at', true ),
		);
	}

	/**
	 * Render the checkbox column.
	 *
	 * @param array<string, mixed> $item Row item.
	 * @return string
	 */
	public function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="%1$s[]" value="%2$s" />',
			esc_attr( $this->_args['singular'] ),
			(int) $item['id']
		);
	}

	/**
	 * Get bulk actions.
	 *
	 * @return array<string, string>
	 */
	public function get_bulk_actions() {
		return array(
			'delete' => __( 'Delete', 'admin-site-enhancements' ),
		);
	}

	/**
	 * Process bulk actions before loading items.
	 *
	 * @return void
	 */
	public function process_bulk_action() {
		if ( 'delete' !== $this->current_action() ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized request.', 'admin-site-enhancements' ) );
		}

		check_admin_referer( 'bulk-' . $this->_args['plural'] );

		$ids = isset( $_REQUEST['submission'] ) ? wp_unslash( $_REQUEST['submission'] ) : array();

		if ( ! is_array( $ids ) ) {
			$ids = array( $ids );
		}

		$ids = array_filter( array_map( 'absint', $ids ) );

		if ( empty( $ids ) ) {
			return;
		}

		$deleted_count = 0;

		foreach ( $ids as $id ) {
			if ( Contact_Form_Submission::delete( $id ) ) {
				++$deleted_count;
			}
		}

		$redirect_args = array(
			'page'    => 'asenha-contact-form-submissions',
			'deleted' => $deleted_count,
		);

		if ( ! empty( $_REQUEST['s'] ) ) {
			$redirect_args['s'] = sanitize_text_field( wp_unslash( $_REQUEST['s'] ) );
		}

		if ( ! empty( $_REQUEST['orderby'] ) ) {
			$redirect_args['orderby'] = sanitize_key( wp_unslash( $_REQUEST['orderby'] ) );
		}

		if ( ! empty( $_REQUEST['order'] ) ) {
			$redirect_args['order'] = sanitize_text_field( wp_unslash( $_REQUEST['order'] ) );
		}

		if ( ! empty( $_REQUEST['paged'] ) ) {
			$redirect_args['paged'] = absint( $_REQUEST['paged'] );
		}

		wp_safe_redirect( add_query_arg( $redirect_args, admin_url( 'admin.php' ) ) );
		exit;
	}

	/**
	 * Prepare table items.
	 *
	 * @return void
	 */
	public function prepare_items() {
		$this->process_bulk_action();

		$per_page     = 10;
		$current_page = $this->get_pagenum();
		$search       = isset( $_REQUEST['s'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) : '';
		$orderby      = isset( $_REQUEST['orderby'] ) ? sanitize_key( wp_unslash( $_REQUEST['orderby'] ) ) : 'created_at';
		$order        = isset( $_REQUEST['order'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['order'] ) ) : 'DESC';

		if ( 'date' === $orderby ) {
			$orderby = 'created_at';
		}

		$total_items = Contact_Form_Submission::count( $search );
		$items       = Contact_Form_Submission::get_list(
			array(
				'per_page' => $per_page,
				'page'     => $current_page,
				'orderby'  => $orderby,
				'order'    => $order,
				'search'   => $search,
			)
		);

		$this->items = $items;
		$this->_column_headers = array( $this->get_columns(), array(), $this->get_sortable_columns() );

		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => $per_page,
				'total_pages' => (int) ceil( $total_items / $per_page ),
			)
		);
	}

	/**
	 * Default column renderer.
	 *
	 * @param array<string, mixed> $item        Row item.
	 * @param string               $column_name Column name.
	 * @return string
	 */
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'subject':
				return esc_html( $this->truncate_text( $item['subject'], 60 ) );
			case 'message':
				return esc_html( $this->truncate_text( wp_strip_all_tags( $item['message'] ), 80 ) );
			case 'name':
				return esc_html( $item['name'] );
			case 'email':
				return sprintf(
					'<a href="mailto:%1$s">%2$s</a>',
					esc_attr( $item['email'] ),
					esc_html( $item['email'] )
				);
			case 'date':
				return esc_html( mysql2date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $item['created_at'] ) );
			case 'actions':
				return $this->get_actions_column( $item );
			default:
				return '';
		}
	}

	/**
	 * Build the actions column markup.
	 *
	 * @param array<string, mixed> $item Row item.
	 * @return string
	 */
	private function get_actions_column( $item ) {
		$view_button = sprintf(
			'<button type="button" class="button button-small asenha-cf-view-submission" data-submission-id="%1$d">%2$s</button>',
			(int) $item['id'],
			esc_html__( 'View', 'admin-site-enhancements' )
		);

		$delete_button = sprintf(
			'<button type="button" class="button button-small asenha-cf-delete-submission" data-submission-id="%1$d" data-nonce="%2$s">%3$s</button>',
			(int) $item['id'],
			esc_attr( wp_create_nonce( 'asenha_cf_delete_' . (int) $item['id'] ) ),
			esc_html__( 'Delete', 'admin-site-enhancements' )
		);

		return $view_button . ' ' . $delete_button;
	}

	/**
	 * Truncate text for list previews.
	 *
	 * @param string $text   Text to truncate.
	 * @param int    $length Maximum length.
	 * @return string
	 */
	private function truncate_text( $text, $length ) {
		if ( mb_strlen( $text, 'UTF-8' ) <= $length ) {
			return $text;
		}

		return mb_substr( $text, 0, $length, 'UTF-8' ) . '...';
	}
}
