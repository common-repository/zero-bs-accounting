<?php
/**
 * Default Template for Zero BS Accounting.
 *
 * @package ZerBSAccounting
 */

// Exit if directly accessed.
defined( 'ABSPATH' ) || exit(1);
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=2.0">
	<link rel="pingback" href="<?php bloginfo( 'pingback_url' ); ?>">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
  <div id="zbs-account-plugin"></div>
  <?php wp_footer(); ?>
</body>
</html>
