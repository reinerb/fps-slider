<?php

namespace FpsSlider;

defined('ABSPATH') || exit;

use Extended\ACF\ConditionalLogic;
use Extended\ACF\Fields\ButtonGroup;
use Extended\ACF\Fields\Field;
use Extended\ACF\Fields\Number;
use Extended\ACF\Fields\Select;
use Extended\ACF\Fields\TrueFalse;

/**
 * A slider's display settings.
 *
 * Resolved as: built-in defaults < options page < per-page overrides (only
 * when the page has "Customize settings" switched on). The same field
 * definitions are used on the options page and on each page.
 */
final class Settings
{
    public const AUTOPLAY     = 'fps_slider_autoplay';
    public const DELAY        = 'fps_slider_delay';
    public const TRANSITION   = 'fps_slider_transition';
    public const ASPECT_RATIO = 'fps_slider_aspect_ratio';
    public const ARROWS       = 'fps_slider_arrows';

    private const TRANSITIONS = [
        'slide' => 'Slide',
        'fade'  => 'Fade',
    ];

    /** Fill the container's height instead of using a fixed ratio. */
    public const FILL = 'fill';

    /** Used for FILL where the container has no height to fill. */
    private const FILL_FALLBACK_RATIO = '16 / 9';

    /** Values other than FILL are output as the CSS aspect-ratio, so keep them CSS-valid. */
    private const ASPECT_RATIOS = [
        '21 / 9'   => '21:9 — panoramic',
        '16 / 9'   => '16:9 — widescreen',
        '3 / 2'    => '3:2 — classic photo',
        '4 / 3'    => '4:3',
        '1 / 1'    => '1:1 — square',
        self::FILL => 'Fill container',
    ];

    private const MIN_DELAY = 2.0;
    private const MAX_DELAY = 30.0;

    public function __construct(
        public readonly bool $autoplay = true,
        public readonly float $delay = 5.0, // seconds
        public readonly string $transition = 'slide',
        public readonly string $aspectRatio = '16 / 9',
        public readonly bool $arrows = true,
    ) {
    }

    /**
     * Defaults from the options page.
     */
    public static function global(): self
    {
        return self::load('option', new self());
    }

    /**
     * The page's own settings if it customizes them, otherwise the global ones.
     */
    public static function forPost(int $postId): self
    {
        $global = self::global();

        if ($postId > 0 && get_field(Fields::CUSTOMIZE, $postId)) {
            return self::load($postId, $global);
        }

        return $global;
    }

    public function fillsContainer(): bool
    {
        return $this->aspectRatio === self::FILL;
    }

    /**
     * The CSS aspect-ratio: the chosen ratio, or the fallback when filling.
     */
    public function cssAspectRatio(): string
    {
        return $this->fillsContainer() ? self::FILL_FALLBACK_RATIO : $this->aspectRatio;
    }

    public function delayMs(): int
    {
        return (int) round($this->delay * 1000);
    }

    /**
     * ACF fields for these settings, defaulting to this instance's values.
     *
     * @param string|null $toggle Name of a true/false field that must be on for
     *                            these fields to show (per-page overrides).
     * @return list<Field>
     */
    public function fields(?string $toggle = null): array
    {
        $base = $toggle !== null ? [$toggle => '1'] : [];

        return [
            TrueFalse::make('Autoplay', self::AUTOPLAY)
                ->helperText('Advance slides automatically. Visitors can always pause, and it starts paused for those who prefer reduced motion.')
                ->stylized()
                ->default($this->autoplay ? 1 : 0)
                ->conditionalLogic(self::when($base))
                ->column(50),
            Number::make('Time per slide', self::DELAY)
                ->suffix('seconds')
                ->min(self::MIN_DELAY)
                ->max(self::MAX_DELAY)
                ->step(0.5)
                ->default($this->delay)
                ->conditionalLogic(self::when($base + [self::AUTOPLAY => '1']))
                ->column(50),
            ButtonGroup::make('Transition', self::TRANSITION)
                ->choices(self::TRANSITIONS)
                ->default($this->transition)
                ->conditionalLogic(self::when($base))
                ->column(50),
            Select::make('Aspect ratio', self::ASPECT_RATIO)
                ->helperText('Images are cropped to fill this shape. “Fill container” matches the height of the column the slider sits in, e.g. beside text in a two-column layout. Where there’s no height to match, such as when columns stack on mobile, it uses 16:9.')
                ->choices(self::ASPECT_RATIOS)
                ->default($this->aspectRatio)
                ->conditionalLogic(self::when($base))
                ->column(50),
            TrueFalse::make('Previous/next buttons', self::ARROWS)
                ->stylized()
                ->default($this->arrows ? 1 : 0)
                ->conditionalLogic(self::when($base)),
        ];
    }

    private static function load(int|string $source, self $fallback): self
    {
        $transition = get_field(self::TRANSITION, $source);
        $ratio      = get_field(self::ASPECT_RATIO, $source);
        $delay      = get_field(self::DELAY, $source);

        return new self(
            autoplay: self::bool(get_field(self::AUTOPLAY, $source), $fallback->autoplay),
            delay: is_numeric($delay)
                ? min(self::MAX_DELAY, max(self::MIN_DELAY, (float) $delay))
                : $fallback->delay,
            transition: is_string($transition) && isset(self::TRANSITIONS[$transition])
                ? $transition
                : $fallback->transition,
            aspectRatio: is_string($ratio) && isset(self::ASPECT_RATIOS[$ratio])
                ? $ratio
                : $fallback->aspectRatio,
            arrows: self::bool(get_field(self::ARROWS, $source), $fallback->arrows),
        );
    }

    private static function bool(mixed $value, bool $fallback): bool
    {
        return $value === null || $value === '' ? $fallback : (bool) $value;
    }

    /**
     * @param array<string, string> $rules field name => required value, ANDed
     * @return list<ConditionalLogic>
     */
    private static function when(array $rules): array
    {
        $logic = null;

        foreach ($rules as $name => $value) {
            $logic = $logic === null
                ? ConditionalLogic::where($name, '==', $value)
                : $logic->and($name, '==', $value);
        }

        return $logic === null ? [] : [$logic];
    }
}
