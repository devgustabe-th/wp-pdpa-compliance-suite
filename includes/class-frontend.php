<?php
namespace WP_PDPA_CS;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles frontend logic and shortcodes.
 */
class Frontend {

	private $form_errors = [];

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_frontend_styles' ] );
		add_shortcode( 'pdpa_dsar_form', [ $this, 'render_dsar_form' ] );
	}

	/**
	 * Enqueue styles and scripts for the frontend.
	 */
	public function enqueue_frontend_styles() {
		global $post;
		if ( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'pdpa_dsar_form' ) ) {
			wp_enqueue_style(
				'wp-pdpa-cs-public-styles',
				WPPCS_URL . 'assets/css/public-styles.css',
				[],
				WPPCS_VERSION
			);
		}
	}

	/**
	 * Renders the DSAR form shortcode.
	 *
	 * @return string The form HTML.
	 */
	public function render_dsar_form() {
		// Step 1: Handle form submission
		$this->handle_form_submission();

		// Step 2: Display the form by loading a template
		ob_start();

		// Display success/error messages if any
		if ( ! empty( $this->form_errors ) ) {
			echo '<div class="wppcs-alert wppcs-alert-error">' . implode( '<br>', $this->form_errors ) . '</div>';
		}

		$this->get_template( 'dsar-form' );
		return ob_get_clean();
	}

	/**
	 * Handles the logic for when the DSAR form is submitted.
	 */
	private function handle_form_submission() {
		if ( 'POST' !== $_SERVER['REQUEST_METHOD'] || empty( $_POST['wppcs_action'] ) || 'submit_dsar' !== $_POST['wppcs_action'] ) {
			return;
		}

		// 1. Security Check: Verify nonce
		if ( ! isset( $_POST['wppcs_nonce'] ) || ! wp_verify_nonce( $_POST['wppcs_nonce'], 'wppcs_submit_dsar_nonce' ) ) {
			$this->form_errors[] = __( 'Security check failed. Please try again.', 'wp-pdpa-cs' );
			return;
		}

		// 2. Sanitize and Validate Data
		$name         = isset( $_POST['wppcs_name'] ) ? sanitize_text_field( $_POST['wppcs_name'] ) : '';
		$email        = isset( $_POST['wppcs_email'] ) ? sanitize_email( $_POST['wppcs_email'] ) : '';
		$request_type = isset( $_POST['wppcs_request_type'] ) ? sanitize_key( $_POST['wppcs_request_type'] ) : '';
		$details      = isset( $_POST['wppcs_details'] ) ? sanitize_textarea_field( $_POST['wppcs_details'] ) : '';

		if ( empty( $name ) || empty( $email ) || empty( $request_type ) ) {
			$this->form_errors[] = __( 'Please fill in all required fields.', 'wp-pdpa-cs' );
			return;
		}
		if ( ! is_email( $email ) ) {
			$this->form_errors[] = __( 'Please enter a valid email address.', 'wp-pdpa-cs' );
			return;
		}

		// 3. Handle File Upload (if present)
		$attachment_path = '';
		if ( isset( $_FILES['wppcs_attachment'] ) && ! empty( $_FILES['wppcs_attachment']['name'] ) ) {
			if ( ! function_exists( 'wp_handle_upload' ) ) {
				require_once ABSPATH . 'wp-admin/includes/file.php';
			}
			$uploaded_file = $_FILES['wppcs_attachment'];
			$upload_overrides = [ 'test_form' => false ];
			$movefile = wp_handle_upload( $uploaded_file, $upload_overrides );

			if ( $movefile && ! isset( $movefile['error'] ) ) {
				$attachment_path = $movefile['file']; // We store the server path
			} else {
				$this->form_errors[] = __( 'File upload error:', 'wp-pdpa-cs' ) . ' ' . $movefile['error'];
				return;
			}
		}

		// 4. Insert Data into Database
		global $wpdb;
		$table_name = $wpdb->prefix . 'pdpa_dsar_requests';

		$result = $wpdb->insert(
			$table_name,
			[
				'requester_name'  => $name,
				'requester_email' => $email,
				'request_type'    => $request_type,
				'request_details' => $details,
				'attachment_path' => $attachment_path,
				'request_status'  => 'new', // Default status
				'created_at'      => current_time( 'mysql', 1 ), // GMT time
			],
			[ '%s', '%s', '%s', '%s', '%s', '%s', '%s' ]
		);

		// 5. Redirect to prevent form resubmission
		if ( $result ) {
			$current_url = home_url( add_query_arg( null, null ) );
			wp_redirect( add_query_arg( 'request_success', '1', $current_url ) );
			exit;
		} else {
			$this->form_errors[] = __( 'Could not save your request. Please try again later.', 'wp-pdpa-cs' );
		}
	}

	/**
	 * Helper function to load templates.
	 */
	public function get_template( $template_name ) {
		// Display success message after redirect
		if ( isset( $_GET['request_success'] ) && '1' === $_GET['request_success'] ) {
			echo '<div class="wppcs-alert wppcs-alert-success">' . esc_html__( 'Thank you! Your request has been submitted successfully.', 'wp-pdpa-cs' ) . '</div>';
			return; // Don't show the form again
		}

		$theme_template = get_stylesheet_directory() . '/wp-pdpa-cs/' . $template_name . '.php';
		if ( file_exists( $theme_template ) ) { load_template( $theme_template, false ); return; }
		$plugin_template = WPPCS_PATH . 'templates/' . $template_name . '.php';
		if ( file_exists( $plugin_template ) ) { load_template( $plugin_template, false ); }
	}
}