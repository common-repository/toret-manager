<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

class Toret_Manager_Admin_User_Details {

	/**
	 * Plugin slug
	 *
	 * @var string $toret_manager
	 */
	protected string $toret_manager;

	/**
	 * Constructor
	 *
	 * @param string $toret_manager
	 */
	public function __construct( string $toret_manager ) {
		$this->toret_manager = $toret_manager;
	}

	/**
	 * Add user profile fields
	 *
	 * @param WP_User $user
	 */
	function profile_fields( WP_User $user ) {
		$option_id       = TORET_MANAGER_ITEM_INTERNALID;
		$option_excluded = TORET_MANAGER_EXCLUDED_ITEM . '_' . 'user';

		$internalID = get_user_meta( $user->ID, TORET_MANAGER_ITEM_INTERNALID, true );
		$excluded   = get_user_meta( $user->ID, TORET_MANAGER_EXCLUDED_ITEM, true );

		?>
        <h3><?php esc_html_e( 'Toret Manager', 'toret-manager' ); ?></h3>
        <table class="form-table">
            <tr>
                <th>
                    <label for="<?php echo esc_attr( $option_id . '_' . $user->ID ) ?>"><?php esc_html_e( 'Toret Manager Internal ID', 'toret-manager' ); ?></label>
                </th>
                <td>
                    <input type="text" data-id="<?php echo esc_attr( $user->ID ); ?>"
                           name="<?php echo esc_attr( $option_id . '_' . $user->ID ) ?>"
                           id="<?php echo esc_attr( $option_id . '_' . $user->ID ) ?>"
                           value="<?php echo esc_attr( $internalID ) ?>"
                           class="regular-text trman-internalid-form-field"/>
                </td>
                <td>
                    <button type="button" id="trman-save-internalid-<?php echo esc_attr( $user->ID ); ?>"
                            data-id="<?php echo esc_attr( $user->ID ); ?>" data-type="user"
                            class="button-primary trman-save-internalid"
                            disabled="disabled"><?php esc_html_e( 'Save Internal ID', 'toret-manager' ) ?></button>
                </td>
            </tr>
            <tr>
                <th>
                    <label for="<?php echo esc_attr( $option_excluded ); ?>"><?php esc_html_e( 'Toret Manager Synchronization', 'toret-manager' ) ?></label>
                </th>
                <td>
                    <input name="<?php echo esc_attr( $option_excluded ); ?>"
                           id="<?php echo esc_attr( $option_excluded ); ?>"
                           type="checkbox" <?php echo esc_attr( $excluded == "1" ? "checked" : "" ); ?> value="1"/>
					<?php esc_html_e( 'Exclude user from synchronization.', 'toret-manager' ) ?>
                </td>
            </tr>
        </table>
		<?php
	}

	/**
	 * Save user profile fields
	 *
	 * @param mixed $user_id
	 */
	function save_profile_fields( $user_id ) {
		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'update-user_' . $user_id ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_user', $user_id ) ) {
			return;
		}

		$option_excluded = TORET_MANAGER_EXCLUDED_ITEM . '_' . 'user';

		if ( isset( $_POST[ $option_excluded ] ) ) {
			update_user_meta( $user_id, TORET_MANAGER_EXCLUDED_ITEM, sanitize_text_field( $_POST[ $option_excluded ] ) );
		} else {
			delete_user_meta( $user_id, TORET_MANAGER_EXCLUDED_ITEM );
		}
	}


}