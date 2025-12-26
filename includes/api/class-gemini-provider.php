<?php
/**
 * Google Gemini API Provider
 *
 * @package AI_Featured_Image_Generator
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Class AIFIG_Gemini_Provider
 *
 * Google Gemini (Imagen) API implementation.
 */
class AIFIG_Gemini_Provider extends AIFIG_API_Interface
{

    /**
     * API endpoint for image generation.
     *
     * @var string
     */
    private $api_endpoint = 'https://generativelanguage.googleapis.com/v1beta/models/imagen-3.0-generate-001:predict';

    /**
     * Get provider name.
     *
     * @return string
     */
    public function get_provider_name()
    {
        return 'Google Gemini (Imagen)';
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
        // Gemini Imagen supports various aspect ratios
        $aspect_ratio = $this->get_aspect_ratio($width, $height);

        $body = array(
            'instances' => array(
                array(
                    'prompt' => $prompt,
                ),
            ),
            'parameters' => array(
                'sampleCount' => 1,
                'aspectRatio' => $aspect_ratio,
                'safetyFilterLevel' => 'block_some',
            ),
        );

        $response = wp_remote_post(
            $this->api_endpoint . '?key=' . $this->api_key,
            array(
                'headers' => array(
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

        // Extract image data from response
        if (!isset($data['predictions'][0]['bytesBase64Encoded'])) {
            return new WP_Error(
                'invalid_response',
                __('Invalid API response: No image data found', 'featured-image-creator-ai')
            );
        }

        // Gemini returns base64 encoded image
        $image_data = base64_decode($data['predictions'][0]['bytesBase64Encoded']);

        // Create temporary file
        $temp_file = wp_tempnam();
        file_put_contents($temp_file, $image_data);

        return array(
            'url' => $temp_file, // Return temp file path instead of URL
            'is_local_file' => true,
            'revised_prompt' => $prompt,
        );
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
            'https://generativelanguage.googleapis.com/v1beta/models?key=' . $this->api_key,
            array(
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
     * Get the aspect ratio string for Gemini.
     *
     * @param int $width  Desired width.
     * @param int $height Desired height.
     * @return string Aspect ratio string.
     */
    private function get_aspect_ratio($width, $height)
    {
        $ratio = $width / $height;

        // Gemini supports: 1:1, 3:4, 4:3, 9:16, 16:9
        $supported_ratios = array(
            '1:1' => 1.0,
            '3:4' => 0.75,
            '4:3' => 1.333,
            '9:16' => 0.5625,
            '16:9' => 1.777,
        );

        $closest_ratio = '1:1';
        $closest_diff = PHP_FLOAT_MAX;

        foreach ($supported_ratios as $ratio_name => $ratio_value) {
            $diff = abs($ratio - $ratio_value);

            if ($diff < $closest_diff) {
                $closest_diff = $diff;
                $closest_ratio = $ratio_name;
            }
        }

        return $closest_ratio;
    }
}
