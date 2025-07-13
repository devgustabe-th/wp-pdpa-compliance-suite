<?php
namespace WP_PDPA_CS;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Settings {

    const OPTION_GROUP = 'wppcs_settings_group';
    const OPTION_NAME = 'wppcs_settings';
    const TEXT_DOMAIN = 'wp-pdpa-cs'; // Define text domain for translation context

    public function __construct() {
        add_action( 'admin_init', [ $this, 'register_settings' ] );
    }

    public function register_settings() {
        register_setting(
            self::OPTION_GROUP,
            self::OPTION_NAME,
            [ $this, 'sanitize_settings' ]
        );

        // Section 1: Cookie Banner
        add_settings_section( 'wppcs_banner_section', __( 'Cookie Consent Banner', 'wp-pdpa-cs' ), '__return_false', self::OPTION_GROUP );
        add_settings_field('enable_banner', __( 'Enable Cookie Banner', 'wp-pdpa-cs' ), [ $this, 'render_field' ], self::OPTION_GROUP, 'wppcs_banner_section', [ 'type' => 'checkbox', 'id' => 'enable_banner', 'desc' => __('Enable to show the cookie consent banner on the frontend.', 'wp-pdpa-cs') ] );
        add_settings_field('banner_title', __( 'Banner Title', 'wp-pdpa-cs' ), [ $this, 'render_field' ], self::OPTION_GROUP, 'wppcs_banner_section', [ 'type' => 'text', 'id' => 'banner_title', 'default' => 'We Value Your Privacy' ] );
        add_settings_field('banner_description', __( 'Banner Description', 'wp-pdpa-cs' ), [ $this, 'render_field' ], self::OPTION_GROUP, 'wppcs_banner_section', [ 'type' => 'textarea', 'id' => 'banner_description', 'default' => 'We use cookies to enhance your Browse experience...' ] );
        add_settings_field('accept_button_text', __( 'Accept Button Text', 'wp-pdpa-cs' ), [ $this, 'render_field' ], self::OPTION_GROUP, 'wppcs_banner_section', [ 'type' => 'text', 'id' => 'accept_button_text', 'default' => 'Accept All' ] );

        // Section 2: Integrations
        add_settings_section( 'wppcs_integrations_section', __( 'Integrations', 'wp-pdpa-cs' ), '__return_false', self::OPTION_GROUP );
        add_settings_field('enable_register_consent', __( 'Registration Page Consent', 'wp-pdpa-cs' ), [ $this, 'render_field' ], self::OPTION_GROUP, 'wppcs_integrations_section', [ 'type' => 'checkbox', 'id' => 'enable_register_consent', 'desc' => __('Add a mandatory privacy policy checkbox to the WordPress registration form.', 'wp-pdpa-cs') ] );
        add_settings_field('register_consent_label', __( 'Checkbox Label', 'wp-pdpa-cs' ), [ $this, 'render_field' ], self::OPTION_GROUP, 'wppcs_integrations_section', [ 'type' => 'textarea', 'id' => 'register_consent_label', 'desc' => 'Use the <code>[privacy_policy]</code> shortcode to automatically link to your privacy policy page.', 'default' => 'I have read and agree to the [privacy_policy].' ] );
        add_settings_field('register_error_message', __( 'Error Message', 'wp-pdpa-cs' ), [ $this, 'render_field' ], self::OPTION_GROUP, 'wppcs_integrations_section', [ 'type' => 'text', 'id' => 'register_error_message', 'desc' => 'The error shown if the checkbox is not ticked.', 'default' => 'You must accept the privacy policy to register.' ] );
    }

    /**
     * Sanitize and register strings for translation.
     */
    public function sanitize_settings( $input ) {
        $new_input = [];
        $translatable_fields = [
            'banner_title',
            'banner_description',
            'accept_button_text',
            'register_consent_label',
            'register_error_message'
        ];

        // Sanitize checkboxes
        $new_input['enable_banner'] = isset( $input['enable_banner'] ) ? 'on' : '';
        $new_input['enable_register_consent'] = isset( $input['enable_register_consent'] ) ? 'on' : '';
        
        // Sanitize and register translatable fields
        foreach ( $translatable_fields as $field_name ) {
            if ( isset( $input[ $field_name ] ) ) {
                $sanitized_value = ('banner_description' === $field_name || 'register_consent_label' === $field_name)
                    ? sanitize_textarea_field( $input[ $field_name ] )
                    : sanitize_text_field( $input[ $field_name ] );
                
                $new_input[ $field_name ] = $sanitized_value;

                // --- THIS IS THE KEY PART FOR MULTILINGUAL SUPPORT ---
                if ( function_exists( 'pll_register_string' ) ) {
                    pll_register_string( $field_name, $sanitized_value, self::TEXT_DOMAIN, ('banner_description' === $field_name || 'register_consent_label' === $field_name) );
                }
                if ( function_exists( 'icl_register_string' ) ) {
                    icl_register_string( self::TEXT_DOMAIN, $field_name . '_string_for_wpml', $sanitized_value );
                }
                 do_action( 'wpml_register_single_string', self::TEXT_DOMAIN, $field_name . '_string_for_wpml', $sanitized_value );
            }
        }

        return $new_input;
    }
    
    /**
     * Re-factored function to render any field type.
     */
    public function render_field( $args ) {
        $options = get_option( self::OPTION_NAME, [] );
        $value = $options[ $args['id'] ] ?? $args['default'] ?? '';
        $name_attr = self::OPTION_NAME . '[' . $args['id'] . ']';
        $description = $args['desc'] ?? '';

        switch ( $args['type'] ) {
            case 'textarea':
                echo '<textarea rows="5" class="large-text" name="' . esc_attr($name_attr) . '">' . esc_textarea( $value ) . '</textarea>';
                break;
            case 'checkbox':
                echo '<input type="checkbox" name="' . esc_attr($name_attr) . '" ' . checked( $value, 'on', false ) . ' />';
                if (!empty($description)) {
                    // Use wp_kses_post to allow <code> tag in description
                    echo ' <label for="'.esc_attr($name_attr).'">' . wp_kses_post($description) . '</label>';
                }
                return; // No extra description paragraph for checkboxes
            case 'text':
            default:
                echo '<input type="text" class="regular-text" name="' . esc_attr($name_attr) . '" value="' . esc_attr( $value ) . '">';
                break;
        }

        if ( ! empty( $description ) ) {
            // Use wp_kses_post to allow <code> tag in description
            printf( '<p class="description">%s</p>', wp_kses_post( $description ) );
        }
    }

    /**
     * Renders the settings page form.
     */
    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'PDPA Compliance Settings', 'wp-pdpa-cs' ); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields( self::OPTION_GROUP );
                do_settings_sections( self::OPTION_GROUP );
                submit_button( __( 'Save Settings', 'wp-pdpa-cs' ) );
                ?>
            </form>
        </div>
        <?php
    }
}