// Behaviour tests for the <image-slider> web component (assets/js/slider.js):
// the server renders the Slides as lightbox links inside the element; the
// component adds a scroll track and, for more than one Slide, prev/next
// navigation by buttons and arrow keys.

const fs = require('fs');
const path = require('path');
const { JSDOM } = require('jsdom');

const SRC = path.join(__dirname, '..', '..', 'assets', 'js', 'slider.js');
const code = fs.readFileSync(SRC, 'utf8');

function slide(id, group) {
	return `<a class="image-slider-slide" href="file.php?id=${id}" data-lightbox="${group}" data-title="${id}.jpg" data-thumb="file.php?id=${id}&amp;t=1"><img src="file.php?id=${id}" alt="${id}.jpg"></a>`;
}

const html = `<!DOCTYPE html><html><body>
  <image-slider id="three" class="image-slider">${slide(1, 'slider-p7-1')}${slide(2, 'slider-p7-1')}${slide(3, 'slider-p7-1')}</image-slider>
  <image-slider id="one" class="image-slider">${slide(4, 'slider-p7-2')}</image-slider>
  <image-slider id="empty" data-ids="99"></image-slider>
  <image-slider id="thumbs" class="image-slider" data-thumbnails="1">${slide(5, 'g3')}${slide(6, 'g3')}${slide(7, 'g3')}</image-slider>
  <image-slider id="auto" class="image-slider" data-autoplay="4">${slide(8, 'g4')}${slide(9, 'g4')}</image-slider>
  <image-slider id="auto0" class="image-slider" data-autoplay="0">${slide(10, 'g5')}${slide(11, 'g5')}</image-slider>
  <image-slider id="autobad" class="image-slider" data-autoplay="abc">${slide(12, 'g6')}${slide(13, 'g6')}</image-slider>
  <image-slider id="onewithoptions" class="image-slider" data-autoplay="4" data-thumbnails="1">${slide(14, 'g7')}</image-slider>
</body></html>`;

const dom = new JSDOM(html, { runScripts: 'outside-only', pretendToBeVisual: true });
const { window } = dom;
const doc = window.document;

// Autoplay timers are recorded instead of run, so the tests can fire them.
const timers = new Map();
let nextTimer = 1;
window.setInterval = (fn, ms) => {
	timers.set(nextTimer, { fn, ms });
	return nextTimer++;
};
window.clearInterval = (id) => timers.delete(id);

// prefers-reduced-motion, switchable per test.
let reducedMotion = false;
window.matchMedia = (query) => ({ matches: query.includes('reduce') && reducedMotion });

window.SLIDER_CONFIG = { messages: {
	previous: 'Vorige', next: 'Volgende', showImage: 'Toon afbeelding %d',
	thumbnails: 'Miniaturen', pause: 'Pauzeer', play: 'Speel af',
} };
window.eval(code);

let failures = 0;
function assert(name, cond) {
	console.log((cond ? 'PASS' : 'FAIL') + ' - ' + name);
	if (!cond) {
		failures++;
	}
}

function key(el, name) {
	el.dispatchEvent(new window.KeyboardEvent('keydown', { key: name, bubbles: true }));
}

const three = doc.getElementById('three');
const one = doc.getElementById('one');
const empty = doc.getElementById('empty');

// --- Structure ---------------------------------------------------------------

const track = three.querySelector('.image-slider-track');
assert('the Slides are moved into a scroll track', track && track.querySelectorAll('a.image-slider-slide').length === 3);
assert('the Slides keep their order', [...track.querySelectorAll('a')].map((a) => a.getAttribute('href')).join() === 'file.php?id=1,file.php?id=2,file.php?id=3');
assert('the Slides keep their lightbox group', [...track.querySelectorAll('a')].every((a) => a.getAttribute('data-lightbox') === 'slider-p7-1'));
assert('the Slides do not get the postimage class', !three.querySelector('.postimage'));

const prev = three.querySelector('.image-slider-prev');
const next = three.querySelector('.image-slider-next');
assert('a Slider with several Slides gets prev/next buttons', prev && next);
assert('the buttons are labelled from the config', prev.getAttribute('aria-label') === 'Vorige' && next.getAttribute('aria-label') === 'Volgende');
assert('the buttons are real buttons that do not submit', prev.type === 'button' && next.type === 'button');
assert('the Slider is keyboard focusable', three.getAttribute('tabindex') === '0');

// --- Navigation ----------------------------------------------------------------

assert('a Slider starts at the first Slide', three.index === 0);
assert('prev is disabled on the first Slide', prev.disabled && !next.disabled);

next.click();
assert('next moves to the second Slide', three.index === 1);
assert('both buttons are enabled in the middle', !prev.disabled && !next.disabled);

next.click();
assert('next moves to the last Slide', three.index === 2);
assert('next is disabled on the last Slide', next.disabled);

next.click();
assert('next on the last Slide stays there', three.index === 2);

prev.click();
assert('prev moves back', three.index === 1);

key(three, 'ArrowLeft');
assert('ArrowLeft moves back', three.index === 0);

key(three, 'ArrowRight');
assert('ArrowRight moves forward', three.index === 1);

assert('the current Slide is marked', track.children[1].getAttribute('aria-current') === 'true' && !track.children[0].hasAttribute('aria-current'));

// --- Single Slide and empty ----------------------------------------------------

assert('a single-Slide Slider has no buttons', !one.querySelector('button'));
assert('a single-Slide Slider still shows its Slide in a track', one.querySelectorAll('.image-slider-track a.image-slider-slide').length === 1);
assert('a single-Slide Slider is marked single', one.classList.contains('image-slider--single'));
assert('a Slider without Slides renders nothing', empty.children.length === 0 && empty.hidden);

// --- Thumbnails ----------------------------------------------------------------

assert('a Slider without data-thumbnails has no thumbnail strip', !three.querySelector('.image-slider-thumbs'));

const thumbsSlider = doc.getElementById('thumbs');
const strip = thumbsSlider.querySelector('.image-slider-thumbs');
const thumbs = strip ? [...strip.querySelectorAll('button')] : [];
assert('data-thumbnails="1" adds a thumbnail strip', strip && thumbs.length === 3);
assert('thumbnails show the thumbnail image', thumbs[0] && thumbs[0].querySelector('img').getAttribute('src') === 'file.php?id=5&t=1');
assert('thumbnail buttons are labelled', thumbs[1] && thumbs[1].getAttribute('aria-label') === 'Toon afbeelding 2');
assert('the first thumbnail starts as current', thumbs[0] && thumbs[0].getAttribute('aria-current') === 'true');
assert('the strip is a labelled group', strip.getAttribute('role') === 'group' && strip.getAttribute('aria-label') === 'Miniaturen');
assert('thumbnails are not separate tab stops', thumbs.every((t) => t.tabIndex === -1));
assert('a single Slide gets no strip, whatever the options', !doc.getElementById('onewithoptions').querySelector('.image-slider-thumbs'));

thumbs[2].click();
assert('clicking a thumbnail shows that Slide', thumbsSlider.index === 2);
assert('the clicked thumbnail becomes current', thumbs[2].getAttribute('aria-current') === 'true' && !thumbs[0].hasAttribute('aria-current'));

thumbsSlider.querySelector('.image-slider-prev').click();
assert('the current thumbnail follows the buttons', thumbs[1].getAttribute('aria-current') === 'true');

// --- Autoplay ------------------------------------------------------------------

function timerOf(ms) {
	return [...timers.values()].find((t) => t.ms === ms);
}

function pointer(el, type, pointerType) {
	const e = new window.Event(type);
	Object.defineProperty(e, 'pointerType', { value: pointerType });
	el.dispatchEvent(e);
}

const auto = doc.getElementById('auto');
assert('data-autoplay starts a timer at that many seconds', timers.size === 1 && timerOf(4000));
assert('data-autoplay="0", a malformed value, a single Slide and no attribute start no timer', timers.size === 1);
assert('Sliders without autoplay have no pause button', !three.querySelector('.image-slider-toggle') && !doc.getElementById('autobad').querySelector('.image-slider-toggle'));

timerOf(4000).fn();
assert('autoplay advances to the next Slide', auto.index === 1);

timerOf(4000).fn();
assert('autoplay wraps from the last Slide to the first', auto.index === 0);

auto.dispatchEvent(new window.Event('pointerenter'));
assert('pointing at the Slider pauses autoplay', timers.size === 0);

auto.dispatchEvent(new window.Event('pointerleave'));
assert('leaving the Slider resumes autoplay', timers.size === 1 && timerOf(4000));

auto.dispatchEvent(new window.FocusEvent('focusin'));
assert('focus inside the Slider pauses autoplay', timers.size === 0);

auto.dispatchEvent(new window.FocusEvent('focusout'));
assert('losing focus resumes autoplay', timers.size === 1);

auto.dispatchEvent(new window.FocusEvent('focusin'));
pointer(auto, 'pointerenter', 'mouse');
pointer(auto, 'pointerleave', 'mouse');
assert('the pointer leaving keeps autoplay paused while focus is inside', timers.size === 0);
auto.dispatchEvent(new window.FocusEvent('focusout'));
assert('autoplay resumes once both pointer and focus have left', timers.size === 1);

const toggle = auto.querySelector('.image-slider-toggle');
assert('an autoplaying Slider has a pause button', toggle && toggle.type === 'button' && toggle.getAttribute('aria-label') === 'Pauzeer');
toggle.click();
assert('the pause button stops autoplay', timers.size === 0 && toggle.getAttribute('aria-label') === 'Speel af');
pointer(auto, 'pointerenter', 'mouse');
pointer(auto, 'pointerleave', 'mouse');
assert('a paused Slider stays paused after hovering', timers.size === 0);
toggle.click();
assert('the play button restarts autoplay', timers.size === 1 && toggle.getAttribute('aria-label') === 'Pauzeer');

toggle.dispatchEvent(new window.FocusEvent('focusin', { bubbles: true }));
assert('focusing the pause button itself does not pause autoplay', timers.size === 1);
toggle.dispatchEvent(new window.FocusEvent('focusout', { bubbles: true }));

pointer(auto, 'pointerdown', 'touch');
pointer(auto, 'pointerleave', 'touch');
assert('touching the Slider stops autoplay for good', timers.size === 0 && toggle.getAttribute('aria-label') === 'Speel af');
toggle.click();
assert('after a touch the play button still restarts it', timers.size === 1);

auto.remove();
assert('removing the Slider stops its timer', timers.size === 0);
doc.body.appendChild(auto);
assert('putting it back resumes autoplay', timers.size === 1);
toggle.click();
auto.remove();
doc.body.appendChild(auto);
assert('a paused Slider stays paused when moved', timers.size === 0);

reducedMotion = true;
const calm = doc.createElement('image-slider');
calm.setAttribute('data-autoplay', '3');
calm.innerHTML = slide(15, 'g8') + slide(16, 'g8');
doc.body.appendChild(calm);
assert('with reduced motion autoplay starts paused', !timerOf(3000) && calm.querySelector('.image-slider-toggle').getAttribute('aria-label') === 'Speel af');
calm.querySelector('.image-slider-toggle').click();
assert('with reduced motion the reader can still start it', !!timerOf(3000));
reducedMotion = false;

// --- Late elements -------------------------------------------------------------

const late = doc.createElement('image-slider');
late.innerHTML = slide(5, 'g') + slide(6, 'g');
doc.body.appendChild(late);
assert('a Slider added after load is set up too', late.querySelectorAll('button').length === 2);

process.exitCode = failures ? 1 : 0;
if (!failures) {
	console.log('All slider assertions passed.');
}
