<?php
/**
 * Post Meta Box class
 *
 * @package AI_Featured_Image_Generator
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Class AIFIG_Post_Meta_Box
 *
 * Adds meta box to post editor for generating featured images.
 */
class AIFIG_Post_Meta_Box
{

    /**
     * Constructor.
     */
    public function __construct()
    {
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('wp_ajax_aifig_generate_single', array($this, 'ajax_generate_single'));
    }

    /**
     * Add meta box to post editor.
     */
    public function add_meta_box()
    {
        add_meta_box(
            'aifig-generate-box',
            __('Featured Image Creator AI', 'featured-image-creator-ai'),
            array($this, 'render_meta_box'),
            'post',
            'side',
            'default'
        );
    }

    /**
     * Render meta box content.
     *
     * @param WP_Post $post Current post object.
     */
    public function render_meta_box($post)
    {
        // Check if API is configured
        $generator = new AIFIG_Image_Generator();

        if (!$generator->is_configured()) {
            $settings_url = admin_url('options-general.php?page=aifig-settings');
            printf(
                '<p>%s <a href="%s">%s</a></p>',
                esc_html__('Please configure your API key to use this feature.', 'featured-image-creator-ai'),
                esc_url($settings_url),
                esc_html__('Go to Settings', 'featured-image-creator-ai')
            );
            return;
        }

        // Check if post has a title
        $has_title = !empty($post->post_title);

        wp_nonce_field('aifig_generate_single', 'aifig_generate_nonce');
        ?>
        <div class="aifig-meta-box">
            <?php if (has_post_thumbnail($post->ID)): ?>
                <p class="aifig-status">
                    <span class="dashicons dashicons-yes-alt"></span>
                    <?php esc_html_e('This post has a featured image.', 'featured-image-creator-ai'); ?>
                </p>
            <?php else: ?>
                <p class="aifig-status aifig-no-image">
                    <span class="dashicons dashicons-info"></span>
                    <?php esc_html_e('No featured image set.', 'featured-image-creator-ai'); ?>
                </p>
            <?php endif; ?>

            <!-- Tabs Navigation -->
            <div class="aifig-tabs">
                <button type="button" class="aifig-tab-btn active" data-tab="ai-generate">
                    <span class="dashicons dashicons-format-image"></span>
                    <?php esc_html_e('AI Generate', 'featured-image-creator-ai'); ?>
                </button>
                <button type="button" class="aifig-tab-btn" data-tab="manual-upload">
                    <span class="dashicons dashicons-upload"></span>
                    <?php esc_html_e('Manual Upload', 'featured-image-creator-ai'); ?>
                </button>
            </div>

            <!-- AI Generate Tab -->
            <div class="aifig-tab-content active" id="ai-generate-tab">
                <?php if (!$has_title): ?>
                    <p class="aifig-warning">
                        <span class="dashicons dashicons-warning"></span>
                        <?php esc_html_e('Please add a post title before generating an image.', 'featured-image-creator-ai'); ?>
                    </p>
                <?php endif; ?>

                <p>
                    <button type="button" class="button button-primary button-large aifig-generate-btn"
                        data-post-id="<?php echo esc_attr($post->ID); ?>" <?php disabled(!$has_title); ?>>
                        <span class="dashicons dashicons-format-image"></span>
                        <?php esc_html_e('Generate Featured Image', 'featured-image-creator-ai'); ?>
                    </button>
                </p>

                <div class="aifig-loading" style="display: none;">
                    <span class="spinner is-active"></span>
                    <p><?php esc_html_e('Generating image...', 'featured-image-creator-ai'); ?></p>
                </div>

                <div class="aifig-result" style="display: none;"></div>
            </div>

            <!-- Manual Upload Tab -->
            <div class="aifig-tab-content" id="manual-upload-tab">
                <p class="description">
                    <?php esc_html_e('Upload your own image to use as the featured image.', 'featured-image-creator-ai'); ?>
                </p>

                <p>
                    <button type="button" class="button button-secondary button-large aifig-upload-btn">
                        <span class="dashicons dashicons-upload"></span>
                        <?php esc_html_e('Upload Image', 'featured-image-creator-ai'); ?>
                    </button>
                </p>

                <div class="aifig-upload-preview" style="display: none;">
                    <img src="" alt="Preview" style="max-width: 100%; height: auto; margin-top: 10px; border-radius: 4px;">
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * AJAX handler for generating single post image.
     */
    public function ajax_generate_single()
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

        // Check if user can edit this post
        if (!current_user_can('edit_post', $post_id)) {
            wp_send_json_error(
                array(
                    'message' => __('You do not have permission to edit this post.', 'featured-image-creator-ai'),
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
                )
            );
        }

        // Get image URL
        $image_url = wp_get_attachment_image_url($attachment_id, 'medium');

        wp_send_json_success(
            array(
                'message' => __('Featured image generated successfully!', 'featured-image-creator-ai'),
                'attachment_id' => $attachment_id,
                'image_url' => $image_url,
            )
        );
    }
}
