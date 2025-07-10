<?php
namespace WP_PDPA_CS;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles the Cookie Script Manager admin page.
 */
class Cookie_Manager {

    /**
     * Constructor
     */
	public function __construct() {
		// Actions for this page will be added here in the future.
	}

    /**
     * Renders the main page for the Cookie Manager.
     */
	public function render_page() {
		?>
		<div class="wrap wp-pdpa-cs-wrap">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'Cookie Script Manager', 'wp-pdpa-cs' ); ?></h1>
			<a href="#" class="page-title-action"><?php esc_html_e( 'Add New Script', 'wp-pdpa-cs' ); ?></a>
			<hr class="wp-header-end">

			<p><?php esc_html_e( 'Here you can manage tracking scripts from services like Google Analytics and Facebook Pixel. These scripts will only be loaded on the frontend if the user gives consent to the corresponding cookie category.', 'wp-pdpa-cs' ); ?></p>
            
            <p><em>(<?php esc_html_e( 'The list of managed scripts will appear here.', 'wp-pdpa-cs' ); ?>)</em></p>
		</div>
		<?php
	}
}