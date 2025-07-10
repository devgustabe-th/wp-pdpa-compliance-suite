<?php
/**
 * The template for the Cookie Settings Modal.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div id="wppcs-settings-modal-overlay" class="wppcs-modal-overlay wppcs-modal-hidden">
    <div id="wppcs-settings-modal" class="wppcs-modal">
        <div class="wppcs-modal-header">
            <h2><?php esc_html_e( 'Privacy Settings', 'wp-pdpa-cs' ); ?></h2>
            <button id="wppcs-close-modal" class="wppcs-modal-close">&times;</button>
        </div>
        <div class="wppcs-modal-body">
            <div class="wppcs-setting-item">
                <div class="wppcs-setting-info">
                    <strong><?php esc_html_e( 'Strictly Necessary Cookies', 'wp-pdpa-cs' ); ?></strong>
                    <p><?php esc_html_e( 'These cookies are essential for the website to function and cannot be switched off.', 'wp-pdpa-cs' ); ?></p>
                </div>
                <div class="wppcs-setting-toggle">
                    <label class="wppcs-switch always-on">
                        <input type="checkbox" checked disabled>
                        <span class="wppcs-slider round"></span>
                    </label>
                </div>
            </div>
            <div class="wppcs-setting-item">
                <div class="wppcs-setting-info">
                    <strong><?php esc_html_e( 'Analytics Cookies', 'wp-pdpa-cs' ); ?></strong>
                    <p><?php esc_html_e( 'These cookies allow us to count visits and traffic sources so we can measure and improve the performance of our site.', 'wp-pdpa-cs' ); ?></p>
                </div>
                <div class="wppcs-setting-toggle">
                    <label class="wppcs-switch">
                        <input type="checkbox" id="wppcs-consent-analytics" checked>
                        <span class="wppcs-slider round"></span>
                    </label>
                </div>
            </div>
            <div class="wppcs-setting-item">
                <div class="wppcs-setting-info">
                    <strong><?php esc_html_e( 'Marketing Cookies', 'wp-pdpa-cs' ); ?></strong>
                    <p><?php esc_html_e( 'These cookies may be set through our site by our advertising partners to build a profile of your interests.', 'wp-pdpa-cs' ); ?></p>
                </div>
                <div class="wppcs-setting-toggle">
                    <label class="wppcs-switch">
                        <input type="checkbox" id="wppcs-consent-marketing" checked>
                        <span class="wppcs-slider round"></span>
                    </label>
                </div>
            </div>
        </div>
        <div class="wppcs-modal-footer">
            <button id="wppcs-save-settings" class="wppcs-button"><?php esc_html_e( 'Save My Choices', 'wp-pdpa-cs' ); ?></button>
        </div>
    </div>
</div>