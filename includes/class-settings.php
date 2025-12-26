<?php
/**
 * Settings page class
 *
 * @package AI_Featured_Image_Generator
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Class AIFIG_Settings
 *
 * Handles plugin settings page and options.
 */
class AIFIG_Settings
{

    /**
     * Constructor.
     */
    public function __construct()
    {
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('admin_init', array($this, 'register_settings'));
    }

    /**
     * Add settings page to WordPress admin menu.
     */
    public function add_settings_page()
    {
        // Add top-level menu
        add_menu_page(
            __('Featured Image Creator AI', 'featured-image-creator-ai'),
            __('Featured Image Creator AI', 'featured-image-creator-ai'),
            'manage_options',
            'aifig-settings',
            array($this, 'render_settings_page'),
            'dashicons-format-image',
            30
        );

        // Add Settings submenu (will replace the default first item)
        add_submenu_page(
            'aifig-settings',
            __('Settings', 'featured-image-creator-ai'),
            __('Settings', 'featured-image-creator-ai'),
            'manage_options',
            'aifig-settings',
            array($this, 'render_settings_page')
        );
    }

    /**
     * Register plugin settings.
     */
    public function register_settings()
    {
        // Register settings
        register_setting(
            'aifig_settings_group',
            'aifig_api_key',
            array(
                'type' => 'string',
                'sanitize_callback' => array($this, 'sanitize_api_key'),
                'default' => '',
            )
        );

        register_setting(
            'aifig_settings_group',
            'aifig_api_provider',
            array(
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => 'openai',
            )
        );

        register_setting(
            'aifig_settings_group',
            'aifig_prompt_template',
            array(
                'type' => 'string',
                'sanitize_callback' => array('AIFIG_Security', 'sanitize_prompt_template'),
                'default' => 'Create a professional blog featured image for: {title}',
            )
        );

        register_setting(
            'aifig_settings_group',
            'aifig_image_width',
            array(
                'type' => 'integer',
                'sanitize_callback' => 'absint',
                'default' => 1024,
            )
        );

        register_setting(
            'aifig_settings_group',
            'aifig_image_height',
            array(
                'type' => 'integer',
                'sanitize_callback' => 'absint',
                'default' => 675,
            )
        );

        // Add settings sections
        add_settings_section(
            'aifig_api_section',
            __('API Configuration', 'featured-image-creator-ai'),
            array($this, 'render_api_section'),
            'aifig-settings'
        );

        add_settings_section(
            'aifig_image_section',
            __('Image Settings', 'featured-image-creator-ai'),
            array($this, 'render_image_section'),
            'aifig-settings'
        );

        // Add settings fields
        add_settings_field(
            'aifig_api_provider',
            __('API Provider', 'featured-image-creator-ai'),
            array($this, 'render_api_provider_field'),
            'aifig-settings',
            'aifig_api_section'
        );

        add_settings_field(
            'aifig_api_key',
            __('API Key', 'featured-image-creator-ai'),
            array($this, 'render_api_key_field'),
            'aifig-settings',
            'aifig_api_section'
        );

        add_settings_field(
            'aifig_prompt_template',
            __('Prompt Template', 'featured-image-creator-ai'),
            array($this, 'render_prompt_template_field'),
            'aifig-settings',
            'aifig_image_section'
        );

        add_settings_field(
            'aifig_image_dimensions',
            __('Image Dimensions', 'featured-image-creator-ai'),
            array($this, 'render_image_dimensions_field'),
            'aifig-settings',
            'aifig_image_section'
        );
    }

    /**
     * Sanitize API key before saving.
     *
     * @param string $api_key API key to sanitize.
     * @return string Encrypted API key.
     */
    public function sanitize_api_key($api_key)
    {
        $api_key = AIFIG_Security::sanitize_api_key($api_key);

        // If empty, keep the existing key (don't overwrite)
        if (empty($api_key)) {
            return get_option('aifig_api_key', '');
        }

        // Encrypt the API key before storing
        return AIFIG_Security::encrypt($api_key);
    }

    /**
     * Render API section description.
     */
    public function render_api_section()
    {
        echo '<p>' . esc_html__('Configure your AI image generation API settings. Your API key is encrypted before storage.', 'featured-image-creator-ai') . '</p>';
    }

    /**
     * Render image section description.
     */
    public function render_image_section()
    {
        echo '<p>' . esc_html__('Customize how images are generated for your posts.', 'featured-image-creator-ai') . '</p>';
    }

    /**
     * Render API provider field.
     */
    public function render_api_provider_field()
    {
        $provider = get_option('aifig_api_provider', 'openai');
        ?>
        <select name="aifig_api_provider" id="aifig_api_provider">
            <option value="openai" <?php selected($provider, 'openai'); ?>>
                <?php esc_html_e('OpenAI DALL-E 3', 'featured-image-creator-ai'); ?>
            </option>
            <option value="gemini" <?php selected($provider, 'gemini'); ?>>
                <?php esc_html_e('Google Gemini (Imagen)', 'featured-image-creator-ai'); ?>
            </option>
            <option value="stability" <?php selected($provider, 'stability'); ?>>
                <?php esc_html_e('Stability AI (Stable Diffusion 3)', 'featured-image-creator-ai'); ?>
            </option>
        </select>
        <p class="description">
            <?php esc_html_e('Select your AI image generation provider.', 'featured-image-creator-ai'); ?>
        </p>
        </p>
        <?php
    }

    /**
     * Render API key field.
     */
    public function render_api_key_field()
    {
        $encrypted_key = get_option('aifig_api_key', '');
        $has_key = !empty($encrypted_key);
        ?>
        <input type="password" name="aifig_api_key" id="aifig_api_key" class="regular-text"
            placeholder="<?php echo $has_key ? esc_attr__('••••••••••••••••', 'featured-image-creator-ai') : esc_attr__('Enter your API key', 'featured-image-creator-ai'); ?>"
            autocomplete="off" />
        <?php if ($has_key): ?>
            <p class="description">
                <?php esc_html_e('API key is configured. Leave blank to keep current key, or enter a new key to update.', 'featured-image-creator-ai'); ?>
            </p>
        <?php else: ?>
            <p class="description">
                <?php
                $provider = get_option('aifig_api_provider', 'openai');
                if ('openai' === $provider) {
                    printf(
                        /* translators: %s: OpenAI API keys URL */
                        esc_html__('Get your API key from %s', 'featured-image-creator-ai'),
                        '<a href="https://platform.openai.com/api-keys" target="_blank">OpenAI Platform</a>'
                    );
                } elseif ('gemini' === $provider) {
                    printf(
                        /* translators: %s: Google AI Studio URL */
                        esc_html__('Get your API key from %s', 'featured-image-creator-ai'),
                        '<a href="https://aistudio.google.com/app/apikey" target="_blank">Google AI Studio</a>'
                    );
                } elseif ('stability' === $provider) {
                    printf(
                        /* translators: %s: Stability AI URL */
                        esc_html__('Get your API key from %s', 'featured-image-creator-ai'),
                        '<a href="https://platform.stability.ai/account/keys" target="_blank">Stability AI Platform</a>'
                    );
                }
                ?>
            </p>
        <?php endif; ?>
    <?php
    }

    /**
     * Render prompt template field.
     */
    public function render_prompt_template_field()
    {
        $template = get_option('aifig_prompt_template', 'Create a professional blog featured image for: {title}');
        ?>
        <textarea name="aifig_prompt_template" id="aifig_prompt_template" rows="3"
            class="large-text"><?php echo esc_textarea($template); ?></textarea>
        <p class="description">
            <?php esc_html_e('Template for generating image prompts. Use {title} as a placeholder for the post title.', 'featured-image-creator-ai'); ?>
        </p>
        <?php
    }

    /**
     * Render image dimensions field.
     */
    public function render_image_dimensions_field()
    {
        $width = get_option('aifig_image_width', 1024);
        $height = get_option('aifig_image_height', 675);
        ?>
        <input type="number" name="aifig_image_width" id="aifig_image_width" value="<?php echo esc_attr($width); ?>" min="256"
            max="2048" class="small-text" /> ×
        <input type="number" name="aifig_image_height" id="aifig_image_height" value="<?php echo esc_attr($height); ?>"
            min="256" max="2048" class="small-text" /> px
        <p class="description">
            <?php esc_html_e('Desired image dimensions. Default: 1024×675px (recommended for featured images).', 'featured-image-creator-ai'); ?>
        </p>
        <?php
    }

    /**
     * Render settings page.
     */
    public function render_settings_page()
    {
        if (!current_user_can('manage_options')) {
            return;
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

            <form action="options.php" method="post">
                <?php
                settings_fields('aifig_settings_group');
                do_settings_sections('aifig-settings');
                submit_button(__('Save Settings', 'featured-image-creator-ai'));
                ?>
            </form>

            <hr style="margin: 40px 0; border: none; border-top: 1px solid #dcdcde;">

            <div class="aifig-quick-actions"
                style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; border-radius: 8px; color: white; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                <h2 style="margin-top: 0; color: white; font-size: 24px;">🚀 Quick Actions</h2>
                <p style="margin-bottom: 20px; opacity: 0.9;">Generate featured images for your posts</p>

                <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                    <a href="<?php echo esc_url(admin_url('tools.php?page=aifig-bulk-generate')); ?>" class="button button-hero"
                        style="background: white; color: #667eea; border: none; padding: 12px 24px; font-weight: 600; box-shadow: 0 2px 4px rgba(0,0,0,0.1); text-decoration: none; display: inline-flex; align-items: center; gap: 8px;">
                        <span class="dashicons dashicons-images-alt2" style="font-size: 20px;"></span>
                        <?php esc_html_e('Bulk Generate Images', 'featured-image-creator-ai'); ?>
                    </a>

                    <a href="<?php echo esc_url(admin_url('edit.php?post_type=post')); ?>" class="button button-hero"
                        style="background: rgba(255,255,255,0.2); color: white; border: 1px solid rgba(255,255,255,0.3); padding: 12px 24px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 8px;">
                        <span class="dashicons dashicons-edit" style="font-size: 20px;"></span>
                        <?php esc_html_e('Manage Posts', 'featured-image-creator-ai'); ?>
                    </a>
                </div>

                <p style="margin-top: 20px; margin-bottom: 0; font-size: 14px; opacity: 0.8;">
                    💡 <strong>Tip:</strong> You can also generate images from individual post editors using the sidebar meta
                    box.
                </p>
            </div>
        </div>
        <?php
    }
}
