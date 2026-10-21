<?php
/**
 * Bulk Generator class
 *
 * @package AI_Featured_Image_Generator
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Class AIFIG_Bulk_Generator
 *
 * Handles batch generation of featured images.
 */
class AIFIG_Bulk_Generator
{

    /**
     * Constructor.
     */
    public function __construct()
    {
        add_filter('bulk_actions-edit-post', array($this, 'register_bulk_action'));
        add_filter('handle_bulk_actions-edit-post', array($this, 'handle_bulk_action'), 10, 3);
        add_action('admin_notices', array($this, 'bulk_action_notices'));
        add_action('admin_menu', array($this, 'add_bulk_page'));
        add_action('wp_ajax_aifig_batch_generate', array($this, 'ajax_batch_generate'));
        add_action('wp_ajax_aifig_batch_recolor', array($this, 'ajax_batch_recolor'));
        add_action('wp_ajax_aifig_get_batch_ids', array($this, 'ajax_get_batch_ids'));
    }

    /**
     * Register bulk action.
     *
     * @param array $bulk_actions Existing bulk actions.
     * @return array Modified bulk actions.
     */
    public function register_bulk_action($bulk_actions)
    {
        $bulk_actions['aifig_generate'] = __('Generate Featured Images', 'featured-image-creator-ai');
        return $bulk_actions;
    }

    /**
     * Handle bulk action.
     *
     * @param string $redirect_to Redirect URL.
     * @param string $action      Action name.
     * @param array  $post_ids    Post IDs.
     * @return string Modified redirect URL.
     */
    public function handle_bulk_action($redirect_to, $action, $post_ids)
    {
        if ('aifig_generate' !== $action) {
            return $redirect_to;
        }

        // Check capability
        if (!current_user_can('edit_posts')) {
            return $redirect_to;
        }

        $generator = new AIFIG_Image_Generator();

        if (!$generator->is_configured()) {
            $redirect_to = add_query_arg('aifig_bulk_error', 'not_configured', $redirect_to);
            return $redirect_to;
        }

        $generated = 0;
        $errors = 0;

        foreach ($post_ids as $post_id) {
            // Skip if already has featured image
            if (has_post_thumbnail($post_id)) {
                continue;
            }

            $result = $generator->generate_for_post($post_id);

            if (is_wp_error($result)) {
                $errors++;
            } else {
                $generated++;
            }
        }

        $redirect_to = add_query_arg('aifig_bulk_generated', $generated, $redirect_to);

        if ($errors > 0) {
            $redirect_to = add_query_arg('aifig_bulk_errors', $errors, $redirect_to);
        }

        return $redirect_to;
    }

    /**
     * Display bulk action notices.
     */
    public function bulk_action_notices()
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Displaying notices only, no data processing
        if (!empty($_REQUEST['aifig_bulk_generated'])) {
            $generated = intval($_REQUEST['aifig_bulk_generated']); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

            printf(
                '<div class="notice notice-success is-dismissible"><p>%s</p></div>',
                sprintf(
                    /* translators: %d: Number of images generated */
                    esc_html(_n(
                        'Generated %d featured image.',
                        'Generated %d featured images.',
                        $generated,
                        'featured-image-creator-ai'
                    )),
                    absint($generated)
                )
            );
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Displaying notices only, no data processing
        if (!empty($_REQUEST['aifig_bulk_errors'])) {
            $errors = intval($_REQUEST['aifig_bulk_errors']); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

            printf(
                '<div class="notice notice-warning is-dismissible"><p>%s</p></div>',
                sprintf(
                    /* translators: %d: Number of errors */
                    esc_html(_n(
                        '%d error occurred during generation.',
                        '%d errors occurred during generation.',
                        $errors,
                        'featured-image-creator-ai'
                    )),
                    absint($errors)
                )
            );
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Displaying notices only, no data processing
        if (!empty($_REQUEST['aifig_bulk_error'])) {
            $error = sanitize_text_field(wp_unslash($_REQUEST['aifig_bulk_error'])); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

            $messages = array(
                'not_configured' => __('API key not configured. Please configure your API key in settings.', 'featured-image-creator-ai'),
            );

            $message = isset($messages[$error]) ? $messages[$error] : __('An error occurred.', 'featured-image-creator-ai');

            printf(
                '<div class="notice notice-error is-dismissible"><p>%s</p></div>',
                esc_html($message)
            );
        }
    }

    /**
     * Add bulk generation page.
     */
    public function add_bulk_page()
    {
        add_submenu_page(
            'aifig-settings',
            __('Bulk Generate Featured Images', 'featured-image-creator-ai'),
            __('Bulk Generate', 'featured-image-creator-ai'),
            'edit_posts',
            'aifig-bulk-generate',
            array($this, 'render_bulk_page')
        );
    }

    /**
     * Render bulk generation page.
     */
    public function render_bulk_page()
    {
        if (!current_user_can('edit_posts')) {
            return;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display logic only.
        $mode = isset($_GET['mode']) ? sanitize_key(wp_unslash($_GET['mode'])) : 'missing';
        if (!in_array($mode, array('missing', 'regenerate', 'recolor'), true)) {
            $mode = 'missing';
        }
        $generator  = new AIFIG_Image_Generator();
        $color_set  = (bool) sanitize_hex_color(get_option('aifig_color_value', ''));

        if ($mode === 'regenerate') {
            // Fetch all posts (published)
             $posts = AIFIG_Image_Generator::get_posts_without_featured_image(array(
                // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Intentional override.
                'meta_query' => array(), // Override to get all posts
             ));
        } elseif ($mode === 'recolor') {
            $posts = AIFIG_Image_Generator::get_posts_with_featured_image();
        } else {
            $posts = AIFIG_Image_Generator::get_posts_without_featured_image();
        }
        ?>
        <div class="wrap aifig-bulk-container">
            <h1><?php esc_html_e('Bulk Generate Featured Images', 'featured-image-creator-ai'); ?></h1>
            
            <nav class="nav-tab-wrapper" style="margin-bottom: 20px;">
                <a href="<?php echo esc_url(admin_url('admin.php?page=aifig-bulk-generate&mode=missing')); ?>" class="nav-tab <?php echo $mode === 'missing' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('Generate Missing', 'featured-image-creator-ai'); ?>
                </a>
                <a href="<?php echo esc_url(admin_url('admin.php?page=aifig-bulk-generate&mode=regenerate')); ?>" class="nav-tab <?php echo $mode === 'regenerate' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('Regenerate All', 'featured-image-creator-ai'); ?>
                </a>
                <a href="<?php echo esc_url(admin_url('admin.php?page=aifig-bulk-generate&mode=recolor')); ?>" class="nav-tab <?php echo $mode === 'recolor' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('Recolour Existing', 'featured-image-creator-ai'); ?>
                </a>
            </nav>

            <p class="description" style="font-size: 16px; margin-bottom: 30px;">
                <?php 
                if ($mode === 'regenerate') {
                    esc_html_e('Regenerate featured images for ALL posts. This will overwrite existing featured images.', 'featured-image-creator-ai');
                } elseif ($mode === 'recolor') {
                    esc_html_e('Shift the colours of every existing featured image toward your brand colour. The image stays the same, only the colours change. No API calls, no cost.', 'featured-image-creator-ai');
                } else {
                    esc_html_e('Automatically generate AI-powered featured images for posts that don\'t have one.', 'featured-image-creator-ai');
                }
                ?>
            </p>

            <?php if ($mode !== 'recolor' && !$generator->is_configured()): ?>
                <div class="notice notice-error" style="border-left-width: 4px; padding: 15px;">
                    <h3 style="margin-top: 0;">⚠️ <?php esc_html_e('API Not Configured', 'featured-image-creator-ai'); ?></h3>
                    <p>
                        <?php
                        $settings_url = admin_url('admin.php?page=aifig-settings');
                        printf(
                            /* translators: %s: Settings page URL */
                            wp_kses_post(__('Please <a href="%s"><strong>configure your API key</strong></a> to use this feature.', 'featured-image-creator-ai')),
                            esc_url($settings_url)
                        );
                        ?>
                    </p>
                </div>
            <?php elseif ($mode === 'recolor' && !$color_set): ?>
                <div class="notice notice-error" style="border-left-width: 4px; padding: 15px;">
                    <h3 style="margin-top: 0;">⚠️ <?php esc_html_e('No Brand Colour Set', 'featured-image-creator-ai'); ?></h3>
                    <p>
                        <?php
                        $settings_url = admin_url('admin.php?page=aifig-settings');
                        printf(
                            /* translators: %s: Settings page URL */
                            wp_kses_post(__('Pick a <a href="%s"><strong>brand colour</strong></a> under the Colour Theme settings first. Recolouring shifts your existing featured images toward that colour.', 'featured-image-creator-ai')),
                            esc_url($settings_url)
                        );
                        ?>
                    </p>
                </div>
            <?php elseif (empty($posts)): ?>
                <div
                    style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); padding: 40px; border-radius: 12px; color: white; text-align: center; box-shadow: 0 8px 16px rgba(17, 153, 142, 0.3);">
                    <div style="font-size: 64px; margin-bottom: 45px;">✅</div>
                    <h2 style="color: white; margin: 0 0 10px 0; font-size: 28px;">
                        <?php esc_html_e('All Set!', 'featured-image-creator-ai'); ?>
                    </h2>
                    <p style="font-size: 18px; margin: 0; opacity: 0.9;">
                        <?php esc_html_e('No posts found to process.', 'featured-image-creator-ai'); ?>
                    </p>
                </div>
            <?php else: ?>
                <!-- Stats Card -->
                <div
                    style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; border-radius: 12px; color: white; margin-bottom: 30px; box-shadow: 0 8px 16px rgba(102, 126, 234, 0.3);">
                    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 20px;">
                        <div>
                            <div style="font-size: 48px; font-weight: 700; margin-bottom: 15px;"><?php echo count($posts); ?></div>
                            <div style="font-size: 18px; opacity: 0.9;">
                                <?php
                                if ($mode === 'regenerate') {
                                     printf(
                                        /* translators: %d: Number of posts */
                                        esc_html(_n(
                                            'Post available for regeneration',
                                            'Posts available for regeneration',
                                            count($posts),
                                            'featured-image-creator-ai'
                                        ))
                                    );
                                } elseif ($mode === 'recolor') {
                                    printf(
                                        /* translators: %d: Number of posts */
                                        esc_html(_n(
                                            'Post with a featured image to recolour',
                                            'Posts with featured images to recolour',
                                            count($posts),
                                            'featured-image-creator-ai'
                                        ))
                                    );
                                } else {
                                    printf(
                                        /* translators: %d: Number of posts */
                                        esc_html(_n(
                                            'Post without featured image',
                                            'Posts without featured images',
                                            count($posts),
                                            'featured-image-creator-ai'
                                        ))
                                    );
                                }
                                ?>
                            </div>
                        </div>
                        <div>
                            <button type="button" class="button button-hero aifig-start-batch"
                                data-mode="<?php echo esc_attr($mode); ?>">
                                <span class="dashicons dashicons-images-alt2"></span>
                                <?php
                                if ($mode === 'regenerate') {
                                    esc_html_e('Regenerate All Images', 'featured-image-creator-ai');
                                } elseif ($mode === 'recolor') {
                                    esc_html_e('Recolour All Images', 'featured-image-creator-ai');
                                } else {
                                    esc_html_e('Generate All Images', 'featured-image-creator-ai');
                                }
                                ?>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Info Cards -->
                <div style="display: flex; justify-content: center; gap: 20px; margin-bottom: 30px; flex-wrap: wrap;">
                    <div class="card" style="text-align: center; flex: 0 1 280px;">
                        <div style="font-size: 48px; margin-bottom: 20px;">🎨</div>
                        <h3 style="margin: 0 0 10px 0; color: #667eea;">AI-Powered</h3>
                        <p style="margin: 0; color: #646970;">Images generated using advanced AI technology</p>
                    </div>
                    <div class="card" style="text-align: center; flex: 0 1 280px;">
                        <div style="font-size: 48px; margin-bottom: 20px;">⚡</div>
                        <h3 style="margin: 0 0 10px 0; color: #667eea;">Fast Processing</h3>
                        <p style="margin: 0; color: #646970;">Automatic batch processing with progress tracking</p>
                    </div>
                    <div class="card" style="text-align: center; flex: 0 1 280px;">
                        <div style="font-size: 48px; margin-bottom: 20px;">✨</div>
                        <h3 style="margin: 0 0 10px 0; color: #667eea;">Perfect Size</h3>
                        <p style="margin: 0; color: #646970;">Images resized to your exact specifications</p>
                    </div>
                </div>

                <div class="aifig-batch-progress card" style="display: none;">
                    <h3 style="color: #667eea; margin-top: 0;">
                        <span class="dashicons dashicons-update" style="animation: rotation 2s infinite linear;"></span>
                        <?php esc_html_e('Generation in Progress', 'featured-image-creator-ai'); ?>
                    </h3>
                    <progress max="100" value="0" style="width: 100%; height: 30px; border-radius: 15px;"></progress>
                    <p style="font-size: 18px; font-weight: 600; text-align: center; margin: 15px 0; color: #667eea;">
                        <span class="aifig-progress-text">0%</span>
                        <span style="margin: 0 10px; color: #646970;">•</span>
                        <span class="aifig-progress-status" style="color: #646970;"></span>
                    </p>
                </div>

                <div class="aifig-batch-results card" style="display: none;">
                    <h3 style="color: #11998e; margin-top: 0;">
                        <span class="dashicons dashicons-yes-alt"></span>
                        <?php esc_html_e('Results', 'featured-image-creator-ai'); ?>
                    </h3>
                    <div class="aifig-results-content"></div>
                </div>

                <style>
                    @keyframes rotation {
                        from {
                            transform: rotate(0deg);
                        }

                        to {
                            transform: rotate(359deg);
                        }
                    }
                </style>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * AJAX handler for batch generation.
     */
    public function ajax_batch_generate()
    {
        // Verify nonce and capability
        if (!AIFIG_Security::verify_request('aifig_generate_image', 'edit_posts')) {
            wp_send_json_error(
                array(
                    'message' => __('Security check failed.', 'featured-image-creator-ai'),
                )
            );
        }

        $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified by AIFIG_Security::verify_request above


        if (!$post_id) {
            wp_send_json_error(
                array(
                    'message' => __('Invalid post ID.', 'featured-image-creator-ai'),
                )
            );
        }

        // Generate image
        $generator = new AIFIG_Image_Generator();
        $attachment_id = $generator->generate_for_post($post_id);

        if (is_wp_error($attachment_id)) {
            wp_send_json_error(
                array(
                    'message' => $attachment_id->get_error_message(),
                    'post_id' => $post_id,
                    'post_title' => get_the_title($post_id),
                )
            );
        }

        wp_send_json_success(
            array(
                'message' => __('Featured image generated successfully!', 'featured-image-creator-ai'),
                'post_id' => $post_id,
                'post_title' => get_the_title($post_id),
                'attachment_id' => $attachment_id,
            )
        );
    }

    /**
     * AJAX handler to get batch IDs.
     */
    public function ajax_get_batch_ids()
    {
        // Verify nonce and capability
        if (!AIFIG_Security::verify_request('aifig_generate_image', 'edit_posts')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'featured-image-creator-ai')));
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified by AIFIG_Security::verify_request.
        $mode = isset($_POST['mode']) ? sanitize_key(wp_unslash($_POST['mode'])) : 'missing';

        if ($mode === 'regenerate') {
            $posts = AIFIG_Image_Generator::get_posts_without_featured_image(array(
                // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Intentional override.
                'meta_query' => array(),
            ));
        } elseif ($mode === 'recolor') {
            $posts = AIFIG_Image_Generator::get_posts_with_featured_image();
        } else {
            $posts = AIFIG_Image_Generator::get_posts_without_featured_image();
        }

        wp_send_json_success($posts);
    }

    /**
     * AJAX handler to recolour one post's existing featured image.
     */
    public function ajax_batch_recolor()
    {
        if (!AIFIG_Security::verify_request('aifig_generate_image', 'edit_posts')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'featured-image-creator-ai')));
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified above.
        $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;

        if (!$post_id) {
            wp_send_json_error(array('message' => __('Invalid post ID.', 'featured-image-creator-ai')));
        }

        $color = sanitize_hex_color(get_option('aifig_color_value', ''));
        if (!$color) {
            wp_send_json_error(array(
                'message'    => __('Set a brand colour under Colour Theme settings first.', 'featured-image-creator-ai'),
                'post_id'    => $post_id,
                'post_title' => get_the_title($post_id),
            ));
        }

        if (!class_exists('AIFIG_Image_Color') || !AIFIG_Image_Color::is_available()) {
            wp_send_json_error(array(
                'message'    => __('Image recolouring needs the Imagick or GD PHP extension, which is not available on this server.', 'featured-image-creator-ai'),
                'post_id'    => $post_id,
                'post_title' => get_the_title($post_id),
            ));
        }

        $attachment_id = get_post_thumbnail_id($post_id);
        $file          = $attachment_id ? get_attached_file($attachment_id) : '';

        if (!$attachment_id || !$file || !file_exists($file)) {
            wp_send_json_error(array(
                'message'    => __('This post has no featured image file to recolour.', 'featured-image-creator-ai'),
                'post_id'    => $post_id,
                'post_title' => get_the_title($post_id),
            ));
        }

        $strength = (int) get_option('aifig_color_strength', 60);
        $changed  = AIFIG_Image_Color::apply($file, $color, $strength);

        if (!$changed) {
            wp_send_json_error(array(
                'message'    => __('Could not recolour this image.', 'featured-image-creator-ai'),
                'post_id'    => $post_id,
                'post_title' => get_the_title($post_id),
            ));
        }

        // Pixels changed; refresh attachment metadata (e.g. filesize).
        $metadata = wp_generate_attachment_metadata($attachment_id, $file);
        wp_update_attachment_metadata($attachment_id, $metadata);

        wp_send_json_success(array(
            'message'    => __('Featured image recoloured.', 'featured-image-creator-ai'),
            'post_id'    => $post_id,
            'post_title' => get_the_title($post_id),
        ));
    }
}
