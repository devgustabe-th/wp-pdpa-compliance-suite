<?php
/**
 * The template for displaying the Cookie Consent Banner.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$options = get_option( 'wppcs_settings', [] );
$title   = $options['banner_title'] ?? __( 'We Value Your Privacy', 'wp-pdpa-cs' );
$desc    = $options['banner_description'] ?? __( 'We use cookies to enhance your Browse experience...', 'wp-pdpa-cs' );
$button_text = $options['accept_button_text'] ?? __( 'Accept All', 'wp-pdpa-cs' );

$privacy_policy_url = '#'; 

?>
<div id="wppcs-cookie-banner" class="wppcs-cookie-banner">
    <div class="wppcs-banner-content">
        <h3 class="wppcs-banner-title"><?php echo esc_html( $title ); ?></h3>
        <p class="wppcs-banner-description">
            <?php echo esc_html( $desc ); ?>
            <a href="<?php echo esc_url( $privacy_policy_url ); ?>" class="wppcs-privacy-link"><?php esc_html_e( 'Privacy Policy', 'wp-pdpa-cs' ); ?></a>
        </p>
    </div>
    <div class="wppcs-banner-actions">
        <button id="wppcs-open-settings" class="wppcs-button wppcs-button-secondary"><?php esc_html_e( 'Settings', 'wp-pdpa-cs' ); ?></button>
        <button id="wppcs-accept-all" class="wppcs-button"><?php echo esc_html( $button_text ); ?></button>
    </div>
</div>

<?php
// THE MISTAKE WAS HERE. THIS LINE IS NOW REMOVED.
// The modal is now loaded from the main class.
?>