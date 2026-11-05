<?php

namespace FpsSlider;

use Extended\ACF\Fields\Gallery;
use Extended\ACF\Location;

/**
 * ACF field group holding the slider's images.
 */
final class Fields
{
    public const GALLERY = 'fps_slider_images';

    /**
     * Temporary: pages the field group appears on, until the options page
     * provides this selection.
     */
    private const PAGE_SLUGS = ['slider-test'];

    public function register(): void
    {
        add_action('acf/init', [$this, 'registerFieldGroup']);
    }

    public function registerFieldGroup(): void
    {
        // Each Location is an OR group, so the fields show on any listed page.
        $locations = array_map(
            fn (int $id) => Location::where('page', (string) $id),
            $this->pageIds(),
        );

        if ($locations === []) {
            return;
        }

        register_extended_field_group([
            'title'    => 'FPS Slider',
            'style'    => 'default',
            'location' => $locations,
            'fields'   => [
                Gallery::make('Slider images', self::GALLERY)
                    ->helperText('Images shown by the [fps-slider] shortcode, in this order.')
                    ->format('id')
                    ->previewSize('medium')
                    ->library('all'),
            ],
        ]);
    }

    /**
     * @return int[]
     */
    private function pageIds(): array
    {
        $ids = [];

        foreach (self::PAGE_SLUGS as $slug) {
            $page = get_page_by_path($slug);

            if ($page !== null) {
                $ids[] = $page->ID;
            }
        }

        return $ids;
    }
}
