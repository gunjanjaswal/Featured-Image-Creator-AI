<?php
/**
 * Image overlay class
 *
 * @package AI_Featured_Image_Generator
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Class AIFIG_Image_Overlay
 *
 * Burns a text headline and/or a logo onto a generated image. Uses Imagick
 * when available (best text quality) and falls back to GD with the bundled
 * Poppins font. All work is local — no API calls.
 */
class AIFIG_Image_Overlay
{

    /**
     * Whether overlay rendering is possible on this server.
     *
     * @return bool
     */
    public static function is_available()
    {
        if (extension_loaded('imagick') && class_exists('Imagick')) {
            return true;
        }
        return extension_loaded('gd') && function_exists('imagettftext');
    }

    /**
     * Bundled fonts directory (with trailing slash).
     *
     * @return string
     */
    public static function fonts_dir()
    {
        return AIFIG_PLUGIN_DIR . 'assets/fonts/';
    }

    /**
     * Resolve a font weight to a bundled TTF path.
     *
     * @param string $weight bold|semibold|regular.
     * @return string Absolute font path.
     */
    public static function font_path($weight)
    {
        $map = array(
            'bold'     => 'Poppins-Bold.ttf',
            'semibold' => 'Poppins-SemiBold.ttf',
            'regular'  => 'Poppins-Regular.ttf',
        );
        $file = isset($map[$weight]) ? $map[$weight] : $map['bold'];
        $path = self::fonts_dir() . $file;
        if (!file_exists($path)) {
            $path = self::fonts_dir() . 'Poppins-Bold.ttf';
        }
        return $path;
    }

    /**
     * Get overlay settings from options, resolving the logo attachment to a path.
     *
     * @return array
     */
    public static function get_options()
    {
        $logo_id   = absint(get_option('aifig_overlay_logo_id', 0));
        $logo_path = $logo_id ? get_attached_file($logo_id) : '';

        return array(
            'enabled'       => (bool) get_option('aifig_overlay_enabled', false),
            'text'          => '', // Caller supplies the resolved text (e.g. post title).
            'font_weight'   => get_option('aifig_overlay_font_weight', 'bold'),
            'font_scale'    => absint(get_option('aifig_overlay_font_scale', 7)), // % of image width.
            'max_lines'     => max(1, absint(get_option('aifig_overlay_max_lines', 3))),
            'text_color'    => get_option('aifig_overlay_text_color', '#ffffff'),
            'position'      => get_option('aifig_overlay_position', 'bottom'), // top|middle|bottom.
            'scrim'         => get_option('aifig_overlay_scrim', 'gradient'),  // none|dark|light|gradient.
            'logo_path'     => is_string($logo_path) ? $logo_path : '',
            'logo_position' => get_option('aifig_overlay_logo_position', 'top-right'),
            'logo_scale'    => absint(get_option('aifig_overlay_logo_scale', 14)), // % of image width.
        );
    }

    /**
     * Apply the overlay to an image file in place.
     *
     * @param string $file_path Absolute path to the image.
     * @param array  $args      Overrides merged over get_options(). Must include 'text'.
     * @return bool True if the file was modified.
     */
    public static function apply($file_path, $args = array())
    {
        if (!self::is_available() || !file_exists($file_path)) {
            return false;
        }

        $opts = wp_parse_args($args, self::get_options());

        if (empty($opts['enabled'])) {
            return false;
        }

        $text = isset($opts['text']) ? trim(wp_strip_all_tags($opts['text'])) : '';
        $logo = !empty($opts['logo_path']) && file_exists($opts['logo_path']) ? $opts['logo_path'] : '';

        // Nothing to draw.
        if ('' === $text && '' === $logo) {
            return false;
        }

        if (extension_loaded('imagick') && class_exists('Imagick')) {
            $done = self::apply_imagick($file_path, $opts, $text, $logo);
            if ($done) {
                return true;
            }
            // Fall through to GD if Imagick failed for any reason.
        }

        return self::apply_gd($file_path, $opts, $text, $logo);
    }

    /**
     * Convert a hex color to an RGB array.
     *
     * @param string $hex   Hex color (#rgb or #rrggbb).
     * @param array  $fallback RGB fallback.
     * @return array{0:int,1:int,2:int}
     */
    private static function hex_to_rgb($hex, $fallback = array(255, 255, 255))
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

    /* ------------------------------------------------------------------ */
    /* GD implementation                                                  */
    /* ------------------------------------------------------------------ */

    /**
     * Apply overlay using the GD extension.
     *
     * @param string $file_path Image path.
     * @param array  $opts      Resolved options.
     * @param string $text      Headline text.
     * @param string $logo      Logo path or ''.
     * @return bool
     */
    private static function apply_gd($file_path, $opts, $text, $logo)
    {
        $data = @file_get_contents($file_path);
        if (false === $data) {
            return false;
        }

        $img = @imagecreatefromstring($data);
        if (!$img) {
            return false;
        }

        if (function_exists('imagepalettetotruecolor')) {
            imagepalettetotruecolor($img);
        }
        imagealphablending($img, true);
        imagesavealpha($img, true);

        $w = imagesx($img);
        $h = imagesy($img);
        $pad = (int) round($w * 0.04);

        if ('' !== $text) {
            $font      = self::font_path($opts['font_weight']);
            $font_px   = max(12, (int) round($w * (max(2, (int) $opts['font_scale']) / 100)));
            $max_width = (int) round($w * 0.9);
            $lines     = self::wrap_text_gd($text, $font_px, $font, $max_width, (int) $opts['max_lines']);

            $line_height = (int) round($font_px * 1.32);
            $block_h     = $line_height * count($lines);

            // Vertical start of the text block.
            switch ($opts['position']) {
                case 'top':
                    $block_top = $pad;
                    break;
                case 'middle':
                    $block_top = (int) round(($h - $block_h) / 2);
                    break;
                case 'bottom':
                default:
                    $block_top = $h - $block_h - $pad;
                    break;
            }

            self::draw_scrim_gd($img, $w, $h, $block_top, $block_h, $pad, $opts);

            list($r, $g, $b) = self::hex_to_rgb($opts['text_color']);
            $text_color   = imagecolorallocate($img, $r, $g, $b);
            $shadow_color = imagecolorallocatealpha($img, 0, 0, 0, 75);

            $y = $block_top;
            foreach ($lines as $line) {
                $box        = imagettfbbox($font_px, 0, $font, $line);
                $line_width = abs($box[2] - $box[0]);
                $x          = (int) round(($w - $line_width) / 2);
                // Baseline: top of block + ascent (approx 0.82 of line height).
                $baseline = $y + (int) round($font_px * 0.92);

                imagettftext($img, $font_px, 0, $x + 2, $baseline + 2, $shadow_color, $font, $line);
                imagettftext($img, $font_px, 0, $x, $baseline, $text_color, $font, $line);

                $y += $line_height;
            }
        }

        if ('' !== $logo) {
            self::draw_logo_gd($img, $w, $h, $pad, $logo, $opts);
        }

        $ok = self::save_gd($img, $file_path);
        imagedestroy($img);
        return $ok;
    }

    /**
     * Word-wrap text for GD, capping line count with an ellipsis.
     *
     * @param string $text      Text.
     * @param int    $font_px   Font size.
     * @param string $font      Font path.
     * @param int    $max_width Max line width in px.
     * @param int    $max_lines Maximum lines.
     * @return string[]
     */
    private static function wrap_text_gd($text, $font_px, $font, $max_width, $max_lines)
    {
        $words   = preg_split('/\s+/', trim($text));
        $lines   = array();
        $current = '';

        foreach ($words as $word) {
            $try = '' === $current ? $word : $current . ' ' . $word;
            $box = imagettfbbox($font_px, 0, $font, $try);
            $width = abs($box[2] - $box[0]);
            if ($width > $max_width && '' !== $current) {
                $lines[] = $current;
                $current = $word;
            } else {
                $current = $try;
            }
        }
        if ('' !== $current) {
            $lines[] = $current;
        }

        if (count($lines) > $max_lines) {
            $lines    = array_slice($lines, 0, $max_lines);
            $last     = rtrim($lines[$max_lines - 1]) . '…';
            // Trim until it fits with the ellipsis.
            while ($last !== '…') {
                $box = imagettfbbox($font_px, 0, $font, $last);
                if (abs($box[2] - $box[0]) <= $max_width) {
                    break;
                }
                $last = preg_replace('/\s*\S\…$/u', '…', $last);
                if (null === $last) {
                    break;
                }
            }
            $lines[$max_lines - 1] = $last;
        }

        return $lines;
    }

    /**
     * Draw the readability scrim behind the text block (GD).
     *
     * @param resource|GdImage $img       Image.
     * @param int              $w         Width.
     * @param int              $h         Height.
     * @param int              $block_top Block top.
     * @param int              $block_h   Block height.
     * @param int              $pad       Padding.
     * @param array            $opts      Options.
     * @return void
     */
    private static function draw_scrim_gd($img, $w, $h, $block_top, $block_h, $pad, $opts)
    {
        $scrim = $opts['scrim'];
        if ('none' === $scrim) {
            return;
        }

        if ('gradient' === $scrim) {
            // Fade from transparent to dark toward the edge the text sits on.
            $bottom_anchored = ('bottom' === $opts['position'] || 'middle' === $opts['position']);
            if ($bottom_anchored) {
                $y_start = max(0, $block_top - $pad * 2);
                $y_end   = $h;
            } else {
                $y_start = 0;
                $y_end   = min($h, $block_top + $block_h + $pad * 2);
            }
            $span = max(1, $y_end - $y_start);
            for ($y = $y_start; $y < $y_end; $y++) {
                $t = ($y - $y_start) / $span; // 0..1
                if (!$bottom_anchored) {
                    $t = 1 - $t;
                }
                $alpha = (int) round(127 - ($t * $t * 95)); // up to ~75% opacity black.
                $col   = imagecolorallocatealpha($img, 0, 0, 0, max(0, min(127, $alpha)));
                imageline($img, 0, $y, $w, $y, $col);
            }
            return;
        }

        // Solid translucent box behind the block.
        $box_top    = max(0, $block_top - $pad);
        $box_bottom = min($h, $block_top + $block_h + $pad);
        if ('light' === $scrim) {
            $col = imagecolorallocatealpha($img, 255, 255, 255, 70);
        } else { // dark
            $col = imagecolorallocatealpha($img, 0, 0, 0, 60);
        }
        imagefilledrectangle($img, 0, $box_top, $w, $box_bottom, $col);
    }

    /**
     * Composite the logo (GD).
     *
     * @param resource|GdImage $img  Image.
     * @param int              $w    Width.
     * @param int              $h    Height.
     * @param int              $pad  Padding.
     * @param string           $logo Logo path.
     * @param array            $opts Options.
     * @return void
     */
    private static function draw_logo_gd($img, $w, $h, $pad, $logo, $opts)
    {
        $data = @file_get_contents($logo);
        if (false === $data) {
            return;
        }
        $logo_img = @imagecreatefromstring($data);
        if (!$logo_img) {
            return;
        }
        imagealphablending($logo_img, true);
        imagesavealpha($logo_img, true);

        $lw = imagesx($logo_img);
        $lh = imagesy($logo_img);
        if ($lw < 1 || $lh < 1) {
            imagedestroy($logo_img);
            return;
        }

        $target_w = max(1, (int) round($w * (max(2, (int) $opts['logo_scale']) / 100)));
        $target_h = (int) round($lh * ($target_w / $lw));

        list($dx, $dy) = self::corner_xy($opts['logo_position'], $w, $h, $target_w, $target_h, $pad);

        imagecopyresampled($img, $logo_img, $dx, $dy, 0, 0, $target_w, $target_h, $lw, $lh);
        imagedestroy($logo_img);
    }

    /**
     * Compute the top-left position for a corner placement.
     *
     * @param string $position Corner key.
     * @param int    $w        Canvas width.
     * @param int    $h        Canvas height.
     * @param int    $iw       Item width.
     * @param int    $ih       Item height.
     * @param int    $pad      Padding.
     * @return array{0:int,1:int}
     */
    private static function corner_xy($position, $w, $h, $iw, $ih, $pad)
    {
        switch ($position) {
            case 'top-left':
                return array($pad, $pad);
            case 'bottom-left':
                return array($pad, $h - $ih - $pad);
            case 'bottom-right':
                return array($w - $iw - $pad, $h - $ih - $pad);
            case 'top-right':
            default:
                return array($w - $iw - $pad, $pad);
        }
    }

    /**
     * Save a GD image back to its original format.
     *
     * @param resource|GdImage $img       Image.
     * @param string           $file_path Path (extension drives format).
     * @return bool
     */
    private static function save_gd($img, $file_path)
    {
        $ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
        switch ($ext) {
            case 'jpg':
            case 'jpeg':
                return imagejpeg($img, $file_path, 90);
            case 'webp':
                if (function_exists('imagewebp')) {
                    return imagewebp($img, $file_path, 90);
                }
                return imagepng($img, $file_path);
            case 'png':
            default:
                return imagepng($img, $file_path);
        }
    }

    /* ------------------------------------------------------------------ */
    /* Imagick implementation                                             */
    /* ------------------------------------------------------------------ */

    /**
     * Apply overlay using the Imagick extension.
     *
     * @param string $file_path Image path.
     * @param array  $opts      Resolved options.
     * @param string $text      Headline text.
     * @param string $logo      Logo path or ''.
     * @return bool
     */
    private static function apply_imagick($file_path, $opts, $text, $logo)
    {
        try {
            $img = new Imagick($file_path);
            $img->setImageColorspace(Imagick::COLORSPACE_SRGB);
            $w = $img->getImageWidth();
            $h = $img->getImageHeight();
            $pad = (int) round($w * 0.04);

            if ('' !== $text) {
                $font    = self::font_path($opts['font_weight']);
                $font_px = max(12, (int) round($w * (max(2, (int) $opts['font_scale']) / 100)));

                $draw = new ImagickDraw();
                $draw->setFont($font);
                $draw->setFontSize($font_px);

                $max_width = (int) round($w * 0.9);
                $lines     = self::wrap_text_imagick($img, $draw, $text, $max_width, (int) $opts['max_lines']);

                $metrics     = $img->queryFontMetrics($draw, 'Ag');
                $line_height = (int) round($metrics['textHeight'] * 1.18);
                $block_h     = $line_height * count($lines);

                switch ($opts['position']) {
                    case 'top':
                        $block_top = $pad;
                        break;
                    case 'middle':
                        $block_top = (int) round(($h - $block_h) / 2);
                        break;
                    case 'bottom':
                    default:
                        $block_top = $h - $block_h - $pad;
                        break;
                }

                self::draw_scrim_imagick($img, $w, $h, $block_top, $block_h, $pad, $opts);

                // Shadow then text, centered.
                $shadow = new ImagickDraw();
                $shadow->setFont($font);
                $shadow->setFontSize($font_px);
                $shadow->setFillColor(new ImagickPixel('rgba(0,0,0,0.55)'));
                $shadow->setTextAlignment(Imagick::ALIGN_CENTER);

                $draw->setTextAlignment(Imagick::ALIGN_CENTER);
                $draw->setFillColor(new ImagickPixel($opts['text_color']));

                $cx = (int) round($w / 2);
                $y  = $block_top + (int) round($metrics['ascender']);
                foreach ($lines as $line) {
                    $img->annotateImage($shadow, $cx + 2, $y + 2, 0, $line);
                    $img->annotateImage($draw, $cx, $y, 0, $line);
                    $y += $line_height;
                }
            }

            if ('' !== $logo) {
                self::draw_logo_imagick($img, $w, $h, $pad, $logo, $opts);
            }

            $result = $img->writeImage($file_path);
            $img->clear();
            $img->destroy();
            return (bool) $result;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Word-wrap for Imagick using font metrics.
     *
     * @param Imagick     $img       Image.
     * @param ImagickDraw $draw      Draw settings.
     * @param string      $text      Text.
     * @param int         $max_width Max width px.
     * @param int         $max_lines Max lines.
     * @return string[]
     */
    private static function wrap_text_imagick($img, $draw, $text, $max_width, $max_lines)
    {
        $words   = preg_split('/\s+/', trim($text));
        $lines   = array();
        $current = '';

        foreach ($words as $word) {
            $try     = '' === $current ? $word : $current . ' ' . $word;
            $metrics = $img->queryFontMetrics($draw, $try);
            if ($metrics['textWidth'] > $max_width && '' !== $current) {
                $lines[] = $current;
                $current = $word;
            } else {
                $current = $try;
            }
        }
        if ('' !== $current) {
            $lines[] = $current;
        }

        if (count($lines) > $max_lines) {
            $lines = array_slice($lines, 0, $max_lines);
            $lines[$max_lines - 1] = rtrim($lines[$max_lines - 1]) . '…';
        }

        return $lines;
    }

    /**
     * Draw the readability scrim (Imagick).
     *
     * @param Imagick $img       Image.
     * @param int     $w         Width.
     * @param int     $h         Height.
     * @param int     $block_top Block top.
     * @param int     $block_h   Block height.
     * @param int     $pad       Padding.
     * @param array   $opts      Options.
     * @return void
     */
    private static function draw_scrim_imagick($img, $w, $h, $block_top, $block_h, $pad, $opts)
    {
        $scrim = $opts['scrim'];
        if ('none' === $scrim) {
            return;
        }

        if ('gradient' === $scrim) {
            $bottom_anchored = ('bottom' === $opts['position'] || 'middle' === $opts['position']);
            if ($bottom_anchored) {
                $y_start = max(0, $block_top - $pad * 2);
                $y_end   = $h;
            } else {
                $y_start = 0;
                $y_end   = min($h, $block_top + $block_h + $pad * 2);
            }
            $band_h = max(1, $y_end - $y_start);

            $gradient = new Imagick();
            $start    = $bottom_anchored ? 'rgba(0,0,0,0)' : 'rgba(0,0,0,0.75)';
            $end      = $bottom_anchored ? 'rgba(0,0,0,0.75)' : 'rgba(0,0,0,0)';
            $gradient->newPseudoImage($w, $band_h, "gradient:{$start}-{$end}");
            $img->compositeImage($gradient, Imagick::COMPOSITE_OVER, 0, $y_start);
            $gradient->destroy();
            return;
        }

        $draw       = new ImagickDraw();
        $box_top     = max(0, $block_top - $pad);
        $box_bottom  = min($h, $block_top + $block_h + $pad);
        $fill        = ('light' === $scrim) ? 'rgba(255,255,255,0.45)' : 'rgba(0,0,0,0.5)';
        $draw->setFillColor(new ImagickPixel($fill));
        $draw->rectangle(0, $box_top, $w, $box_bottom);
        $img->drawImage($draw);
        $draw->destroy();
    }

    /**
     * Composite the logo (Imagick).
     *
     * @param Imagick $img  Image.
     * @param int     $w    Width.
     * @param int     $h    Height.
     * @param int     $pad  Padding.
     * @param string  $logo Logo path.
     * @param array   $opts Options.
     * @return void
     */
    private static function draw_logo_imagick($img, $w, $h, $pad, $logo, $opts)
    {
        try {
            $logo_img = new Imagick($logo);
            $lw = $logo_img->getImageWidth();
            $lh = $logo_img->getImageHeight();
            if ($lw < 1 || $lh < 1) {
                $logo_img->destroy();
                return;
            }
            $target_w = max(1, (int) round($w * (max(2, (int) $opts['logo_scale']) / 100)));
            $target_h = (int) round($lh * ($target_w / $lw));
            $logo_img->resizeImage($target_w, $target_h, Imagick::FILTER_LANCZOS, 1);

            list($dx, $dy) = self::corner_xy($opts['logo_position'], $w, $h, $target_w, $target_h, $pad);
            $img->compositeImage($logo_img, Imagick::COMPOSITE_OVER, $dx, $dy);
            $logo_img->destroy();
        } catch (Exception $e) {
            return;
        }
    }
}
