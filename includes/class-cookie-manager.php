<?php
namespace WP_PDPA_CS;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Cookie_Manager {

	public function __construct() {
		// Actions for this page will be added here later
	}

    /**
     * Main router for the page.
     * Decides whether to show the list table or the add/edit form.
     */
	public function render_page() {
        $action = $_GET['action'] ?? 'list';

        switch ( $action ) {
            case 'add':
            case 'edit':
                $this->render_add_edit_form();
                break;
            
            default:
                $this->render_list_page();
                break;
        }
	}

    /**
     * Renders the list table view.
     */
    private function render_list_page() {
        // Instantiate our list table class
        $list_table = new Managed_Scripts_List_Table();
        // Fetch, prepare, and sort the data
        $list_table->prepare_items();
		?>
		<div class="wrap wp-pdpa-cs-wrap">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'Cookie Script Manager', 'wp-pdpa-cs' ); ?></h1>
            <?php
            // Correct link for the "Add New" button
            $add_new_url = add_query_arg( [ 'page' => 'wp-pdpa-cs-cookie-manager', 'action' => 'add' ], admin_url( 'admin.php' ) );
            ?>
			<a href="<?php echo esc_url( $add_new_url ); ?>" class="page-title-action"><?php esc_html_e( 'Add New Script', 'wp-pdpa-cs' ); ?></a>
			<hr class="wp-header-end">

			<p><?php esc_html_e( 'Manage tracking scripts. They will only load if the user gives consent to the corresponding category.', 'wp-pdpa-cs' ); ?></p>
            
            <form method="post">
                <?php
                // Display the list table
                $list_table->display();
                ?>
            </form>
		</div>
		<?php
    }

    /**
     * Renders the form for adding or editing a script.
     */
    private function render_add_edit_form() {
        // We will add logic for editing later. For now, it's just the "Add New" form.
        ?>
        <div class="wrap wp-pdpa-cs-wrap">
            <h1><?php esc_html_e( 'Add New Managed Script', 'wp-pdpa-cs' ); ?></h1>
            <p><?php esc_html_e( 'Use this helper to add a new tracking script. The plugin will handle injecting it onto the page based on user consent.', 'wp-pdpa-cs' ); ?></p>

            <form method="post">
                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row"><label for="cookie_category"><?php esc_html_e( 'Cookie Category', 'wp-pdpa-cs' ); ?> <span class="description">(required)</span></label></th>
                            <td>
                                <select name="cookie_category" id="cookie_category" required>
                                    <option value=""><?php esc_html_e( '-- Select a category --', 'wp-pdpa-cs' ); ?></option>
                                    <option value="analytics"><?php esc_html_e( 'Analytics', 'wp-pdpa-cs' ); ?></option>
                                    <option value="marketing"><?php esc_html_e( 'Marketing', 'wp-pdpa-cs' ); ?></option>
                                </select>
                                <p class="description"><?php esc_html_e( 'This links the script to a consent category.', 'wp-pdpa-cs' ); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="service_name"><?php esc_html_e( 'Service Name', 'wp-pdpa-cs' ); ?> <span class="description">(required)</span></label></th>
                            <td>
                                <input type="text" name="service_name" id="service_name" class="regular-text" placeholder="e.g., Google Analytics" required>
                                <p class="description"><?php esc_html_e( 'A friendly name for your reference.', 'wp-pdpa-cs' ); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="tracking_id"><?php esc_html_e( 'Tracking ID', 'wp-pdpa-cs' ); ?> <span class="description">(required)</span></label></th>
                            <td>
                                <input type="text" name="tracking_id" id="tracking_id" class="regular-text" placeholder="e.g., G-XXXXXXXXXX or 123456789" required>
                                <p class="description"><?php esc_html_e( 'Enter the Tracking ID, Pixel ID, etc., for the service.', 'wp-pdpa-cs' ); ?></p>
                            </td>
                        </tr>
                         <tr>
                            <th scope="row"><label for="description"><?php esc_html_e( 'Description', 'wp-pdpa-cs' ); ?></label></th>
                            <td>
                                <textarea name="description" id="description" class="large-text" rows="4" placeholder="<?php esc_attr_e( 'e.g., Used to track website traffic and user behavior.', 'wp-pdpa-cs' ); ?>"></textarea>
                                <p class="description"><?php esc_html_e( 'A brief description of what this script does (for your internal reference).', 'wp-pdpa-cs' ); ?></p>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <input type="hidden" name="wppcs_action" value="add_new_script">
                <?php wp_nonce_field( 'wppcs_add_new_script_nonce', '_wppcs_nonce' ); ?>
                <?php submit_button( __( 'Add Script', 'wp-pdpa-cs' ) ); ?>
            </form>
        </div>
        <?php
    }
}