<?php
/**
 * Plugin Name: OL Shortcodes (Search, Account, Add Listing)
 * Description: Shortcodes [ol_search], [ol_account], [ol_add_listing] aligned with OneListing + Directorist. Uses Directorist avatar (user meta 'pro_pic'), click-to-open account dropdown (Dashboard + Logout), and a Settings panel to enable/disable XML-RPC.
 * Version:     1.0.9
 * Author:      GoDi
 * Author URI:  https://godi.my.id
 * License:     GPLv2 or later
 * Text Domain: onelisting
 *
 * Requires at least: 5.8
 * Tested up to: 6.8.2
 * Requires PHP: 7.4
 * PHP tested up to: 8.3.24
 *
 * Requires Plugins: directorist
 * Directorist tested up to: 8.4.5
 *
 * Requires Theme: onelisting
 * Onelisting tested up to: 2.0.12 (Pro)
 * Recommended Theme: Onelisting Pro Child
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/* =========================================================
 * i18n (optional)
 * ========================================================= */
add_action( 'plugins_loaded', function(){
	load_plugin_textdomain( 'onelisting', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
});

/* =========================================================
 * XML-RPC Toggle (Public 1.0.9)
 * Option: ol_disable_xmlrpc (0/1)
 * If 1, disable XML-RPC endpoint via filter.
 * ========================================================= */
add_action( 'init', function () {
	$disabled = (int) get_option( 'ol_disable_xmlrpc', 0 ); // default: 0 (XML-RPC ON)
	if ( $disabled ) {
		add_filter( 'xmlrpc_enabled', '__return_false' );
	}
});

/* =========================================================
 * Admin Settings
 * Menu: Settings → OL Shortcodes
 * Checkbox: Disable XML-RPC
 * ========================================================= */
add_action( 'admin_menu', function () {
	add_options_page(
		'OL Shortcodes Settings',
		'OL Shortcodes',
		'manage_options',
		'ol-shortcodes-settings',
		'ol_shortcodes_settings_page_render'
	);
});

add_action( 'admin_init', function () {
	register_setting(
		'ol_shortcodes_settings',
		'ol_disable_xmlrpc',
		[
			'type'              => 'integer',
			'sanitize_callback' => function( $val ){ return $val ? 1 : 0; },
			'default'           => 0,
		]
	);
});

/**
 * Render Settings page
 */
function ol_shortcodes_settings_page_render() {
	if ( ! current_user_can( 'manage_options' ) ) return;
	$val = (int) get_option( 'ol_disable_xmlrpc', 0 );
	?>
	<div class="wrap">
		<h1>OL Shortcodes — Settings</h1>

		<form method="post" action="options.php">
			<?php settings_fields( 'ol_shortcodes_settings' ); ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">XML-RPC</th>
					<td>
						<label>
							<input type="checkbox" name="ol_disable_xmlrpc" value="1" <?php checked( $val, 1 ); ?> />
							Disable XML-RPC (recommended for security)
						</label>
						<p class="description">
							If checked, the XML-RPC endpoint (e.g., <code>/xmlrpc.php</code>) will be disabled using <code>add_filter('xmlrpc_enabled','__return_false')</code>.
							Some integrations (e.g., legacy Jetpack) may still require XML-RPC enabled.
						</p>
					</td>
				</tr>
			</table>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}

/* =========================================================
 * Enqueue Inline Assets (CSS/JS)
 * ========================================================= */
add_action( 'wp_enqueue_scripts', function () {

	$css = <<<CSS
/* === OL Shortcodes — account dropdown & menu === */
.ol-acc { position: relative; display: inline-block; }
.ol-acc__btn { background: transparent !important; border: 0; padding: 0; display: inline-flex; align-items: center; cursor: pointer; line-height: 1; }
.ol-acc__name { margin-left:.5rem; }
.ol-acc__menu {
	position:absolute; right:0; top:calc(100% + 8px);
	min-width: 200px; background:#fff;
	border:1px solid rgba(0,0,0,.08); border-radius:10px;
	box-shadow:0 10px 24px rgba(0,0,0,.12);
	padding:.4rem 0; margin:0; list-style:none;
	display:none; z-index:1000;
}
.ol-acc__menu.is-open { display:block; }
.ol-acc__menu li { list-style:none; }
.ol-acc__menu a {
	display:flex; align-items:center; gap:.55rem;
	padding:.55rem .8rem; text-decoration:none; color:#1c2635;
}
.ol-acc__menu a:hover { background:rgba(0,0,0,.05); }
.ol-acc__sep { height:1px; margin:.3rem 0; background:rgba(0,0,0,.08); }
.ol-acc__icon { width:18px; display:inline-flex; align-items:center; justify-content:center; }

/* === OL Shortcodes — avatar: force transparent, circular, without theme shadow === */
.ol-acc__avatar { display:inline-flex; align-items:center; justify-content:center; border-radius:9999px; overflow:hidden; line-height:0; }
.ol-acc__avatar, .ol-acc__btn, .ol-acc__avatar-img, .ol-acc__avatar img,
.header, .menu-area .ol-acc__avatar img {
	background: transparent !important;
	box-shadow: none !important;
}
.ol-acc__avatar-img { display:block; border-radius:9999px !important; }

/* Some themes add background to img.avatar; neutralize it */
img.avatar { background: transparent !important; box-shadow:none !important; }

/* Avoid unwanted paddings from global button styles */
.ol-acc__btn > .ol-acc__avatar { padding:0 !important; margin:0 !important; }

/* === OL Shortcodes — minimal search form === */
.ol-search-form { display:inline-flex; gap:.5rem; align-items:center; }
.ol-search-input { min-width: 220px; }
.ol-search-submit { cursor:pointer; }
CSS;

	$js = <<<JS
(function(){
	function closeAll(){
		document.querySelectorAll('.ol-acc__menu').forEach(function(m){ m.classList.remove('is-open'); });
		document.querySelectorAll('.ol-acc__btn[aria-expanded="true"]').forEach(function(b){ b.setAttribute('aria-expanded','false'); });
	}
	document.addEventListener('click', function(e){
		var btn = e.target.closest('.ol-acc__btn');
		if (btn) {
			var wrap = btn.closest('.ol-acc');
			var menu = wrap ? wrap.querySelector('.ol-acc__menu') : null;
			if (!menu) return;
			var expanded = btn.getAttribute('aria-expanded') === 'true';
			closeAll();
			if (!expanded) {
				btn.setAttribute('aria-expanded','true');
				menu.classList.add('is-open');
			}
			e.preventDefault();
			return;
		}
		if (!e.target.closest('.ol-acc')) { closeAll(); }
	});
	document.addEventListener('keydown', function(e){
		if (e.key === 'Escape') { closeAll(); }
	});
})();
JS;

	wp_register_style(  'ol-shortcodes', false, [], '1.0.9' );
	wp_enqueue_style(   'ol-shortcodes' );
	wp_add_inline_style('ol-shortcodes', $css );

	wp_register_script( 'ol-shortcodes', false, [], '1.0.9', true );
	wp_enqueue_script(  'ol-shortcodes' );
	wp_add_inline_script('ol-shortcodes', $js );
});

/* =========================================================
 * Helper: Directorist dashboard base URL
 * ========================================================= */
function ol_directorist_dashboard_base() {
	if ( class_exists( 'ATBDP_Permalink' ) && method_exists( '\ATBDP_Permalink', 'get_dashboard_page_link' ) ) {
		return \ATBDP_Permalink::get_dashboard_page_link();
	}
	$page = get_page_by_path( 'dashboard' );
	return $page ? get_permalink( $page ) : home_url( '/' );
}

/* =========================================================
 * Helper: Directorist avatar (user meta 'pro_pic'), fallback to helper/Gravatar
 * ========================================================= */
function ol_get_directorist_avatar_html( $user_id, $size = 32 ) {
	$size = (int) $size;

	// Primary: Directorist meta 'pro_pic' (attachment ID)
	$pro_pic_id = get_user_meta( $user_id, 'pro_pic', true );
	if ( $pro_pic_id ) {
		$img = wp_get_attachment_image( (int) $pro_pic_id, [ $size, $size ], false, [
			'class' => 'ol-acc__avatar-img',
			'alt'   => esc_attr__( 'Avatar', 'onelisting' ),
		] );
		if ( $img ) return $img;
	}

	// Try Directorist helper if available
	if ( function_exists( 'directorist_get_user_avatar' ) ) {
		$html = directorist_get_user_avatar( $user_id, [ 'size' => $size, 'class' => 'ol-acc__avatar-img' ] );
		if ( ! empty( $html ) ) return $html;
	}

	// Custom override hook
	$maybe = apply_filters( 'ol_get_directorist_avatar_html', '', $user_id, $size );
	if ( ! empty( $maybe ) ) return $maybe;

	// Fallback: WordPress Gravatar
	return get_avatar( $user_id, $size, '', esc_attr__( 'Avatar', 'onelisting' ), [ 'class' => 'ol-acc__avatar-img' ] );
}

/* =========================================================
 * Shortcodes register
 * ========================================================= */
add_action( 'init', function () {
	add_shortcode( 'ol_search',      'ol_search_shortcode' );
	add_shortcode( 'ol_account',     'ol_account_shortcode' );
	add_shortcode( 'ol_add_listing', 'ol_add_listing_shortcode' );
});

/* =========================================================
 * [ol_search] — trigger icon / form
 * ========================================================= */
function ol_search_shortcode( $atts = [], $content = null ) {
	$atts = shortcode_atts( [
		'mode'         => 'form', // 'trigger' or 'form'
		'placeholder'  => __( 'Search…', 'onelisting' ),
		'button_label' => __( 'Search', 'onelisting' ),
		'aria_label'   => __( 'Search form', 'onelisting' ),
		'post_type'    => '',
		'id'           => 'ol-search-' . wp_generate_password( 6, false, false ),
		'class'        => '',
		'method'       => 'get',
		'action'       => home_url( '/' ),
		'input_class'  => 'ol-search-input',
		'button_class' => 'ol-search-submit',
	], $atts, 'ol_search' );

	// Trigger icon (match OneListing header classes)
	if ( strtolower( $atts['mode'] ) === 'trigger' ) {
		ob_start(); ?>
		<a href="#"
		   class="theme-menu-action-box__search--trigger dspb-search__button <?php echo esc_attr( $atts['class'] ); ?>"
		   aria-label="<?php echo esc_attr__( 'Open search', 'onelisting' ); ?>">
			<i class="search-icon theme-icon theme-search-solid" aria-hidden="true"></i>
		</a>
		<?php
		return ob_get_clean();
	}

	// Standard search form
	ob_start(); ?>
	<form role="search"
		  id="<?php echo esc_attr( $atts['id'] ); ?>"
		  class="ol-search-form <?php echo esc_attr( $atts['class'] ); ?>"
		  method="<?php echo esc_attr( strtolower( $atts['method'] ) === 'post' ? 'post' : 'get' ); ?>"
		  action="<?php echo esc_url( $atts['action'] ); ?>"
		  aria-label="<?php echo esc_attr( $atts['aria_label'] ); ?>">
		<label class="screen-reader-text" for="<?php echo esc_attr( $atts['id'] ); ?>-field">
			<?php echo esc_html( $atts['aria_label'] ); ?>
		</label>
		<input type="search"
			   id="<?php echo esc_attr( $atts['id'] ); ?>-field"
			   class="<?php echo esc_attr( $atts['input_class'] ); ?>"
			   name="s"
			   value="<?php echo esc_attr( get_search_query() ); ?>"
			   placeholder="<?php echo esc_attr( $atts['placeholder'] ); ?>" />
		<?php if ( ! empty( $atts['post_type'] ) ) : ?>
			<input type="hidden" name="post_type" value="<?php echo esc_attr( $atts['post_type'] ); ?>">
		<?php endif; ?>
		<button type="submit" class="<?php echo esc_attr( $atts['button_class'] ); ?>" aria-label="<?php echo esc_attr( $atts['button_label'] ); ?>">
			<?php echo esc_html( $atts['button_label'] ); ?>
		</button>
	</form>
	<?php
	return ob_get_clean();
}

/* =========================================================
 * [ol_account] — Directorist Avatar + click dropdown (Dashboard & Logout)
 * ========================================================= */
function ol_account_shortcode( $atts = [], $content = null ) {
	$atts = shortcode_atts( [
		'login_text'      => __( 'Sign In', 'onelisting' ),
		'modal'           => 'true',
		'show_name'       => 'false',
		'show_avatar'     => 'true',
		'size'            => 32,
		'redirect'        => 'current',
		'dashboard_label' => __( 'Dashboard', 'onelisting' ),
		'logout_label'    => __( 'Log Out',  'onelisting' ),
		'class'           => '',
	], $atts, 'ol_account' );

	$show_name   = filter_var( $atts['show_name'], FILTER_VALIDATE_BOOLEAN );
	$show_avatar = filter_var( $atts['show_avatar'], FILTER_VALIDATE_BOOLEAN );
	$use_modal   = filter_var( $atts['modal'], FILTER_VALIDATE_BOOLEAN );
	$uid         = wp_generate_password( 6, false, false );

	// More robust current URL (works better across hosts)
	$current_url = ( is_ssl() ? 'https://' : 'http://' ) . ( $_SERVER['HTTP_HOST'] ?? '' ) . ( $_SERVER['REQUEST_URI'] ?? '' );
	$redirect    = ( $atts['redirect'] === 'current' ) ? $current_url : $atts['redirect'];

	ob_start();

	echo '<div class="theme-menu-action-box__author ' . esc_attr( $atts['class'] ) . '">';
	echo '  <div class="theme-menu-action-box__author--access-area">';

	if ( ! is_user_logged_in() ) {
		// === Guest view ===
		if ( $use_modal ) { ?>
			<div class="theme-menu-action-box__login">
				<div class="theme-menu-action-box__login--modal">
					<a href="#"
					   class="btn theme-btn btn-sm btn-outline-light"
					   data-bs-toggle="modal"
					   data-bs-target="#theme-login-modal"
					   aria-haspopup="dialog"
					   aria-controls="theme-login-modal">
						<span class="d-none d-lg-block"><?php echo esc_html( $atts['login_text'] ); ?></span>
						<?php if ( function_exists( 'directorist_icon' ) ) { directorist_icon( 'las la-user', true, '' ); } ?>
					</a>
				</div>
			</div>
		<?php } else {
			$login_url = wp_login_url( $redirect ); ?>
			<a class="btn theme-btn btn-sm btn-outline-light" href="<?php echo esc_url( $login_url ); ?>">
				<span class="d-none d-lg-block"><?php echo esc_html( $atts['login_text'] ); ?></span>
				<?php if ( function_exists( 'directorist_icon' ) ) { directorist_icon( 'las la-user', true, '' ); } ?>
			</a>
		<?php }
	} else {
		// === Logged-in view ===
		$user_id   = get_current_user_id();
		$user      = wp_get_current_user();
		$user_name = $user ? $user->display_name : '';
		$avatar_html = $show_avatar ? ol_get_directorist_avatar_html( $user_id, (int) $atts['size'] ) : '';

		$dashboard_url = ol_directorist_dashboard_base();
		$logout_url    = wp_logout_url( $redirect );
		$menu_id       = 'ol-acc-menu-' . $uid; ?>

		<div class="ol-acc">
			<button type="button"
				class="ol-acc__btn"
				aria-haspopup="menu"
				aria-expanded="false"
				aria-controls="<?php echo esc_attr( $menu_id ); ?>">
				<span class="ol-acc__avatar"><?php echo $avatar_html; ?></span>
				<?php if ( $show_name && $user_name ) : ?>
					<span class="ol-acc__name d-none d-lg-inline"><?php echo esc_html( $user_name ); ?></span>
				<?php endif; ?>
			</button>

			<ul id="<?php echo esc_attr( $menu_id ); ?>" class="ol-acc__menu" role="menu">
				<li role="none">
					<a role="menuitem" href="<?php echo esc_url( $dashboard_url ); ?>">
						<span class="ol-acc__icon"><?php if(function_exists('directorist_icon')) directorist_icon('las la-tachometer-alt'); ?></span>
						<?php echo esc_html( $atts['dashboard_label'] ); ?>
					</a>
				</li>
				<li role="none" class="ol-acc__sep" aria-hidden="true"></li>
				<li role="none">
					<a role="menuitem" href="<?php echo esc_url( $logout_url ); ?>">
						<span class="ol-acc__icon"><?php if(function_exists('directorist_icon')) directorist_icon('las la-sign-out-alt'); ?></span>
						<?php echo esc_html( $atts['logout_label'] ); ?>
					</a>
				</li>
			</ul>
		</div>
		<?php
	}

	echo '  </div>';
	echo '</div>';

	return ob_get_clean();
}

/* =========================================================
 * [ol_add_listing] — Add Listing button
 * ========================================================= */
function ol_add_listing_shortcode( $atts = [], $content = null ) {
	$atts = shortcode_atts( [
		'text'  => __( 'Add Listing', 'onelisting' ),
		'class' => 'btn theme-btn btn-sm btn-primary btn-add-listing',
		'url'   => '',
		'icon'  => 'true',
	], $atts, 'ol_add_listing' );

	$use_icon = filter_var( $atts['icon'], FILTER_VALIDATE_BOOLEAN );

	// Determine URL
	if ( ! empty( $atts['url'] ) ) {
		$link = $atts['url'];
	} elseif ( class_exists( 'ATBDP_Permalink' ) && method_exists( '\ATBDP_Permalink', 'get_add_listing_page_link' ) ) {
		$link = \ATBDP_Permalink::get_add_listing_page_link();
	} else {
		$maybe = get_page_by_path('add-listing');
		$link  = $maybe ? get_permalink($maybe) : home_url( '/add-listing/' );
	}

	ob_start(); ?>
	<a href="<?php echo esc_url( $link ); ?>"
	   class="<?php echo esc_attr( $atts['class'] ); ?>"
	   aria-label="<?php echo esc_attr( $atts['text'] ); ?>">
		<?php
		if ( $use_icon ) {
			if ( function_exists( 'directorist_icon' ) ) { directorist_icon( 'las la-plus' ); echo ' '; }
			else { echo '<span class="ol-icon" aria-hidden="true">＋</span> '; }
		}
		?>
		<span class="d-none d-lg-inline"><?php echo esc_html( $atts['text'] ); ?></span>
	</a>
	<?php
	return ob_get_clean();
}
