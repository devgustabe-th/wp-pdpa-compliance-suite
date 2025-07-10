<?php
// NEW SIMPLIFIED VERSION of class-settings.php
namespace WP_PDPA_CS;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Settings {

    const OPTION_GROUP = 'wppcs_settings_group';
    const OPTION_NAME = 'wppcs_settings';

    public function __construct() {
        add_action( 'admin_init', [ $this, 'register_settings' ] );
    }

    public function register_settings() {
        // Register one single setting that will store all our options as an array.
        register_setting(
            self::OPTION_GROUP,
            self::OPTION_NAME,
            [ $this, 'sanitize_settings' ]
        );

        // Add a single section to our settings page.
        add_settings_section(
            'wppcs_banner_section',
            __( 'Cookie Consent Banner', 'wp-pdpa-cs' ),
            '__return_false', // No description for the section itself.
            self::OPTION_GROUP
        );

        // Add the fields to our section.
        add_settings_field(
            'enable_banner',
            __( 'Enable Cookie Banner', 'wp-pdpa-cs' ),
            [ $this, 'render_enable_banner_field' ],
            self::OPTION_GROUP,
            'wppcs_banner_section'
        );
        add_settings_field(
            'banner_title',
            __( 'Banner Title', 'wp-pdpa-cs' ),
            [ $this, 'render_banner_title_field' ],
            self::OPTION_GROUP,
            'wppcs_banner_section'
        );
        add_settings_field(
            'banner_description',
            __( 'Banner Description', 'wp-pdpa-cs' ),
            [ $this, 'render_banner_description_field' ],
            self::OPTION_GROUP,
            'wppcs_banner_section'
        );
    }

    public function sanitize_settings( $input ) {
        $new_input = [];
        if ( isset( $input['enable_banner'] ) ) {
            $new_input['enable_banner'] = 'on';
        }
        if ( isset( $input['banner_title'] ) ) {
            $new_input['banner_title'] = sanitize_text_field( $input['banner_title'] );
        }
        if ( isset( $input['banner_description'] ) ) {
            $new_input['banner_description'] = sanitize_textarea_field( $input['banner_description'] );
        }
        return $new_input;
    }
    
    // --- Field Rendering Callbacks ---

    public function render_enable_banner_field() {
        $options = get_option( self::OPTION_NAME, [] );
        $checked = isset( $options['enable_banner'] ) ? 'checked' : '';
        echo '<input type="checkbox" name="' . self::OPTION_NAME . '[enable_banner]" ' . $checked . ' />';
        echo '<p class="description">' . esc_html__('Enable to show the cookie consent banner on the frontend.', 'wp-pdpa-cs') . '</p>';
    }

    public function render_banner_title_field() {
        $options = get_option( self::OPTION_NAME, [] );
        $value = isset( $options['banner_title'] ) ? $options['banner_title'] : 'We Value Your Privacy';
        echo '<input type="text" class="regular-text" name="' . self::OPTION_NAME . '[banner_title]" value="' . esc_attr( $value ) . '">';
    }

    public function render_banner_description_field() {
        $options = get_option( self::OPTION_NAME, [] );
        $value = isset( $options['banner_description'] ) ? $options['banner_description'] : 'We use cookies to enhance your Browse experience...';
        echo '<textarea rows="5" class="large-text" name="' . self::OPTION_NAME . '[banner_description]">' . esc_textarea( $value ) . '</textarea>';
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
                // Output necessary hidden fields
                settings_fields( self::OPTION_GROUP );
                // Output the fields for our sections
                do_settings_sections( self::OPTION_GROUP );
                // Output the save button
                submit_button( 'Save Settings' );
                ?>
            </form>
        </div>
        <?php
    }
}