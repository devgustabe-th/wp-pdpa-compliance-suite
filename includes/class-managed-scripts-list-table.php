<?php
namespace WP_PDPA_CS;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// We need to load the WP_List_Table class file if it's not already loaded
if ( ! class_exists( '\WP_List_Table' ) ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * List table class for managed scripts.
 */
class Managed_Scripts_List_Table extends \WP_List_Table {

    public function __construct() {
        parent::__construct( [
            'singular' => 'Managed Script',
            'plural'   => 'Managed Scripts',
            'ajax'     => false,
        ] );
    }

    /**
     * Get the column names.
     */
    public function get_columns() {
        return [
            'cb'              => '<input type="checkbox" />',
            'service_name'    => __( 'Service Name', 'wp-pdpa-cs' ),
            'cookie_category' => __( 'Category', 'wp-pdpa-cs' ),
            'tracking_id'     => __( 'Tracking ID', 'wp-pdpa-cs' ),
            'status'          => __( 'Status', 'wp-pdpa-cs' ),
        ];
    }

    /**
     * Default column rendering.
     */
    protected function column_default( $item, $column_name ) {
        return isset( $item[ $column_name ] ) ? esc_html( $item[ $column_name ] ) : '';
    }

    /**
     * Render the checkbox column.
     */
    protected function column_cb( $item ) {
        return sprintf(
            '<input type="checkbox" name="script_id[]" value="%d" />',
            $item['script_id']
        );
    }

    /**
     * Render the service_name column with actions.
     */
    protected function column_service_name( $item ) {
        $actions = [
            'edit' => sprintf( '<a href="#">%s</a>', __( 'Edit', 'wp-pdpa-cs' ) ),
            'delete' => sprintf( '<a href="#" style="color:#a00;">%s</a>', __( 'Delete', 'wp-pdpa-cs' ) ),
        ];
        
        return sprintf(
            '<strong>%s</strong>%s',
            esc_html( $item['service_name'] ),
            $this->row_actions( $actions )
        );
    }

    /**
     * Prepare the items for the table.
     */
    public function prepare_items() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'pdpa_managed_scripts';
        $per_page   = 20;

        $columns  = $this->get_columns();
        $hidden   = [];
        $sortable = [];

        $this->_column_headers = [ $columns, $hidden, $sortable ];

        $this->items = $wpdb->get_results( "SELECT * FROM {$table_name} ORDER BY service_name ASC", ARRAY_A );
        
        // For now, we are not implementing pagination.
        // We will add it later if needed.
    }

    /**
     * Message to be displayed when there are no items.
     */
    public function no_items() {
        _e( 'No scripts have been added yet.', 'wp-pdpa-cs' );
    }
}