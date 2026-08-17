<?php
/**
 * Contact Form admin page.
 *
 * @package Admin_Site_Enhancements
 */

namespace ASENHA\Classes;

defined( 'ABSPATH' ) || exit;

/**
 * Registers and renders the submissions admin page.
 */
class Contact_Form_Admin {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_submenu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		add_action( 'wp_ajax_asenha_contact_form_get_submission', array( $this, 'ajax_get_submission' ) );
		add_action( 'wp_ajax_asenha_contact_form_delete_submission', array( $this, 'ajax_delete_submission' ) );
	}

	/**
	 * Register the Dashboard submenu page.
	 *
	 * @return void
	 */
	public function register_submenu() {
		add_submenu_page(
			'index.php',
			__( 'Contact Form Submissions', 'admin-site-enhancements' ),
			__( 'Contact Form Submissions', 'admin-site-enhancements' ),
			'manage_options',
			'asenha-contact-form-submissions',
			array( $this, 'render_submissions_page' )
		);
	}

	/**
	 * Enqueue admin assets on the submissions page.
	 *
	 * @param string $hook_suffix Current admin page hook suffix.
	 * @return void
	 */
	public function enqueue_admin_assets( $hook_suffix ) {
		if ( 'dashboard_page_asenha-contact-form-submissions' !== $hook_suffix ) {
			return;
		}

		wp_enqueue_style(
			'asenha-contact-form-admin',
			CONTACT_FORM_URL . 'assets/css/contact-form-admin.css',
			array(),
			CONTACT_FORM_VERSION
		);

		wp_enqueue_script(
			'asenha-contact-form-admin',
			CONTACT_FORM_URL . 'assets/js/contact-form-admin.js',
			array( 'jquery' ),
			CONTACT_FORM_VERSION,
			true
		);

		wp_localize_script(
			'asenha-contact-form-admin',
			'asenhaContactFormAdmin',
			array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'asenha_contact_form_admin' ),
				'i18n'    => array(
					'close'                => __( 'Close', 'admin-site-enhancements' ),
					'delete_confirm'       => __( 'Delete this submission?', 'admin-site-enhancements' ),
					'bulk_delete_confirm'  => __( 'Delete the selected submissions?', 'admin-site-enhancements' ),
					'no_items_selected'    => __( 'No submissions selected.', 'admin-site-enhancements' ),
					'delete_failed'        => __( 'Unable to delete this submission.', 'admin-site-enhancements' ),
					'load_failed'      => __( 'Unable to load submission details.', 'admin-site-enhancements' ),
					'subject'          => __( 'Subject', 'admin-site-enhancements' ),
					'message'          => __( 'Message', 'admin-site-enhancements' ),
					'name'             => __( 'Name', 'admin-site-enhancements' ),
					'email'            => __( 'Email', 'admin-site-enhancements' ),
					'date'             => __( 'Date', 'admin-site-enhancements' ),
				),
			)
		);
	}

	/**
	 * Render the submissions list page.
	 *
	 * @return void
	 */
	public function render_submissions_page() {
		$list_table = new Contact_Form_Submissions_Table();
		$list_table->prepare_items();
		?>
		<div class="wrap asenha-contact-form-admin-wrap">
			<h1 class="wp-heading-inline"><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<hr class="wp-header-end">
			<?php
			if ( isset( $_GET['deleted'] ) ) {
				$deleted_count = absint( $_GET['deleted'] );

				if ( $deleted_count > 0 ) {
					?>
					<div class="notice notice-success is-dismissible">
						<p>
							<?php
							echo esc_html(
								sprintf(
									/* translators: %d: number of deleted submissions */
									_n( '%d submission deleted.', '%d submissions deleted.', $deleted_count, 'admin-site-enhancements' ),
									$deleted_count
								)
							);
							?>
						</p>
					</div>
					<?php
				}
			}
			?>
			<form method="get">
				<input type="hidden" name="page" value="asenha-contact-form-submissions" />
				<?php
				$list_table->search_box( __( 'Search Submissions', 'admin-site-enhancements' ), 'asenha-cf-submission' );
				$list_table->display();
				?>
			</form>
		</div>
		<div class="asenha-cf-modal-overlay" id="asenha-cf-submission-modal" hidden>
			<div class="asenha-cf-modal" role="dialog" aria-modal="true" aria-labelledby="asenha-cf-modal-title">
				<div class="asenha-cf-modal-header">
					<h2 id="asenha-cf-modal-title"><?php esc_html_e( 'Submission Details', 'admin-site-enhancements' ); ?></h2>
					<button type="button" class="button-link asenha-cf-modal-close" aria-label="<?php esc_attr_e( 'Close', 'admin-site-enhancements' ); ?>">&times;</button>
				</div>
				<div class="asenha-cf-modal-body">
					<div class="asenha-cf-modal-loader" hidden>
						<span class="spinner is-active"></span>
					</div>
					<div class="asenha-cf-modal-content"></div>
				</div>
				<div class="asenha-cf-modal-footer">
					<button type="button" class="button asenha-cf-modal-close"><?php esc_html_e( 'Close', 'admin-site-enhancements' ); ?></button>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * AJAX handler for viewing a submission.
	 *
	 * @return void
	 */
	public function ajax_get_submission() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized request.', 'admin-site-enhancements' ) ) );
		}

		check_ajax_referer( 'asenha_contact_form_admin', 'nonce' );

		$submission_id = isset( $_POST['submission_id'] ) ? absint( $_POST['submission_id'] ) : 0;
		$submission    = Contact_Form_Submission::get( $submission_id );

		if ( empty( $submission ) ) {
			wp_send_json_error( array( 'message' => __( 'Submission not found.', 'admin-site-enhancements' ) ) );
		}

		$html  = '<dl class="asenha-cf-submission-details">';
		$html .= '<dt>' . esc_html__( 'Subject', 'admin-site-enhancements' ) . '</dt>';
		$html .= '<dd>' . esc_html( $submission['subject'] ) . '</dd>';
		$html .= '<dt>' . esc_html__( 'Message', 'admin-site-enhancements' ) . '</dt>';
		$html .= '<dd class="asenha-cf-submission-message">' . nl2br( esc_html( $submission['message'] ) ) . '</dd>';
		$html .= '<dt>' . esc_html__( 'Name', 'admin-site-enhancements' ) . '</dt>';
		$html .= '<dd>' . esc_html( $submission['name'] ) . '</dd>';
		$html .= '<dt>' . esc_html__( 'Email', 'admin-site-enhancements' ) . '</dt>';
		$html .= '<dd><a href="mailto:' . esc_attr( $submission['email'] ) . '">' . esc_html( $submission['email'] ) . '</a></dd>';
		$html .= '<dt>' . esc_html__( 'Date', 'admin-site-enhancements' ) . '</dt>';
		$html .= '<dd>' . esc_html( Contact_Form_Submission::format_submission_date( $submission['created_at'] ) ) . '</dd>';
		$html .= '</dl>';

		wp_send_json_success(
			array(
				'html' => $html,
			)
		);
	}

	/**
	 * AJAX handler for deleting a submission.
	 *
	 * @return void
	 */
	public function ajax_delete_submission() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized request.', 'admin-site-enhancements' ) ) );
		}

		$submission_id = isset( $_POST['submission_id'] ) ? absint( $_POST['submission_id'] ) : 0;
		$nonce         = isset( $_POST['delete_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['delete_nonce'] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, 'asenha_cf_delete_' . $submission_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid security token.', 'admin-site-enhancements' ) ) );
		}

		if ( ! Contact_Form_Submission::delete( $submission_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Unable to delete this submission.', 'admin-site-enhancements' ) ) );
		}

		wp_send_json_success(
			array(
				'message' => __( 'Submission deleted.', 'admin-site-enhancements' ),
			)
		);
	}
}
