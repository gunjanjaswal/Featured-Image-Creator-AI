<?php
/**
 * Brand colour theming for generated images.
 *
 * Two independent layers:
 *   1. A prompt fragment that steers the AI model toward a chosen colour
 *      (works with every provider, no extra cost, natural-looking).
 *   2. An optional local recolour pass that washes the finished image toward
 *      the exact colour using Imagick or GD, for sites that need the hue to
 *      match a brand precisely.
 *
 * @package AI_Featured_Image_Generator
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Class AIFIG_Image_Color
 */
class AIFIG_Image_Color
{

    /**
     * Whether the local recolour pass can run on this server.
     *
     * @return bool
     */
    public static function is_available()
    {
        if (extension_loaded('imagick') && class_exists('Imagick')) {
            return true;
        }
        return extension_loaded('gd');
    }

    /**
     * Build the prompt fragment that steers the model toward $hex.
     *
     * @param string $hex Hex colour (#rrggbb).
     * @return string Fragment, or '' when the colour is invalid.
     */
    public static function prompt_fragment($hex)
    {
        $hex = sanitize_hex_color($hex);
        if (!$hex) {
            return '';
        }

        $name = self::nearest_name($hex);
        $desc = $name ? $name . ' (' . $hex . ')' : $hex;

        return 'Use a colour palette built around ' . $desc
            . ' as the dominant, recurring accent colour throughout the image.';
    }

    /**
     * Recolour the image at $file_path toward $hex.
     *
     * Operates on a copy in memory and only overwrites the file once the new
     * image is fully rendered, so any failure leaves the original untouched.
     *
     * @param string $file_path Absolute path to the image.
     * @param string $hex       Target hex colour.
     * @param int    $strength  Blend strength, 0-100.
     * @return bool True when the file was rewritten, false otherwise.
     */
    public static function apply($file_path, $hex, $strength = 60)
    {
        $hex = sanitize_hex_color($hex);
        if (!$hex || !file_exists($file_path)) {
            return false;
        }

        $strength = max(0, min(100, (int) $strength));
        if (0 === $strength) {
            return false;
        }

        if (extension_loaded('imagick') && class_exists('Imagick')) {
            $done = self::apply_imagick($file_path, $hex, $strength);
            if ($done) {
                return true;
            }
            // Fall through to GD if Imagick failed for any reason.
        }

        if (extension_loaded('gd')) {
            return self::apply_gd($file_path, $hex, $strength);
        }

        return false;
    }

    /**
     * Imagick recolour: "Colorize" blend (hue + saturation of the target,
     * luminosity of the original) dissolved over the original by $strength.
     *
     * @param string $file_path File path.
     * @param string $hex       Target colour.
     * @param int    $strength  0-100.
     * @return bool
     */
    private static function apply_imagick($file_path, $hex, $strength)
    {
        try {
            $img = new Imagick($file_path);
            $img->setImageColorspace(Imagick::COLORSPACE_SRGB);

            $geo    = $img->getImageGeometry();
            $width  = $geo['width'];
            $height = $geo['height'];

            // A solid layer of the target colour.
            $solid = new Imagick();
            $solid->newImage($width, $height, new ImagickPixel($hex));
            $solid->setImageFormat($img->getImageFormat());

            // "Colorize" blend keeps the original's light and shade but takes
            // the target's hue and saturation.
            $tinted = clone $img;
            $tinted->compositeImage($solid, Imagick::COMPOSITE_COLORIZE, 0, 0);

            // Dissolve the tinted copy back over the original by $strength.
            if (method_exists($tinted, 'setImageAlpha')) {
                $tinted->setImageAlpha($strength / 100);
            } elseif (method_exists($tinted, 'setImageOpacity')) {
                $tinted->setImageOpacity($strength / 100);
            }
            $img->compositeImage($tinted, Imagick::COMPOSITE_DISSOLVE, 0, 0);

            $img->writeImage($file_path);

            $solid->clear();
            $tinted->clear();
            $img->clear();

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * GD recolour: grayscale + colorize toward the target, merged over the
     * original by $strength.
     *
     * @param string $file_path File path.
     * @param string $hex       Target colour.
     * @param int    $strength  0-100.
     * @return bool
     */
    private static function apply_gd($file_path, $hex, $strength)
    {
        $data = @file_get_contents($file_path);
        if (false === $data) {
            return false;
        }

        $base = @imagecreatefromstring($data);
        if (!$base) {
            return false;
        }
        if (function_exists('imagepalettetotruecolor')) {
            imagepalettetotruecolor($base);
        }

        $width  = imagesx($base);
        $height = imagesy($base);

        list($r, $g, $b) = self::hex_to_rgb($hex);

        // Grayscale + colorize copy.
        $tint = imagecreatetruecolor($width, $height);
        imagecopy($tint, $base, 0, 0, 0, 0, $width, $height);
        if (function_exists('imagefilter')) {
            imagefilter($tint, IMG_FILTER_GRAYSCALE);
            // Shift the midpoint toward the target colour.
            imagefilter($tint, IMG_FILTER_COLORIZE, $r - 128, $g - 128, $b - 128);
        }

        // Merge the colorized copy over the original by $strength percent.
        imagecopymerge($base, $tint, 0, 0, 0, 0, $width, $height, $strength);

        $ok = self::save_gd($base, $file_path);

        imagedestroy($tint);
        imagedestroy($base);

        return $ok;
    }

    /**
     * Save a GD image back to its file, matching the existing extension.
     *
     * @param resource|\GdImage $img       Image.
     * @param string            $file_path Destination.
     * @return bool
     */
    private static function save_gd($img, $file_path)
    {
        $ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));

        if (('jpg' === $ext || 'jpeg' === $ext) && function_exists('imagejpeg')) {
            return (bool) imagejpeg($img, $file_path, 90);
        }
        if ('webp' === $ext && function_exists('imagewebp')) {
            return (bool) imagewebp($img, $file_path, 90);
        }
        if (function_exists('imagepng')) {
            return (bool) imagepng($img, $file_path);
        }
        return false;
    }

    /**
     * Convert a hex colour to an [r, g, b] array.
     *
     * @param string $hex      Hex colour.
     * @param array  $fallback Fallback RGB.
     * @return array{0:int,1:int,2:int}
     */
    private static function hex_to_rgb($hex, $fallback = array(0, 0, 0))
    {
        $hex = ltrim((string) $hex, '#');
        if (3 === strlen($hex)) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        if (6 !== strlen($hex) || !ctype_xdigit($hex)) {
            return $fallback;
        }
        return array(
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        );
    }

    /**
     * Nearest plain-English colour name for a hex value, to help the model.
     *
     * @param string $hex Hex colour.
     * @return string Name, or '' when unknown.
     */
    private static function nearest_name($hex)
    {
        $anchors = array(
            'red'     => array(220, 38, 38),
            'orange'  => array(234, 88, 12),
            'amber'   => array(245, 158, 11),
            'yellow'  => array(234, 179, 8),
            'green'   => array(22, 163, 74),
            'teal'    => array(13, 148, 136),
            'cyan'    => array(6, 182, 212),
            'blue'    => array(37, 99, 235),
            'navy'    => array(30, 58, 138),
            'indigo'  => array(79, 70, 229),
            'purple'  => array(147, 51, 234),
            'magenta' => array(192, 38, 211),
            'pink'    => array(236, 72, 153),
            'brown'   => array(120, 72, 40),
            'black'   => array(17, 17, 17),
            'white'   => array(245, 245, 245),
            'gray'    => array(107, 114, 128),
        );

        list($r, $g, $b) = self::hex_to_rgb($hex, array(-1, -1, -1));
        if ($r < 0) {
            return '';
        }

        $best      = '';
        $best_dist = PHP_INT_MAX;
        foreach ($anchors as $name => $rgb) {
            $dist = ($r - $rgb[0]) ** 2 + ($g - $rgb[1]) ** 2 + ($b - $rgb[2]) ** 2;
            if ($dist < $best_dist) {
                $best_dist = $dist;
                $best      = $name;
            }
        }

        return $best;
    }
}
