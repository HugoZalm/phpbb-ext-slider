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
	return `<a class="image-slider-slide" href="file.php?id=${id}" data-lightbox="${group}" data-title="${id}.jpg"><img src="file.php?id=${id}" alt="${id}.jpg"></a>`;
}

const html = `<!DOCTYPE html><html><body>
  <image-slider id="three" class="image-slider">${slide(1, 'slider-p7-1')}${slide(2, 'slider-p7-1')}${slide(3, 'slider-p7-1')}</image-slider>
  <image-slider id="one" class="image-slider">${slide(4, 'slider-p7-2')}</image-slider>
  <image-slider id="empty" data-ids="99"></image-slider>
</body></html>`;

const dom = new JSDOM(html, { runScripts: 'outside-only', pretendToBeVisual: true });
const { window } = dom;
const doc = window.document;

window.SLIDER_CONFIG = { messages: { previous: 'Vorige', next: 'Volgende' } };
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

// --- Late elements -------------------------------------------------------------

const late = doc.createElement('image-slider');
late.innerHTML = slide(5, 'g') + slide(6, 'g');
doc.body.appendChild(late);
assert('a Slider added after load is set up too', late.querySelectorAll('button').length === 2);

process.exitCode = failures ? 1 : 0;
if (!failures) {
	console.log('All slider assertions passed.');
}
