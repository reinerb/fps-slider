import EmblaCarousel, { type EmblaCarouselType, type EmblaPluginType } from 'embla-carousel';
import Autoplay from 'embla-carousel-autoplay';
import Fade from 'embla-carousel-fade';
import './main.css';

const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

/**
 * Hides offscreen slides from assistive tech, and eager-loads the current
 * slide and its neighbours. Native lazy loading won't fetch images clipped by
 * the viewport until they scroll in, which would flash a blank mid-transition.
 */
function syncSlides(embla: EmblaCarouselType): void {
  const slides = embla.slideNodes();
  const count = slides.length;
  const current = embla.selectedScrollSnap();

  slides.forEach((slide, index) => {
    const offset = (index - current + count) % count;

    slide.setAttribute('aria-hidden', String(offset !== 0));

    if (offset <= 1 || offset === count - 1) {
      const image = slide.querySelector('img');

      if (image?.loading === 'lazy') {
        image.loading = 'eager';
      }
    }
  });
}

/**
 * Options come from the shortcode's data attributes:
 * - data-autoplay: delay in ms (absent when autoplay is off)
 * - data-transition: "slide" | "fade"
 */
function init(root: HTMLElement): void {
  const viewport = root.querySelector<HTMLElement>('.fps-slider__viewport');

  if (!viewport || viewport.querySelectorAll('.fps-slider__slide').length < 2) {
    return;
  }

  const delay = Number(root.dataset.autoplay) || 0;
  const plugins: EmblaPluginType[] = [];

  // Hover/focus pausing is handled below rather than by the plugin: its
  // built-in listeners would resume playback after the user pressed pause.
  // stopOnInteraction: true just means "don't auto-resume after a drag".
  const autoplay =
    delay > 0
      ? Autoplay({
          delay,
          playOnInit: false,
          stopOnInteraction: true,
          stopOnMouseEnter: false,
          stopOnFocusIn: false,
        })
      : null;

  if (autoplay) {
    plugins.push(autoplay);
  }

  if (root.dataset.transition === 'fade') {
    plugins.push(Fade());
  }

  const embla = EmblaCarousel(viewport, { loop: true }, plugins);
  const container = embla.containerNode();
  const toggle = root.querySelector<HTMLButtonElement>('.fps-slider__toggle');

  let paused = reduceMotion;
  let hovered = false;
  let focused = false;

  const update = (): void => {
    const playing = autoplay !== null && !paused && !hovered && !focused;

    if (playing) {
      autoplay.play();
    } else {
      autoplay?.stop();
    }

    if (toggle) {
      toggle.dataset.playing = String(!paused);
      toggle.setAttribute('aria-label', (paused ? toggle.dataset.labelPlay : toggle.dataset.labelPause) ?? '');
    }

    // Announce slide changes only when the user is driving them.
    container.setAttribute('aria-live', playing ? 'off' : 'polite');
  };

  if (autoplay) {
    root.addEventListener('mouseenter', () => {
      hovered = true;
      update();
    });

    root.addEventListener('mouseleave', () => {
      hovered = false;
      update();
    });

    root.addEventListener('focusin', () => {
      focused = true;
      update();
    });

    root.addEventListener('focusout', (event) => {
      focused = root.contains(event.relatedTarget as Node | null);
      update();
    });

    toggle?.addEventListener('click', () => {
      paused = !paused;
      update();
    });

    // The plugin stops on pointerDown; resume after a drag if nothing else is pausing.
    embla.on('pointerUp', update);
  }

  root.querySelector('.fps-slider__prev')?.addEventListener('click', () => embla.scrollPrev(reduceMotion));
  root.querySelector('.fps-slider__next')?.addEventListener('click', () => embla.scrollNext(reduceMotion));

  embla.on('select', syncSlides);
  syncSlides(embla);

  const controls = root.querySelector<HTMLElement>('.fps-slider__controls');

  if (controls) {
    controls.hidden = false;
  }

  update();
}

document.querySelectorAll<HTMLElement>('.fps-slider').forEach(init);
