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
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_frontend_assets' ] );
		add_shortcode( 'pdpa_dsar_form', [ $this, 'render_dsar_form' ] );
		add_action( 'wp_footer', [ $this, 'render_cookie_banner_and_modal' ] );

		// Hook the AJAX handler for both logged-in and logged-out users
		add_action( 'wp_ajax_wppcs_log_consent', [ $this, 'handle_ajax_log_consent' ] );
		add_action( 'wp_ajax_nopriv_wppcs_log_consent', [ $this, 'handle_ajax_log_consent' ] );
	}

	/**
	 * Enqueue styles and scripts for the frontend.
	 */
	public function enqueue_frontend_assets() {
		global $post;
		$options = get_option( 'wppcs_settings', [] );
		$banner_enabled = $options['enable_banner'] ?? 'on';
		$is_banner_active = 'on' === $banner_enabled && ! isset( $_COOKIE['wppcs_consent_given'] );
		$should_load_assets = ( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'pdpa_dsar_form' ) ) || $is_banner_active;

		if ( $should_load_assets ) {
			wp_enqueue_style( 'wp-pdpa-cs-public-styles', WPPCS_URL . 'assets/css/public-styles.css', [], WPPCS_VERSION );
			if ( $is_banner_active ) {
				wp_enqueue_script( 'wp-pdpa-cs-public-scripts', WPPCS_URL . 'assets/js/public-scripts.js', [], WPPCS_VERSION, true );

				// Pass PHP data to our JavaScript file
				wp_localize_script(
					'wp-pdpa-cs-public-scripts',
					'wppcs_ajax',
					[
						'ajax_url' => admin_url( 'admin-ajax.php' ),
						'nonce'    => wp_create_nonce( 'wppcs_consent_nonce' ),
					]
				);
			}
		}
	}

	/**
	 * AJAX handler for logging user consent.
	 */
	public function handle_ajax_log_consent() {
		check_ajax_referer( 'wppcs_consent_nonce', 'nonce' );

		$consent_type = isset( $_POST['consent_type'] ) ? sanitize_key( $_POST['consent_type'] ) : 'unknown';
		$consent_details_raw = isset( $_POST['consent_details'] ) ? stripslashes( $_POST['consent_details'] ) : '{}';
        
        json_decode( $consent_details_raw );
        $consent_details = (json_last_error() === JSON_ERROR_NONE) ? $consent_details_raw : '{}';

		global $wpdb;
		$table_name = $wpdb->prefix . 'pdpa_consent_logs';
		$user_id = get_current_user_id();
		$guest_id = ( $user_id === 0 ) ? 'guest_' . hash( 'sha256', $_SERVER['REMOTE_ADDR'] . $_SERVER['HTTP_USER_AGENT'] ) : '';

		$wpdb->insert(
			$table_name,
			[
				'user_id'          => $user_id,
				'guest_identifier' => $guest_id,
				'ip_address'       => $this->get_anonymized_ip(),
				'consent_type'     => $consent_type,
				'consent_details'  => $consent_details,
				'created_at'       => current_time( 'mysql', 1 ),
			],
			[ '%d', '%s', '%s', '%s', '%s', '%s' ]
		);

		wp_send_json_success( [ 'message' => 'Consent logged.' ] );
	}
    
    private function get_anonymized_ip(): string {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        return preg_replace( '/\.\d+$/', '.0', $ip );
    }

	/**
	 * Renders BOTH the Cookie Consent Banner and the Settings Modal in the footer.
	 */
	public function render_cookie_banner_and_modal() {
		$options = get_option( 'wppcs_settings', [] );
		$is_enabled = $options['enable_banner'] ?? 'on';

		if ( 'on' !== $is_enabled || isset( $_COOKIE['wppcs_consent_given'] ) ) {
			return;
		}

		$this->get_template( 'cookie-banner' );
		$this->get_template( 'cookie-settings-modal' );
	}

	/**
	 * Renders the DSAR form shortcode.
	 */
	public function render_dsar_form() {
		$this->handle_form_submission();
		ob_start();
		if ( ! empty( $this->form_errors ) ) {
			echo '<div class="wppcs-alert wppcs-alert-error">' . implode( '<br>', $this->form_errors ) . '</div>';
		}
		$this->get_template( 'dsar-form' );
		return ob_get_clean();
	}

	/**
	 * Handles the logic for when the DSAR form is submitted. (This is the function that was missing)
	 */
	private function handle_form_submission() {
        if ( 'POST' !== $_SERVER['REQUEST_METHOD'] || empty( $_POST['wppcs_action'] ) || 'submit_dsar' !== $_POST['wppcs_action'] ) {
			return;
		}

		if ( ! isset( $_POST['wppcs_nonce'] ) || ! wp_verify_nonce( $_POST['wppcs_nonce'], 'wppcs_submit_dsar_nonce' ) ) {
			$this->form_errors[] = __( 'Security check failed. Please try again.', 'wp-pdpa-cs' );
			return;
		}

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

		$attachment_path = '';
		if ( isset( $_FILES['wppcs_attachment'] ) && ! empty( $_FILES['wppcs_attachment']['name'] ) ) {
			if ( ! function_exists( 'wp_handle_upload' ) ) {
				require_once ABSPATH . 'wp-admin/includes/file.php';
			}
			$uploaded_file = $_FILES['wppcs_attachment'];
			$upload_overrides = [ 'test_form' => false ];
			$movefile = wp_handle_upload( $uploaded_file, $upload_overrides );

			if ( $movefile && ! isset( $movefile['error'] ) ) {
				$attachment_path = $movefile['file'];
			} else {
				$this->form_errors[] = __( 'File upload error:', 'wp-pdpa-cs' ) . ' ' . $movefile['error'];
				return;
			}
		}

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
				'request_status'  => 'new',
				'created_at'      => current_time( 'mysql', 1 ),
			],
			[ '%s', '%s', '%s', '%s', '%s', '%s', '%s' ]
		);

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
	public function get_template( $template_name, $args = [] ) {
        if ( isset( $_GET['request_success'] ) && '1' === $_GET['request_success'] && 'dsar-form' === $template_name ) {
			echo '<div class="wppcs-alert wppcs-alert-success">' . esc_html__( 'Thank you! Your request has been submitted successfully.', 'wp-pdpa-cs' ) . '</div>';
			return;
		}

		if ( ! empty( $args ) && is_array( $args ) ) {
			extract( $args );
		}
		
		$theme_template = get_stylesheet_directory() . '/wp-pdpa-cs/' . $template_name . '.php';
		if ( file_exists( $theme_template ) ) { load_template( $theme_template, false, $args ); return; }
		$plugin_template = WPPCS_PATH . 'templates/' . $template_name . '.php';
		if ( file_exists( $plugin_template ) ) { load_template( $plugin_template, false, $args ); }
	}
}