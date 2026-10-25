(function () {
    'use strict';

    // The server renders each Slide as a lightbox link (<a data-lightbox><img></a>)
    // inside <image-slider>, so the images and the lightbox work without this
    // script. The component adds a scroll-snap track (native swiping) and, for
    // more than one Slide, prev/next buttons and arrow keys.

    const CONFIG = (typeof window.SLIDER_CONFIG === 'object' && window.SLIDER_CONFIG) || {};
    const MESSAGES = Object.assign({ previous: 'Previous', next: 'Next' }, CONFIG.messages || {});

    class ImageSlider extends HTMLElement {
        connectedCallback() {
            if (this.track) {
                return;
            }

            const slides = Array.from(this.querySelectorAll(':scope > a.image-slider-slide'));

            if (!slides.length) {
                this.hidden = true;
                return;
            }

            this.track = document.createElement('div');
            this.track.className = 'image-slider-track';
            slides.forEach((slide) => this.track.appendChild(slide));
            this.appendChild(this.track);
            this.index = 0;

            if (slides.length === 1) {
                this.classList.add('image-slider--single');
                return;
            }

            this.prevButton = this.addButton('image-slider-prev', MESSAGES.previous, () => this.goTo(this.index - 1));
            this.nextButton = this.addButton('image-slider-next', MESSAGES.next, () => this.goTo(this.index + 1));
            this.setAttribute('tabindex', '0');

            this.addEventListener('keydown', (e) => {
                if (e.key === 'ArrowLeft') {
                    e.preventDefault();
                    this.goTo(this.index - 1);
                } else if (e.key === 'ArrowRight') {
                    e.preventDefault();
                    this.goTo(this.index + 1);
                }
            });

            // Follow swipes: the Slide snapped into view becomes the current one.
            this.track.addEventListener('scroll', () => {
                const width = this.track.clientWidth;

                if (width) {
                    this.setCurrent(Math.round(this.track.scrollLeft / width));
                }
            }, { passive: true });

            this.setCurrent(0);
        }

        addButton(className, label, onClick) {
            const button = document.createElement('button');

            button.type = 'button';
            button.className = 'image-slider-button ' + className;
            button.setAttribute('aria-label', label);
            button.title = label;
            button.addEventListener('click', onClick);
            this.appendChild(button);

            return button;
        }

        goTo(index) {
            const count = this.track.children.length;
            const target = Math.max(0, Math.min(count - 1, index));

            if (typeof this.track.scrollTo === 'function') {
                this.track.scrollTo({ left: target * this.track.clientWidth, behavior: 'smooth' });
            }

            this.setCurrent(target);
        }

        // Record the current Slide and update the buttons, without scrolling.
        setCurrent(index) {
            const slides = this.track.children;

            this.index = index;

            Array.from(slides).forEach((slide, i) => {
                if (i === index) {
                    slide.setAttribute('aria-current', 'true');
                } else {
                    slide.removeAttribute('aria-current');
                }
            });

            this.prevButton.disabled = index === 0;
            this.nextButton.disabled = index === slides.length - 1;
        }
    }

    if (!customElements.get('image-slider')) {
        customElements.define('image-slider', ImageSlider);
    }
})();
