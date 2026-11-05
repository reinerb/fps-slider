import EmblaCarousel, { type EmblaCarouselType } from 'embla-carousel';
import Autoplay from 'embla-carousel-autoplay';
import './main.css';

const AUTOPLAY_DELAY = 5000;

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

function init(root: HTMLElement): void {
  const viewport = root.querySelector<HTMLElement>('.fps-slider__viewport');
  const controls = root.querySelector<HTMLElement>('.fps-slider__controls');
  const toggle = root.querySelector<HTMLButtonElement>('.fps-slider__toggle');
  const prev = root.querySelector<HTMLButtonElement>('.fps-slider__prev');
  const next = root.querySelector<HTMLButtonElement>('.fps-slider__next');

  // Controls are only rendered when there's more than one slide.
  if (!viewport || !controls || !toggle || !prev || !next) {
    return;
  }

  // Hover/focus pausing is handled below rather than by the plugin: its
  // built-in listeners would resume playback after the user pressed pause.
  // stopOnInteraction: true just means "don't auto-resume after a drag".
  const autoplay = Autoplay({
    delay: AUTOPLAY_DELAY,
    playOnInit: false,
    stopOnInteraction: true,
    stopOnMouseEnter: false,
    stopOnFocusIn: false,
  });

  const embla = EmblaCarousel(viewport, { loop: true }, [autoplay]);
  const container = embla.containerNode();

  let paused = reduceMotion;
  let hovered = false;
  let focused = false;

  const update = (): void => {
    const playing = !paused && !hovered && !focused;

    if (playing) {
      autoplay.play();
    } else {
      autoplay.stop();
    }

    toggle.dataset.playing = String(!paused);
    toggle.setAttribute('aria-label', (paused ? toggle.dataset.labelPlay : toggle.dataset.labelPause) ?? '');

    // Announce slide changes only when the user is driving them.
    container.setAttribute('aria-live', playing ? 'off' : 'polite');
  };

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

  toggle.addEventListener('click', () => {
    paused = !paused;
    update();
  });

  prev.addEventListener('click', () => embla.scrollPrev(reduceMotion));
  next.addEventListener('click', () => embla.scrollNext(reduceMotion));

  // The plugin stops on pointerDown; resume after a drag if nothing else is pausing.
  embla.on('pointerUp', update);
  embla.on('select', syncSlides);

  syncSlides(embla);
  controls.hidden = false;
  update();
}

document.querySelectorAll<HTMLElement>('.fps-slider').forEach(init);
