<?php
/**
 * OpenAI API Provider
 *
 * @package AI_Featured_Image_Generator
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Class AIFIG_OpenAI_Provider
 *
 * OpenAI DALL-E 3 API implementation.
 */
class AIFIG_OpenAI_Provider extends AIFIG_API_Interface
{

    /**
     * API endpoint for image generation.
     *
     * @var string
     */
    private $api_endpoint = 'https://api.openai.com/v1/images/generations';

    /**
     * Model to use.
     *
     * @var string
     */
    private $model;

    /**
     * Image quality.
     *
     * @var string
     */
    private $quality;

    /**
     * Constructor.
     *
     * @param string $api_key API key.
     * @param string $model   Model name (default: dall-e-3).
     * @param string $quality Image quality (default: standard).
     */
    public function __construct($api_key, $model = 'dall-e-3', $quality = 'standard')
    {
        parent::__construct($api_key);
        $this->model = $model;
        $this->quality = $quality;
    }

    /**
     * Get provider name.
     *
     * @return string
     */
    public function get_provider_name()
    {
        return 'OpenAI ' . $this->model;
    }

    /**
     * Generate an image from a prompt.
     *
     * @param string $prompt Image generation prompt.
     * @param int    $width  Image width in pixels.
     * @param int    $height Image height in pixels.
     * @return array|WP_Error Array with 'url' key on success, WP_Error on failure.
     */
    public function generate_image($prompt, $width = 1024, $height = 675)
    {
        // DALL-E 3 supports specific sizes: 1024x1024, 1792x1024, or 1024x1792
        // We'll use 1024x1024 and crop/resize as needed
        $size = $this->get_closest_size($width, $height);

        $quality = $this->normalize_quality($this->quality);

        $body = array(
            'model' => $this->model,
            'prompt' => $prompt,
            'n' => 1,
            'size' => $size,
            'quality' => $quality,
        );

        $response = wp_remote_post(
            $this->api_endpoint,
            array(
                'headers' => array(
                    'Authorization' => 'Bearer ' . $this->api_key,
                    'Content-Type' => 'application/json',
                ),
                'body' => wp_json_encode($body),
                'timeout' => 60,
            )
        );

        if (is_wp_error($response)) {
            return new WP_Error(
                'api_request_failed',
                sprintf(
                    /* translators: %s: Error message */
                    __('API request failed: %s', 'featured-image-creator-ai'),
                    $response->get_error_message()
                )
            );
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        $data = json_decode($response_body, true);

        if (200 !== $response_code) {
            $error_message = isset($data['error']['message']) ? $data['error']['message'] : __('Unknown error', 'featured-image-creator-ai');

            return new WP_Error(
                'api_error',
                sprintf(
                    /* translators: 1: HTTP status code, 2: Error message */
                    __('API returned error (HTTP %1$d): %2$s', 'featured-image-creator-ai'),
                    $response_code,
                    $error_message
                )
            );
        }

        if (isset($data['data'][0]['url'])) {
            return array(
                'url' => $data['data'][0]['url'],
                'revised_prompt' => isset($data['data'][0]['revised_prompt']) ? $data['data'][0]['revised_prompt'] : $prompt,
            );
        } elseif (isset($data['data'][0]['b64_json'])) {
            $image_data = base64_decode($data['data'][0]['b64_json']);
			if ( ! function_exists( 'wp_tempnam' ) ) {
    require_once ABSPATH . 'wp-admin/includes/file.php';
}
            $temp_file = wp_tempnam();
            file_put_contents($temp_file, $image_data);

            return array(
                'url' => $temp_file,
                'revised_prompt' => isset($data['data'][0]['revised_prompt']) ? $data['data'][0]['revised_prompt'] : $prompt,
            );
        }

        return new WP_Error(
            'invalid_response',
            sprintf(
                /* translators: %s: Response data */
                __('Invalid API response: No image URL found. Response: %s', 'featured-image-creator-ai'),
                wp_json_encode($data)
            )
        );
    }

    /**
     * Whether this provider supports vision (alt-text description).
     *
     * @return bool
     */
    public function supports_vision()
    {
        return true;
    }

    /**
     * Describe an image using an OpenAI vision-capable chat model.
     *
     * @param string $file_path Local image path.
     * @param string $context   Optional grounding hint.
     * @return string|WP_Error
     */
    public function describe_image($file_path, $context = '')
    {
        if (!file_exists($file_path)) {
            return new WP_Error('missing_file', __('Image file not found for description.', 'featured-image-creator-ai'));
        }

        $bytes = file_get_contents($file_path);
        if (false === $bytes) {
            return new WP_Error('read_failed', __('Could not read image for description.', 'featured-image-creator-ai'));
        }

        $data_uri = 'data:' . $this->guess_mime($file_path) . ';base64,' . base64_encode($bytes);

        /** This filter allows overriding the vision model used for alt text. */
        $vision_model = apply_filters('aifig_openai_vision_model', 'gpt-4o-mini');

        $body = array(
            'model'      => $vision_model,
            'max_tokens' => 120,
            'messages'   => array(
                array(
                    'role'    => 'user',
                    'content' => array(
                        array(
                            'type' => 'text',
                            'text' => $this->build_alt_text_prompt($context),
                        ),
                        array(
                            'type'      => 'image_url',
                            'image_url' => array('url' => $data_uri),
                        ),
                    ),
                ),
            ),
        );

        $response = wp_remote_post(
            'https://api.openai.com/v1/chat/completions',
            array(
                'headers' => array(
                    'Authorization' => 'Bearer ' . $this->api_key,
                    'Content-Type'  => 'application/json',
                ),
                'body'    => wp_json_encode($body),
                'timeout' => 45,
            )
        );

        if (is_wp_error($response)) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code($response);
        $data = json_decode(wp_remote_retrieve_body($response), true);

        if (200 !== $code) {
            $message = isset($data['error']['message']) ? $data['error']['message'] : __('Unknown error', 'featured-image-creator-ai');
            return new WP_Error('vision_error', $message);
        }

        if (!isset($data['choices'][0]['message']['content'])) {
            return new WP_Error('vision_invalid', __('No description returned.', 'featured-image-creator-ai'));
        }

        $alt = $this->clean_alt_text($data['choices'][0]['message']['content']);
        if ('' === $alt) {
            return new WP_Error('vision_empty', __('Empty description returned.', 'featured-image-creator-ai'));
        }

        return $alt;
    }

    /**
     * Validate API key.
     *
     * @return bool|WP_Error True if valid, WP_Error on failure.
     */
    public function validate_api_key()
    {
        // Make a simple API call to validate the key
        $response = wp_remote_get(
            'https://api.openai.com/v1/models',
            array(
                'headers' => array(
                    'Authorization' => 'Bearer ' . $this->api_key,
                ),
                'timeout' => 10,
            )
        );

        if (is_wp_error($response)) {
            return $response;
        }

        $response_code = wp_remote_retrieve_response_code($response);

        if (200 === $response_code) {
            return true;
        }

        return new WP_Error(
            'invalid_api_key',
            __('Invalid API key', 'featured-image-creator-ai')
        );
    }

    /**
     * Get the closest supported size for DALL-E 3.
     *
     * @param int $width  Desired width.
     * @param int $height Desired height.
     * @return string Size string (e.g., '1024x1024').
     */
    /**
     * Normalize the configured quality to a value the target model accepts.
     *
     * DALL-E 3 accepts: standard, hd.
     * GPT Image models accept: low, medium, high, auto.
     *
     * @param string $quality Configured quality setting.
     * @return string Quality value valid for the current model.
     */
    private function normalize_quality($quality)
    {
        if ('dall-e-3' === $this->model) {
            $map = array(
                'standard' => 'standard',
                'hd'       => 'hd',
                'high'     => 'hd',
                'low'      => 'standard',
                'medium'   => 'standard',
                'auto'     => 'standard',
            );

            return isset($map[$quality]) ? $map[$quality] : 'standard';
        }

        // GPT Image models.
        $map = array(
            'low'      => 'low',
            'medium'   => 'medium',
            'high'     => 'high',
            'auto'     => 'auto',
            'standard' => 'medium',
            'hd'       => 'high',
        );

        return isset($map[$quality]) ? $map[$quality] : 'auto';
    }

    private function get_closest_size($width, $height)
    {
        // Default supported sizes (DALL-E 3)
        $supported_sizes = array(
            '1024x1024' => array('width' => 1024, 'height' => 1024),
            '1792x1024' => array('width' => 1792, 'height' => 1024),
            '1024x1792' => array('width' => 1024, 'height' => 1792),
        );

        // Custom sizes for GPT Image models (based on API error feedback)
        if ($this->model !== 'dall-e-3') {
            $supported_sizes = array(
                '1024x1024' => array('width' => 1024, 'height' => 1024),
                '1536x1024' => array('width' => 1536, 'height' => 1024),
                '1024x1536' => array('width' => 1024, 'height' => 1536),
            );
        }

        // Calculate aspect ratio
        $target_ratio = $width / $height;

        $closest_size = '1024x1024';
        $closest_diff = PHP_FLOAT_MAX;

        foreach ($supported_sizes as $size_name => $dimensions) {
            $size_ratio = $dimensions['width'] / $dimensions['height'];
            $diff = abs($target_ratio - $size_ratio);

            if ($diff < $closest_diff) {
                $closest_diff = $diff;
                $closest_size = $size_name;
            }
        }

        return $closest_size;
    }
}
