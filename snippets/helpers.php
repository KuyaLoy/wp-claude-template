<?php
/**
 * Theme helpers
 *
 * Two small helpers — kept intentionally minimal because:
 * - WebP / image optimization is handled by an image plugin (ShortPixel, EWWW, Imagify, etc.)
 * - SVGs are uploaded via ACF (image field for files, textarea for inline paste)
 * - <img> and <picture> are written directly in templates with native HTML
 *
 * What's here:
 * 1. aiims_img() — output a static theme image as <img> with auto width/height + lazy
 * 2. aiims_svg_kses() — allowed-tag list for wp_kses() when rendering ACF textarea SVG
 */

/**
 * Output a static theme image as an <img> tag with auto width + height attributes.
 *
 * Use ONLY for static assets that ship with the theme (logo, decorative shapes, etc.).
 * For ACF images, write the <img> tag directly using the ACF array fields — see
 * theme/templates/parts/section-_example.php.
 *
 * Usage:
 *   <?php aiims_img('logo.svg', 'JG Vertical', 'h-8'); ?>
 *   <?php aiims_img('hero/banner.jpg', 'Rope access at work', 'w-full h-auto'); ?>
 *
 * Path is relative to assets/images/ in the theme directory.
 *
 * @param string $path   Path under assets/images/ (e.g. 'hero/banner.jpg')
 * @param string $alt    Alt text — empty string only for purely decorative images
 * @param string $class  Optional CSS classes
 */
function aiims_img($path, $alt = '', $class = '')
{
    $clean = ltrim($path, '/');
    $rel   = 'assets/images/' . $clean;
    $abs   = get_stylesheet_directory() . '/' . $rel;

    if (!file_exists($abs)) {
        // Try assets/icons as a fallback for SVG-named static icons
        $rel_icon = 'assets/icons/' . $clean;
        $abs_icon = get_stylesheet_directory() . '/' . $rel_icon;
        if (file_exists($abs_icon)) {
            $rel = $rel_icon;
            $abs = $abs_icon;
        } else {
            echo '<!-- aiims_img: not found: ' . esc_html($clean) . ' -->';
            return;
        }
    }

    $url = get_stylesheet_directory_uri() . '/' . $rel;
    [$w, $h] = aiims_image_dimensions($abs);

    $attrs = ' src="' . esc_url($url) . '"';
    $attrs .= ' alt="' . esc_attr($alt) . '"';
    if ($class)        $attrs .= ' class="' . esc_attr($class) . '"';
    if ($w !== null)   $attrs .= ' width="'  . (int) $w . '"';
    if ($h !== null)   $attrs .= ' height="' . (int) $h . '"';
    $attrs .= ' loading="lazy" decoding="async"';

    echo '<img' . $attrs . '>';
}

/**
 * Read width + height from a file on disk.
 *
 * Works for raster images (PNG/JPG/WebP/AVIF) via getimagesize().
 * Works for SVG by parsing width/height attributes or viewBox.
 *
 * Returns [width, height] or [null, null] if undetectable.
 */
function aiims_image_dimensions($path)
{
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

    if ($ext === 'svg') {
        $svg = @file_get_contents($path);
        if (!$svg) return [null, null];

        $w = $h = null;
        if (preg_match('/<svg[^>]*\swidth="([\d.]+)(px)?"/i',  $svg, $m)) $w = $m[1];
        if (preg_match('/<svg[^>]*\sheight="([\d.]+)(px)?"/i', $svg, $m)) $h = $m[1];

        if ((!$w || !$h) && preg_match('/<svg[^>]*\sviewBox="([\d.\s-]+)"/i', $svg, $m)) {
            $vb = preg_split('/\s+/', trim($m[1]));
            if (count($vb) === 4) {
                $w = $w ?: $vb[2];
                $h = $h ?: $vb[3];
            }
        }
        return [$w, $h];
    }

    $size = @getimagesize($path);
    if ($size && isset($size[0], $size[1])) {
        return [$size[0], $size[1]];
    }
    return [null, null];
}

/**
 * Allowed-tag list for wp_kses() when rendering an ACF textarea field that
 * holds raw SVG markup pasted by the editor.
 *
 * Usage in a section template:
 *
 *   $svg = get_sub_field('icon_svg');   // textarea — content editor pastes <svg>...</svg>
 *   if ($svg) {
 *       echo '<div class="w-12 h-12 text-primary">';
 *       echo wp_kses($svg, aiims_svg_kses());
 *       echo '</div>';
 *   }
 *
 * The SVG inherits text color via fill="currentColor" — ask content editors to
 * paste SVGs that use currentColor on the paths they want themed.
 */
function aiims_svg_kses()
{
    return [
        'svg' => [
            'xmlns'       => true,
            'viewbox'     => true,
            'fill'        => true,
            'stroke'      => true,
            'class'       => true,
            'width'       => true,
            'height'      => true,
            'aria-hidden' => true,
            'role'        => true,
            'focusable'   => true,
        ],
        'g' => [
            'fill'         => true,
            'stroke'       => true,
            'transform'    => true,
            'opacity'      => true,
            'clip-path'    => true,
        ],
        'path' => [
            'd'              => true,
            'fill'           => true,
            'stroke'         => true,
            'stroke-width'   => true,
            'stroke-linecap' => true,
            'stroke-linejoin'=> true,
            'fill-rule'      => true,
            'clip-rule'      => true,
            'transform'      => true,
            'opacity'        => true,
        ],
        'circle'  => ['cx'=>true,'cy'=>true,'r'=>true,'fill'=>true,'stroke'=>true,'stroke-width'=>true],
        'rect'    => ['x'=>true,'y'=>true,'width'=>true,'height'=>true,'fill'=>true,'stroke'=>true,'rx'=>true,'ry'=>true],
        'line'    => ['x1'=>true,'y1'=>true,'x2'=>true,'y2'=>true,'stroke'=>true,'stroke-width'=>true],
        'polyline'=> ['points'=>true,'fill'=>true,'stroke'=>true],
        'polygon' => ['points'=>true,'fill'=>true,'stroke'=>true],
        'ellipse' => ['cx'=>true,'cy'=>true,'rx'=>true,'ry'=>true,'fill'=>true,'stroke'=>true],
        'defs'    => [],
        'clippath'=> ['id'=>true],
        'lineargradient' => ['id'=>true,'x1'=>true,'y1'=>true,'x2'=>true,'y2'=>true,'gradientunits'=>true],
        'radialgradient' => ['id'=>true,'cx'=>true,'cy'=>true,'r'=>true,'gradientunits'=>true],
        'stop'    => ['offset'=>true,'stop-color'=>true,'stop-opacity'=>true],
        'use'     => ['href'=>true,'xlink:href'=>true],
        'title'   => [],
        'desc'    => [],
    ];
}

/* -----------------------------------------------------------
 *  Brand config — single source of truth for brand identity.
 *  Reads from brand.config.json at the workspace root (one level above
 *  the theme). Cached in a static var for the request lifetime so we
 *  don't repeatedly hit disk.
 *
 *  Usage:
 *    echo aiims_brand('contact.phone_display');     // dot notation
 *    echo aiims_brand('contact.email');
 *    echo aiims_brand('brand.name');
 *    $config = aiims_brand();                       // full array
 *
 *  Returns empty string for missing keys (safe to echo).
 * -------------------------------------------------------- */

function aiims_brand($key = null)
{
    static $config = null;

    if ($config === null) {
        // Look one level up from the theme directory (workspace root)
        $path = dirname(get_stylesheet_directory()) . '/brand.config.json';
        if (!file_exists($path)) {
            // Fallback: try theme directory itself
            $path = get_stylesheet_directory() . '/brand.config.json';
        }
        if (file_exists($path)) {
            $json   = file_get_contents($path);
            $config = json_decode($json, true) ?: [];
        } else {
            $config = [];
        }
    }

    if ($key === null) return $config;

    // Dot-notation lookup
    $parts = explode('.', $key);
    $value = $config;
    foreach ($parts as $part) {
        if (!is_array($value) || !array_key_exists($part, $value)) return '';
        $value = $value[$part];
    }
    return is_scalar($value) ? $value : '';
}

/* -----------------------------------------------------------
 *  SVG upload safety — allow + sanitize SVG file uploads in WP Admin.
 *  (Even if you mostly use textarea-paste for SVGs, content editors may
 *  prefer uploading SVG files via the Media Library / ACF image field.)
 * -------------------------------------------------------- */

add_filter('upload_mimes', function ($mimes) {
    $mimes['svg'] = 'image/svg+xml';
    return $mimes;
});

add_filter('wp_check_filetype_and_ext', function ($data, $file, $filename) {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    if ($ext === 'svg') {
        $data['ext']             = 'svg';
        $data['type']            = 'image/svg+xml';
        $data['proper_filename'] = $filename;
    }
    return $data;
}, 10, 3);

add_filter('wp_handle_upload_prefilter', function ($file) {
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($ext !== 'svg' || !current_user_can('upload_files')) return $file;

    $svg = file_get_contents($file['tmp_name']);
    $svg = preg_replace('/<script.*?<\/script>/is', '', $svg);
    $svg = preg_replace('/on\w+="[^"]*"/i', '', $svg);
    $svg = preg_replace("/<\?php.*?\?>/is", '', $svg);
    file_put_contents($file['tmp_name'], $svg);

    return $file;
});

add_action('admin_head', function () {
    echo '<style>.attachment .thumbnail img[src$=".svg"], .media-icon img[src$=".svg"]{width:100%!important;height:auto!important}</style>';
});
