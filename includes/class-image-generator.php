<?php
/**
 * Image Generator class
 *
 * @package AI_Featured_Image_Generator
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Class AIFIG_Image_Generator
 *
 * Handles image generation and WordPress media library integration.
 */
class AIFIG_Image_Generator
{

    /**
     * API provider instance.
     *
     * @var AIFIG_API_Interface
     */
    private $api_provider;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->initialize_api_provider();
    }

    /**
     * Initialize API provider based on settings.
     */
    private function initialize_api_provider()
    {
        $encrypted_key = get_option('aifig_api_key', '');

        if (empty($encrypted_key)) {
            return;
        }

        $api_key = AIFIG_Security::decrypt($encrypted_key);
        $provider_setting = get_option('aifig_api_provider', 'openai');
        $quality = get_option('aifig_image_quality', 'standard');

        // Map setting to provider class and model/params
        switch ($provider_setting) {
            case 'openai': // Default DALL-E 3
                $this->api_provider = new AIFIG_OpenAI_Provider($api_key, 'dall-e-3', $quality);
                break;
            case 'gpt-image-1':
            case 'gpt-image-1-mini':
            case 'gpt-image-1.5':
            case 'gpt-image-latest':
                // Assuming these map to specific OpenAI models or similar
                // Adjust model name logic as needed based on actual API requirements
                // For now, passing the setting value as the model name
                $this->api_provider = new AIFIG_OpenAI_Provider($api_key, $provider_setting, $quality);
                break;
            case 'gemini':
                $this->api_provider = new AIFIG_Gemini_Provider($api_key);
                break;
            case 'stability': // Stable Diffusion 3
                $this->api_provider = new AIFIG_Stability_Provider($api_key, 'sd3');
                break;
            case 'seedream-4.5':
                // Assuming this uses Stability provider with a different model code
                $this->api_provider = new AIFIG_Stability_Provider($api_key, 'seedream-4.5'); // Verify model code if possible
                break;
            default:
                // Fallback or legacy support
                if (strpos($provider_setting, 'gpt-image') !== false) {
                     $this->api_provider = new AIFIG_OpenAI_Provider($api_key, $provider_setting, $quality);
                } else {
                    $this->api_provider = null;
                }
        }
    }

    /**
     * Check if API is configured.
     *
     * @return bool
     */
    public function is_configured()
    {
        return null !== $this->api_provider;
    }

    /**
     * Generate a featured image for a post and set it as the thumbnail.
     *
     * @param int   $post_id Post ID.
     * @param array $args    Optional overrides: 'style' (preset key).
     * @return int|WP_Error Attachment ID on success, WP_Error on failure.
     */
    public function generate_for_post($post_id, $args = array())
    {
        $attachment_id = $this->create_attachment_for_post($post_id, $args);

        if (is_wp_error($attachment_id)) {
            return $attachment_id;
        }

        // Set as featured image.
        set_post_thumbnail($post_id, $attachment_id);

        // Generate accessible alt text (best effort, respects setting).
        $this->maybe_generate_alt_text($attachment_id, $post_id);

        // Create social / Open Graph variants from this image (respects setting).
        $this->maybe_generate_social_variants($attachment_id, $post_id);

        return $attachment_id;
    }

    /**
     * Generate an image and create a media attachment, WITHOUT setting it as
     * the post thumbnail. Used by both single generation and variations.
     *
     * @param int   $post_id Post ID.
     * @param array $args    Optional overrides: 'style' (preset key).
     * @return int|WP_Error Attachment ID on success, WP_Error on failure.
     */
    public function create_attachment_for_post($post_id, $args = array())
    {
        if (!$this->is_configured()) {
            return new WP_Error(
                'not_configured',
                __('API key not configured. Please configure your API key in settings.', 'featured-image-creator-ai')
            );
        }

        $post = get_post($post_id);
        if (!$post) {
            return new WP_Error(
                'invalid_post',
                __('Invalid post ID.', 'featured-image-creator-ai')
            );
        }

        // Get post title.
        $title = get_the_title($post_id);
        if (empty($title)) {
            return new WP_Error(
                'no_title',
                __('Post must have a title to generate an image.', 'featured-image-creator-ai')
            );
        }

        // Build prompt from template, applying the chosen style preset.
        $style  = isset($args['style']) ? $args['style'] : null;
        $prompt = $this->build_prompt($title, $style);

        // Get image dimensions.
        $width  = get_option('aifig_image_width', 1024);
        $height = get_option('aifig_image_height', 675);

        // Generate image.
        $result = $this->api_provider->generate_image($prompt, $width, $height);

        if (is_wp_error($result)) {
            return $result;
        }

        // Download and attach image (also converts, resizes and applies overlay).
        $attachment_id = $this->download_and_attach($result['url'], $post_id, $title);

        if (is_wp_error($attachment_id)) {
            return $attachment_id;
        }

        // Store metadata.
        update_post_meta($attachment_id, '_aifig_generated', true);
        update_post_meta($attachment_id, '_aifig_prompt', $prompt);
        if (isset($result['revised_prompt'])) {
            update_post_meta($attachment_id, '_aifig_revised_prompt', $result['revised_prompt']);
        }

        return $attachment_id;
    }

    /**
     * Generate multiple candidate images for a post (no thumbnail set).
     *
     * @param int   $post_id Post ID.
     * @param int   $count   Number of variations (1-8).
     * @param array $args    Optional overrides: 'style' (preset key).
     * @return array|WP_Error Array of attachment IDs, or WP_Error if all failed.
     */
    public function generate_variations($post_id, $count = 4, $args = array())
    {
        $count = max(1, min(8, (int) $count));
        $ids   = array();
        $last_error = null;

        for ($i = 0; $i < $count; $i++) {
            $id = $this->create_attachment_for_post($post_id, $args);
            if (is_wp_error($id)) {
                $last_error = $id;
                continue;
            }
            $ids[] = $id;
        }

        if (empty($ids)) {
            return $last_error instanceof WP_Error
                ? $last_error
                : new WP_Error('variations_failed', __('No variations could be generated.', 'featured-image-creator-ai'));
        }

        return $ids;
    }

    /**
     * Choose one variation as the featured image and discard the rest.
     *
     * Only plugin-generated attachments are deleted, as a safety guard.
     *
     * @param int   $post_id     Post ID.
     * @param int   $chosen_id   Attachment ID to keep and feature.
     * @param int[] $discard_ids Candidate attachment IDs to remove.
     * @return int|WP_Error The chosen attachment ID, or WP_Error.
     */
    public function set_variation($post_id, $chosen_id, $discard_ids = array())
    {
        $chosen_id = absint($chosen_id);

        if (!$chosen_id || 'attachment' !== get_post_type($chosen_id)) {
            return new WP_Error('invalid_attachment', __('Invalid image selection.', 'featured-image-creator-ai'));
        }

        set_post_thumbnail($post_id, $chosen_id);

        foreach ((array) $discard_ids as $discard_id) {
            $discard_id = absint($discard_id);
            if (!$discard_id || $discard_id === $chosen_id) {
                continue;
            }
            // Only delete images this plugin generated.
            if (get_post_meta($discard_id, '_aifig_generated', true)) {
                wp_delete_attachment($discard_id, true);
            }
        }

        $this->maybe_generate_alt_text($chosen_id, $post_id);
        $this->maybe_generate_social_variants($chosen_id, $post_id);

        return $chosen_id;
    }

    /**
     * Generate and store accessible alt text for an attachment, if enabled.
     *
     * Uses the provider's vision model when available, otherwise falls back
     * to the post title.
     *
     * @param int $attachment_id Attachment ID.
     * @param int $post_id       Source post ID (for context/fallback).
     * @return void
     */
    public function maybe_generate_alt_text($attachment_id, $post_id)
    {
        if (!get_option('aifig_alt_text_enabled', false)) {
            return;
        }
        if (!$this->is_configured()) {
            return;
        }

        $title = get_the_title($post_id);
        $alt   = '';

        if ($this->api_provider->supports_vision()) {
            $file = get_attached_file($attachment_id);
            if ($file && file_exists($file)) {
                $described = $this->api_provider->describe_image($file, $title);
                if (!is_wp_error($described) && '' !== $described) {
                    $alt = $described;
                }
            }
        }

        // Fallback: use the post title.
        if ('' === $alt) {
            $alt = $title;
        }

        $alt = sanitize_text_field($alt);
        if ('' !== $alt) {
            update_post_meta($attachment_id, '_wp_attachment_image_alt', $alt);
        }
    }

    /**
     * Create social / Open Graph sized variants from a source image, if enabled.
     *
     * This is a local crop/resize operation and makes no API calls.
     *
     * @param int $source_id Source (featured) attachment ID.
     * @param int $post_id   Post ID.
     * @return void
     */
    private function maybe_generate_social_variants($source_id, $post_id)
    {
        if (!class_exists('AIFIG_Social_Variants') || !AIFIG_Social_Variants::is_enabled()) {
            return;
        }
        AIFIG_Social_Variants::generate_for_attachment($source_id, $post_id);
    }

    /**
     * Build prompt from template and post title, applying a style preset.
     *
     * @param string      $title Post title.
     * @param string|null $style Style preset key, or null to use the saved setting.
     * @return string Generated prompt.
     */
    private function build_prompt($title, $style = null)
    {
        $template = get_option('aifig_prompt_template', 'Create a professional blog featured image for: {title}');
        $prompt   = str_replace('{title}', $title, $template);

        if (null === $style) {
            $style = get_option('aifig_style_preset', 'none');
        }

        if ('none' !== $style && class_exists('AIFIG_Styles')) {
            $suffix = AIFIG_Styles::get_suffix($style);
            if ('' !== $suffix) {
                $prompt = rtrim($prompt) . ' Style: ' . $suffix;
            }
        }

        return $prompt;
    }

    /**
     * Download image from URL and attach to post.
     *
     * @param string $image_url Image URL.
     * @param int    $post_id   Post ID.
     * @param string $title     Image title.
     * @return int|WP_Error Attachment ID on success, WP_Error on failure.
     */
    private function download_and_attach($image_url, $post_id, $title)
    {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        // Check if this is already a local file (from Gemini/Stability)
        if (file_exists($image_url)) {
            $temp_file = $image_url;
        } else {
            // Download image from URL (OpenAI)
            $temp_file = download_url($image_url);

            if (is_wp_error($temp_file)) {
                return new WP_Error(
                    'download_failed',
                    sprintf(
                        /* translators: %s: Error message */
                        __('Failed to download image: %s', 'featured-image-creator-ai'),
                        $temp_file->get_error_message()
                    )
                );
            }
        }

        // Get output format
        $output_format = get_option('aifig_output_format', 'png');

        // Convert image if needed (e.g. OpenAI returns PNG, but user wants JPG/WEBP)
        // We do this before sideloading to ensure the file content matches the extension
        $image_editor = wp_get_image_editor($temp_file);
        if (!is_wp_error($image_editor)) {
            // Generate a new temp filename with the correct extension
            $converted_temp = wp_tempnam(sanitize_file_name($title) . '.' . $output_format);
            
            // Determine mime type
            $mime_type = 'image/' . ($output_format === 'jpg' ? 'jpeg' : $output_format);

            // Save converted image
            $saved = $image_editor->save($converted_temp, $mime_type);

            if (!is_wp_error($saved)) {
                // If successful, swap temp file
                if ($converted_temp !== $temp_file) {
                    wp_delete_file($temp_file); // Delete old temp
                }
                $temp_file = $saved['path'];
            }
        }
        
        // Prepare file array
        $file_array = array(
            'name' => sanitize_file_name($title) . '.' . $output_format,
            'tmp_name' => $temp_file,
        );

        // Upload to media library
        $attachment_id = media_handle_sideload($file_array, $post_id);

        // Clean up temp file
        if (file_exists($temp_file)) {
            // If sideload failed or moved the file, we might not need to delete, but usually safe to try if it exists still
             // media_handle_sideload moves the file, so temp_file might be gone or empty
            wp_delete_file($temp_file);
        }

        if (is_wp_error($attachment_id)) {
            return new WP_Error(
                'upload_failed',
                sprintf(
                    /* translators: %s: Error message */
                    __('Failed to upload image: %s', 'featured-image-creator-ai'),
                    $attachment_id->get_error_message()
                )
            );
        }

        // Resize image to exact dimensions if needed
        $this->resize_image($attachment_id);

        // Burn text / logo overlay onto the image if enabled.
        $this->maybe_apply_overlay($attachment_id, $post_id);

        return $attachment_id;
    }

    /**
     * Apply the text/logo overlay to a generated attachment, if enabled.
     *
     * @param int $attachment_id Attachment ID.
     * @param int $post_id       Source post ID (for {title} substitution).
     * @return void
     */
    private function maybe_apply_overlay($attachment_id, $post_id)
    {
        if (!get_option('aifig_overlay_enabled', false)) {
            return;
        }
        if (!class_exists('AIFIG_Image_Overlay') || !AIFIG_Image_Overlay::is_available()) {
            return;
        }

        $file = get_attached_file($attachment_id);
        if (!$file || !file_exists($file)) {
            return;
        }

        // Resolve the overlay text (supports the {title} placeholder).
        $text_source = get_option('aifig_overlay_text', '{title}');
        $text        = str_replace('{title}', get_the_title($post_id), $text_source);

        $changed = AIFIG_Image_Overlay::apply($file, array('text' => $text));

        if ($changed) {
            // Pixels changed; refresh attachment metadata (e.g. filesize).
            $metadata = wp_generate_attachment_metadata($attachment_id, $file);
            wp_update_attachment_metadata($attachment_id, $metadata);
        }
    }

    /**
     * Resize image to exact dimensions.
     *
     * @param int $attachment_id Attachment ID.
     */
    private function resize_image($attachment_id)
    {
        $target_width = get_option('aifig_image_width', 1024);
        $target_height = get_option('aifig_image_height', 675);

        $file_path = get_attached_file($attachment_id);
        if (!$file_path) {
            return;
        }

        $image = wp_get_image_editor($file_path);
        if (is_wp_error($image)) {
            return;
        }

        $current_size = $image->get_size();

        // Only resize if dimensions don't match
        if ($current_size['width'] !== $target_width || $current_size['height'] !== $target_height) {
            $image->resize($target_width, $target_height, true);
            $image->save($file_path);

            // Regenerate metadata
            $metadata = wp_generate_attachment_metadata($attachment_id, $file_path);
            wp_update_attachment_metadata($attachment_id, $metadata);
        }
    }

    /**
     * Get posts without featured images.
     *
     * @param array $args Additional query arguments.
     * @return array Array of post IDs.
     */
    public static function get_posts_without_featured_image($args = array())
    {
        $defaults = array(
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Necessary to find posts without featured images, only used in admin
            'meta_query' => array(
                array(
                    'key' => '_thumbnail_id',
                    'compare' => 'NOT EXISTS',
                ),
            ),
        );

        $args = wp_parse_args($args, $defaults);
        $query = new WP_Query($args);

        return $query->posts;
    }
}
