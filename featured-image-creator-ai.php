<?php
/**
 * Plugin Name: Featured Image Creator AI
 * Plugin URI: https://github.com/gunjanjaswal/Featured-Image-Creator-AI
 * Description: Automatically generate 1024x675px featured images for posts using AI image generation APIs. Bring your own API key.
 * Version: 1.0.1
 * Requires at least: 5.8
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
define('AIFIG_VERSION', '1.0.3');
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
	}
}
add_action('init', 'aifig_init');

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

	// Log result for debugging
	if (is_wp_error($result)) {
		error_log('AIFIG: Failed to auto-generate image for post ' . $post->ID . ': ' . $result->get_error_message());
	} else {
		error_log('AIFIG: Successfully auto-generated image for post ' . $post->ID);
	}
}
add_action('future_to_publish', 'aifig_auto_generate_on_publish');

/**
 * Enqueue admin scripts and styles.
 */
function aifig_enqueue_admin_assets($hook)
{
	// Only load on post edit screens, settings page, and bulk generation page
	$allowed_hooks = array('post.php', 'post-new.php', 'edit.php', 'settings_page_aifig-settings', 'tools_page_aifig-bulk-generate');

	if (!in_array($hook, $allowed_hooks, true)) {
		return;
	}

	wp_enqueue_style(
		'aifig-admin-css',
		AIFIG_PLUGIN_URL . 'assets/css/admin.css',
		array(),
		AIFIG_VERSION
	);

	wp_enqueue_script(
		'aifig-admin-js',
		AIFIG_PLUGIN_URL . 'assets/js/admin.js',
		array('jquery'),
		AIFIG_VERSION,
		true
	);

	// Localize script with AJAX URL and nonce
	wp_localize_script(
		'aifig-admin-js',
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
		'coffee' => '<a href="https://buymeacoffee.com/gunjanjaswal" target="_blank" style="color:#ff813f;font-weight:bold;">☕ ' . __('Buy me a coffee', 'featured-image-creator-ai') . '</a>',
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
		$custom_links = array(
			'docs' => '<a href="https://github.com/gunjanjaswal/ai-featured-image-generator#readme" target="_blank">' . __('Documentation', 'featured-image-creator-ai') . '</a>',
			'support' => '<a href="https://wordpress.org/support/plugin/ai-featured-image-generator/" target="_blank">' . __('Support', 'featured-image-creator-ai') . '</a>',
		);

		$links = array_merge($links, $custom_links);
	}

	return $links;
}
add_filter('plugin_row_meta', 'aifig_plugin_row_meta', 10, 2);
