<?php
/**
 * Style presets class
 *
 * @package AI_Featured_Image_Generator
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Class AIFIG_Styles
 *
 * Provides ready-made visual style presets that are appended to the image
 * prompt, so users don't have to hand-write prompt engineering for a
 * consistent look.
 */
class AIFIG_Styles
{

    /**
     * Get all available style presets.
     *
     * Each preset maps a stable key to a translated label and a prompt
     * suffix that is appended to the generated prompt.
     *
     * @return array<string,array{label:string,suffix:string}>
     */
    public static function get_presets()
    {
        $presets = array(
            'none' => array(
                'label'  => __('None (use prompt as-is)', 'featured-image-creator-ai'),
                'suffix' => '',
            ),
            'photographic' => array(
                'label'  => __('Photographic', 'featured-image-creator-ai'),
                'suffix' => 'Photorealistic, high-detail photography, natural lighting, sharp focus, shallow depth of field, professional DSLR camera.',
            ),
            'flat_illustration' => array(
                'label'  => __('Flat Illustration', 'featured-image-creator-ai'),
                'suffix' => 'Flat vector illustration, clean bold outlines, solid colors, minimal shading, modern editorial style.',
            ),
            'digital_art' => array(
                'label'  => __('Digital Art', 'featured-image-creator-ai'),
                'suffix' => 'Polished digital painting, rich colors, dramatic lighting, detailed, concept-art quality, trending on ArtStation.',
            ),
            '3d_render' => array(
                'label'  => __('3D Render', 'featured-image-creator-ai'),
                'suffix' => '3D rendered, soft studio lighting, smooth glossy materials, subtle reflections, high detail, octane render.',
            ),
            'watercolor' => array(
                'label'  => __('Watercolor', 'featured-image-creator-ai'),
                'suffix' => 'Watercolor painting, soft brush strokes, visible paper texture, gentle color bleeds, hand-painted and artistic.',
            ),
            'minimal' => array(
                'label'  => __('Minimal', 'featured-image-creator-ai'),
                'suffix' => 'Minimalist design, generous negative space, simple geometric shapes, limited color palette, clean and uncluttered.',
            ),
            'isometric' => array(
                'label'  => __('Isometric', 'featured-image-creator-ai'),
                'suffix' => 'Isometric illustration, 3D-style perspective, clean geometry, vibrant colors, modern tech-infographic look.',
            ),
            'cyberpunk' => array(
                'label'  => __('Cyberpunk / Neon', 'featured-image-creator-ai'),
                'suffix' => 'Cyberpunk aesthetic, glowing neon lighting, futuristic city mood, high contrast, cinematic sci-fi atmosphere.',
            ),
            'paper_cut' => array(
                'label'  => __('Paper-cut', 'featured-image-creator-ai'),
                'suffix' => 'Layered paper-cut craft style, stacked cut-paper shapes, soft drop shadows, tactile handmade texture.',
            ),
            'corporate' => array(
                'label'  => __('Corporate Clean', 'featured-image-creator-ai'),
                'suffix' => 'Clean corporate style, professional and modern, soft gradients, blue and neutral palette, business-appropriate.',
            ),
            'retro' => array(
                'label'  => __('Retro / Vintage', 'featured-image-creator-ai'),
                'suffix' => 'Retro vintage aesthetic, muted warm tones, grain texture, 70s/80s poster vibe, nostalgic.',
            ),
        );

        /**
         * Filter the available style presets.
         *
         * @param array $presets Associative array of presets.
         */
        return apply_filters('aifig_style_presets', $presets);
    }

    /**
     * Whether a key is a known preset.
     *
     * @param string $key Preset key.
     * @return bool
     */
    public static function is_valid($key)
    {
        $presets = self::get_presets();
        return is_string($key) && isset($presets[$key]);
    }

    /**
     * Sanitize a submitted style key, falling back to 'none'.
     *
     * @param string $key Raw key.
     * @return string Valid preset key.
     */
    public static function sanitize($key)
    {
        $key = sanitize_text_field($key);
        return self::is_valid($key) ? $key : 'none';
    }

    /**
     * Get the prompt suffix for a preset key.
     *
     * @param string $key Preset key.
     * @return string Suffix (may be empty).
     */
    public static function get_suffix($key)
    {
        $presets = self::get_presets();
        if (isset($presets[$key])) {
            return $presets[$key]['suffix'];
        }
        return '';
    }
}
