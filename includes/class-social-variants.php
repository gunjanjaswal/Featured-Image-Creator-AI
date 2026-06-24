<?php
/**
 * Social / Open Graph image variants
 *
 * @package AI_Featured_Image_Generator
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Class AIFIG_Social_Variants
 *
 * Creates platform-sized variants (Open Graph, Twitter, square, Pinterest)
 * from a single generated image by cropping/resizing locally — no extra API
 * calls. Optionally wires the Open Graph variant into Yoast / Rank Math and
 * outputs a fallback og:image tag when no SEO plugin is active.
 */
class AIFIG_Social_Variants
{

    /**
     * Available variant specifications.
     *
     * @return array<string,array{label:string,width:int,height:int}>
     */
    public static function get_specs()
    {
        return apply_filters(
            'aifig_social_variants',
            array(
                'og' => array(
                    'label'  => __('Open Graph — Facebook / LinkedIn (1200×630)', 'featured-image-creator-ai'),
                    'width'  => 1200,
                    'height' => 630,
                ),
                'twitter' => array(
                    'label'  => __('Twitter / X (1200×675)', 'featured-image-creator-ai'),
                    'width'  => 1200,
                    'height' => 675,
                ),
                'square' => array(
                    'label'  => __('Square — Instagram / feeds (1080×1080)', 'featured-image-creator-ai'),
                    'width'  => 1080,
                    'height' => 1080,
                ),
                'pinterest' => array(
                    'label'  => __('Pinterest (1000×1500)', 'featured-image-creator-ai'),
                    'width'  => 1000,
                    'height' => 1500,
                ),
            )
        );
    }

    /**
     * Whether the feature is enabled.
     *
     * @return bool
     */
    public static function is_enabled()
    {
        return (bool) get_option('aifig_social_enabled', false);
    }

    /**
     * The variant type keys the user has enabled.
     *
     * @return string[]
     */
    public static function enabled_types()
    {
        $all = array_keys(self::get_specs());
        $sel = get_option('aifig_social_types', array('og', 'square'));
        if (!is_array($sel)) {
            $sel = array();
        }
        return array_values(array_intersect($all, $sel));
    }

    /**
     * Generate the enabled variants from a source attachment.
     *
     * @param int        $source_id Source (featured) attachment ID.
     * @param int        $post_id   Post ID the variants belong to.
     * @param array|null $types     Variant keys, or null for the saved set.
     * @return array<string,int> Map of type => attachment ID.
     */
    public static function generate_for_attachment($source_id, $post_id, $types = null)
    {
        if (null === $types) {
            $types = self::enabled_types();
        }
        if (empty($types)) {
            return array();
        }

        $source_file = get_attached_file($source_id);
        if (!$source_file || !file_exists($source_file)) {
            return array();
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        // Remove any variants created on a previous run for this post.
        self::cleanup($post_id);

        $title = get_the_title($post_id);
        $ext   = strtolower(pathinfo($source_file, PATHINFO_EXTENSION));
        if ('' === $ext) {
            $ext = 'png';
        }
        $mime  = 'image/' . ('jpg' === $ext ? 'jpeg' : $ext);
        $specs = self::get_specs();
        $map   = array();

        foreach ($types as $type) {
            if (!isset($specs[$type])) {
                continue;
            }
            $width  = (int) $specs[$type]['width'];
            $height = (int) $specs[$type]['height'];

            $editor = wp_get_image_editor($source_file);
            if (is_wp_error($editor)) {
                continue;
            }

            // Center-crop to the exact target aspect/size.
            $editor->resize($width, $height, true);

            $tmp   = wp_tempnam(sanitize_file_name($title . '-' . $type) . '.' . $ext);
            $saved = $editor->save($tmp, $mime);
            if (is_wp_error($saved) || empty($saved['path'])) {
                if (file_exists($tmp)) {
                    wp_delete_file($tmp);
                }
                continue;
            }

            $file_array = array(
                'name'     => sanitize_file_name($title . '-' . $type) . '.' . $ext,
                'tmp_name' => $saved['path'],
            );

            $att_id = media_handle_sideload(
                $file_array,
                $post_id,
                sprintf(
                    /* translators: 1: post title, 2: variant label. */
                    __('%1$s — %2$s', 'featured-image-creator-ai'),
                    $title,
                    $specs[$type]['label']
                )
            );

            if (file_exists($saved['path'])) {
                wp_delete_file($saved['path']);
            }

            if (is_wp_error($att_id)) {
                continue;
            }

            update_post_meta($att_id, '_aifig_generated', true);
            update_post_meta($att_id, '_aifig_variant_type', $type);
            update_post_meta($att_id, '_aifig_source', $source_id);

            $map[$type] = $att_id;
        }

        if (!empty($map)) {
            update_post_meta($post_id, '_aifig_social_images', $map);

            if (get_option('aifig_social_set_og', true)) {
                $og_id = isset($map['og']) ? $map['og'] : reset($map);
                update_post_meta($post_id, '_aifig_og_image_id', $og_id);
                self::set_seo_og_image($post_id, $og_id);
            }
        }

        return $map;
    }

    /**
     * Delete the previously generated variants for a post.
     *
     * @param int $post_id Post ID.
     * @return void
     */
    public static function cleanup($post_id)
    {
        $prev = get_post_meta($post_id, '_aifig_social_images', true);
        if (is_array($prev)) {
            foreach ($prev as $att_id) {
                $att_id = absint($att_id);
                if ($att_id
                    && get_post_meta($att_id, '_aifig_generated', true)
                    && get_post_meta($att_id, '_aifig_variant_type', true)
                ) {
                    wp_delete_attachment($att_id, true);
                }
            }
        }
        delete_post_meta($post_id, '_aifig_social_images');
    }

    /**
     * Point common SEO plugins at the Open Graph variant.
     *
     * Setting the meta is harmless when the plugin is not installed.
     *
     * @param int $post_id Post ID.
     * @param int $att_id  Attachment ID to use for OG/Twitter image.
     * @return void
     */
    public static function set_seo_og_image($post_id, $att_id)
    {
        $url = wp_get_attachment_image_url($att_id, 'full');
        if (!$url) {
            return;
        }

        // Yoast SEO.
        update_post_meta($post_id, '_yoast_wpseo_opengraph-image', $url);
        update_post_meta($post_id, '_yoast_wpseo_opengraph-image-id', $att_id);
        update_post_meta($post_id, '_yoast_wpseo_twitter-image', $url);
        update_post_meta($post_id, '_yoast_wpseo_twitter-image-id', $att_id);

        // Rank Math.
        update_post_meta($post_id, 'rank_math_facebook_image', $url);
        update_post_meta($post_id, 'rank_math_facebook_image_id', $att_id);
        update_post_meta($post_id, 'rank_math_twitter_image', $url);
        update_post_meta($post_id, 'rank_math_twitter_image_id', $att_id);
    }

    /**
     * Output fallback Open Graph / Twitter image tags in the document head.
     *
     * Skips output when a known SEO plugin is active (it handles OG tags), or
     * when the feature/OG option is off, or off-singular.
     *
     * @return void
     */
    public static function maybe_output_og_tags()
    {
        if (!self::is_enabled() || !get_option('aifig_social_set_og', true)) {
            return;
        }
        // Let dedicated SEO plugins own the OG tags to avoid duplicates.
        if (defined('WPSEO_VERSION') || class_exists('RankMath', false)) {
            return;
        }
        if (!is_singular()) {
            return;
        }

        $post_id = get_queried_object_id();
        $og_id   = absint(get_post_meta($post_id, '_aifig_og_image_id', true));
        if (!$og_id) {
            return;
        }

        $src = wp_get_attachment_image_src($og_id, 'full');
        if (!$src) {
            return;
        }

        printf('<meta property="og:image" content="%s" />' . "\n", esc_url($src[0]));
        printf('<meta property="og:image:width" content="%d" />' . "\n", (int) $src[1]);
        printf('<meta property="og:image:height" content="%d" />' . "\n", (int) $src[2]);
        echo '<meta name="twitter:card" content="summary_large_image" />' . "\n";
        printf('<meta name="twitter:image" content="%s" />' . "\n", esc_url($src[0]));
    }
}
