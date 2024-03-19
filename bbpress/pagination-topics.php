<?php

/**
 * Pagination for pages of topics (when viewing a forum)
 *
 * @package bbPress
 * @subpackage Theme
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

do_action( 'bbp_template_before_pagination_loop' ); ?>

<div class="bbp-pagination">
	<div class="bbp-pagination-count"><?php bbp_forum_pagination_count(); ?></div>
	<div class="bbp-pagination-links socialv-bp-pagination"><?php bbp_forum_pagination_links(); ?></div>
</div>

<?php do_action( 'bbp_template_after_pagination_loop' );
