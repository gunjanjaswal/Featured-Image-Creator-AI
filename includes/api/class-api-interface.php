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
}
