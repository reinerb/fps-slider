<?php

namespace FpsSlider;

/**
 * [fps-slider] — renders the current page's gallery as an Embla carousel,
 * configured by its Settings.
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
        $postId = (int) (get_the_ID() ?: get_queried_object_id());
        $images = $this->images($postId);

        if ($images === []) {
            return '';
        }

        $settings    = Settings::forPost($postId);
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

        // Read by the script; data-autoplay is the delay in ms, absent when off.
        $attributes = [
            'class'                => 'fps-slider',
            'role'                 => 'region',
            'aria-roledescription' => 'carousel',
            'aria-label'           => __('Image gallery', 'fps-slider'),
            'style'                => '--fps-slider-aspect-ratio: ' . $settings->aspectRatio,
            'data-transition'      => $settings->transition,
            'data-autoplay'        => $settings->autoplay ? (string) $settings->delayMs() : null,
        ];

        return sprintf(
            '<div%s>%s<div class="fps-slider__viewport"><div class="fps-slider__container" id="%s" aria-live="off">%s</div></div></div>',
            $this->attributes($attributes),
            $total > 1 ? $this->controls($containerId, $settings) : '',
            esc_attr($containerId),
            $slides,
        );
    }

    /**
     * @param array<string, string|null> $attributes Null values are omitted.
     */
    private function attributes(array $attributes): string
    {
        $html = '';

        foreach ($attributes as $name => $value) {
            if ($value !== null) {
                $html .= sprintf(' %s="%s"', $name, esc_attr($value));
            }
        }

        return $html;
    }

    /**
     * Pause/play first, per APG, so it's the first thing keyboard users reach.
     * Hidden until the script initialises the carousel.
     */
    private function controls(string $containerId, Settings $settings): string
    {
        $buttons = '';
        $target  = esc_attr($containerId);

        if ($settings->autoplay) {
            $pause = esc_attr__('Pause slideshow', 'fps-slider');

            $buttons .= sprintf(
                '<button type="button" class="fps-slider__button fps-slider__toggle" aria-controls="%s" aria-label="%s" data-label-pause="%s" data-label-play="%s" data-playing="true">%s%s</button>',
                $target,
                $pause,
                $pause,
                esc_attr__('Play slideshow', 'fps-slider'),
                $this->icon('M7 5h3v14H7zM14 5h3v14h-3z', 'pause'),
                $this->icon('M8 5v14l11-7z', 'play'),
            );
        }

        if ($settings->arrows) {
            $buttons .= sprintf(
                '<button type="button" class="fps-slider__button fps-slider__prev" aria-controls="%1$s" aria-label="%2$s">%3$s</button>'
                    . '<button type="button" class="fps-slider__button fps-slider__next" aria-controls="%1$s" aria-label="%4$s">%5$s</button>',
                $target,
                esc_attr__('Previous slide', 'fps-slider'),
                $this->icon('M15.4 7.4 14 6l-6 6 6 6 1.4-1.4-4.6-4.6z'),
                esc_attr__('Next slide', 'fps-slider'),
                $this->icon('M8.6 16.6 10 18l6-6-6-6-1.4 1.4 4.6 4.6z'),
            );
        }

        return $buttons === '' ? '' : '<div class="fps-slider__controls" hidden>' . $buttons . '</div>';
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
    private function images(int $postId): array
    {
        if ($postId <= 0 || !function_exists('get_field')) {
            return [];
        }

        $ids    = array_filter(array_map('intval', (array) (get_field(Fields::GALLERY, $postId) ?: [])));
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
