<?php

/**
 * Template part for displaying the header navigation menu
 *
 * @package socialv
 */

namespace SocialV\Utility;

$socialv_options = get_option('socialv-options');
$display_search = (class_exists('ReduxFramework') && function_exists('buddypress') && $socialv_options['header_display_search'] == 'yes') ? true : false;
$display_profile = (class_exists('ReduxFramework') && function_exists('buddypress') && $socialv_options['header_display_login'] == 'yes') ? true : false;
$display_notification = (function_exists('buddypress') && class_exists('ReduxFramework') && $socialv_options['header_display_notification'] == 'yes') ? true : false;
$display_message = (function_exists('buddypress') && class_exists('ReduxFramework') && $socialv_options['header_display_messages'] == 'yes') ? true : false;
$display_frndreq = (function_exists('buddypress') && class_exists('ReduxFramework') && $socialv_options['header_display_frndreq'] == 'yes') ? true : false;
$display_sidebar = (class_exists('ReduxFramework') && $socialv_options['header_display_side_area'] == 'yes') ? true : false;
$display_layout =  (class_exists('ReduxFramework') && $socialv_options['socialv_enable_switcher'] == 'yes') ? true : false;
$display_cart = (class_exists('ReduxFramework') && class_exists('WooCommerce') && $socialv_options['display_header_cart_button'] == 'yes') ? true : false;
$menu_item = (class_exists('ReduxFramework') && !empty($socialv_options['header_menu_limit'])) ? $socialv_options['header_menu_limit'] : '';
$display_language = (class_exists('ReduxFramework') && class_exists('SitePress') && $socialv_options['header_language_switch'] == 'yes') ? true : false;
?>
<header class="header-verticle has-sticky <?php echo esc_attr(($display_sidebar == true) ? '' : 'no-sidebar'); ?>" id="default-header">
	<div class="container-fluid">
		<div class="row">
			<div class="col-md-12">
				<nav id="site-navigation" data-menu="<?php echo esc_attr($menu_item); ?>" data-text="<?php esc_attr_e('More', 'socialv'); ?>" class="navbar deafult-header navbar-expand-xl navbar-light p-0" aria-label="<?php esc_attr_e('Main menu', 'socialv'); ?>" <?php
																																																																		if (socialv()->is_amp()) {
																																																																		?> [class]=" siteNavigationMenu.expanded ? 'main-navigation nav--toggle-sub nav--toggle-small nav--toggled-on' : 'main-navigation nav--toggle-sub nav--toggle-small' " <?php
																																																																																																											}
																																																																																																												?>>

					<?php get_template_part('template-parts/header/logo'); ?>
					<?php if ($display_sidebar == true) : ?>
						<div class="sidebar-toggle" id="menu-btn-side-close" data-toggle="sidebar" data-active="true">
							<span class="menu-btn d-inline-block is-active">
								<i class="iconly-Arrow-Right-2 icli"></i>
							</span>
						</div>
					<?php endif; ?>
					<div id="navbarSupportedContent" class="collapse navbar-collapse new-collapse">
						<div id="socialv-menu-container" class="menu-all-pages-container">
							<?php
							if (socialv()->is_primary_nav_menu_active()) {
								socialv()->display_primary_nav_menu(array(
									'menu_class' => 'sf-menu top-menu navbar-nav ml-auto',
									'item_spacing' => 'discard',
									'link_before'  => '<span class="menu-title">',
									'link_after'   => '</span>',
								));
							}
							?>
						</div>
					</div>
					<div class="socialv-header-right">
						<ul class="list-inline list-main-parent">
							<?php
							if ($display_language == true) {
								get_template_part('template-parts/header/language');
							}
							if ($display_search == true) {
								if (!(!is_user_logged_in() && isset($socialv_options['display_resticated_page']) && $socialv_options['display_resticated_page'] == 'yes')) { ?>
									<li class="inline-item header-search <?php esc_attr_e(($display_language == true) ? 'd-none' : ''); ?>">
										<?php get_template_part('template-parts/header/search'); ?>
									</li>
									<li class="inline-item header-search-toggle header-notification-icon <?php esc_attr_e(($display_language == true) ? 'd-block' : ''); ?>">
										<div class="dropdown dropdown-search">
											<button class="dropdown-toggle search-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="iconly-Search icli"></i></button>
											<div class="dropdown-menu header-search dropdown-menu-right">
												<?php get_template_part('template-parts/header/search'); ?>
											</div>
										</div>
									</li>
								<?php }
							}
							if ($display_layout == true) {
								socialv()->socialv_switch();
							}

							if ($display_frndreq == true && is_user_logged_in()) { ?>
								<li class="inline-item header-friend header-notification-icon">
									<?php get_template_part('template-parts/header/friends-request'); ?>
								</li>
							<?php }
							if ($display_message == true && is_user_logged_in()) { ?>
								<li class="inline-item header-messages header-notification-icon">
									<?php get_template_part('template-parts/header/messages'); ?>
								</li>
							<?php }
							if ($display_notification == true && is_user_logged_in()) { ?>
								<li class="inline-item header-notifcation header-notification-icon">
									<?php get_template_part('template-parts/header/notifications'); ?>
								</li>
							<?php }
							$is_woocomerce = (bool) function_exists('is_woocommerce') ? (!is_woocommerce() || !is_shop() || !is_cart() || !is_account_page()) : false;
							if ($display_cart == true && $is_woocomerce) { ?>
								<li class="inline-item header-cart-icon header-notification-icon">
									<?php get_template_part('template-parts/header/cart'); ?>
								</li>
							<?php }
							if (class_exists('ReduxFramework') && function_exists('buddypress') || class_exists('LearnPress')) { ?>
								<li class="inline-item header-login">
									<?php get_template_part('template-parts/header/user'); ?>
								</li>
							<?php } ?>
						</ul>
						<?php if (socialv()->is_primary_nav_menu_active()) { ?>
							<button class="navbar-toggler open-menu-toggle custom-toggler ham-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="<?php esc_attr_e('Toggle navigation', 'socialv'); ?>">
								<span class="menu-btn menu-btn-toggle" id="menu-btn">
									<span class="line one"></span>
									<span class="line two"></span>
									<span class="line three"></span>
								</span>
							</button>
						<?php } ?>
					</div>
				</nav><!-- #site-navigation -->
			</div>
		</div>
	</div>
</header><!-- #masthead -->

<?php
if (function_exists('buddypress') && $display_profile == true) {
	socialv()->socialv_user_profile_modal();
} ?>