<?php
namespace WP_PDPA_CS;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Database handler class.
 */
class DB {

	/**
	 * Run the installer to create database tables.
	 */
	public static function install() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		// Table for Consent Logs
		$table_name_logs = $wpdb->prefix . 'pdpa_consent_logs';
		$sql_logs = "CREATE TABLE $table_name_logs (
			log_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id BIGINT(20) UNSIGNED DEFAULT 0,
			guest_identifier VARCHAR(191) NOT NULL DEFAULT '',
			ip_address VARCHAR(100) NOT NULL DEFAULT '',
			consent_type VARCHAR(100) NOT NULL,
			consent_details TEXT,
			policy_version VARCHAR(50) NOT NULL DEFAULT '',
			created_at DATETIME NOT NULL,
			PRIMARY KEY (log_id),
			INDEX idx_user_id (user_id),
			INDEX idx_guest_identifier (guest_identifier),
			INDEX idx_created_at (created_at)
		) $charset_collate;";
		dbDelta( $sql_logs );

		// Table for DSAR Requests
		$table_name_dsar = $wpdb->prefix . 'pdpa_dsar_requests';
		$sql_dsar = "CREATE TABLE $table_name_dsar (
			request_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id BIGINT(20) UNSIGNED DEFAULT 0,
			requester_email VARCHAR(191) NOT NULL,
			requester_name VARCHAR(255) DEFAULT '',
			request_type VARCHAR(50) NOT NULL,
			request_status VARCHAR(50) NOT NULL DEFAULT 'new',
			request_details LONGTEXT,
			attachment_path VARCHAR(255) DEFAULT '',
			created_at DATETIME NOT NULL,
			resolved_at DATETIME,
			resolved_by BIGINT(20) UNSIGNED DEFAULT 0,
			admin_notes LONGTEXT,
			PRIMARY KEY (request_id),
			INDEX idx_status_created (request_status, created_at)
		) $charset_collate;";
		dbDelta( $sql_dsar );
	}
}