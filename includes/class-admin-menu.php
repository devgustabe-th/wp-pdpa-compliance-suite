<?php
namespace WP_PDPA_CS;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Admin_Menu {

    private $admin_notices = [];

    public function __construct() {
        add_action( 'admin_menu', [ $this, 'setup_admin_menu' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_styles' ] );
        add_action( 'admin_notices', [ $this, 'display_admin_notices' ] );
        add_action( 'admin_init', [ $this, 'handle_export_actions' ] );
    }

    public function handle_export_actions() {
        if ( ! isset( $_GET['page'] ) || 'wp-pdpa-cs-consent-log' !== $_GET['page'] || ! isset( $_GET['action'] ) || 'export_csv' !== $_GET['action'] ) {
            return;
        }
        if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'wppcs_export_consent_log_nonce' ) ) {
            wp_die( 'Security check failed!' );
        }
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'You do not have permission to export this data.' );
        }

        global $wpdb;
        $logs_table = $wpdb->prefix . 'pdpa_consent_logs';
        $all_logs = $wpdb->get_results( "SELECT * FROM $logs_table ORDER BY created_at DESC", ARRAY_A );

        if ( empty( $all_logs ) ) {
            set_transient( 'wppcs_admin_notice', [ 'type' => 'info', 'message' => 'There are no consent logs to export.' ], 5 );
            wp_safe_redirect( remove_query_arg( [ 'action', '_wpnonce' ] ) );
            exit;
        }

        $filename = 'consent-log-' . date('Y-m-d') . '.csv';

        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename=' . $filename );
        
        $output = fopen( 'php://output', 'w' );
        fputcsv( $output, [ 'Log ID', 'User ID', 'Guest Identifier', 'IP Address', 'Consent Type', 'Consent Details', 'Date (UTC)' ] );
        foreach ( $all_logs as $log ) {
            fputcsv( $output, $log );
        }
        fclose( $output );
        exit;
    }

    public function handle_request_actions() {
        if ( ! isset( $_POST['wppcs_action'] ) || 'update_request_status' !== $_POST['wppcs_action'] ) {
            return;
        }
        $request_id = isset( $_POST['request_id'] ) ? absint( $_POST['request_id'] ) : 0;
        if ( ! $request_id || ! current_user_can( 'manage_options' ) ) {
            return;
        }
        if ( ! isset( $_POST['_wppcs_request_nonce'] ) || ! wp_verify_nonce( $_POST['_wppcs_request_nonce'], 'wppcs_update_request_status_' . $request_id ) ) {
            $this->admin_notices[] = [ 'type' => 'error', 'message' => 'Security check failed!' ];
            return;
        }
        $new_status   = sanitize_key( $_POST['request_status'] );
        $admin_notes  = sanitize_textarea_field( $_POST['admin_notes'] );
        $resolved_at  = ( in_array( $new_status, [ 'completed', 'rejected' ] ) ) ? current_time( 'mysql', 1 ) : null;
        global $wpdb;
        $table_name = $wpdb->prefix . 'pdpa_dsar_requests';
        $updated = $wpdb->update(
            $table_name,
            [ 'request_status' => $new_status, 'admin_notes' => $admin_notes, 'resolved_at' => $resolved_at, 'resolved_by' => get_current_user_id() ],
            [ 'request_id' => $request_id ],
            [ '%s', '%s', '%s', '%d' ],
            [ '%d' ]
        );
        $this->admin_notices[] = ( $updated !== false ) ? [ 'type' => 'success', 'message' => __( 'Request status updated successfully.', 'wp-pdpa-cs' ) ] : [ 'type' => 'info', 'message' => __( 'No changes were made to the request status.', 'wp-pdpa-cs' ) ];
    }

    public function display_admin_notices() {
        if ( ! empty( $this->admin_notices ) ) {
            foreach ( $this->admin_notices as $notice ) {
                printf( '<div class="notice notice-%s is-dismissible"><p>%s</p></div>', esc_attr( $notice['type'] ), esc_html( $notice['message'] ) );
            }
        }
        if ( $notice = get_transient( 'wppcs_admin_notice' ) ) {
            printf( '<div class="notice notice-%s is-dismissible"><p>%s</p></div>', esc_attr( $notice['type'] ), esc_html( $notice['message'] ) );
            delete_transient( 'wppcs_admin_notice' );
        }
    }

    public function enqueue_admin_styles( $hook ) {
        if ( strpos( $hook, 'wp-pdpa-cs' ) === false ) { return; }
        wp_enqueue_style( 'wp-pdpa-cs-admin-styles', WPPCS_URL . 'assets/css/admin-styles.css', [], WPPCS_VERSION );
    }

    public function setup_admin_menu() {
        add_menu_page( 'PDPA Compliance', '🛡️ PDPA Compliance', 'manage_options', 'wp-pdpa-cs-dashboard', [ $this, 'dashboard_page_html' ], 'dashicons-shield-alt' );
        
        add_submenu_page( 'wp-pdpa-cs-dashboard', 'Dashboard', 'Dashboard', 'manage_options', 'wp-pdpa-cs-dashboard', [ $this, 'dashboard_page_html' ] );
        
        $requests_page_hook = add_submenu_page( 'wp-pdpa-cs-dashboard', 'Data Requests', 'Data Requests', 'manage_options', 'wp-pdpa-cs-requests', [ $this, 'requests_page_router' ] );
        
        add_submenu_page( 'wp-pdpa-cs-dashboard', 'Consent Log', 'Consent Log', 'manage_options', 'wp-pdpa-cs-consent-log', [ $this, 'consent_log_page_html' ] );

        // --- NEW SUBMENU ADDED HERE ---
        add_submenu_page(
            'wp-pdpa-cs-dashboard',
            __( 'Cookie Management', 'wp-pdpa-cs' ),
            __( 'Cookie Management', 'wp-pdpa-cs' ),
            'manage_options',
            'wp-pdpa-cs-cookie-manager',
            [ $this, 'cookie_manager_page_html' ]
        );
        // --- END NEW SUBMENU ---
        
        add_submenu_page( 'wp-pdpa-cs-dashboard', 'Settings', 'Settings', 'manage_options', 'wp-pdpa-cs-settings', [ $this, 'settings_page_html' ] );
        
        add_action( 'load-' . $requests_page_hook, [ $this, 'handle_request_actions' ] );
    }

    /**
     * NEW: Callback to render the Cookie Management page.
     */
    public function cookie_manager_page_html() {
        if ( class_exists( __NAMESPACE__ . '\Cookie_Manager' ) ) {
            $cookie_manager_page = new \WP_PDPA_CS\Cookie_Manager();
            $cookie_manager_page->render_page();
        }
    }

    public function consent_log_page_html() {
        global $wpdb;
        $logs_table = $wpdb->prefix . 'pdpa_consent_logs';
        $all_logs = $wpdb->get_results( "SELECT * FROM $logs_table ORDER BY created_at DESC LIMIT 100" );
        $export_url = add_query_arg( [ 'action' => 'export_csv', '_wpnonce' => wp_create_nonce( 'wppcs_export_consent_log_nonce' ) ] );
        ?>
        <div class="wrap wp-pdpa-cs-wrap">
            <h1 class="wp-heading-inline"><?php esc_html_e( 'Consent Log', 'wp-pdpa-cs' ); ?></h1>
            <a href="<?php echo esc_url( $export_url ); ?>" class="page-title-action"><?php esc_html_e( 'Export to CSV', 'wp-pdpa-cs' ); ?></a>
            <hr class="wp-header-end">
            <p><?php esc_html_e( 'This log records every consent action taken by users via the cookie banner.', 'wp-pdpa-cs' ); ?></p>
            <table class="wp-list-table widefat striped fixed">
                <thead>
                    <tr>
                        <th style="width:20%;"><?php esc_html_e( 'User', 'wp-pdpa-cs' ); ?></th>
                        <th style="width:15%;"><?php esc_html_e( 'Consent Type', 'wp-pdpa-cs' ); ?></th>
                        <th><?php esc_html_e( 'Details', 'wp-pdpa-cs' ); ?></th>
                        <th style="width:15%;"><?php esc_html_e( 'IP Address', 'wp-pdpa-cs' ); ?></th>
                        <th style="width:20%;"><?php esc_html_e( 'Date', 'wp-pdpa-cs' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( ! empty( $all_logs ) ) : ?>
                        <?php foreach ( $all_logs as $log ) : ?>
                            <tr>
                                <td>
                                    <?php
                                    if ( $log->user_id > 0 ) {
                                        $user = get_userdata( $log->user_id );
                                        echo '<strong>' . esc_html( $user ? $user->user_login : 'User #' . $log->user_id ) . '</strong>';
                                    } else {
                                        echo 'Guest: ' . esc_html( substr( $log->guest_identifier, 0, 12 ) ) . '...';
                                    }
                                    ?>
                                </td>
                                <td><span class="badge"><?php echo esc_html( str_replace('_', ' ', $log->consent_type) ); ?></span></td>
                                <td><code style="font-size: 12px;"><?php echo esc_html( $log->consent_details ); ?></code></td>
                                <td><?php echo esc_html( $log->ip_address ); ?></td>
                                <td><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $log->created_at ) ) ); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="5"><?php esc_html_e( 'No consent logs found yet. Try accepting the cookie banner on the frontend.', 'wp-pdpa-cs' ); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
    
    public function requests_page_router() {
        echo '<div class="wrap wp-pdpa-cs-wrap">';
        if ( isset( $_GET['action'] ) && 'view' === $_GET['action'] && isset( $_GET['request_id'] ) ) {
            $this->single_request_page_html();
        } else {
            $this->requests_list_page_html();
        }
        echo '</div>';
    }

    public function settings_page_html() {
        if ( class_exists( __NAMESPACE__ . '\Settings' ) ) {
            $settings_page = new \WP_PDPA_CS\Settings();
            $settings_page->render_settings_page();
        } else {
            echo '<div class="wrap"><h1>Error: Settings Class Not Found</h1></div>';
        }
    }

    public function requests_list_page_html() {
        global $wpdb;
        $requests_table = $wpdb->prefix . 'pdpa_dsar_requests';
        $all_requests = $wpdb->get_results( "SELECT * FROM $requests_table ORDER BY created_at DESC" );
        ?>
        <h1><?php esc_html_e( 'Manage Data Requests', 'wp-pdpa-cs' ); ?></h1>
        <p><?php esc_html_e( 'Here you can view and manage all data subject access requests (DSAR) submitted by your users.', 'wp-pdpa-cs' ); ?></p>
        <table class="wp-list-table widefat striped fixed">
            <thead>
                <tr>
                    <th style="width:20%;"><?php esc_html_e( 'Requester', 'wp-pdpa-cs' ); ?></th>
                    <th style="width:15%;"><?php esc_html_e( 'Request Type', 'wp-pdpa-cs' ); ?></th>
                    <th><?php esc_html_e( 'Details', 'wp-pdpa-cs' ); ?></th>
                    <th style="width:15%;"><?php esc_html_e( 'Date Submitted', 'wp-pdpa-cs' ); ?></th>
                    <th style="width:12%;"><?php esc_html_e( 'Status', 'wp-pdpa-cs' ); ?></th>
                    <th style="width:10%;"><?php esc_html_e( 'Actions', 'wp-pdpa-cs' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ( ! empty( $all_requests ) ) : ?>
                    <?php foreach ( $all_requests as $request ) : ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html( $request->requester_name ); ?></strong><br>
                                <small><?php echo esc_html( $request->requester_email ); ?></small>
                            </td>
                            <td><span class="badge type-<?php echo esc_attr($request->request_type); ?>"><?php echo esc_html( ucfirst($request->request_type) ); ?></span></td>
                            <td><?php echo esc_html( wp_trim_words( $request->request_details, 15, '...' ) ); ?></td>
                            <td><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $request->created_at ) ) ); ?></td>
                            <td><span class="badge status-<?php echo esc_attr($request->request_status); ?>"><?php echo esc_html( ucfirst( str_replace( '_', ' ', $request->request_status ) ) ); ?></span></td>
                            <td>
                                <?php
                                $view_url = add_query_arg( [ 'page' => 'wp-pdpa-cs-requests', 'action' => 'view', 'request_id' => $request->request_id, ], admin_url( 'admin.php' ) );
                                ?>
                                <a href="<?php echo esc_url( $view_url ); ?>" class="button button-secondary"><?php esc_html_e( 'View', 'wp-pdpa-cs' ); ?></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr><td colspan="6"><?php esc_html_e( 'No data requests found yet.', 'wp-pdpa-cs' ); ?></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        <?php
    }
    
    public function single_request_page_html() {
        global $wpdb;
        $request_id = isset( $_GET['request_id'] ) ? absint( $_GET['request_id'] ) : 0;
        $requests_table = $wpdb->prefix . 'pdpa_dsar_requests';
        $request = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $requests_table WHERE request_id = %d", $request_id ) );

        if ( ! $request ) {
            echo '<h1>' . esc_html__( 'Request Not Found', 'wp-pdpa-cs' ) . '</h1><p>' . esc_html__( 'The requested item could not be found.', 'wp-pdpa-cs' ) . '</p>';
            return;
        }
        ?>
        <h1><?php esc_html_e( 'View Data Request', 'wp-pdpa-cs' ); ?> #<?php echo esc_html( $request->request_id ); ?> <a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-pdpa-cs-requests' ) ); ?>" class="page-title-action"><?php esc_html_e( '← Back to All Requests', 'wp-pdpa-cs' ); ?></a></h1>
        <div class="wp-pdpa-cs-single-view">
            <div class="details-panel">
                <h2><?php esc_html_e( 'Request Details', 'wp-pdpa-cs' ); ?></h2>
                <table class="form-table">
                     <tbody>
                        <tr><th><?php esc_html_e( 'Requester Name', 'wp-pdpa-cs' ); ?></th><td><?php echo esc_html( $request->requester_name ); ?></td></tr>
                        <tr><th><?php esc_html_e( 'Requester Email', 'wp-pdpa-cs' ); ?></th><td><a href="mailto:<?php echo esc_attr( $request->requester_email ); ?>"><?php echo esc_html( $request->requester_email ); ?></a></td></tr>
                        <tr><th><?php esc_html_e( 'Request Type', 'wp-pdpa-cs' ); ?></th><td><span class="badge type-<?php echo esc_attr($request->request_type); ?>"><?php echo esc_html( ucfirst($request->request_type) ); ?></span></td></tr>
                        <tr><th><?php esc_html_e( 'Date Submitted', 'wp-pdpa-cs' ); ?></th><td><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $request->created_at ) ) ); ?></td></tr>
                        <tr><th><?php esc_html_e( 'Current Status', 'wp-pdpa-cs' ); ?></th><td><span class="badge status-<?php echo esc_attr($request->request_status); ?>"><?php echo esc_html( ucfirst( str_replace( '_', ' ', $request->request_status ) ) ); ?></span></td></tr>
                        <tr><th><?php esc_html_e( 'Full Details', 'wp-pdpa-cs' ); ?></th><td><?php echo nl2br( esc_html( $request->request_details ) ); ?></td></tr>
                        <?php if ( ! empty( $request->attachment_path ) ) : ?>
                            <tr><th><?php esc_html_e( 'Attached File', 'wp-pdpa-cs' ); ?></th>
                                <td>
                                    <?php
                                    $uploads_dir = wp_get_upload_dir();
                                    $attachment_url = str_replace( $uploads_dir['basedir'], $uploads_dir['baseurl'], $request->attachment_path );
                                    ?>
                                    <a href="<?php echo esc_url( $attachment_url ); ?>" target="_blank" download><?php echo esc_html( basename( $request->attachment_path ) ); ?></a>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="actions-panel">
                <h2><?php esc_html_e( 'Manage Status', 'wp-pdpa-cs' ); ?></h2>
                <form method="post">
                    <p><label for="request_status"><?php esc_html_e( 'Update Status To:', 'wp-pdpa-cs' ); ?></label>
                        <select name="request_status" id="request_status" class="widefat">
                            <?php $statuses = [ 'new', 'in_progress', 'awaiting_verification', 'completed', 'rejected' ];
                            foreach ( $statuses as $status ) {
                                printf( '<option value="%s" %s>%s</option>', esc_attr( $status ), selected( $request->request_status, $status, false ), esc_html( ucfirst( str_replace( '_', ' ', $status ) ) ) );
                            } ?>
                        </select>
                    </p>
                    <p><label for="admin_notes"><?php esc_html_e( 'Admin Notes (Internal Use Only)', 'wp-pdpa-cs' ); ?></label>
                        <textarea name="admin_notes" id="admin_notes" rows="6" class="widefat"><?php echo esc_textarea( $request->admin_notes ); ?></textarea>
                    </p>
                    <p>
                        <?php wp_nonce_field( 'wppcs_update_request_status_' . $request->request_id, '_wppcs_request_nonce' ); ?>
                        <input type="hidden" name="wppcs_action" value="update_request_status">
                        <input type="hidden" name="request_id" value="<?php echo esc_attr( $request->request_id ); ?>">
                        <button type="submit" class="button button-primary button-large"><?php esc_html_e( 'Update Status', 'wp-pdpa-cs' ); ?></button>
                    </p>
                </form>
            </div>
        </div>
        <?php
    }

    public function dashboard_page_html() {
        global $wpdb;
        $requests_table = $wpdb->prefix . 'pdpa_dsar_requests';
        $logs_table     = $wpdb->prefix . 'pdpa_consent_logs';
        $new_requests_count = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(request_id) FROM $requests_table WHERE request_status = %s", 'new' ) );
        $latest_logs = $wpdb->get_results( "SELECT * FROM $logs_table ORDER BY created_at DESC LIMIT 5" );
        ?>
        <div class="wrap wp-pdpa-cs-wrap">
            <h1><?php esc_html_e( 'PDPA Compliance Dashboard', 'wp-pdpa-cs' ); ?></h1>
            <div class="wp-pdpa-cs-cards">
                <div class="card">
                    <h2><?php esc_html_e( 'New Data Requests', 'wp-pdpa-cs' ); ?></h2>
                    <p class="stat"><?php echo esc_html( number_format_i18n( $new_requests_count ) ); ?></p>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-pdpa-cs-requests' ) ); ?>"><?php esc_html_e( 'Manage Requests', 'wp-pdpa-cs' ); ?> &rarr;</a>
                </div>
                <div class="card placeholder">
                    <h2><?php esc_html_e( 'Requests Nearing Deadline', 'wp-pdpa-cs' ); ?></h2>
                    <p class="stat">0</p>
                    <a href="#"><?php esc_html_e( 'View Details', 'wp-pdpa-cs' ); ?> &rarr;</a>
                </div>
            </div>
            <div class="wp-pdpa-cs-recent-logs">
                <h2><?php esc_html_e( 'Recent Consent Logs', 'wp-pdpa-cs' ); ?></h2>
                <table class="wp-list-table widefat striped">
                    <thead><tr><th><?php esc_html_e( 'User', 'wp-pdpa-cs' ); ?></th><th><?php esc_html_e( 'Consent Type', 'wp-pdpa-cs' ); ?></th><th><?php esc_html_e( 'Details', 'wp-pdpa-cs' ); ?></th><th><?php esc_html_e( 'Date', 'wp-pdpa-cs' ); ?></th></tr></thead>
                    <tbody>
                        <?php if ( ! empty( $latest_logs ) ) : ?>
                            <?php foreach ( $latest_logs as $log ) : ?>
                                <tr>
                                    <td>
                                        <?php
                                        if ( $log->user_id > 0 ) {
                                            $user = get_userdata( $log->user_id );
                                            echo esc_html( $user ? $user->user_login : 'User #' . $log->user_id );
                                        } else {
                                            echo 'Guest: ' . esc_html( substr( $log->guest_identifier, 0, 8 ) ) . '...';
                                        }
                                        ?>
                                    </td>
                                    <td><span class="badge"><?php echo esc_html( str_replace('_', ' ', $log->consent_type) ); ?></span></td>
                                    <td><?php echo esc_html( $log->consent_details ); ?></td>
                                    <td><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $log->created_at ) ) ); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr><td colspan="4"><?php esc_html_e( 'No consent logs found yet.', 'wp-pdpa-cs' ); ?></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }
}