<?php
/**
 * Plugin Name:       Zero BS Accounting
 * Plugin URI:        https://wppool.dev/zero-bs-accounting
 * Description:       Accounting for the non-accountants.
 * Version:           2.0.6
 * Author:            WPPOOL
 * Author URI:        https://wppool.dev
 * License:           GPLv2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       zbs-account
 * Tested up to:      6.6
 *
 * @package ZerBSAccounting
 */

// If this file is called directly, abort.
if ( ! defined('WPINC') ) {
	die;
}
/**
 * Zero_BS_Accounting_class.
 */
class Zero_BS_Accounting {

	/**
	 * Declaring basename variable.
	 *
	 * @var basename
	 */
	public $plugin;

	/**
	 * Storing basename in the variable.
	 */
	public function __construct() {
		$this->plugin = plugin_basename(__FILE__);
	}

	/**
	 * Registering all theactions.
	 */
	public function register() {
		// Registering_settings- currency and position.
		add_action('init', [ $this, 'zbs_register_settings' ]);
		// Link of plugin main page.
		add_action('plugin_action_links_' . plugin_basename(__FILE__), [ $this, 'zbs_account_action_links' ]);
		// Create admin menu in admin bar.
		add_action('admin_bar_menu', [ $this, 'zbs_account_admin_bar_item' ], 500);
		// Registering menu link to the permited user.
		add_action('admin_menu', [ $this, 'zbs_account_register_custom_menu_link' ]);
		// Plugin redirection to the plugin page.
		add_action('admin_init', [ $this, 'zbs_account_menu_item_redirect_url' ], 1);
		// Create zbs_profile table and migrate all users.
		add_action('admin_init', [ $this, 'zbs_account_migration' ], 1);
		// Declaring template.
		add_filter('page_template', [ $this, 'zbs_account_declare_template' ]);
		// Enqueue all scripts.
		add_action('wp_enqueue_scripts', [ $this, 'zbs_account_enqueue_assets' ]);
		// Regsiter post type, Transaction and Debt.
		add_action('init', [ $this, 'zbs_account_post_type' ]);
		// Adding post meta of Transaction and Debt wehre all Earning, Expense, Debt Tracking data store.
		add_action('init', [ $this, 'zbs_account_register_post_meta' ]);
		// Category Add Icon & Color html part/template.
		add_action('transaction_category_add_form_fields', [ $this, 'zbs_account_add_icon_on_transaction_category' ], 10, 2);
		// Category edit icon html part/template.
		add_action('transaction_category_edit_form_fields', [ $this, 'zbs_account_edit_icon_on_transaction_category' ], 10);
		// Adding default Category terms.
		add_action('init', [ $this, 'zbs_account_register_default_terms' ]);
		// Registers additional REST API fields for the 'transaction' post type and 'transaction_category' custom taxonomy.
		add_action('rest_api_init', [ $this, 'zbs_account_formated_date_on_rest' ]);
		// Subscription in Fluent for Pro plugin.
		add_action('wp_ajax_zbs_subscribe', [ $this, 'zbs_account_subscribe' ]);
		// Appsero init tracker.
		add_action('init', [ $this, 'appsero_init_tracker_zero_bs_accounting' ]);

		add_action('wp_ajax_zbs_insertProfile', [ $this, 'zbs_account_insert_profile' ]);
		add_action('wp_ajax_zbs_updateProfile', [ $this, 'zbs_account_update_profile' ]);
		add_action('wp_ajax_zbs_deleteProfile', [ $this, 'zbs_account_delete_profile' ]);
		add_action('wp_ajax_zbs_displayProfile', [ $this, 'zbs_account_display_profile' ]);
		add_action('wp_ajax_zbs_set_default_profile', [ $this, 'zbs_account_set_default_profile_id' ]);
		add_action('wp_ajax_zbs_get_profile_settings', [ $this, 'get_profile_settings' ]);
		add_action('wp_ajax_zbs_updated_profile_settings', [ $this, 'update_profile_settings' ]);
		add_action('wp_ajax_zbs_update_transaction', [ $this, 'zbs_account_update_transaction' ]);
		add_action('template_redirect', [ $this, 'zbs_account_check_user_accessibility' ]);
		add_action('user_register', [ $this, 'zbs_account_on_user_register' ]);
	}

	/**
	 * Registering_settings
	 */
	public function zbs_register_settings() {
		register_setting(
			// Name of the settings group.
			'zbs_settings',
			// Registered settings.
			'zbs_currency',
			[
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => 'USD',
				'show_in_rest'      => true,
			]
		);
		register_setting(
			'zbs_settings',
			'zbs_currency_position',
			[
				'type' => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default' => 'before',
				'show_in_rest' => true,
			]
		);

	}

	/**
	 * Initialize the plugin tracker
	 *
	 * @return void
	 */
	public function appsero_init_tracker_zero_bs_accounting() {
		if ( ! class_exists('Appsero\Client') ) {
			require_once __DIR__ . '/appsero/src/Client.php';
		}

		$client = new Appsero\Client('ea96124c-b9e1-457e-91d9-0d7a1322afaf', 'Zero BS Accounting', __FILE__);

		// Active insights.
		$client->insights()->init();
	}

	/**
	 * Adding action link.
	 *
	 * @param string $links holds zbsa page link.
	 */
	public function zbs_account_action_links( $links ) {
		$page_id = get_option('zbs-accountpage');

		if ( $page_id ) {

			$links = array_merge([
				'<a target="_blank" href="' . esc_url(get_page_link(get_option('zbs-accountpage'))) . '">' . __('ZBS Page', 'zbs-account') . '</a>',
			], $links);
		}

		return $links;
	}

	/**
	 * Checking if current user can access the plug-in.
	 */
	public function zbs_account_check_user_accessibility() {
		if ( ! current_user_can('edit_posts') && is_page('zero-bs-accounting') ) {
			wp_die("You Don't Have Permission to Access This Page");
		}
	}

	/**
	 * Adding menu in adminbar.
	 *
	 * @param int $admin_bar holds menu items.
	 */
	public function zbs_account_admin_bar_item( $admin_bar ) {
		if ( ! current_user_can('edit_posts') ) {
			return;
		}
		$admin_bar->add_menu([
			'id'     => 'zbs-account-site-name',
			'parent' => 'site-name',
			'group'  => null,
			'title'  => 'Accounting',
			'href'   => get_the_permalink(get_option('zbs-accountpage')),
			'meta'   => [
				'title' => __('Accounting', 'zbs-account'),
			],
		]);

		$admin_bar->add_menu([
			'id'     => 'zbs-account-top-secondary',
			'parent' => 'top-secondary',
			'group'  => null,
			'title'  => 'Accounting',
			'href'   => get_the_permalink(get_option('zbs-accountpage')),
			'meta'   => [
				'title' => __('Accounting', 'zbs-account'),
			],
		]);
	}

	/**
	 * Registering menu link to the permited user.
	 */
	public function zbs_account_register_custom_menu_link() {
		add_menu_page('Accounting', 'Accounting', 'edit_posts', 'zbs-page', 'zbs_account_menu_item_redirect_url', 'dashicons-money-alt', 3);
	}

	/**
	 * Menu item redirection.
	 */
	public function zbs_account_menu_item_redirect_url() {

		$menu_redirect = ! empty( $_GET['page'] ) ? sanitize_text_field( wp_unslash($_GET['page'])) : '';

		if ( get_option('zbs_accounting_do_activation_redirect', false) ) {
			delete_option('zbs_accounting_do_activation_redirect');
			wp_safe_redirect(get_the_permalink(get_option('zbs-accountpage')));
			exit();
		}

		if ( 'zbs-page' === $menu_redirect ) {
			wp_safe_redirect(get_the_permalink(get_option('zbs-accountpage')));
			exit();
		}
	}

	/**
	 * Enqueing assets.
	 */
	public function zbs_account_enqueue_assets() {
		if ( is_page('zero-bs-accounting') ) {
			wp_enqueue_style('zero-bs-accounting-css', plugins_url('/public/styles.css', __FILE__), [], microtime() );
			wp_enqueue_style('zero-bs-accounting-google-icon', 'https://fonts.googleapis.com/icon?family=Material+Icons+Outlined', [], microtime() );
			wp_enqueue_style('zero-bs-accounting-google-font', 'https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap', [], microtime());
			wp_enqueue_script('zero-bs-accounting-chart-js', plugins_url('/assets/js/chart.min.js', __FILE__), [], microtime(), true);
			wp_enqueue_script("$this->plugin-js", plugins_url('/dist/js/scripts.js', __FILE__), [ 'jquery' ], filemtime(plugin_dir_path(__FILE__) . 'dist/js/scripts.js'), true);
			wp_localize_script("$this->plugin-js", 'zbs_account', [
				'site'             => site_url('/'),
				'plugin_dir_url'   => plugins_url('/public', __FILE__),
				'user'             => is_user_logged_in() ? json_encode(wp_get_current_user()) : null,
				'nonce'            => wp_create_nonce('wp_rest'),
				'login_url'        => wp_login_url(site_url('/zero-bs-accounting'), false),
				'ajaxurl'          => admin_url('admin-ajax.php'),
				'default_profile'  => get_user_meta(get_current_user_id(), 'zbs_profile', true),
				'ajaxnonce'        => wp_create_nonce('ajax-nonce'),

			]);
		}
	}

	/**
	 * Declaring template.
	 *
	 * @param string $page_template holds file path.
	 */
	public function zbs_account_declare_template( $page_template ) {
		if ( is_page('zero-bs-accounting') ) {
			$page_template = dirname(__FILE__) . '/templates/index.php';
		}
		return $page_template;
	}

	/**
	 * Adding post type.
	 */
	public function zbs_account_post_type() {
		// Transaction.
		$labels = [
			'name'               => __('Transaction', 'zbs-account'),
			'singular_name'      => __('Transaction', 'zbs-account'),
			'menu_name'          => __('Transaction', 'zbs-account'),
			'name_admin_bar'     => __('Transaction', 'zbs-account'),
			'add_new'            => __('Add New', 'zbs-account'),
			'add_new_item'       => __('Add New Transaction', 'zbs-account'),
			'new_item'           => __('New Transaction', 'zbs-account'),
			'edit_item'          => __('Edit Transaction', 'zbs-account'),
			'view_item'          => __('View Transaction', 'zbs-account'),
			'all_items'          => __('All Transaction', 'zbs-account'),
			'search_items'       => __('Search Transaction', 'zbs-account'),
			'parent_item_colon'  => __('Parent Transaction:', 'zbs-account'),
			'not_found'          => __('No transaction found.', 'zbs-account'),
			'not_found_in_trash' => __('No transaction found in Trash.', 'zbs-account'),
		];

		$args = [
			'labels'              => $labels,
			'description'         => __('Transaction for zbs-account', 'zbs-account'),
			'public'              => true,
			'publicly_queryable'  => true,
			'show_ui'             => false,
			'show_in_menu'        => true,
			'show_in_rest'        => true,
			'query_var'           => true,
			'capability_type'     => 'post',
			'has_archive'         => true,
			'hierarchical'        => false,
			'menu_position'       => null,
			'menu_icon'           => 'dashicons-money-alt',
			'exclude_from_search' => true,
			'supports'            => [ 'title', 'author', 'custom-fields' ],
		];

		register_post_type('transaction', $args);
		register_taxonomy('transaction_category', 'transaction', [
			'hierarchical' => true,
			'show_in_rest' => true,
		]);

		// Debt.
		$labels_debt = [
			'name'               => __('Debt', 'zbs-account'),
			'singular_name'      => __('Debt', 'zbs-account'),
			'menu_name'          => __('Debt', 'zbs-account'),
			'name_admin_bar'     => __('Debt', 'zbs-account'),
			'add_new'            => __('Add New', 'zbs-account'),
			'add_new_item'       => __('Add New Debt', 'zbs-account'),
			'new_item'           => __('New Debt', 'zbs-account'),
			'edit_item'          => __('Edit Debt', 'zbs-account'),
			'view_item'          => __('View Debt', 'zbs-account'),
			'all_items'          => __('All Debt', 'zbs-account'),
			'search_items'       => __('Search Debt', 'zbs-account'),
			'parent_item_colon'  => __('Parent Debt:', 'zbs-account'),
			'not_found'          => __('No debt found.', 'zbs-account'),
			'not_found_in_trash' => __('No debt found in Trash.', 'zbs-account'),
		];

		$args_debt = [
			'labels'              => $labels_debt,
			'description'         => __('Debt for zbs-account', 'zbs-account'),
			'public'              => true,
			'publicly_queryable'  => true,
			'show_ui'             => false,
			'show_in_menu'        => true,
			'show_in_rest'        => true,
			'query_var'           => true,
			'capability_type'     => 'post',
			'has_archive'         => true,
			'hierarchical'        => false,
			'menu_position'       => null,
			'menu_icon'           => 'dashicons-money-alt',
			'exclude_from_search' => true,
			'supports'            => [ 'title', 'author', 'thumbnail', 'custom-fields' ],
		];

		register_post_type('debt', $args_debt);
	}

	/**
	 * Adding post meta.
	 */
	public function zbs_account_register_post_meta() {
		register_post_meta('transaction', 'transaction_amount', [
			'show_in_rest' => true,
			'single'       => true,
			'type'         => 'string',
		]);
		register_post_meta('transaction', 'transaction_note', [
			'show_in_rest' => true,
			'single'       => true,
			'type'         => 'string',
		]);
		register_post_meta('transaction', 'transaction_type', [
			'show_in_rest'      => true,
			'single'            => true,
			'type'              => 'string',
			'show_admin_column' => true,
		]);
		register_post_meta('transaction', 'transaction_profile', [
			'show_in_rest'      => true,
			'single'            => true,
			'type'              => 'string',
			'show_admin_column' => true,
		]);
		register_post_meta('transaction', 'debt_profile', [
			'show_in_rest'      => true,
			'single'            => true,
			'type'              => 'string',
			'show_admin_column' => true,
		]);

		// Debt.
		register_post_meta('debt', 'debt_note', [
			'show_in_rest' => true,
			'single'       => true,
			'type'         => 'string',
		]);

		register_post_meta('debt', 'debt_transactions', [
			'show_in_rest' => true,
			'single'       => true,
			'type'         => 'string',
		]);
		register_post_meta('debt', 'debt_phone', [
			'show_in_rest' => true,
			'single'       => true,
			'type'         => 'string',
		]);
		register_post_meta('debt', 'debt_email', [
			'show_in_rest' => true,
			'single'       => true,
			'type'         => 'string',
		]);

		register_post_meta('debt', 'debt_profile', [
			'show_in_rest'      => true,
			'single'            => true,
			'type'              => 'string',
			'show_admin_column' => true,
		]);
	}

	/**
	 * Category Add Icon & Color.
	 */
	public function zbs_account_add_icon_on_transaction_category() {         ?>
<div class="form-field">
    <label for="taxIcon"><?php esc_html_e('Icon', 'zbs-account'); ?></label>
    <input type="text" name="taxIcon" id="taxIcon" value="">
</div>
<div class="form-field">
    <label for="taxColor"><?php esc_html_e('Color', 'zbs-account'); ?></label>
    <input type="text" name="taxColor" id="taxColor" value="">
</div>
<?php
	}

	/**
	 * Category edit icon.
	 *
	 * @param object $term holds transaction category details.
	 */
	public function zbs_account_edit_icon_on_transaction_category( $term ) {
		$t_id       = $term->term_id;
		$term_icon  = get_term_meta($t_id, 'icon', true);
		$term_color = get_term_meta($t_id, 'color', true);
		?>
<tr class="form-field">
    <th><label for="taxIcon"><?php esc_html_e('Icon', 'zbs-account'); ?></label></th>
    <td>
        <input type="text" name="taxIcon" id="taxIcon"
            value="<?php echo ! empty($term_icon) ? esc_attr($term_icon) : ''; ?>">
    </td>
</tr>
<tr class="form-field">
    <th><label for="taxColor"><?php esc_html_e('Color', 'zbs-account'); ?></label></th>
    <td>
        <input type="text" name="taxColor" id="taxColor"
            value="<?php echo ! empty($term_color) ? esc_attr($term_color) : ''; ?>">
    </td>
</tr>
<?php
	}


	/**
	 * Adding default terms.
	 */
	public function zbs_account_register_default_terms() {
		$this->taxonomy = 'transaction_category';
		$this->terms    = [
			[
				'name'   => 'Expense',
				'slug'   => 'expense',
				'icon'   => '',
				'color'  => '',
				'parent' => null,
			],
			[
				'name'   => 'Earning',
				'slug'   => 'earning',
				'icon'   => '',
				'color'  => '',
				'parent' => null,
			],
			[
				'name'   => 'Food & Drink',
				'slug'   => 'food-drink',
				'icon'   => 'restaurant',
				'color'  => '#fea800',
				'parent' => 'expense',
			],
			[
				'name'   => 'Shopping',
				'slug'   => 'shopping',
				'icon'   => 'shopping_bag',
				'color'  => '#e26aef',
				'parent' => 'expense',
			],
			[
				'name'   => 'Transport',
				'slug'   => 'transport',
				'icon'   => 'train',
				'color'  => '#fbcc00',
				'parent' => 'expense',
			],
			[
				'name'   => 'Home',
				'slug'   => 'home',
				'icon'   => 'home',
				'color'  => '#b5985b',
				'parent' => 'expense',
			],
			[
				'name'   => 'Bills & Fees',
				'slug'   => 'bills-fees',
				'icon'   => 'payments',
				'color'  => '#5ec3ab',
				'parent' => 'expense',
			],
			[
				'name'   => 'Entertainment',
				'slug'   => 'entertainment',
				'icon'   => 'sports_esports',
				'color'  => '#fea800',
				'parent' => 'expense',
			],
			[
				'name'   => 'Car',
				'slug'   => 'car',
				'icon'   => 'directions_car',
				'color'  => '#45a7e5',
				'parent' => 'expense',
			],
			[
				'name'   => 'Travel',
				'slug'   => 'travel',
				'icon'   => 'flight_takeoff',
				'color'  => '#f9639f',
				'parent' => 'expense',
			],
			[
				'name'   => 'Family & Personal',
				'slug'   => 'family-personal',
				'icon'   => 'perm_identity',
				'color'  => '#44a7e5',
				'parent' => 'expense',
			],
			[
				'name'   => 'Healthcare',
				'slug'   => 'healthcare',
				'icon'   => 'health_and_safety',
				'color'  => '#df6576',
				'parent' => 'expense',
			],
			[
				'name'   => 'Education',
				'slug'   => 'education',
				'icon'   => 'school',
				'color'  => '#3b73ad',
				'parent' => 'expense',
			],
			[
				'name'   => 'Groceries',
				'slug'   => 'groceries',
				'icon'   => 'local_grocery_store',
				'color'  => '#db8139',
				'parent' => 'expense',
			],
			[
				'name'   => 'Sports & Hobbies',
				'slug'   => 'sports-hobbies',
				'icon'   => 'sports_basketball',
				'color'  => '#60d0c9',
				'parent' => 'expense',
			],
			[
				'name'   => 'Beauty',
				'slug'   => 'beauty',
				'icon'   => 'face_retouching_natural',
				'color'  => '#7843d0',
				'parent' => 'expense',
			],
			[
				'name'   => 'Work',
				'slug'   => 'work',
				'icon'   => 'work',
				'color'  => '#6d6f8a',
				'parent' => 'expense',
			],
			[
				'name'   => 'Salary',
				'slug'   => 'salary',
				'icon'   => 'payments',
				'color'  => '#1eb174',
				'parent' => 'earning',
			],
			[
				'name'   => 'Business',
				'slug'   => 'business',
				'icon'   => 'storefront',
				'color'  => '#fda207',
				'parent' => 'earning',
			],
			[
				'name'   => 'Extra Income',
				'slug'   => 'extra-income',
				'icon'   => 'paid',
				'color'  => '#74c442',
				'parent' => 'earning',
			],
			[
				'name'   => 'Loan',
				'slug'   => 'loan',
				'icon'   => 'account_balance',
				'color'  => '#df6576',
				'parent' => 'earning',
			],
			[
				'name'   => 'Parental Leander',
				'slug'   => 'parental-leander',
				'icon'   => 'supervisor_account',
				'color'  => '#f9639f',
				'parent' => 'earning',
			],
			[
				'name'   => 'Insurance Payment',
				'slug'   => 'insurance-payment',
				'icon'   => 'verified_user',
				'color'  => '#44a7e5',
				'parent' => 'earning',
			],
			[
				'name'   => 'Gifts',
				'slug'   => 'gifts',
				'icon'   => 'card_giftcard',
				'color'  => '#1eb173',
				'parent' => null,
			],
			[
				'name'   => 'Other',
				'slug'   => 'other',
				'icon'   => 'quiz',
				'color'  => '#67686c',
				'parent' => null,
			],
		];

		$expense_parent = null;
		$earning_parent = null;
		$prev_terms     = [];

		foreach ( get_terms('transaction_category', [ 'hide_empty' => false ]) as $termitem ) {
			$prev_terms[] = $termitem->slug;
		}

		foreach ( $this->terms as $term_key => $term ) {
			if ( ! in_array($term['slug'], $prev_terms) ) {
				$termarray = [
					'slug' => $term['slug'],
				];

				if ( 'expense' === $term['parent'] ) {
					$termarray['parent'] = $expense_parent;
				}

				if ( 'earning' === $term['parent'] ) {
					$termarray['parent'] = $earning_parent;
				}

				$thisterm = wp_insert_term(
					$term['name'],
					$this->taxonomy,
					$termarray
				);

				if ( ! is_wp_error($thisterm) ) {
					add_term_meta($thisterm['term_id'], 'icon', $term['icon'], false);
					add_term_meta($thisterm['term_id'], 'color', $term['color'], false);

					if ( 'Expense' === $term['name'] ) {
						$expense_parent = $thisterm['term_id'];
					}

					if ( 'Earning' === $term['name'] ) {
						$earning_parent = $thisterm['term_id'];
					}
				}

				unset($term);
			}
		}
	}

	/**
	 * Adding formated date to rest response.
	 */
	public function zbs_account_formated_date_on_rest() {
		register_rest_field(
			[ 'transaction' ],
			'formatted_date',
			[
				'get_callback'    => function () {
					return get_the_date();
				},
				'update_callback' => null,
				'schema'          => null,
			]
		);

		register_rest_field(
			[ 'transaction' ],
			'category',
			[
				'get_callback'    => function ( $object ) {
					$terms = get_the_terms($object['id'], 'transaction_category');

					return ! empty($terms) ? array_pop($terms) : null;
				},
				'update_callback' => null,
				'schema'          => null,
			]
		);

		register_rest_field(
			'transaction_category',
			'meta',
			[
				'get_callback'    => function ( $object ) {
					return get_term_meta($object['id']);
				},
				'update_callback' => function ( $values, $object ) {
					foreach ( $values as $key => $value ) {
						update_term_meta($object->term_id, $key, $value);
					}
				},
				'schema'          => null,
			]
		);
	}

	/**
	 * Subscription.
	 */
	public function zbs_account_subscribe() {
		$email = isset($_post['email']) ? sanitize_text_field(wp_unslash($_post['email'])) : '';
		$users = get_users([
			'role'    => 'administrator',
			'orderby' => 'ID',
			'order'   => 'ASC',
			'number'  => 1,
			'paged'   => 1,
		]);

		$admin_user = ( is_array($users) && ! empty($users) ) ? $users[0] : false;
		$first_name = '';
		$last_name  = '';

		if ( $admin_user ) {
			$first_name = $admin_user->first_name ? $admin_user->first_name : $admin_user->display_name;
			$last_name  = $admin_user->last_name;
		}

		$webhook_url = 'https://fluent.wppool.dev/wp-admin/?fluentcrm=1&route=contact&hash=10220b66-9b67-40e6-b05b-63019fbea1a3';

		try {
			$res = wp_remote_post($webhook_url, [
				'body' => [
					'first_name' => $first_name,
					'last_name'  => $last_name,
					'email'      => $email,
					'tags[]'     => 11,
					'lists[]'    => 24,
					'source'     => esc_url(home_url()),
				],
			]);

			echo esc_html__('You\'re on the waiting list. You\'d be first to know when we launch the premium version of Zero BS Accounting', 'zbs-account');
		} catch ( \Exception $exception ) {
			print_r($exception, 1);
		}

		die();
	}
	/**
	 * Adding Account Profile.
	 */
	public function zbs_account_insert_profile() {
		$inputs = file_get_contents('php://input');
		$data   = json_decode($inputs, true);

		global $wpdb;
		$table_name = $wpdb->prefix . 'zbs_profiles';
		$name       = null;

		if ( isset($data['accountName']) && ! empty($data['accountName']) ) {
			$name = sanitize_text_field($data['accountName']);
			if ( empty($name) ) {
				wp_send_json_error([
					'message' => esc_html__('Account name is required', 'zbs-account'),
				]);
			} else {
				$uid        = get_current_user_id();
				$check_data = $wpdb->get_row($wpdb->prepare("SELECT * from {$wpdb->prefix}zbs_profiles WHERE user_id = %d", $uid));

				if ( ! is_null($check_data) ) {
					$result = $wpdb->get_row(
						$wpdb->prepare(
							"SELECT `name` FROM {$wpdb->prefix}zbs_profiles WHERE user_id = %d AND name LIKE %s",
							$uid,
							$name
						)
					);
					if ( is_null($result) ) {
						$wpdb->INSERT(
							"{$table_name}",
							[
								'name'    => $name,
								'user_id' => $uid,
							]
						);
						wp_send_json_success([
							'message' => esc_html__('Account created successfully', 'zbs-account'),
						]);
					} elseif ( ! is_null($result) ) {
						wp_send_json_error([
							'message' => esc_html__('Account name already Exists', 'zbs-account'),
						]);
					} else {
						wp_send_json_error([
							'message' => esc_html__("Account name can't be empty", 'zbs-account'),
						]);
					}
				} else {
					$wpdb->INSERT(
						"{$table_name}",
						[
							'name'    => $name,
							'user_id' => $uid,
						]
					);
					wp_send_json_success([
						'message' => esc_html__('Account created successfully', 'zbs-account'),
					]);
					wp_send_json_success([
						'message' => esc_html__('Account created successfully', 'zbs-account'),
					]);
				}
			}
		} else {
			wp_send_json_error([
				'message' => esc_html__('Account name is required', 'zbs-account'),
			]);
		}

	}

	/**
	 * Editing Account Profile.
	 */
	public function zbs_account_update_profile() {
		$inputs = file_get_contents('php://input');
		$data   = json_decode($inputs, true);

		global $wpdb;
		$user       = wp_get_current_user();
		$table_name = $wpdb->prefix . 'zbs_profiles';
		$name       = sanitize_text_field($data['updatedName']);
		$profile_id = $data['id'];
		$get_row     = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}zbs_profiles WHERE id = %d", $profile_id));
		$check_data = $wpdb->get_row($wpdb->prepare("SELECT * from {$wpdb->prefix}zbs_profiles WHERE user_id = %d", get_current_user_id()));

		if ( ! is_null($check_data) ) {
			$result = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT `name` FROM {$wpdb->prefix}zbs_profiles WHERE user_id = %d AND name LIKE %s",
					get_current_user_id(),
					$name
				)
			);
			if ( is_null($result) ) {
				if ( ! $name || ! isset($name) || empty($name) || is_null($name) || '' === $name ) {
					wp_send_json_error([
						'message' => esc_html__("Account name Can't be empty", 'zbs-account'),
					]);
				} else {
					if ( $get_row ) {
						if ( get_current_user_id() == $get_row->user_id ) {
							$wpdb->update($table_name,
							[
								'name' => $name,
							],
							[
								'id' => $profile_id,
							]
							);
							wp_send_json_success([
								'message' => esc_html__('Account Updated successfully', 'zbs-account'),
							]);
						} else {
							wp_send_json_error([
								'message' => esc_html__('Invalid User', 'zbs-account'),
							]);
						}
					} else {
						wp_send_json_error([
							'message' => esc_html__('No Data Found to Update', 'zbs-account'),
						]);
					}
				}
			} elseif ( ! is_null($result) ) {
				wp_send_json_error([
					'message' => esc_html__('Account name already Exists', 'zbs-account'),
				]);
			}
		} elseif ( is_null($check_data) ) {
			if ( ! $name || null === $name || '' === $name ) {
				wp_send_json_error([
					'message' => esc_html__("Account name Can't be empty", 'zbs-account'),
				]);
			} else {
				if ( $get_row ) {
					if ( get_current_user_id() == $get_row->user_id ) {
						$wpdb->update($table_name,
						[
							'name' => $name,
						],
						[
							'id' => $profile_id,
						]
						);
						wp_send_json_success([
							'message' => esc_html__('Account Updated successfully', 'zbs-account'),
						]);
					} else {
						wp_send_json_error([
							'message' => esc_html__('Invalid User', 'zbs-account'),
						]);
					}
				} else {
					wp_send_json_error([
						'message' => esc_html__('No Data Found to Update', 'zbs-account'),
					]);
				}
			}
		}

		die();
	}

	/**
	 * Deleting Account Profile.
	 */
	public function zbs_account_delete_profile() {
		$inputs = file_get_contents('php://input');
		$data   = json_decode($inputs, true);

		global $wpdb;
		$user       = wp_get_current_user();
		$id         = $data['id'];
		$table_name = $wpdb->prefix . 'zbs_profiles';
		$get_row    = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}zbs_profiles WHERE id = %d AND user_id = %d", $id, $user->ID));
		if ( $get_row ) {
			$wpdb->delete($table_name, [ 'id' => $id ]);
			echo esc_html__('Account Deleted', 'zbs-account');
		} else {
			echo esc_html__('There is no such Account to Delete', 'zbs-account');
		}

		die();
	}

	/**
	 * Display all the Account Profiles for respected user.
	 */
	public function zbs_account_display_profile() {
		$user = get_current_user_id();
		global $wpdb;
		$result = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}zbs_profiles WHERE user_id = %d", $user));

		wp_send_json_success($result);
	}

	/**
	 * Set user's default profile.
	 *
	 * @param int $profile_id holds profile id.
	 */
	public function zbs_account_set_default_profile_id( $profile_id ) {
		$inputs = file_get_contents('php://input');
		$data   = json_decode($inputs, true);

		$current_user_id = get_current_user_id();
		$user_id         = get_current_user_id();
		$profile_id      = $data['profileID'] ?? 0;
		global $wpdb;
		$result = $wpdb->get_row($wpdb->prepare("SELECT * from {$wpdb->prefix}zbs_profiles WHERE id = %d AND user_id = %d", $profile_id, $user_id));
		if ( $result ) {
			update_user_meta($current_user_id, 'zbs_profile', $profile_id);
			wp_send_json_success([
				'message' => esc_html__('Default Profile set', 'zbs-account'),
			]);
		} else {
			wp_send_json_error([
				'message' => esc_html__('Account does not Exist', 'zbs-account'),
			]);
		}
	}

	/**
	 * Getting the profile settings.
	 * Co work sam.
	 */
	public function get_profile_settings() {
		$profile_id = get_user_meta(get_current_user_id(), 'zbs_profile', true);

		global $wpdb;
		$profile = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}zbs_profiles WHERE id = %d", $profile_id));

		// Get the preveious version currency.
		$currency = get_option('zbs_currency');
		$currency_position = get_option('zbs_currency_position');
		$option_name = 'zbs_currency_updated';

		// Check if data not found, then set default data.
		if ( empty($currency) ) {
			$currency = 'USD';
		}

		if ( empty($currency_position) ) {
			$currency_position = 'left';
		}

		$table_name = $wpdb->prefix . 'zbs_profiles';
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ) === $table_name ) {

			 // Check if the update has already been performed.
			 $updated = get_option($option_name, false);

			if ( empty($currency) ) {
				$currency = 'USD';
			}

			if ( empty($currency_position) ) {
				$currency_position = 'left';
			}

			// Check if data already copied or not, if not then copy all the preveious data.
			if ( ! $updated ) {
				$wpdb->update(
					$table_name,
					[
						'currency' => $currency,
						'currency_position' => $currency_position,
					],
					[
						'id' => 1,
					]
				);

				// Set the option to mark the update as performed.
				update_option($option_name, true);
			}
		}

		if ( $profile ) {
			wp_send_json_success([
				'currency' => $profile->currency ? $profile->currency : $currency,
				'currency_position' => $profile->currency_position ? $profile->currency_position : $currency_position,
			]);
		} else {
			wp_send_json_success([
				'currency' => $currency,
				'currency_position' => $currency_position,
			]);
		}

		wp_die();
	}



	/**
	 * Update profile settings
	 */
	public function update_profile_settings() {
		$profile_id = get_user_meta(get_current_user_id(), 'zbs_profile', true);

		$inputs = file_get_contents('php://input');
		$data   = json_decode($inputs, true);

		global $wpdb;
		$wpdb->update(
			"{$wpdb->prefix}zbs_profiles",
			[
				'currency' => $data['zbs_currency'] ?? 'USD',
				'currency_position' => $data['zbs_currency_position'] ?? 'left',
			],
			[
				'id' => $profile_id,
			]
		);

		wp_send_json_success([
			'message' => esc_html__('Profile settings updated successfully', 'zbs-account'),
		]);

		wp_die();
	}

	/**
	 * Zbs_account_update_transaction and add new transaction here. sam
	 */
	public function zbs_account_update_transaction() {
		$inputs = file_get_contents('php://input');
		$inputs = json_decode($inputs, true);

		$transaction_id = $inputs['id'] ?? false;
		// Get the current user profile.
		$profile_id     = get_user_meta(get_current_user_id(), 'zbs_profile', true);

		$title = $inputs['title'];

		// Category.
		$category_id = $inputs['category'];

		// Meta.
		$amount = $inputs['amount'];
		$type   = $inputs['type'];
		$note   = $inputs['note'];

		// Update or create post transaction.
		$post = [
			'ID'          => $transaction_id,
			'post_title'  => $title,
			'post_type'   => 'transaction',
			'post_status' => 'publish',
			'post_author' => get_current_user_id(),

			'meta_input' => [
				'transaction_amount'  => $amount,
				'transaction_type'    => $type,
				'transaction_note'    => $note,
				'transaction_profile' => $profile_id,
				'debt_profile' => $profile_id,
			],
		];

		if ( $transaction_id && get_post($transaction_id) ) {
			wp_update_post($post);
		} else {
			// Remove ID.
			unset($post['ID']);
			$transaction_id = wp_insert_post($post);
		}

		// Update or create meta transaction.

		update_post_meta($transaction_id, 'transaction_amount', $amount);
		update_post_meta($transaction_id, 'transaction_type', $type);
		update_post_meta($transaction_id, 'transaction_note', $note);
		update_post_meta($transaction_id, 'transaction_profile', $profile_id);
		update_post_meta($transaction_id, 'debt_profile', $profile_id);

		// Update category.
		wp_set_object_terms($transaction_id, $category_id, 'transaction_category');

		wp_send_json_success([
			'message' => esc_html__('Transaction updated successfully', 'zbs-account'),
		]);
	}

	/**
	 * Profile data migration.
	 */
	public function zbs_account_migration() {
		$zbs_migration = get_option('zbs_accounting_migration', false);

		// Bail if already migrated.
		if ( true === $zbs_migration ) {
			return true;
		}

		// Create table.
		$this->zbs_account_create_database_table();

		// Migrate all users.
		$all_users = get_users();
		foreach ( $all_users as $user ) {
			$this->organize_old_transactions( $user->ID );
			$this->organize_old_debt( $user->ID );
		}

		// Down the flag.
		update_option('zbs_accounting_migration', true);
	}
	/**
	 * On first time load & new user creation & update, will retun profile id.
	 *
	 * @param int $user_id holds user_id.
	 *
	 * Co work-sam.
	 */
	public function get_default_profile_id( $user_id = null ) {
		// User er id parameter a na thakle nicher line exicute hbe.
		if ( ! $user_id ) {
			$user_id = wp_get_current_user();
		}

		// Check if the user already has a default profile.
		$profile_id  = get_user_meta($user_id, 'zbs_profile', true);

		if ( $profile_id ) {
			return $profile_id;
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'zbs_profiles';

		// Check if a default profile already exists in the database for the user.
		// phpcs:ignore
		$result = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_name} WHERE name = 'Default Profile' AND user_id = %d", $user_id ) );
		if ( $result ) {
			// If a default profile exists, update the user meta and return the existing profile ID.
			update_user_meta( $user_id, 'zbs_profile', $result->id );
			return $result->id;
		}

		// If no default profile exists, create a new one.
		$wpdb->insert($table_name,
			[
				'name'    => 'Default Profile',
				'user_id' => $user_id,
			]);

		$profile_id  = $wpdb->insert_id;
		// Update zb_profile meta.
		update_user_meta($user_id, 'zbs_profile', $profile_id );

		return $profile_id;
	}

	/**
	 * Organize old transactions
	 *
	 * @param int $user_id holds user_id.
	 *
	 * @param int $profile_id holds profile_id.
	 */
	public function organize_old_transactions( $user_id = null, $profile_id = null ) {

		$user_id = $user_id ? $user_id : wp_get_current_user();
		$profile_id  = $profile_id ? $profile_id : $this->get_default_profile_id($user_id);

		$args = [
			'post_type' => 'transaction',
			'author' => $user_id, // will come from argument.
			'posts_per_page' => -1,
			// Meta_query: transaction_profile is not set.
			'meta_query' => [
				[
					'key' => 'transaction_profile',
					'compare' => 'NOT EXISTS',
				],
			],

		];
		$transaction_query = new WP_Query($args);
		if ( $transaction_query->have_posts() ) {
			while ( $transaction_query->have_posts() ) {
				$transaction_query->the_post();
				$post_id = get_the_ID();
				update_post_meta($post_id, 'transaction_profile', $profile_id );
			}
			wp_reset_postdata();
		}
	}

	/**
	 * Organize old debt
	 *
	 * @param int $user_id holds user_id.
	 *
	 * @param int $profile_id holds profile_id.
	 */
	public function organize_old_debt( $user_id = null, $profile_id = null ) {

		$user_id = $user_id ? $user_id : wp_get_current_user();
		$profile_id  = $profile_id ? $profile_id : $this->get_default_profile_id($user_id);

		$args = [
			'post_type' => 'debt',
			'author' => $user_id, // will come from argument.
			'posts_per_page' => -1,
			// Meta_query: debt_profile is not set.
			'meta_query' => [
				[
					'key' => 'debt_profile',
					'compare' => 'NOT EXISTS',
				],
			],

		];
		$transaction_query = new WP_Query($args);
		if ( $transaction_query->have_posts() ) {
			while ( $transaction_query->have_posts() ) {
				$transaction_query->the_post();
				$post_id = get_the_ID();
				update_post_meta($post_id, 'debt_profile', $profile_id );
			}
			wp_reset_postdata();
		}
	}

	/**
	 * Create DB table.
	 * Co Work.
	 */
	public function zbs_account_create_database_table() {

		global $wpdb;
		$table_name = $wpdb->prefix . 'zbs_profiles';

		// Creating Table intially, if already not exists.
		// phpcs:ignore
		if ( $wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name ) {
			$sql        = "CREATE TABLE IF NOT EXISTS {$table_name} (
					id INT(30) NOT NULL AUTO_INCREMENT,
					name VARCHAR (250) NOT NULL,
					user_id INT (30) NOT NULL,
					currency varchar(10) DEFAULT 'USD',
					currency_position varchar(10) DEFAULT 'left',
					currency_thousand_separator varchar(10) DEFAULT ',',
					currency_decimal_separator varchar(10) DEFAULT '.',
					created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
					PRIMARY KEY (id)
				);";
				require_once (ABSPATH . 'wp-admin/includes/upgrade.php');
				dbDelta($sql);
				// Optin value will set here.
		}

	}

	/**
	 * Zbs_account_get_transaction. I dont find any reason of this code - unused for now
	 *
	 * @param int $transaction_id storing transaction id of a post.
	 */
	public function zbs_account_get_transaction( $transaction_id ) {
		$transaction = get_post($transaction_id);

		$transaction->meta     = get_post_meta($transaction_id);
		$transaction->category = wp_get_object_terms($transaction_id, 'transaction_category');

		return $transaction;
	}

	/**
	 * Zbs_account_on_user_register.
	 *
	 * @param int $user_id holds user's id on register.
	 */
	public function zbs_account_on_user_register( $user_id ) {
		$this->get_default_profile_id( $user_id );
	}
}

if ( class_exists('Zero_BS_Accounting') ) {
	$zero_bs_accounting_plugin = new Zero_BS_Accounting();
	$zero_bs_accounting_plugin->register();
}


// Activation.
require_once plugin_dir_path(__FILE__) . 'inc/zbs-account-activate.php';
register_activation_hook(__FILE__, [ '\\ZERO_BS_ACCOUNTING\\Zero_BS_AccountingPluginActivate', 'activate' ]);

// Deactivation.
require_once plugin_dir_path(__FILE__) . 'inc/zbs-account-deactivate.php';
register_deactivation_hook(__FILE__, [ '\\ZERO_BS_ACCOUNTING\\Zero_BS_AccountingPluginDeactivate', 'deactivate' ]);