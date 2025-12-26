<?php
/**
 * Stability AI API Provider
 *
 * @package AI_Featured_Image_Generator
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Class AIFIG_Stability_Provider
 *
 * Stability AI (Stable Diffusion) API implementation.
 */
class AIFIG_Stability_Provider extends AIFIG_API_Interface
{

    /**
     * API endpoint for image generation.
     *
     * @var string
     */
    private $api_endpoint = 'https://api.stability.ai/v2beta/stable-image/generate/sd3';

    /**
     * Get provider name.
     *
     * @return string
     */
    public function get_provider_name()
    {
        return 'Stability AI (Stable Diffusion 3)';
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
        // Stability AI supports flexible dimensions
        $dimensions = $this->validate_dimensions($width, $height);

        // Prepare multipart form data
        $boundary = wp_generate_password(24, false);
        $body = '';

        // Add prompt
        $body .= "--{$boundary}\r\n";
        $body .= "Content-Disposition: form-data; name=\"prompt\"\r\n\r\n";
        $body .= "{$prompt}\r\n";

        // Add aspect ratio or dimensions
        $body .= "--{$boundary}\r\n";
        $body .= "Content-Disposition: form-data; name=\"aspect_ratio\"\r\n\r\n";
        $body .= $this->get_aspect_ratio_string($dimensions['width'], $dimensions['height']) . "\r\n";

        // Add output format
        $body .= "--{$boundary}\r\n";
        $body .= "Content-Disposition: form-data; name=\"output_format\"\r\n\r\n";
        $body .= "png\r\n";

        $body .= "--{$boundary}--\r\n";

        $response = wp_remote_post(
            $this->api_endpoint,
            array(
                'headers' => array(
                    'Authorization' => 'Bearer ' . $this->api_key,
                    'Content-Type' => 'multipart/form-data; boundary=' . $boundary,
                    'Accept' => 'image/*',
                ),
                'body' => $body,
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
        $content_type = wp_remote_retrieve_header($response, 'content-type');

        if (200 !== $response_code) {
            $response_body = wp_remote_retrieve_body($response);
            $data = json_decode($response_body, true);
            $error_message = isset($data['message']) ? $data['message'] : __('Unknown error', 'featured-image-creator-ai');

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

        // Check if response is an image
        if (strpos($content_type, 'image/') === false) {
            return new WP_Error(
                'invalid_response',
                __('Invalid API response: Expected image data', 'featured-image-creator-ai')
            );
        }

        // Get image data
        $image_data = wp_remote_retrieve_body($response);

        // Create temporary file
        $temp_file = wp_tempnam();
        file_put_contents($temp_file, $image_data);

        return array(
            'url' => $temp_file,
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
            'https://api.stability.ai/v1/user/account',
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
     * Validate and adjust dimensions.
     *
     * @param int $width  Desired width.
     * @param int $height Desired height.
     * @return array Validated dimensions.
     */
    private function validate_dimensions($width, $height)
    {
        // Stability AI requires dimensions to be multiples of 64
        $width = max(512, min(2048, round($width / 64) * 64));
        $height = max(512, min(2048, round($height / 64) * 64));

        return array(
            'width' => $width,
            'height' => $height,
        );
    }

    /**
     * Get aspect ratio string.
     *
     * @param int $width  Width.
     * @param int $height Height.
     * @return string Aspect ratio.
     */
    private function get_aspect_ratio_string($width, $height)
    {
        $ratio = $width / $height;

        // Common aspect ratios
        $ratios = array(
            '1:1' => 1.0,
            '16:9' => 1.777,
            '21:9' => 2.333,
            '3:2' => 1.5,
            '2:3' => 0.666,
            '4:5' => 0.8,
            '5:4' => 1.25,
            '9:16' => 0.5625,
            '9:21' => 0.428,
        );

        $closest = '1:1';
        $closest_diff = PHP_FLOAT_MAX;

        foreach ($ratios as $ratio_name => $ratio_value) {
            $diff = abs($ratio - $ratio_value);
            if ($diff < $closest_diff) {
                $closest_diff = $diff;
                $closest = $ratio_name;
            }
        }

        return $closest;
    }
}
