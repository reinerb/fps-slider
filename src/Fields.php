<?php

namespace FpsSlider;

use Extended\ACF\Fields\Gallery;
use Extended\ACF\Fields\TrueFalse;
use Extended\ACF\Location;

/**
 * Per-page slider fields: the images, plus optional overrides of the
 * global settings. Shown on the pages chosen on the options page.
 */
final class Fields
{
    public const GALLERY   = 'fps_slider_images';
    public const CUSTOMIZE = 'fps_slider_customize';

    public function register(): void
    {
        // After Options (priority 10) has registered the options fields, so
        // the page list and global defaults below are read through ACF.
        add_action('acf/init', [$this, 'registerFieldGroup'], 20);
    }

    public function registerFieldGroup(): void
    {
        // Each Location is an OR group, so the fields show on any listed page.
        $locations = array_map(
            fn (int $id) => Location::where('page', (string) $id),
            Options::pageIds(),
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
                    ->helperText('Images shown by the [fps-slider] shortcode, in this order. Set alt text on each image in the media library.')
                    ->format('id')
                    ->previewSize('medium')
                    ->library('all'),
                TrueFalse::make('Customize settings', self::CUSTOMIZE)
                    ->helperText(sprintf(
                        'Override the <a href="%s">default slider settings</a> for this page.',
                        esc_url(Options::url()),
                    ))
                    ->stylized()
                    ->default(0),
                // Defaults to the global values, so switching on starts from them.
                ...Settings::global()->fields(self::CUSTOMIZE),
            ],
        ]);
    }
}
