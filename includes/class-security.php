<?php
/**
 * Security utilities class
 *
 * @package AI_Featured_Image_Generator
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Class AIFIG_Security
 *
 * Handles security-related functionality including encryption, nonces, and capability checks.
 */
class AIFIG_Security
{

    /**
     * Encryption key for API keys.
     *
     * @var string
     */
    private static $encryption_key = null;

    /**
     * Get or generate encryption key.
     *
     * @return string
     */
    private static function get_encryption_key()
    {
        if (null === self::$encryption_key) {
            // Use WordPress auth key and salt for encryption
            self::$encryption_key = hash('sha256', AUTH_KEY . AUTH_SALT);
        }
        return self::$encryption_key;
    }

    /**
     * Encrypt a string.
     *
     * @param string $data Data to encrypt.
     * @return string Encrypted data.
     */
    public static function encrypt($data)
    {
        if (empty($data)) {
            return '';
        }

        $key = self::get_encryption_key();
        $method = 'AES-256-CBC';
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($method));

        $encrypted = openssl_encrypt($data, $method, $key, 0, $iv);

        // Combine IV and encrypted data
        return base64_encode($iv . $encrypted);
    }

    /**
     * Decrypt a string.
     *
     * @param string $data Encrypted data.
     * @return string Decrypted data.
     */
    public static function decrypt($data)
    {
        if (empty($data)) {
            return '';
        }

        $key = self::get_encryption_key();
        $method = 'AES-256-CBC';
        $data = base64_decode($data);

        $iv_length = openssl_cipher_iv_length($method);
        $iv = substr($data, 0, $iv_length);
        $encrypted = substr($data, $iv_length);

        return openssl_decrypt($encrypted, $method, $key, 0, $iv);
    }

    /**
     * Verify nonce and capability.
     *
     * @param string $nonce_action Nonce action name.
     * @param string $capability   Required capability.
     * @param string $nonce_field  Nonce field name (default: 'nonce').
     * @return bool True if verified, false otherwise.
     */
    public static function verify_request($nonce_action, $capability = 'edit_posts', $nonce_field = 'nonce')
    {
        // Check nonce
        $nonce = isset($_REQUEST[$nonce_field]) ? sanitize_text_field(wp_unslash($_REQUEST[$nonce_field])) : '';

        if (!wp_verify_nonce($nonce, $nonce_action)) {
            return false;
        }

        // Check capability
        if (!current_user_can($capability)) {
            return false;
        }

        return true;
    }

    /**
     * Sanitize API key.
     *
     * @param string $api_key API key to sanitize.
     * @return string Sanitized API key.
     */
    public static function sanitize_api_key($api_key)
    {
        // Remove any whitespace and special characters except hyphens and underscores
        return preg_replace('/[^a-zA-Z0-9\-_]/', '', trim($api_key));
    }

    /**
     * Sanitize prompt template.
     *
     * @param string $template Prompt template to sanitize.
     * @return string Sanitized template.
     */
    public static function sanitize_prompt_template($template)
    {
        // Allow basic text and {title} placeholder
        $template = sanitize_textarea_field($template);

        // Ensure {title} placeholder exists
        if (strpos($template, '{title}') === false) {
            $template .= ' {title}';
        }

        return $template;
    }

    /**
     * Check if user can generate images.
     *
     * @return bool
     */
    public static function can_generate_images()
    {
        return current_user_can('edit_posts') || current_user_can('upload_files');
    }

    /**
     * Check if user can manage plugin settings.
     *
     * @return bool
     */
    public static function can_manage_settings()
    {
        return current_user_can('manage_options');
    }
}
