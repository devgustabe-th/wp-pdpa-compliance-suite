<?php
/**
 * Template for the Error Notice Popup Modal.
 * This is hidden by default and shown by JavaScript when an error occurs.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>
<div id="wppcs-error-modal-overlay" class="wppcs-modal-overlay wppcs-modal-hidden">
    <div id="wppcs-error-modal" class="wppcs-modal wppcs-error-modal">
        <div class="wppcs-modal-header">
            <span class="wppcs-error-icon dashicons dashicons-warning"></span>
            <h2><?php esc_html_e( 'An Error Occurred', 'wp-pdpa-cs' ); ?></h2>
            <button id="wppcs-close-error-modal" class="wppcs-modal-close">&times;</button>
        </div>
        <div class="wppcs-modal-body">
            <p id="wppcs-error-message"></p>
        </div>
    </div>
</div>