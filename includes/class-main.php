<?php
namespace WP_PDPA_CS;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The main plugin class.
 */
final class Main {

	/**
	 * The single instance of the class.
	 * @var Main|null
	 */
	private static $instance = null;

	/**
	 * Ensures only one instance of the class is loaded.
	 * @return Main
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor.
	 */
	private function __construct() {
		$this->includes();
		$this->init_hooks();
	}

	/**
	 * Include required files.
	 */
	private function includes() {
		require_once WPPCS_PATH . 'includes/class-db.php';
		require_once WPPCS_PATH . 'includes/class-admin-menu.php';
		require_once WPPCS_PATH . 'includes/class-frontend.php';
        require_once WPPCS_PATH . 'includes/class-settings.php';
        require_once WPPCS_PATH . 'includes/class-cookie-manager.php'; // <-- บรรทัดที่เพิ่มเข้ามา
	}

	/**
	 * Initialize all hooks.
	 */
	private function init_hooks() {
		new Admin_Menu();
		new Frontend();
        new Settings();
        new Cookie_Manager(); // <-- บรรทัดที่เพิ่มเข้ามา
	}
}