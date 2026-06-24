<?php
/**
 * Plugin Name: Featured Image Creator AI
 * Plugin URI: https://github.com/gunjanjaswal/Featured-Image-Creator-AI
 * Description: Automatically generate 1024x675px featured images for posts using AI image generation APIs. Bring your own API key.
 * Version: 1.1.0
 * Requires at least: 5.8
 * Tested up to: 7.0
 * Requires PHP: 7.4
 * Author: Gunjan Jaswal
 * Author URI: https://www.gunjanjaswal.me
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: featured-image-creator-ai
 *
 * @package Featured_Image_Creator_AI
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
	die;
}

/**
 * Current plugin version.
 */
define('AIFIG_VERSION', '1.1.0');
define('AIFIG_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AIFIG_PLUGIN_URL', plugin_dir_url(__FILE__));
define('AIFIG_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * The code that runs during plugin activation.
 */
function aifig_activate()
{
	// Set default options
	add_option('aifig_api_provider', 'openai');
	add_option('aifig_prompt_template', 'Create a professional blog featured image for: {title}');
	add_option('aifig_image_width', 1024);
	add_option('aifig_image_height', 675);

	// AI enhancements (1.1.0).
	add_option('aifig_style_preset', 'none');
	add_option('aifig_variations_count', 4);
	add_option('aifig_alt_text_enabled', 0);

	// Text & logo overlay (1.1.0).
	add_option('aifig_overlay_enabled', 0);
	add_option('aifig_overlay_text', '{title}');
	add_option('aifig_overlay_font_weight', 'bold');
	add_option('aifig_overlay_font_scale', 7);
	add_option('aifig_overlay_max_lines', 3);
	add_option('aifig_overlay_text_color', '#ffffff');
	add_option('aifig_overlay_position', 'bottom');
	add_option('aifig_overlay_scrim', 'gradient');
	add_option('aifig_overlay_logo_id', 0);
	add_option('aifig_overlay_logo_position', 'top-right');
	add_option('aifig_overlay_logo_scale', 14);

	// Social / Open Graph variants (1.1.0).
	add_option('aifig_social_enabled', 0);
	add_option('aifig_social_types', array('og', 'square'));
	add_option('aifig_social_set_og', 1);

	// Record the installed version so the "What's New" panel only appears on a
	// genuine update, not on a fresh install.
	update_option('aifig_version', AIFIG_VERSION);

	// Flush rewrite rules
	flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'aifig_activate');

/**
 * The code that runs during plugin deactivation.
 */
function aifig_deactivate()
{
	flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'aifig_deactivate');

/**
 * Autoloader for plugin classes.
 *
 * @param string $class_name The class name to load.
 */
function aifig_autoloader($class_name)
{
	// Only autoload classes from this plugin
	if (strpos($class_name, 'AIFIG_') !== 0) {
		return;
	}

	// Convert class name to file path
	$class_name = str_replace('AIFIG_', '', $class_name);
	$class_name = strtolower(str_replace('_', '-', $class_name));

	// Check if it's an API provider class
	if (strpos($class_name, 'api-') === 0 || in_array($class_name, array('openai-provider', 'gemini-provider', 'stability-provider', 'api-interface'), true)) {
		$file = AIFIG_PLUGIN_DIR . 'includes/api/class-' . $class_name . '.php';
	} else {
		$file = AIFIG_PLUGIN_DIR . 'includes/class-' . $class_name . '.php';
	}

	if (file_exists($file)) {
		require_once $file;
	}
}
spl_autoload_register('aifig_autoloader');

/**
 * Initialize the plugin.
 */
function aifig_init()
{
	// Initialize core classes
	if (is_admin()) {
		new AIFIG_Settings();
		new AIFIG_Admin_Notices();
		new AIFIG_Post_Meta_Box();
		new AIFIG_Bulk_Generator();
		new AIFIG_Whats_New();
	}
}
add_action('init', 'aifig_init');

/**
 * Output fallback Open Graph / Twitter image tags on the front end when no
 * dedicated SEO plugin is handling them. Guarded inside the class.
 */
add_action('wp_head', array('AIFIG_Social_Variants', 'maybe_output_og_tags'), 5);

/**
 * Auto-generate featured image when scheduled post is published.
 *
 * @param WP_Post $post Post object.
 */
function aifig_auto_generate_on_publish($post)
{
	// Only process posts (not pages or custom post types)
	if ($post->post_type !== 'post') {
		return;
	}

	// Check if post already has featured image
	if (has_post_thumbnail($post->ID)) {
		return;
	}

	// Check if API is configured
	$generator = new AIFIG_Image_Generator();
	if (!$generator->is_configured()) {
		return;
	}

	// Generate featured image
	$result = $generator->generate_for_post($post->ID);

	/**
	 * Fires after an auto-generation attempt on a scheduled post publish.
	 * Hook this if you want to log the result yourself.
	 *
	 * @param int|WP_Error $result      Attachment ID on success, WP_Error on failure.
	 * @param int          $post_id     ID of the post that was published.
	 */
	do_action('aifig_auto_generate_result', $result, $post->ID);
}
add_action('future_to_publish', 'aifig_auto_generate_on_publish');
add_action('draft_to_publish', 'aifig_auto_generate_on_publish');
add_action('pending_to_publish', 'aifig_auto_generate_on_publish');

/**
 * Enqueue admin scripts and styles.
 *
 * Iframed-editor note (WordPress 7.0):
 * The block editor canvas runs inside an iframe in WP 7.0. This plugin
 * integrates only via `add_meta_box()` (a sidebar panel rendered in the
 * parent admin chrome, not the iframe). All admin JS is scoped to
 * `post.php` / `post-new.php` / `edit.php` for the meta box button and to
 * the plugin's own settings/bulk-generate screens. No assets are injected
 * into the editor iframe, so the WP 7.0 transition has no functional impact.
 */
function aifig_enqueue_admin_assets($hook)
{
	// Only load on post edit screens and plugin pages
	$allowed_hooks = array('post.php', 'post-new.php', 'edit.php');

	// Check if we are on a plugin page (settings or bulk generate)
	$is_plugin_page = (strpos($hook, 'aifig') !== false);

	if (!in_array($hook, $allowed_hooks, true) && !$is_plugin_page) {
		return;
	}

	// Media frame is needed for manual upload (post screens) and the logo
	// picker (settings page).
	wp_enqueue_media();

	wp_enqueue_style(
		'aifig-admin-css',
		AIFIG_PLUGIN_URL . 'assets/css/admin.css',
		array(),
		AIFIG_VERSION
	);

	wp_enqueue_script(
		'aifig-admin-core-js',
		AIFIG_PLUGIN_URL . 'assets/js/admin.js',
		array('jquery'),
		AIFIG_VERSION,
		true
	);

	// Localize script with AJAX URL and nonce
	wp_localize_script(
		'aifig-admin-core-js',
		'aifigData',
		array(
			'ajaxUrl' => admin_url('admin-ajax.php'),
			'nonce' => wp_create_nonce('aifig_generate_image'),
			'strings' => array(
				'generating' => __('Generating image...', 'featured-image-creator-ai'),
				'success' => __('Featured image generated successfully!', 'featured-image-creator-ai'),
				'error' => __('Error generating image. Please try again.', 'featured-image-creator-ai'),
				'confirmBatch' => __('This will generate featured images for all posts without one. Continue?', 'featured-image-creator-ai'),
				'batchProgress' => __('Processing: {current} of {total}', 'featured-image-creator-ai'),
				'batchComplete' => __('Batch generation complete!', 'featured-image-creator-ai'),
				'generatingOptions' => __('Generating options...', 'featured-image-creator-ai'),
				'settingImage' => __('Setting featured image...', 'featured-image-creator-ai'),
				'useThisOne' => __('Use this one', 'featured-image-creator-ai'),
				'variationsError' => __('Could not generate options. Please try again.', 'featured-image-creator-ai'),
				'selectLogo' => __('Select Logo / Watermark', 'featured-image-creator-ai'),
			),
		)
	);
}
add_action('admin_enqueue_scripts', 'aifig_enqueue_admin_assets');

/**
 * Add custom action links to plugin page.
 *
 * @param array $links Existing plugin action links.
 * @return array Modified plugin action links.
 */
function aifig_plugin_action_links($links)
{
	$custom_links = array(
		'settings' => '<a href="' . admin_url('options-general.php?page=aifig-settings') . '">' . __('Settings', 'featured-image-creator-ai') . '</a>',
		'kofi' => '<a href="https://ko-fi.com/gunjanjaswal" target="_blank" style="color:#0073aa;font-weight:bold;">' . __('Support on Ko-fi', 'featured-image-creator-ai') . '</a>',
	);

	return array_merge($custom_links, $links);
}
add_filter('plugin_action_links_' . AIFIG_PLUGIN_BASENAME, 'aifig_plugin_action_links');

/**
 * Add custom row meta links to plugin page.
 *
 * @param array  $links Existing plugin row meta.
 * @param string $file  Plugin file path.
 * @return array Modified plugin row meta.
 */
function aifig_plugin_row_meta($links, $file)
{
	if (AIFIG_PLUGIN_BASENAME === $file) {
		$links[] = '<a href="https://wordpress.org/support/plugin/featured-image-creator-ai/" target="_blank">' . __('Plugin Support', 'featured-image-creator-ai') . '</a>';
		$links[] = '<a href="mailto:hello@gunjanjaswal.me">' . __('Contact Developer', 'featured-image-creator-ai') . '</a>';
	}

	return $links;
}
add_filter('plugin_row_meta', 'aifig_plugin_row_meta', 10, 2);

/* -------------------------------------------------------------------------
 * WordPress 7.0 integrations: AI Client API + Connectors API
 *
 * WordPress 7.0 ships:
 *   - `wp_supports_ai()` capability check.
 *   - `wp_ai_client_prompt( $prompt )` returning a `WP_AI_Client_Prompt_Builder`.
 *   - A Connectors API + central Connections screen. Plugins register via
 *     the `wp_connectors_init` action, calling `$registry->register( $id, $args )`.
 *
 * Both touchpoints are `function_exists()`-guarded so this plugin works on
 * WP 6.x too. The bundled OpenAI / Gemini / Stability providers remain the
 * default image-generation path; the WP AI Client is opt-in via filter.
 * ---------------------------------------------------------------------- */

/**
 * Indicate whether the site has the WordPress 7.0 AI Client available.
 *
 * Combines core's `wp_supports_ai()` capability check with a plugin-level
 * filter `aifig_use_wp_ai_client` so site owners can disable routing through
 * the core client even when present.
 *
 * @return bool
 */
function aifig_is_wp_ai_client_available()
{
	if (!function_exists('wp_supports_ai') || !function_exists('wp_ai_client_prompt')) {
		return false;
	}
	if (!wp_supports_ai()) {
		return false;
	}
	return (bool) apply_filters('aifig_use_wp_ai_client', true);
}

/**
 * Build a prompt against the WordPress 7.0 AI Client, if available.
 *
 * Returns a `WP_AI_Client_Prompt_Builder` ready for `->generateText()` /
 * `->generateImage()` etc., or null when the core client is not active.
 * Callers can also pass the prompt through `aifig_ai_client_prompt` filter
 * for last-minute mutation.
 *
 * @param string|null $prompt Optional initial prompt content.
 * @return \WP_AI_Client_Prompt_Builder|null
 */
function aifig_wp_ai_client_prompt($prompt = null)
{
	if (!aifig_is_wp_ai_client_available()) {
		return null;
	}
	$prompt = apply_filters('aifig_ai_client_prompt', $prompt);
	return wp_ai_client_prompt($prompt);
}

/**
 * Register this plugin's API key with the WordPress 7.0 Connectors API.
 *
 * The plugin stores a single encrypted key in `aifig_api_key` regardless of
 * the active provider (the active provider is held in `aifig_api_provider`).
 * One `ai_provider` connector is registered so the key surfaces on the
 * central Connections screen alongside core's auto-discovered providers.
 *
 * Note: the value stored in `aifig_api_key` is encrypted by this plugin's
 * sanitize callback. Reads from the Connections screen will see the encrypted
 * blob, not the original key. Writes go through the same `register_setting()`
 * sanitize callback, which re-encrypts plaintext input.
 *
 * @param WP_Connector_Registry $registry Core connector registry instance.
 */
function aifig_register_connectors($registry)
{
	if (!is_object($registry) || !method_exists($registry, 'register')) {
		do_action('aifig_register_connectors', false);
		return;
	}

	$active_provider = get_option('aifig_api_provider', 'openai');
	$provider_labels = array(
		'openai'    => __('OpenAI (DALL-E / GPT Image)', 'featured-image-creator-ai'),
		'gemini'    => __('Google Gemini (Imagen)', 'featured-image-creator-ai'),
		'stability' => __('Stability AI', 'featured-image-creator-ai'),
	);
	$credentials_urls = array(
		'openai'    => 'https://platform.openai.com/api-keys',
		'gemini'    => 'https://aistudio.google.com/app/apikey',
		'stability' => 'https://platform.stability.ai/account/keys',
	);

	$provider_name = isset($provider_labels[$active_provider]) ? $provider_labels[$active_provider] : __('AI Provider', 'featured-image-creator-ai');
	$credentials   = isset($credentials_urls[$active_provider]) ? $credentials_urls[$active_provider] : '';

	$args = array(
		/* translators: %s: Active AI provider name. */
		'name'           => sprintf(__('Featured Image Creator AI — %s', 'featured-image-creator-ai'), $provider_name),
		'description'    => __('AI image-generation provider used by Featured Image Creator AI. The key is encrypted at rest.', 'featured-image-creator-ai'),
		'type'           => 'ai_provider',
		'authentication' => array(
			'method'       => 'api_key',
			'setting_name' => 'aifig_api_key',
		),
		'plugin'         => array(
			'file'      => AIFIG_PLUGIN_BASENAME,
			'is_active' => function () {
				return defined('AIFIG_VERSION');
			},
		),
	);

	if (!empty($credentials)) {
		$args['authentication']['credentials_url'] = $credentials;
	}

	$registry->register('aifig-image-generator', $args);

	do_action('aifig_register_connectors', true);
}
add_action('wp_connectors_init', 'aifig_register_connectors');
