(function () {
    'use strict';

    // The server renders each Slide as a lightbox link (<a data-lightbox><img></a>)
    // inside <image-slider>, so the images and the lightbox work without this
    // script. The component adds a scroll-snap track (native swiping) and, for
    // more than one Slide, prev/next buttons and arrow keys, plus the thumbnail
    // strip (data-thumbnails="1") and autoplay (data-autoplay="seconds").

    const CONFIG = (typeof window.SLIDER_CONFIG === 'object' && window.SLIDER_CONFIG) || {};
    const MESSAGES = Object.assign({
        previous: 'Previous',
        next: 'Next',
        showImage: 'Show image %d',
        thumbnails: 'Thumbnails',
        pause: 'Pause',
        play: 'Play',
    }, CONFIG.messages || {});

    class ImageSlider extends HTMLElement {
        connectedCallback() {
            // Moved in the DOM: only resume autoplay.
            if (this.track) {
                this.update();
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

            if (this.getAttribute('data-thumbnails') === '1') {
                this.addThumbnails(slides);
            }

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
            this.startAutoplay();
        }

        disconnectedCallback() {
            this.stop();
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

        addThumbnails(slides) {
            const strip = document.createElement('div');

            strip.className = 'image-slider-thumbs';
            strip.setAttribute('role', 'group');
            strip.setAttribute('aria-label', MESSAGES.thumbnails);
            this.thumbs = slides.map((slide, i) => {
                const thumb = document.createElement('button');
                const img = document.createElement('img');
                const label = MESSAGES.showImage.replace('%d', i + 1);

                // Keyboard users step through the Slides with the arrow keys
                // instead of tabbing past every thumbnail.
                thumb.type = 'button';
                thumb.tabIndex = -1;
                thumb.className = 'image-slider-thumb';
                thumb.setAttribute('aria-label', label);
                thumb.title = label;
                img.src = slide.getAttribute('data-thumb') || slide.querySelector('img').src;
                img.alt = '';
                img.loading = 'lazy';
                thumb.appendChild(img);
                thumb.addEventListener('click', () => this.goTo(i));
                strip.appendChild(thumb);

                return thumb;
            });

            this.appendChild(strip);
        }

        // Advance every data-autoplay seconds, from the last Slide back to the
        // first. It holds while a mouse points at the Slider or focus is in it,
        // stops for good on touch (the reader took over), and the pause button
        // stops and starts it. With reduced motion it starts paused.
        startAutoplay() {
            const seconds = parseInt(this.getAttribute('data-autoplay'), 10);

            if (!(seconds > 0)) {
                return;
            }

            const isTouch = (e) => e.pointerType === 'touch' || e.pointerType === 'pen';

            this.autoplayMs = seconds * 1000;
            this.hovering = false;
            this.focused = false;
            this.toggleButton = this.addButton('image-slider-toggle', '', () => this.setPaused(!this.paused));

            this.addEventListener('pointerenter', (e) => {
                if (!isTouch(e)) {
                    this.hovering = true;
                    this.update();
                }
            });
            this.addEventListener('pointerleave', () => {
                this.hovering = false;
                this.update();
            });
            this.addEventListener('pointerdown', (e) => {
                if (isTouch(e)) {
                    this.setPaused(true);
                }
            });
            this.addEventListener('focusin', (e) => {
                if (e.target !== this.toggleButton) {
                    this.focused = true;
                    this.update();
                }
            });
            this.addEventListener('focusout', (e) => {
                if (!this.contains(e.relatedTarget)) {
                    this.focused = false;
                    this.update();
                }
            });

            const reduced = typeof window.matchMedia === 'function' && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

            this.setPaused(reduced);
        }

        setPaused(paused) {
            const label = paused ? MESSAGES.play : MESSAGES.pause;

            this.paused = paused;
            this.toggleButton.setAttribute('aria-label', label);
            this.toggleButton.title = label;
            this.toggleButton.classList.toggle('image-slider-toggle--paused', paused);
            this.update();
        }

        // Run the timer exactly when autoplay is on and nothing holds it.
        update() {
            if (!this.autoplayMs) {
                return;
            }

            if (this.paused || this.hovering || this.focused || !this.isConnected) {
                this.stop();
            } else if (!this.timer) {
                this.play();
            }
        }

        play() {
            this.timer = window.setInterval(() => {
                const count = this.track.children.length;

                this.goTo((this.index + 1) % count);
            }, this.autoplayMs);
        }

        stop() {
            if (this.timer) {
                window.clearInterval(this.timer);
                this.timer = null;
            }
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

            [Array.from(slides), this.thumbs || []].forEach((items) => {
                items.forEach((item, i) => {
                    if (i === index) {
                        item.setAttribute('aria-current', 'true');
                    } else {
                        item.removeAttribute('aria-current');
                    }
                });
            });

            this.prevButton.disabled = index === 0;
            this.nextButton.disabled = index === slides.length - 1;
        }
    }

    if (!customElements.get('image-slider')) {
        customElements.define('image-slider', ImageSlider);
    }
})();
