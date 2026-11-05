<?php

namespace FpsSlider;

/**
 * [fps-slider] — renders the current page's gallery as an Embla carousel.
 *
 * Markup follows Embla's viewport > container > slide structure, plus the
 * W3C APG carousel pattern for accessibility. Without JS it degrades to the
 * first image (the viewport clips the rest) and the controls stay hidden.
 */
final class Shortcode
{
    public const TAG = 'fps-slider';

    public function register(): void
    {
        add_shortcode(self::TAG, [$this, 'render']);
    }

    public function render(): string
    {
        $images = $this->images();

        if ($images === []) {
            return '';
        }

        $total       = count($images);
        $containerId = wp_unique_id('fps-slider-');
        $slides      = '';

        foreach ($images as $index => $image) {
            $slides .= sprintf(
                '<div class="fps-slider__slide" role="group" aria-roledescription="slide" aria-label="%s">%s</div>',
                /* translators: 1: slide number, 2: total slides */
                esc_attr(sprintf(__('%1$d of %2$d', 'fps-slider'), $index + 1, $total)),
                $image,
            );
        }

        return sprintf(
            '<div class="fps-slider" role="region" aria-roledescription="carousel" aria-label="%s">'
                . '%s'
                . '<div class="fps-slider__viewport"><div class="fps-slider__container" id="%s" aria-live="off">%s</div></div>'
            . '</div>',
            esc_attr__('Image gallery', 'fps-slider'),
            $total > 1 ? $this->controls($containerId) : '',
            esc_attr($containerId),
            $slides,
        );
    }

    /**
     * Pause/play first, per APG, so it's the first thing keyboard users reach.
     * Hidden until the script initialises the carousel.
     */
    private function controls(string $containerId): string
    {
        $pause = __('Pause slideshow', 'fps-slider');
        $play  = __('Play slideshow', 'fps-slider');

        return sprintf(
            '<div class="fps-slider__controls" hidden>'
                . '<button type="button" class="fps-slider__button fps-slider__toggle" aria-controls="%1$s" aria-label="%2$s" data-label-pause="%2$s" data-label-play="%3$s" data-playing="true">%4$s%5$s</button>'
                . '<button type="button" class="fps-slider__button fps-slider__prev" aria-controls="%1$s" aria-label="%6$s">%7$s</button>'
                . '<button type="button" class="fps-slider__button fps-slider__next" aria-controls="%1$s" aria-label="%8$s">%9$s</button>'
            . '</div>',
            esc_attr($containerId),
            esc_attr($pause),
            esc_attr($play),
            $this->icon('M7 5h3v14H7zM14 5h3v14h-3z', 'pause'),
            $this->icon('M8 5v14l11-7z', 'play'),
            esc_attr__('Previous slide', 'fps-slider'),
            $this->icon('M15.4 7.4 14 6l-6 6 6 6 1.4-1.4-4.6-4.6z'),
            esc_attr__('Next slide', 'fps-slider'),
            $this->icon('M8.6 16.6 10 18l6-6-6-6-1.4 1.4 4.6 4.6z'),
        );
    }

    private function icon(string $path, string $modifier = ''): string
    {
        return sprintf(
            '<svg class="fps-slider__icon%s" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="%s"/></svg>',
            $modifier !== '' ? ' fps-slider__icon--' . $modifier : '',
            $path,
        );
    }

    /**
     * @return list<string> Rendered <img> tags.
     */
    private function images(): array
    {
        if (!function_exists('get_field')) {
            return [];
        }

        $ids    = array_filter(array_map('intval', (array) (get_field(Fields::GALLERY) ?: [])));
        $images = [];

        foreach (array_values($ids) as $index => $id) {
            $attributes = [
                'class'    => 'fps-slider__image',
                'decoding' => 'async',
            ];

            // Let core's loading heuristics handle the first image (it may get
            // fetchpriority="high" when above the fold); the rest are offscreen
            // until the carousel reaches them, and the script preloads neighbours.
            if ($index > 0) {
                $attributes['loading'] = 'lazy';
            }

            // 'full' + core's srcset/sizes lets the browser pick the right
            // rendition; lazy images also get sizes="auto".
            $image = wp_get_attachment_image($id, 'full', false, $attributes);

            if ($image !== '') {
                $images[] = $image;
            }
        }

        return $images;
    }
}
