<?php
namespace WP_PDPA_CS;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Main {

	private static $instance = null;

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		$this->includes();
		$this->init_hooks();
	}

	private function includes() {
		require_once WPPCS_PATH . 'includes/class-db.php';
		require_once WPPCS_PATH . 'includes/class-admin-menu.php';
		require_once WPPCS_PATH . 'includes/class-frontend.php';
        require_once WPPCS_PATH . 'includes/class-settings.php';
        require_once WPPCS_PATH . 'includes/class-cookie-manager.php';
        require_once WPPCS_PATH . 'includes/class-managed-scripts-list-table.php'; // <-- ADDED
	}

	private function init_hooks() {
		new Admin_Menu();
		new Frontend();
        new Settings();
        new Cookie_Manager();
	}
}