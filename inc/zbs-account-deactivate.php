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
 * Plug-in De-Activation class.
 */
class Zero_BS_AccountingPluginDeactivate {
	/**
	 * Deactive_Function
	 */
	public static function deactivate() {
		$zbspage = get_option( 'zbs-accountpage' );
		if ( $zbspage ) {
			wp_delete_post( $zbspage );
		}
		delete_option( 'zbs-accountpage' );
	}
}
