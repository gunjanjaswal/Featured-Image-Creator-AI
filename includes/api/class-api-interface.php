<?php
/**
 * API Interface
 *
 * @package AI_Featured_Image_Generator
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Abstract class AIFIG_API_Interface
 *
 * Defines the contract for API provider implementations.
 */
abstract class AIFIG_API_Interface
{

    /**
     * API key.
     *
     * @var string
     */
    protected $api_key;

    /**
     * Constructor.
     *
     * @param string $api_key API key for the provider.
     */
    public function __construct($api_key)
    {
        $this->api_key = $api_key;
    }

    /**
     * Generate an image from a prompt.
     *
     * @param string $prompt Image generation prompt.
     * @param int    $width  Image width in pixels.
     * @param int    $height Image height in pixels.
     * @return array|WP_Error Array with 'url' key on success, WP_Error on failure.
     */
    abstract public function generate_image($prompt, $width = 1024, $height = 675);

    /**
     * Validate API key.
     *
     * @return bool|WP_Error True if valid, WP_Error on failure.
     */
    abstract public function validate_api_key();

    /**
     * Get provider name.
     *
     * @return string Provider name.
     */
    abstract public function get_provider_name();

    /**
     * Whether this provider can describe an image (vision) for alt text.
     *
     * Providers that support vision override this and describe_image().
     *
     * @return bool
     */
    public function supports_vision()
    {
        return false;
    }

    /**
     * Describe an image for accessible alt text.
     *
     * Default implementation signals no vision support; vision-capable
     * providers override this to return a short description string.
     *
     * @param string $file_path Absolute path to a local image file.
     * @param string $context   Optional grounding hint (e.g. the post title).
     * @return string|WP_Error Alt text on success, WP_Error otherwise.
     */
    public function describe_image($file_path, $context = '')
    {
        return new WP_Error(
            'vision_unsupported',
            __('This provider does not support image description.', 'featured-image-creator-ai')
        );
    }

    /**
     * Build the shared prompt used to request alt text from a vision model.
     *
     * @param string $context Optional grounding hint.
     * @return string
     */
    protected function build_alt_text_prompt($context = '')
    {
        $prompt = 'Write concise, descriptive alt text for this image for accessibility and SEO. '
            . 'Describe what is visually shown in one sentence under 125 characters. '
            . 'Do not start with "Image of" or "A picture of". Return only the alt text, no quotes.';

        $context = trim((string) $context);
        if ('' !== $context) {
            $prompt .= ' For context, this is the featured image for an article titled: "' . $context . '".';
        }

        return $prompt;
    }

    /**
     * Normalize a raw model response into clean alt text.
     *
     * @param string $text Raw text.
     * @return string
     */
    protected function clean_alt_text($text)
    {
        $text = wp_strip_all_tags((string) $text);
        $text = trim($text);
        // Strip surrounding quotes the model sometimes adds.
        $text = trim($text, "\"'“”‘’ \t\n");
        // Collapse whitespace.
        $text = preg_replace('/\s+/', ' ', $text);
        // Keep it to a sensible alt-text length.
        if (function_exists('mb_substr') && mb_strlen($text) > 160) {
            $text = rtrim(mb_substr($text, 0, 157)) . '…';
        } elseif (strlen($text) > 160) {
            $text = rtrim(substr($text, 0, 157)) . '…';
        }
        return $text;
    }

    /**
     * Guess an image mime type from a file path.
     *
     * @param string $file_path File path.
     * @return string
     */
    protected function guess_mime($file_path)
    {
        $ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
        switch ($ext) {
            case 'jpg':
            case 'jpeg':
                return 'image/jpeg';
            case 'webp':
                return 'image/webp';
            case 'gif':
                return 'image/gif';
            case 'png':
            default:
                return 'image/png';
        }
    }
}
