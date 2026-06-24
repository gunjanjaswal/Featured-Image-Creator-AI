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
            'aifig_image_quality',
            array(
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => 'standard',
            )
        );

        register_setting(
            'aifig_settings_group',
            'aifig_output_format',
            array(
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => 'png',
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

        // --- AI enhancements ---
        register_setting(
            'aifig_settings_group',
            'aifig_style_preset',
            array(
                'type' => 'string',
                'sanitize_callback' => array('AIFIG_Styles', 'sanitize'),
                'default' => 'none',
            )
        );

        register_setting(
            'aifig_settings_group',
            'aifig_variations_count',
            array(
                'type' => 'integer',
                'sanitize_callback' => array($this, 'sanitize_variations_count'),
                'default' => 4,
            )
        );

        register_setting(
            'aifig_settings_group',
            'aifig_alt_text_enabled',
            array(
                'type' => 'boolean',
                'sanitize_callback' => array($this, 'sanitize_checkbox'),
                'default' => 0,
            )
        );

        // --- Text & logo overlay ---
        register_setting(
            'aifig_settings_group',
            'aifig_overlay_enabled',
            array(
                'type' => 'boolean',
                'sanitize_callback' => array($this, 'sanitize_checkbox'),
                'default' => 0,
            )
        );

        register_setting(
            'aifig_settings_group',
            'aifig_overlay_text',
            array(
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => '{title}',
            )
        );

        register_setting(
            'aifig_settings_group',
            'aifig_overlay_font_weight',
            array(
                'type' => 'string',
                'sanitize_callback' => array($this, 'sanitize_font_weight'),
                'default' => 'bold',
            )
        );

        register_setting(
            'aifig_settings_group',
            'aifig_overlay_font_scale',
            array(
                'type' => 'integer',
                'sanitize_callback' => array($this, 'sanitize_scale'),
                'default' => 7,
            )
        );

        register_setting(
            'aifig_settings_group',
            'aifig_overlay_max_lines',
            array(
                'type' => 'integer',
                'sanitize_callback' => array($this, 'sanitize_max_lines'),
                'default' => 3,
            )
        );

        register_setting(
            'aifig_settings_group',
            'aifig_overlay_text_color',
            array(
                'type' => 'string',
                'sanitize_callback' => array($this, 'sanitize_hex'),
                'default' => '#ffffff',
            )
        );

        register_setting(
            'aifig_settings_group',
            'aifig_overlay_position',
            array(
                'type' => 'string',
                'sanitize_callback' => array($this, 'sanitize_position'),
                'default' => 'bottom',
            )
        );

        register_setting(
            'aifig_settings_group',
            'aifig_overlay_scrim',
            array(
                'type' => 'string',
                'sanitize_callback' => array($this, 'sanitize_scrim'),
                'default' => 'gradient',
            )
        );

        register_setting(
            'aifig_settings_group',
            'aifig_overlay_logo_id',
            array(
                'type' => 'integer',
                'sanitize_callback' => 'absint',
                'default' => 0,
            )
        );

        register_setting(
            'aifig_settings_group',
            'aifig_overlay_logo_position',
            array(
                'type' => 'string',
                'sanitize_callback' => array($this, 'sanitize_corner'),
                'default' => 'top-right',
            )
        );

        register_setting(
            'aifig_settings_group',
            'aifig_overlay_logo_scale',
            array(
                'type' => 'integer',
                'sanitize_callback' => array($this, 'sanitize_scale'),
                'default' => 14,
            )
        );

        // --- Social / Open Graph variants ---
        register_setting(
            'aifig_settings_group',
            'aifig_social_enabled',
            array(
                'type' => 'boolean',
                'sanitize_callback' => array($this, 'sanitize_checkbox'),
                'default' => 0,
            )
        );

        register_setting(
            'aifig_settings_group',
            'aifig_social_types',
            array(
                'type' => 'array',
                'sanitize_callback' => array($this, 'sanitize_social_types'),
                'default' => array('og', 'square'),
            )
        );

        register_setting(
            'aifig_settings_group',
            'aifig_social_set_og',
            array(
                'type' => 'boolean',
                'sanitize_callback' => array($this, 'sanitize_checkbox'),
                'default' => 1,
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
            'aifig_image_quality',
            __('Image Quality', 'featured-image-creator-ai'),
            array($this, 'render_image_quality_field'),
            'aifig-settings',
            'aifig_image_section'
        );

        add_settings_field(
            'aifig_output_format',
            __('Output Format', 'featured-image-creator-ai'),
            array($this, 'render_output_format_field'),
            'aifig-settings',
            'aifig_image_section'
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

        // --- AI Enhancements section ---
        add_settings_section(
            'aifig_enhance_section',
            __('AI Enhancements', 'featured-image-creator-ai'),
            array($this, 'render_enhance_section'),
            'aifig-settings'
        );

        add_settings_field(
            'aifig_style_preset',
            __('Default Style', 'featured-image-creator-ai'),
            array($this, 'render_style_preset_field'),
            'aifig-settings',
            'aifig_enhance_section'
        );

        add_settings_field(
            'aifig_variations_count',
            __('Variations', 'featured-image-creator-ai'),
            array($this, 'render_variations_count_field'),
            'aifig-settings',
            'aifig_enhance_section'
        );

        add_settings_field(
            'aifig_alt_text_enabled',
            __('Auto Alt Text', 'featured-image-creator-ai'),
            array($this, 'render_alt_text_field'),
            'aifig-settings',
            'aifig_enhance_section'
        );

        // --- Text & Logo Overlay section ---
        add_settings_section(
            'aifig_overlay_section',
            __('Text & Logo Overlay', 'featured-image-creator-ai'),
            array($this, 'render_overlay_section'),
            'aifig-settings'
        );

        add_settings_field(
            'aifig_overlay_enabled',
            __('Enable Overlay', 'featured-image-creator-ai'),
            array($this, 'render_overlay_enabled_field'),
            'aifig-settings',
            'aifig_overlay_section'
        );

        add_settings_field(
            'aifig_overlay_text',
            __('Headline Text', 'featured-image-creator-ai'),
            array($this, 'render_overlay_text_field'),
            'aifig-settings',
            'aifig_overlay_section'
        );

        add_settings_field(
            'aifig_overlay_style',
            __('Text Style', 'featured-image-creator-ai'),
            array($this, 'render_overlay_text_style_field'),
            'aifig-settings',
            'aifig_overlay_section'
        );

        add_settings_field(
            'aifig_overlay_logo',
            __('Logo / Watermark', 'featured-image-creator-ai'),
            array($this, 'render_overlay_logo_field'),
            'aifig-settings',
            'aifig_overlay_section'
        );

        // --- Social & Open Graph Images section ---
        add_settings_section(
            'aifig_social_section',
            __('Social & Open Graph Images', 'featured-image-creator-ai'),
            array($this, 'render_social_section'),
            'aifig-settings'
        );

        add_settings_field(
            'aifig_social_enabled',
            __('Enable Social Images', 'featured-image-creator-ai'),
            array($this, 'render_social_enabled_field'),
            'aifig-settings',
            'aifig_social_section'
        );

        add_settings_field(
            'aifig_social_types',
            __('Sizes to Create', 'featured-image-creator-ai'),
            array($this, 'render_social_types_field'),
            'aifig-settings',
            'aifig_social_section'
        );

        add_settings_field(
            'aifig_social_set_og',
            __('Open Graph Image', 'featured-image-creator-ai'),
            array($this, 'render_social_set_og_field'),
            'aifig-settings',
            'aifig_social_section'
        );
    }

    /**
     * Sanitize the selected social variant types to known keys.
     *
     * @param mixed $value Raw value (array of keys).
     * @return array
     */
    public function sanitize_social_types($value)
    {
        $valid = array_keys(AIFIG_Social_Variants::get_specs());
        if (!is_array($value)) {
            return array();
        }
        $value = array_map('sanitize_text_field', $value);
        return array_values(array_intersect($valid, $value));
    }

    /**
     * Sanitize a checkbox value to 1 or 0.
     *
     * @param mixed $value Raw value.
     * @return int
     */
    public function sanitize_checkbox($value)
    {
        return !empty($value) ? 1 : 0;
    }

    /**
     * Sanitize the variations count (1-8).
     *
     * @param mixed $value Raw value.
     * @return int
     */
    public function sanitize_variations_count($value)
    {
        return max(1, min(8, absint($value)));
    }

    /**
     * Sanitize a percentage scale (2-40).
     *
     * @param mixed $value Raw value.
     * @return int
     */
    public function sanitize_scale($value)
    {
        return max(2, min(40, absint($value)));
    }

    /**
     * Sanitize max lines (1-6).
     *
     * @param mixed $value Raw value.
     * @return int
     */
    public function sanitize_max_lines($value)
    {
        return max(1, min(6, absint($value)));
    }

    /**
     * Sanitize a hex color, falling back to white.
     *
     * @param mixed $value Raw value.
     * @return string
     */
    public function sanitize_hex($value)
    {
        $color = sanitize_hex_color($value);
        return $color ? $color : '#ffffff';
    }

    /**
     * Sanitize the font weight option.
     *
     * @param mixed $value Raw value.
     * @return string
     */
    public function sanitize_font_weight($value)
    {
        $value = sanitize_text_field($value);
        return in_array($value, array('bold', 'semibold', 'regular'), true) ? $value : 'bold';
    }

    /**
     * Sanitize the vertical text position option.
     *
     * @param mixed $value Raw value.
     * @return string
     */
    public function sanitize_position($value)
    {
        $value = sanitize_text_field($value);
        return in_array($value, array('top', 'middle', 'bottom'), true) ? $value : 'bottom';
    }

    /**
     * Sanitize the scrim option.
     *
     * @param mixed $value Raw value.
     * @return string
     */
    public function sanitize_scrim($value)
    {
        $value = sanitize_text_field($value);
        return in_array($value, array('none', 'dark', 'light', 'gradient'), true) ? $value : 'gradient';
    }

    /**
     * Sanitize a corner position option.
     *
     * @param mixed $value Raw value.
     * @return string
     */
    public function sanitize_corner($value)
    {
        $value = sanitize_text_field($value);
        $valid = array('top-left', 'top-right', 'bottom-left', 'bottom-right');
        return in_array($value, $valid, true) ? $value : 'top-right';
    }

    /**
     * Sanitize API key before save.
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
            <optgroup label="OpenAI">
                <option value="openai" <?php selected($provider, 'openai'); ?>>
                    <?php esc_html_e('DALL-E 3', 'featured-image-creator-ai'); ?>
                </option>
                <option value="gpt-image-1" <?php selected($provider, 'gpt-image-1'); ?>>
                    <?php esc_html_e('GPT Image 1', 'featured-image-creator-ai'); ?>
                </option>
                <option value="gpt-image-1-mini" <?php selected($provider, 'gpt-image-1-mini'); ?>>
                    <?php esc_html_e('GPT Image 1 (Mini)', 'featured-image-creator-ai'); ?>
                </option>
                <option value="gpt-image-1.5" <?php selected($provider, 'gpt-image-1.5'); ?>>
                    <?php esc_html_e('GPT Image 1.5', 'featured-image-creator-ai'); ?>
                </option>
                <option value="gpt-image-latest" <?php selected($provider, 'gpt-image-latest'); ?>>
                    <?php esc_html_e('GPT Image Latest', 'featured-image-creator-ai'); ?>
                </option>
            </optgroup>
            <optgroup label="Google">
                <option value="gemini" <?php selected($provider, 'gemini'); ?>>
                    <?php esc_html_e('Google Gemini (Imagen)', 'featured-image-creator-ai'); ?>
                </option>
            </optgroup>
            <optgroup label="Stability AI">
                <option value="stability" <?php selected($provider, 'stability'); ?>>
                    <?php esc_html_e('Stable Diffusion 3', 'featured-image-creator-ai'); ?>
                </option>
                <option value="seedream-4.5" <?php selected($provider, 'seedream-4.5'); ?>>
                    <?php esc_html_e('SeeDream 4.5', 'featured-image-creator-ai'); ?>
                </option>
            </optgroup>
        </select>
        <p class="description">
            <?php esc_html_e('Select your AI image generation provider and model.', 'featured-image-creator-ai'); ?>
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
                if (in_array($provider, ['openai', 'gpt-image-1', 'gpt-image-1.5', 'gpt-image-latest'])) {
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
                } elseif (in_array($provider, ['stability', 'seedream-4.5'])) {
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
     * Render image quality field.
     */
    public function render_image_quality_field()
    {
        $quality = get_option('aifig_image_quality', 'standard');
        ?>
        <select name="aifig_image_quality" id="aifig_image_quality">
            <option value="standard" <?php selected($quality, 'standard'); ?>>
                <?php esc_html_e('Standard', 'featured-image-creator-ai'); ?>
            </option>
            <option value="hd" <?php selected($quality, 'hd'); ?>>
                <?php esc_html_e('HD', 'featured-image-creator-ai'); ?>
            </option>
            <option value="low" <?php selected($quality, 'low'); ?>>
                <?php esc_html_e('Low', 'featured-image-creator-ai'); ?>
            </option>
        </select>
        <p class="description">
            <?php esc_html_e('Select image quality (only applies to supported models like DALL-E 3).', 'featured-image-creator-ai'); ?>
        </p>
        <?php
    }

    /**
     * Render output format field.
     */
    public function render_output_format_field()
    {
        $format = get_option('aifig_output_format', 'png');
        ?>
        <select name="aifig_output_format" id="aifig_output_format">
            <option value="png" <?php selected($format, 'png'); ?>>PNG</option>
            <option value="jpg" <?php selected($format, 'jpg'); ?>>JPG</option>
            <option value="webp" <?php selected($format, 'webp'); ?>>WEBP</option>
        </select>
        <p class="description">
            <?php esc_html_e('Select output image format.', 'featured-image-creator-ai'); ?>
        </p>
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
     * Render AI enhancements section description.
     */
    public function render_enhance_section()
    {
        echo '<p>' . esc_html__('Optional AI features applied when generating images.', 'featured-image-creator-ai') . '</p>';
    }

    /**
     * Render the default style preset field.
     */
    public function render_style_preset_field()
    {
        $current = get_option('aifig_style_preset', 'none');
        ?>
        <select name="aifig_style_preset" id="aifig_style_preset">
            <?php foreach (AIFIG_Styles::get_presets() as $key => $preset): ?>
                <option value="<?php echo esc_attr($key); ?>" <?php selected($current, $key); ?>>
                    <?php echo esc_html($preset['label']); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <p class="description">
            <?php esc_html_e('Visual style appended to every prompt. Can be overridden per post in the editor.', 'featured-image-creator-ai'); ?>
        </p>
        <?php
    }

    /**
     * Render the variations count field.
     */
    public function render_variations_count_field()
    {
        $count = max(1, absint(get_option('aifig_variations_count', 4)));
        ?>
        <select name="aifig_variations_count" id="aifig_variations_count">
            <?php foreach (array(2, 3, 4, 6, 8) as $n): ?>
                <option value="<?php echo esc_attr($n); ?>" <?php selected($count, $n); ?>>
                    <?php
                    /* translators: %d: number of images */
                    printf(esc_html__('%d images', 'featured-image-creator-ai'), absint($n));
                    ?>
                </option>
            <?php endforeach; ?>
        </select>
        <p class="description">
            <?php esc_html_e('How many options the "Generate Options" button creates. Each image uses one API credit.', 'featured-image-creator-ai'); ?>
        </p>
        <?php
    }

    /**
     * Render the auto alt-text toggle.
     */
    public function render_alt_text_field()
    {
        $enabled = (bool) get_option('aifig_alt_text_enabled', false);
        ?>
        <label>
            <input type="hidden" name="aifig_alt_text_enabled" value="0" />
            <input type="checkbox" name="aifig_alt_text_enabled" value="1" <?php checked($enabled); ?> />
            <?php esc_html_e('Automatically write alt text for generated images', 'featured-image-creator-ai'); ?>
        </label>
        <p class="description">
            <?php esc_html_e('Uses the provider\'s vision model (OpenAI / Gemini) to describe the image for SEO and accessibility. Falls back to the post title for providers without vision.', 'featured-image-creator-ai'); ?>
        </p>
        <?php
    }

    /**
     * Render overlay section description (with engine availability notice).
     */
    public function render_overlay_section()
    {
        echo '<p>' . esc_html__('Burn a headline and/or logo directly onto the generated image.', 'featured-image-creator-ai') . '</p>';

        if (!AIFIG_Image_Overlay::is_available()) {
            echo '<div class="notice notice-warning inline" style="margin:10px 0;padding:8px 12px;"><p>'
                . esc_html__('Overlay needs the Imagick or GD PHP extension, which is not available on this server. Ask your host to enable one.', 'featured-image-creator-ai')
                . '</p></div>';
        }
    }

    /**
     * Render the overlay enable toggle.
     */
    public function render_overlay_enabled_field()
    {
        $enabled = (bool) get_option('aifig_overlay_enabled', false);
        ?>
        <label>
            <input type="hidden" name="aifig_overlay_enabled" value="0" />
            <input type="checkbox" name="aifig_overlay_enabled" id="aifig_overlay_enabled" value="1" <?php checked($enabled); ?> />
            <?php esc_html_e('Add a text/logo overlay to generated images', 'featured-image-creator-ai'); ?>
        </label>
        <?php
    }

    /**
     * Render the overlay headline text field.
     */
    public function render_overlay_text_field()
    {
        $text = get_option('aifig_overlay_text', '{title}');
        ?>
        <input type="text" name="aifig_overlay_text" id="aifig_overlay_text" class="regular-text"
            value="<?php echo esc_attr($text); ?>" />
        <p class="description">
            <?php esc_html_e('Text drawn on the image. Use {title} for the post title. Leave blank for logo-only.', 'featured-image-creator-ai'); ?>
        </p>
        <?php
    }

    /**
     * Render the combined overlay text-style controls.
     */
    public function render_overlay_text_style_field()
    {
        $weight   = get_option('aifig_overlay_font_weight', 'bold');
        $scale    = absint(get_option('aifig_overlay_font_scale', 7));
        $lines    = absint(get_option('aifig_overlay_max_lines', 3));
        $color    = get_option('aifig_overlay_text_color', '#ffffff');
        $position = get_option('aifig_overlay_position', 'bottom');
        $scrim    = get_option('aifig_overlay_scrim', 'gradient');

        $weights   = array('bold' => __('Bold', 'featured-image-creator-ai'), 'semibold' => __('Semibold', 'featured-image-creator-ai'), 'regular' => __('Regular', 'featured-image-creator-ai'));
        $positions = array('top' => __('Top', 'featured-image-creator-ai'), 'middle' => __('Middle', 'featured-image-creator-ai'), 'bottom' => __('Bottom', 'featured-image-creator-ai'));
        $scrims    = array('gradient' => __('Gradient fade', 'featured-image-creator-ai'), 'dark' => __('Dark box', 'featured-image-creator-ai'), 'light' => __('Light box', 'featured-image-creator-ai'), 'none' => __('None', 'featured-image-creator-ai'));
        ?>
        <fieldset class="aifig-overlay-grid">
            <label><?php esc_html_e('Font', 'featured-image-creator-ai'); ?>
                <select name="aifig_overlay_font_weight">
                    <?php foreach ($weights as $k => $label): ?>
                        <option value="<?php echo esc_attr($k); ?>" <?php selected($weight, $k); ?>><?php echo esc_html($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label><?php esc_html_e('Position', 'featured-image-creator-ai'); ?>
                <select name="aifig_overlay_position">
                    <?php foreach ($positions as $k => $label): ?>
                        <option value="<?php echo esc_attr($k); ?>" <?php selected($position, $k); ?>><?php echo esc_html($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label><?php esc_html_e('Background', 'featured-image-creator-ai'); ?>
                <select name="aifig_overlay_scrim">
                    <?php foreach ($scrims as $k => $label): ?>
                        <option value="<?php echo esc_attr($k); ?>" <?php selected($scrim, $k); ?>><?php echo esc_html($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label><?php esc_html_e('Color', 'featured-image-creator-ai'); ?>
                <input type="color" name="aifig_overlay_text_color" value="<?php echo esc_attr($color); ?>" />
            </label>
            <label><?php esc_html_e('Font size (% of width)', 'featured-image-creator-ai'); ?>
                <input type="number" name="aifig_overlay_font_scale" min="2" max="40" value="<?php echo esc_attr($scale); ?>" class="small-text" />
            </label>
            <label><?php esc_html_e('Max lines', 'featured-image-creator-ai'); ?>
                <input type="number" name="aifig_overlay_max_lines" min="1" max="6" value="<?php echo esc_attr($lines); ?>" class="small-text" />
            </label>
        </fieldset>
        <?php
    }

    /**
     * Render the logo picker, position and scale.
     */
    public function render_overlay_logo_field()
    {
        $logo_id  = absint(get_option('aifig_overlay_logo_id', 0));
        $logo_url = $logo_id ? wp_get_attachment_image_url($logo_id, 'medium') : '';
        $position = get_option('aifig_overlay_logo_position', 'top-right');
        $scale    = absint(get_option('aifig_overlay_logo_scale', 14));

        $corners = array(
            'top-left'     => __('Top left', 'featured-image-creator-ai'),
            'top-right'    => __('Top right', 'featured-image-creator-ai'),
            'bottom-left'  => __('Bottom left', 'featured-image-creator-ai'),
            'bottom-right' => __('Bottom right', 'featured-image-creator-ai'),
        );
        ?>
        <div class="aifig-logo-picker">
            <input type="hidden" name="aifig_overlay_logo_id" id="aifig_overlay_logo_id" value="<?php echo esc_attr($logo_id); ?>" />
            <div class="aifig-logo-preview" style="<?php echo $logo_url ? '' : 'display:none;'; ?>">
                <img src="<?php echo esc_url($logo_url); ?>" alt="" style="max-width:160px;height:auto;border:1px solid #dcdcde;border-radius:4px;padding:4px;background:#f6f7f7;" />
            </div>
            <p>
                <button type="button" class="button aifig-logo-select"><?php esc_html_e('Select Logo', 'featured-image-creator-ai'); ?></button>
                <button type="button" class="button-link aifig-logo-remove" style="<?php echo $logo_url ? '' : 'display:none;'; ?>color:#b32d2e;">
                    <?php esc_html_e('Remove', 'featured-image-creator-ai'); ?>
                </button>
            </p>
            <p class="description"><?php esc_html_e('A transparent PNG works best. Leave empty for text-only.', 'featured-image-creator-ai'); ?></p>
            <fieldset class="aifig-overlay-grid">
                <label><?php esc_html_e('Logo position', 'featured-image-creator-ai'); ?>
                    <select name="aifig_overlay_logo_position">
                        <?php foreach ($corners as $k => $label): ?>
                            <option value="<?php echo esc_attr($k); ?>" <?php selected($position, $k); ?>><?php echo esc_html($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label><?php esc_html_e('Logo size (% of width)', 'featured-image-creator-ai'); ?>
                    <input type="number" name="aifig_overlay_logo_scale" min="2" max="40" value="<?php echo esc_attr($scale); ?>" class="small-text" />
                </label>
            </fieldset>
        </div>
        <?php
    }

    /**
     * Render the social section description.
     */
    public function render_social_section()
    {
        echo '<p>' . esc_html__('Create extra platform-sized images (Open Graph, Twitter, square, Pinterest) from each generated image. This is a local crop/resize — it does not use additional API credits.', 'featured-image-creator-ai') . '</p>';
    }

    /**
     * Render the social enable toggle.
     */
    public function render_social_enabled_field()
    {
        $enabled = (bool) get_option('aifig_social_enabled', false);
        ?>
        <label>
            <input type="hidden" name="aifig_social_enabled" value="0" />
            <input type="checkbox" name="aifig_social_enabled" value="1" <?php checked($enabled); ?> />
            <?php esc_html_e('Generate social media image sizes alongside the featured image', 'featured-image-creator-ai'); ?>
        </label>
        <?php
    }

    /**
     * Render the social sizes checkboxes.
     */
    public function render_social_types_field()
    {
        $selected = get_option('aifig_social_types', array('og', 'square'));
        if (!is_array($selected)) {
            $selected = array();
        }
        ?>
        <fieldset>
            <?php foreach (AIFIG_Social_Variants::get_specs() as $key => $spec): ?>
                <label style="display:block;margin-bottom:6px;">
                    <input type="checkbox" name="aifig_social_types[]" value="<?php echo esc_attr($key); ?>"
                        <?php checked(in_array($key, $selected, true)); ?> />
                    <?php echo esc_html($spec['label']); ?>
                </label>
            <?php endforeach; ?>
        </fieldset>
        <p class="description">
            <?php esc_html_e('Images are saved to the media library and attached to the post.', 'featured-image-creator-ai'); ?>
        </p>
        <?php
    }

    /**
     * Render the Open Graph image toggle.
     */
    public function render_social_set_og_field()
    {
        $enabled = (bool) get_option('aifig_social_set_og', true);
        ?>
        <label>
            <input type="hidden" name="aifig_social_set_og" value="0" />
            <input type="checkbox" name="aifig_social_set_og" value="1" <?php checked($enabled); ?> />
            <?php esc_html_e('Use the Open Graph size as the post\'s social share image', 'featured-image-creator-ai'); ?>
        </label>
        <p class="description">
            <?php esc_html_e('Sets the image for Yoast SEO and Rank Math when present, and outputs og:image / twitter:image tags otherwise. Requires the "Open Graph" size above.', 'featured-image-creator-ai'); ?>
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
                    <a href="<?php echo esc_url(admin_url('admin.php?page=aifig-bulk-generate')); ?>" class="button button-hero"
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

            <?php $this->render_how_to(); ?>
        </div>
        <?php
    }

    /**
     * Render the "How to use" help panel.
     */
    public function render_how_to()
    {
        ?>
        <div class="aifig-howto" style="background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:24px 28px;margin-top:30px;box-shadow:0 2px 4px rgba(0,0,0,0.05);">
            <h2 style="margin-top:0;font-size:20px;">📖 <?php esc_html_e('How to use', 'featured-image-creator-ai'); ?></h2>

            <h3 style="margin-bottom:6px;"><?php esc_html_e('Getting started', 'featured-image-creator-ai'); ?></h3>
            <ol style="margin-top:0;">
                <li><?php esc_html_e('Choose your AI provider above and paste your API key, then Save.', 'featured-image-creator-ai'); ?></li>
                <li><?php esc_html_e('Edit any post and find the "Featured Image Creator AI" box in the sidebar.', 'featured-image-creator-ai'); ?></li>
                <li><?php esc_html_e('Click "Generate Featured Image" — or "Generate Options" to compare several and pick one.', 'featured-image-creator-ai'); ?></li>
            </ol>

            <h3 style="margin-bottom:6px;">🖌️ <?php esc_html_e('Styles', 'featured-image-creator-ai'); ?></h3>
            <p style="margin-top:0;"><?php esc_html_e('Pick a default look under "AI Enhancements" above. You can override it per post from the Style dropdown in the editor — no prompt writing needed.', 'featured-image-creator-ai'); ?></p>

            <h3 style="margin-bottom:6px;">🏷️ <?php esc_html_e('Text & logo overlay', 'featured-image-creator-ai'); ?></h3>
            <p style="margin-top:0;"><?php esc_html_e('Enable "Text & Logo Overlay" to burn the post title (and your logo) onto every image. The {title} placeholder is replaced automatically. Rendering happens on your server — no extra API cost.', 'featured-image-creator-ai'); ?></p>

            <h3 style="margin-bottom:6px;">🔀 <?php esc_html_e('Variations', 'featured-image-creator-ai'); ?></h3>
            <p style="margin-top:0;"><?php esc_html_e('"Generate Options" creates several images at once so you can pick your favorite. The ones you don\'t choose are deleted automatically. Each option uses one API credit.', 'featured-image-creator-ai'); ?></p>

            <h3 style="margin-bottom:6px;">♿ <?php esc_html_e('Auto alt text', 'featured-image-creator-ai'); ?></h3>
            <p style="margin-top:0;"><?php esc_html_e('Turn on "Auto Alt Text" to have OpenAI or Gemini describe each image for SEO and accessibility. Stability AI falls back to the post title.', 'featured-image-creator-ai'); ?></p>

            <h3 style="margin-bottom:6px;">📣 <?php esc_html_e('Social & Open Graph images', 'featured-image-creator-ai'); ?></h3>
            <p style="margin-top:0;margin-bottom:0;"><?php esc_html_e('Enable "Social & Open Graph Images" to also create Facebook, Twitter, square and Pinterest sizes from the same picture — cropped locally, no extra credits. With "Open Graph Image" on, the share image is set for Yoast / Rank Math automatically, or output as og:image tags when no SEO plugin is active.', 'featured-image-creator-ai'); ?></p>
        </div>
        <?php
    }
}
