<?php
// กำหนด Namespace เพื่อป้องกันชื่อคลาสซ้ำกับปลั๊กอินอื่น
namespace WP_PDPA_CS;

// ป้องกันการเข้าถึงไฟล์โดยตรง
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * คลาสสำหรับจัดการส่วนหน้าบ้านทั้งหมด (Frontend)
 */
class Frontend {

	/**
	 * ตัวแปรสำหรับเก็บข้อความ Error ของฟอร์ม (สำหรับ DSAR Form)
	 * @var array
	 */
	private $form_errors = [];

	/**
	 * Constructor ของคลาส จะทำงานทันทีเมื่อคลาสถูกเรียกใช้
	 */
	public function __construct() {
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_frontend_assets' ] );
		add_shortcode( 'pdpa_dsar_form', [ $this, 'render_dsar_form' ] );
		add_action( 'wp_footer', [ $this, 'render_footer_elements' ] );
		add_action( 'wp_head', [ $this, 'inject_managed_scripts' ] );
		add_action( 'wp_ajax_wppcs_log_consent', [ $this, 'handle_ajax_log_consent' ] );
		add_action( 'wp_ajax_nopriv_wppcs_log_consent', [ $this, 'handle_ajax_log_consent' ] );
		add_action( 'wp_ajax_wppcs_register_user', [ $this, 'handle_ajax_registration' ] );
		add_action( 'wp_ajax_nopriv_wppcs_register_user', [ $this, 'handle_ajax_registration' ] );
		add_action( 'woocommerce_register_form', [ $this, 'add_privacy_checkbox_to_register_form' ] );
		add_action( 'user_register', [ $this, 'log_registration_consent' ], 10, 1 );
	}

	/**
	 * ฟังก์ชันสำหรับโหลดไฟล์ CSS และ JS ที่จำเป็นสำหรับหน้าบ้าน (ฉบับแก้ไขที่ถูกต้อง)
	 */
	public function enqueue_frontend_assets() {
		global $post;
		$options = get_option( 'wppcs_settings', [] );
		$banner_enabled = $options['enable_banner'] ?? 'on';
		$is_banner_active = 'on' === $banner_enabled && ! isset( $_COOKIE['wppcs_consent_given'] );
		
		// --- THE KEY FIX IS HERE ---
		// เราจะตรวจสอบว่าหน้าปัจจุบันใช้ Page Template ที่คุณสร้างขึ้นหรือไม่
		$is_custom_login_page = is_page_template('page-templates/custom-woo-login-register.php');
		
		// เงื่อนไขใหม่: โหลด Assets ถ้ามี Banner, หรือเป็นหน้า Custom Login, หรือมี DSAR Form
		$should_load_assets = $is_banner_active || $is_custom_login_page || ( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'pdpa_dsar_form' ) );

		if ( $should_load_assets ) {
			// โหลด Dashicons เพื่อให้แน่ใจว่าไอคอนแสดงผล
            wp_enqueue_style( 'dashicons' );
			// โหลดไฟล์ CSS หลัก
			wp_enqueue_style( 'wp-pdpa-cs-public-styles', WPPCS_URL . 'assets/css/public-styles.css', ['dashicons'], WPPCS_VERSION );
			
			// โหลดไฟล์ JavaScript หลัก
			wp_enqueue_script( 'wp-pdpa-cs-public-scripts', WPPCS_URL . 'assets/js/public-scripts.js', ['jquery'], WPPCS_VERSION, true );

			// ส่งข้อมูลจาก PHP ไปให้ JavaScript ใช้งาน (สำคัญมากสำหรับ AJAX)
			wp_localize_script(
				'wp-pdpa-cs-public-scripts',
				'wppcs_ajax',
				[
					'ajax_url'       => admin_url( 'admin-ajax.php' ),
					'consent_nonce'  => wp_create_nonce( 'wppcs_consent_nonce' ),
					'register_nonce' => wp_create_nonce( 'wppcs_register_nonce' ), // Nonce สำหรับฟอร์มสมัครสมาชิก
				]
			);
		}
	}

	/**
	 * ตัวจัดการการสมัครสมาชิกผ่าน AJAX
	 */
	public function handle_ajax_registration() {
		check_ajax_referer( 'wppcs_register_nonce', 'security' );
		$errors = new \WP_Error();
		$username = ! empty( $_POST['username'] ) ? sanitize_user( $_POST['username'], true ) : '';
		$email    = ! empty( $_POST['email'] ) ? sanitize_email( $_POST['email'] ) : '';
		$password = ! empty( $_POST['password'] ) ? $_POST['password'] : '';
		$options = get_option( 'wppcs_settings', [] );
		if ( ! empty( $options['enable_register_consent'] ) && 'on' === $options['enable_register_consent'] ) {
			if ( empty( $_POST['wppcs_privacy_consent'] ) ) {
				$error_message = $this->get_translated_string( 'register_error_message', $options['register_error_message'] ?? __( 'You must accept the privacy policy to register.', 'wp-pdpa-cs' ) );
				$errors->add( 'privacy_policy_required', $error_message );
			}
		}
		do_action( 'woocommerce_register_post', $username, $email, $errors );
		$errors = apply_filters( 'woocommerce_registration_errors', $errors, $username, $email );
		if ( $errors->has_errors() ) {
			wp_send_json_error( [ 'messages' => $errors->get_error_messages() ] );
		}
		$new_customer_id = wc_create_new_customer( $email, $username, $password );
		if ( is_wp_error( $new_customer_id ) ) {
			wp_send_json_error( [ 'messages' => $new_customer_id->get_error_messages() ] );
		}
		wp_set_current_user( $new_customer_id );
		wp_set_auth_cookie( $new_customer_id );
		wp_send_json_success( [ 'redirect_url' => wc_get_page_permalink( 'myaccount' ) ] );
	}
    
	/**
	 * แสดงผล Element ทั้งหมดที่ส่วนท้ายของเว็บ
	 */
    public function render_footer_elements() {
        $this->render_cookie_banner_and_modal();
        $this->get_template('error-notice-modal');
    }

	/**
	 * เพิ่ม Checkbox ยอมรับนโยบายในฟอร์มสมัครสมาชิก
	 */
	public function add_privacy_checkbox_to_register_form() {
		$options = get_option( 'wppcs_settings', [] );
		if ( empty( $options['enable_register_consent'] ) || 'on' !== $options['enable_register_consent'] ) {
			return;
		}
		$default_label = 'I have read and agree to the [privacy_policy].';
		$label_text = $this->get_translated_string( 'register_consent_label', $options['register_consent_label'] ?? $default_label );
		$privacy_policy_url = get_privacy_policy_url();
		$privacy_link = $privacy_policy_url ? sprintf( '<a href="%s" target="_blank">%s</a>', esc_url( $privacy_policy_url ), __( 'Privacy Policy', 'wp-pdpa-cs' ) ) : __( 'Privacy Policy', 'wp-pdpa-cs' );
		$final_label_with_link = str_replace( '[privacy_policy]', $privacy_link, $label_text );
		?>
		<p class="form-row validate-required" id="wppcs_privacy_policy_field" style="margin: 15px 0;">
			<label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox">
				<input type="checkbox" class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" name="wppcs_privacy_consent" id="wppcs_privacy_consent" />
				<span class="woocommerce-terms-and-conditions-checkbox-text"><?php echo wp_kses_post( $final_label_with_link ); ?></span>&nbsp;<span class="required">*</span>
			</label>
		</p>
		<?php
	}

	/**
	 * บันทึก Log การให้ความยินยอม หลังจากสมัครสมาชิกสำเร็จ
	 */
	public function log_registration_consent( $user_id ) {
		if ( ! isset( $_POST['wppcs_privacy_consent'] ) ) { return; }
		global $wpdb;
		$table_name = $wpdb->prefix . 'pdpa_consent_logs';
		$wpdb->insert( $table_name, [ 'user_id' => $user_id, 'guest_identifier' => '', 'ip_address' => $this->get_anonymized_ip(), 'consent_type' => 'registration_privacy_policy', 'consent_details' => '{"privacy_policy":true}', 'created_at' => current_time( 'mysql', 1 ), ], [ '%d', '%s', '%s', '%s', '%s', '%s' ] );
	}
	
	public function inject_managed_scripts() {
		if ( ! isset( $_COOKIE['wppcs_consent_given'] ) ) { return; }
		$consent_data_raw = stripslashes( $_COOKIE['wppcs_consent_given'] );
		$consent_data = json_decode( $consent_data_raw, true );
		global $wpdb;
		$table_name = $wpdb->prefix . 'pdpa_managed_scripts';
		$active_scripts = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table_name} WHERE status = %s", 'active' ) );
		if ( empty( $active_scripts ) ) { return; }
		foreach ( $active_scripts as $script ) {
			$category = $script->cookie_category;
			$should_inject = ( $consent_data === 'all' || ( is_array( $consent_data ) && ! empty( $consent_data[ $category ] ) && $consent_data[ $category ] === true ) );
			if ( $should_inject ) {
				$this->render_script_template( $script->service_name, $script->tracking_id );
			}
		}
	}

	private function render_script_template( $service_name, $tracking_id ) {
		switch ( strtolower( $service_name ) ) {
			case 'google analytics':
				?>
                <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo esc_attr( $tracking_id ); ?>"></script>
                <script> window.dataLayer = window.dataLayer || []; function gtag(){dataLayer.push(arguments);} gtag('js', new Date()); gtag('config', '<?php echo esc_attr( $tracking_id ); ?>'); </script>
				<?php
				break;
			case 'facebook pixel':
				?>
                <script>
                !function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window, document,'script','https://connect.facebook.net/en_US/fbevents.js');
                fbq('init', '<?php echo esc_attr( $tracking_id ); ?>');
                fbq('track', 'PageView');
                </script>
                <noscript><img height="1" width="1" style="display:none" src="https://www.facebook.com/tr?id=<?php echo esc_attr( $tracking_id ); ?>&ev=PageView&noscript=1"/></noscript>
				<?php
				break;
		}
	}

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
		$wpdb->insert( $table_name, [ 'user_id' => $user_id, 'guest_identifier' => $guest_id, 'ip_address' => $this->get_anonymized_ip(), 'consent_type' => $consent_type, 'consent_details' => $consent_details, 'created_at' => current_time( 'mysql', 1 ), ], [ '%d', '%s', '%s', '%s', '%s', '%s' ] );
		wp_send_json_success( [ 'message' => 'Consent logged.' ] );
	}
	
	private function get_anonymized_ip(): string {
		$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
		return preg_replace( '/\.\d+$/', '.0', $ip );
	}

	public function render_cookie_banner_and_modal() {
		$options = get_option( 'wppcs_settings', [] );
		$is_enabled = $options['enable_banner'] ?? 'on';
		if ( 'on' !== $is_enabled || isset( $_COOKIE['wppcs_consent_given'] ) ) {
			return;
		}
		$template_args = [
			'title'       => $this->get_translated_string( 'banner_title', $options['banner_title'] ?? 'We Value Your Privacy' ),
			'description' => $this->get_translated_string( 'banner_description', $options['banner_description'] ?? 'We use cookies...' ),
			'button_text' => $this->get_translated_string( 'accept_button_text', $options['accept_button_text'] ?? 'Accept All' ),
		];
		$this->get_template( 'cookie-banner', $template_args );
		$this->get_template( 'cookie-settings-modal' );
	}

	public function render_dsar_form() {
		$this->handle_form_submission();
		ob_start();
		if ( ! empty( $this->form_errors ) ) {
			echo '<div class="wppcs-alert wppcs-alert-error">' . implode( '<br>', $this->form_errors ) . '</div>';
		}
		$this->get_template( 'dsar-form' );
		return ob_get_clean();
	}

	private function handle_form_submission() {
        if ( 'POST' !== $_SERVER['REQUEST_METHOD'] || empty( $_POST['wppcs_action'] ) || 'submit_dsar' !== $_POST['wppcs_action'] ) { return; }
		if ( ! isset( $_POST['wppcs_nonce'] ) || ! wp_verify_nonce( $_POST['wppcs_nonce'], 'wppcs_submit_dsar_nonce' ) ) {
			$this->form_errors[] = __( 'Security check failed. Please try again.', 'wp-pdpa-cs' );
			return;
		}
		$name = isset( $_POST['wppcs_name'] ) ? sanitize_text_field( $_POST['wppcs_name'] ) : '';
		$email = isset( $_POST['wppcs_email'] ) ? sanitize_email( $_POST['wppcs_email'] ) : '';
		$request_type = isset( $_POST['wppcs_request_type'] ) ? sanitize_key( $_POST['wppcs_request_type'] ) : '';
		$details = isset( $_POST['wppcs_details'] ) ? sanitize_textarea_field( $_POST['wppcs_details'] ) : '';
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
			if ( ! function_exists( 'wp_handle_upload' ) ) { require_once ABSPATH . 'wp-admin/includes/file.php'; }
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
		$result = $wpdb->insert( $table_name, [ 'requester_name' => $name, 'requester_email' => $email, 'request_type' => $request_type, 'request_details' => $details, 'attachment_path' => $attachment_path, 'request_status' => 'new', 'created_at' => current_time( 'mysql', 1 ), ], [ '%s', '%s', '%s', '%s', '%s', '%s', '%s' ] );
		if ( $result ) {
			wp_redirect( add_query_arg( 'request_success', '1', home_url( add_query_arg( null, null ) ) ) );
			exit;
		} else {
			$this->form_errors[] = __( 'Could not save your request. Please try again later.', 'wp-pdpa-cs' );
		}
	}
    
    private function get_translated_string( string $name, string $default_value ): string {
        $context = 'wp-pdpa-cs';
        $string_name_wpml = $name . '_string_for_wpml';
        $translated = apply_filters( 'wpml_translate_single_string', $default_value, $context, $string_name_wpml );
        if ( function_exists('pll__') && ( ! isset($translated) || $translated === $default_value ) ) {
            $translated = pll__( $default_value );
        }
        return $translated;
    }

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