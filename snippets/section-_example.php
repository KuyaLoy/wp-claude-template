<?php
/**
 * Section: _example  (REFERENCE — not included anywhere)
 *
 * Demonstrates every pattern used in real section parts:
 * - Native <img> for ACF images (with width/height for CLS)
 * - Native <picture> for ACF image + mobile companion
 * - aiims_img() for static theme images (auto width/height)
 * - ACF textarea + wp_kses() for inline SVGs that need theming via currentColor
 * - ACF image (file) for SVGs/icons that don't need theming
 * - Conditional guards on every output
 * - Output escaping
 * - Tailwind utility patterns
 * - data-reveal animations
 *
 * Copy/adapt as needed. Don't include this file from any template — it's docs only.
 */

/* -----------------------------------------------------------
 *  ACF reads — Phase 2 (dynamic)
 *  Inside Flexible Content layout, use get_sub_field().
 *  Inside a homepage section group, use get_field().
 * -------------------------------------------------------- */

$heading      = get_sub_field('heading');           // text
$subheading   = get_sub_field('subheading');        // textarea / text
$body         = get_sub_field('body');              // wysiwyg
$image        = get_sub_field('image');             // image (return: array)
$mobile_image = get_sub_field('mobile_image');      // image (return: array) — optional companion
$icon_svg     = get_sub_field('icon_svg');          // textarea — editor pastes raw <svg>...
$logo         = get_sub_field('logo');              // image (return: array) — SVG file upload
$cta          = get_sub_field('cta');               // link (return: array)
$variant      = get_sub_field('variant');           // select / radio (return: value)

if ( ! $heading && ! $body && ! $image && ! $cta ) {
    return;
}

$is_dark = $variant === 'dark';
?>

<section
    id="example"
    class="<?= $is_dark ? 'bg-secondary text-white' : 'bg-white' ?>
           py-12 sm:py-16 md:py-20 lg:py-24 xl:py-[120px] 2xl:py-[150px]"
>
    <div class="max-w-[1320px] mx-auto px-4 sm:px-6 lg:px-8 xl:px-12">

        <?php if ($subheading) : ?>
            <p
                class="font-brand text-sm uppercase tracking-widest <?= $is_dark ? 'text-accent' : 'text-primary' ?>"
                data-reveal
            >
                <?= esc_html($subheading) ?>
            </p>
        <?php endif; ?>

        <?php if ($heading) : ?>
            <h2
                class="mt-2 font-brand font-semibold text-3xl md:text-4xl lg:text-5xl <?= $is_dark ? 'text-white' : 'text-secondary' ?>"
                data-reveal
            >
                <?= esc_html($heading) ?>
            </h2>
        <?php endif; ?>

        <?php if ($body) : ?>
            <div
                class="mt-4 text-base md:text-lg <?= $is_dark ? 'text-white/80' : 'text-text' ?>
                       prose max-w-none"
                data-reveal
            >
                <?= wp_kses_post($body) ?>
            </div>
        <?php endif; ?>

        <?php /* ----------------------------------------------------------- */ ?>
        <?php /*  Pattern 1: ACF image — single image, no mobile companion   */ ?>
        <?php /*  Native <img> with width/height from ACF array.             */ ?>
        <?php /*  Image plugin (ShortPixel/EWWW/Imagify) auto-converts WebP. */ ?>
        <?php /* ----------------------------------------------------------- */ ?>
        <?php if ($image && empty($mobile_image)) : ?>
            <div class="mt-12" data-reveal="zoom">
                <img
                    src="<?= esc_url($image['url']) ?>"
                    alt="<?= esc_attr($image['alt'] ?: $heading ?: '') ?>"
                    width="<?= (int) $image['width'] ?>"
                    height="<?= (int) $image['height'] ?>"
                    class="w-full h-auto rounded-lg"
                    loading="lazy"
                    decoding="async"
                >
            </div>
        <?php endif; ?>

        <?php /* ----------------------------------------------------------- */ ?>
        <?php /*  Pattern 2: ACF image + mobile companion                    */ ?>
        <?php /*  <picture> with <source> for mobile breakpoint.             */ ?>
        <?php /* ----------------------------------------------------------- */ ?>
        <?php if ($image && $mobile_image) : ?>
            <div class="mt-12" data-reveal="zoom">
                <picture>
                    <source
                        srcset="<?= esc_url($mobile_image['url']) ?>"
                        media="(max-width: 767px)"
                        width="<?= (int) $mobile_image['width'] ?>"
                        height="<?= (int) $mobile_image['height'] ?>"
                    >
                    <img
                        src="<?= esc_url($image['url']) ?>"
                        alt="<?= esc_attr($image['alt'] ?: $heading ?: '') ?>"
                        width="<?= (int) $image['width'] ?>"
                        height="<?= (int) $image['height'] ?>"
                        class="w-full h-auto rounded-lg"
                        loading="lazy"
                        decoding="async"
                    >
                </picture>
            </div>
        <?php endif; ?>

        <?php /* ----------------------------------------------------------- */ ?>
        <?php /*  Pattern 3: ACF textarea SVG — editor pastes raw <svg>...   */ ?>
        <?php /*  Render with wp_kses() so the SVG can use currentColor      */ ?>
        <?php /*  and be themed by parent text-* class.                      */ ?>
        <?php /* ----------------------------------------------------------- */ ?>
        <?php if ($icon_svg) : ?>
            <div class="mt-8 w-12 h-12 <?= $is_dark ? 'text-accent' : 'text-primary' ?>" data-reveal>
                <?= wp_kses($icon_svg, aiims_svg_kses()) ?>
            </div>
        <?php endif; ?>

        <?php /* ----------------------------------------------------------- */ ?>
        <?php /*  Pattern 4: ACF image field with SVG file uploaded          */ ?>
        <?php /*  Use when SVG doesn't need theming (e.g. brand logo).       */ ?>
        <?php /* ----------------------------------------------------------- */ ?>
        <?php if ($logo) : ?>
            <div class="mt-8" data-reveal>
                <img
                    src="<?= esc_url($logo['url']) ?>"
                    alt="<?= esc_attr($logo['alt'] ?: '') ?>"
                    width="<?= (int) ($logo['width']  ?: 200) ?>"
                    height="<?= (int) ($logo['height'] ?: 60) ?>"
                    class="h-12 w-auto"
                    loading="lazy"
                    decoding="async"
                >
            </div>
        <?php endif; ?>

        <?php /* ----------------------------------------------------------- */ ?>
        <?php /*  Pattern 5: Static theme image — uses aiims_img() for       */ ?>
        <?php /*  auto width/height (the only place we still need a helper). */ ?>
        <?php /*  Path is relative to assets/images/ in the theme directory. */ ?>
        <?php /*  Only used in static phase or for theme-wide assets like    */ ?>
        <?php /*  the logo / footer mark / decorative shapes that aren't     */ ?>
        <?php /*  per-page editable.                                          */ ?>
        <?php /* ----------------------------------------------------------- */ ?>
        <div class="mt-8" data-reveal>
            <?php aiims_img('decorative/shape.svg', '', 'h-16 w-auto opacity-20'); ?>
        </div>

        <?php if ($cta) : ?>
            <a
                href="<?= esc_url($cta['url']) ?>"
                target="<?= esc_attr($cta['target'] ?: '_self') ?>"
                class="mt-8 inline-flex items-center gap-2 bg-primary text-white px-6 py-3 hover:bg-primary/90 transition-colors"
                data-reveal
            >
                <?= esc_html($cta['title']) ?>
            </a>
        <?php endif; ?>

        <?php /* ----------------------------------------------------------- */ ?>
        <?php /*  Repeater example (cards grid) — use have_rows() / the_row()*/ ?>
        <?php /*  Each card has its own image + content + CTA.                */ ?>
        <?php /* ----------------------------------------------------------- */ ?>
        <?php if (have_rows('cards')) : ?>
            <div class="mt-16 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 lg:gap-8" data-reveal-stagger>
                <?php while (have_rows('cards')) : the_row();
                    $card_image = get_sub_field('image');
                    $card_title = get_sub_field('title');
                    $card_desc  = get_sub_field('description');
                    $card_link  = get_sub_field('link');
                ?>
                    <article class="bg-white border border-text/10 p-6 rounded-lg" data-reveal>
                        <?php if ($card_image) : ?>
                            <div class="aspect-[4/3] overflow-hidden rounded">
                                <img
                                    src="<?= esc_url($card_image['url']) ?>"
                                    alt="<?= esc_attr($card_image['alt'] ?: $card_title ?: '') ?>"
                                    width="<?= (int) $card_image['width'] ?>"
                                    height="<?= (int) $card_image['height'] ?>"
                                    class="w-full h-full object-cover"
                                    loading="lazy"
                                    decoding="async"
                                >
                            </div>
                        <?php endif; ?>

                        <?php if ($card_title) : ?>
                            <h3 class="mt-4 font-brand text-xl font-semibold text-secondary">
                                <?= esc_html($card_title) ?>
                            </h3>
                        <?php endif; ?>

                        <?php if ($card_desc) : ?>
                            <p class="mt-2 text-text">
                                <?= esc_html($card_desc) ?>
                            </p>
                        <?php endif; ?>

                        <?php if ($card_link) : ?>
                            <a
                                href="<?= esc_url($card_link['url']) ?>"
                                target="<?= esc_attr($card_link['target'] ?: '_self') ?>"
                                class="mt-4 inline-flex items-center text-accent hover:text-primary"
                            >
                                <?= esc_html($card_link['title']) ?>
                            </a>
                        <?php endif; ?>
                    </article>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>

    </div>
</section>

<?php
/* -----------------------------------------------------------
 *  Static phase reference (Phase 1 — pre-ACF):
 *
 *  Same markup but all values hard-coded:
 *
 *    <h2 class="...">Hard-coded heading from Figma</h2>
 *    <p class="...">Hard-coded body copy.</p>
 *    <?php aiims_img('home-hero/banner.jpg', 'Alt text', 'w-full h-auto'); ?>
 *
 *  No conditional guards needed — content is guaranteed present.
 *  No get_field() / get_sub_field() — that's Phase 2.
 *
 *  Once user approves the static render against Figma:
 *  "Make <section> dynamic" → /make-dynamic <section>
 *  to convert this file to the dynamic version above.
 * -------------------------------------------------------- */
