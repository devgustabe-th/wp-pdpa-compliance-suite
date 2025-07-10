<?php
/**
 * The template for displaying the Data Subject Access Request (DSAR) form.
 *
 * This template can be overridden by copying it to
 * /yourtheme/wp-pdpa-cs/dsar-form.php
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>

<form id="wppcs-dsar-form" class="wppcs-form" method="post" enctype="multipart/form-data">
	
	<div class="wppcs-form-row">
		<label for="wppcs_name"><?php esc_html_e( 'Full Name', 'wp-pdpa-cs' ); ?> <span class="required">*</span></label>
		<input type="text" id="wppcs_name" name="wppcs_name" required>
	</div>

	<div class="wppcs-form-row">
		<label for="wppcs_email"><?php esc_html_e( 'Email Address', 'wp-pdpa-cs' ); ?> <span class="required">*</span></label>
		<input type="email" id="wppcs_email" name="wppcs_email" required>
	</div>

	<div class="wppcs-form-row">
		<label for="wppcs_request_type"><?php esc_html_e( 'Request Type', 'wp-pdpa-cs' ); ?> <span class="required">*</span></label>
		<select id="wppcs_request_type" name="wppcs_request_type" required>
			<option value=""><?php esc_html_e( '-- Select a request type --', 'wp-pdpa-cs' ); ?></option>
			<option value="access"><?php esc_html_e( 'Request to Access My Data', 'wp-pdpa-cs' ); ?></option>
			<option value="rectify"><?php esc_html_e( 'Request to Correct My Data', 'wp-pdpa-cs' ); ?></option>
			<option value="erasure"><?php esc_html_e( 'Request to Erase My Data (Right to be Forgotten)', 'wp-pdpa-cs' ); ?></option>
			<option value="object"><?php esc_html_e( 'Object to Data Processing', 'wp-pdpa-cs' ); ?></option>
		</select>
	</div>

	<div class="wppcs-form-row">
		<label for="wppcs_details"><?php esc_html_e( 'Request Details', 'wp-pdpa-cs' ); ?></label>
		<textarea id="wppcs_details" name="wppcs_details" rows="5"></textarea>
	</div>
    
    <div class="wppcs-form-row">
		<label for="wppcs_attachment"><?php esc_html_e( 'Identity Verification File (Optional)', 'wp-pdpa-cs' ); ?></label>
        <input type="file" id="wppcs_attachment" name="wppcs_attachment">
        <small class="description"><?php esc_html_e( 'Please upload a file to help us verify your identity (e.g., a redacted ID card). Max file size: 2MB.', 'wp-pdpa-cs' ); ?></small>
	</div>

	<div class="wppcs-form-row">
		<input type="hidden" name="wppcs_action" value="submit_dsar">
		<?php wp_nonce_field( 'wppcs_submit_dsar_nonce', 'wppcs_nonce' ); ?>
		<button type="submit" class="wppcs-button"><?php esc_html_e( 'Submit Request', 'wp-pdpa-cs' ); ?></button>
	</div>

</form>