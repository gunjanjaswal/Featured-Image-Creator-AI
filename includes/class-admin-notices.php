<?php
/**
 * Admin notices class
 *
 * @package AI_Featured_Image_Generator
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Class AIFIG_Admin_Notices
 *
 * Handles admin notification display.
 */
class AIFIG_Admin_Notices
{

    /**
     * Constructor.
     */
    public function __construct()
    {
        add_action('admin_notices', array($this, 'display_notices'));
    }

    /**
     * Display admin notices.
     */
    public function display_notices()
    {
        // Check for success message
        $success = get_transient('aifig_success_message');
        if ($success) {
            $this->render_notice($success, 'success');
            delete_transient('aifig_success_message');
        }

        // Check for error message
        $error = get_transient('aifig_error_message');
        if ($error) {
            $this->render_notice($error, 'error');
            delete_transient('aifig_error_message');
        }

        // Check for warning message
        $warning = get_transient('aifig_warning_message');
        if ($warning) {
            $this->render_notice($warning, 'warning');
            delete_transient('aifig_warning_message');
        }

        // Check for info message
        $info = get_transient('aifig_info_message');
        if ($info) {
            $this->render_notice($info, 'info');
            delete_transient('aifig_info_message');
        }

        // Check if API key is not set
        $this->check_api_key_notice();
    }

    /**
     * Render a notice.
     *
     * @param string $message Notice message.
     * @param string $type    Notice type (success, error, warning, info).
     */
    private function render_notice($message, $type = 'info')
    {
        $allowed_types = array('success', 'error', 'warning', 'info');

        if (!in_array($type, $allowed_types, true)) {
            $type = 'info';
        }

        printf(
            '<div class="notice notice-%s is-dismissible"><p>%s</p></div>',
            esc_attr($type),
            wp_kses_post($message)
        );
    }

    /**
     * Check if API key is configured and show notice if not.
     */
    private function check_api_key_notice()
    {
        // Only show on relevant admin pages
        $screen = get_current_screen();
        if (!$screen || !in_array($screen->id, array('post', 'edit-post', 'settings_page_aifig-settings'), true)) {
            return;
        }

        // Check if user dismissed this notice
        $user_id = get_current_user_id();
        if (get_user_meta($user_id, 'aifig_dismissed_api_key_notice', true)) {
            return;
        }

        // Check if API key is set
        $api_key = get_option('aifig_api_key');
        if (empty($api_key)) {
            $settings_url = admin_url('options-general.php?page=aifig-settings');

            printf(
                '<div class="notice notice-warning is-dismissible" data-dismissible="aifig-api-key-notice"><p>%s <a href="%s">%s</a></p></div>',
                esc_html__('Featured Image Creator AI: Please configure your API key to start generating images.', 'featured-image-creator-ai'),
                esc_url($settings_url),
                esc_html__('Go to Settings', 'featured-image-creator-ai')
            );
        }
    }

    /**
     * Set a success message.
     *
     * @param string $message Success message.
     */
    public static function set_success($message)
    {
        set_transient('aifig_success_message', $message, 30);
    }

    /**
     * Set an error message.
     *
     * @param string $message Error message.
     */
    public static function set_error($message)
    {
        set_transient('aifig_error_message', $message, 30);
    }

    /**
     * Set a warning message.
     *
     * @param string $message Warning message.
     */
    public static function set_warning($message)
    {
        set_transient('aifig_warning_message', $message, 30);
    }

    /**
     * Set an info message.
     *
     * @param string $message Info message.
     */
    public static function set_info($message)
    {
        set_transient('aifig_info_message', $message, 30);
    }
}
