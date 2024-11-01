<?php
/**
 * Zero_BS_AccountingPlugin Activation class
 *
 * @package zero_bs_accounting
 * @since 1.0.0
 */

namespace ZERO_BS_ACCOUNTING;

// Exit if directly accessed.
defined( 'ABSPATH' ) || exit(1);

/**
 * Plug-in Activation class.
 */
class Zero_BS_AccountingPluginActivate {

	/**
	 * Active_Function
	 */
	public static function activate() {

		global $wpdb;
		if ( null === $wpdb->get_row( "SELECT post_name FROM {$wpdb->prefix}posts WHERE post_name = 'zero-bs-accounting'", 'ARRAY_A' ) ) {

			// Create_zbs-account_page.
			$current_user = wp_get_current_user();
			$page         = [
				'post_title'  => esc_html__( 'Zero BS Accounting', 'zbs-account' ),
				'post-slug'   => 'zero-bs-accounting',
				'post_status' => 'publish',
				'post_author' => $current_user->ID,
				'post_type'   => 'page',
			];

			// Save_zbs-account_page_as_option.
			$zbspage = wp_insert_post( $page );
			update_option( 'zbs-accountpage', $zbspage );
		}
		add_option('zbs_accounting_do_activation_redirect', true);
	}
}
