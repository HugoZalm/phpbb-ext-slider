// Behaviour tests for the Slider panel (styles/all/template/sliderpanel.js):
// it lists the post's image attachments in filename order with thumbnails,
// uploads images through phpBB's uploader, selects all, and inserts the full
// Slider tag with every option written (ACP defaults pre-filled).

const fs = require('fs');
const path = require('path');
const { JSDOM } = require('jsdom');

const SRC = path.join(__dirname, '..', '..', 'styles', 'all', 'template', 'sliderpanel.js');
const code = fs.readFileSync(SRC, 'utf8');

function row(id, name) {
	return `<tr class="attach-row" data-attach-id="${id}"><td class="attach-name"><span class="file-name"><a href="#">${name}</a></span></td></tr>`;
}

const html = `<!DOCTYPE html><html><body>
  <span class="slider-editor-wrap">
    <button type="button" id="slider-editor-button" aria-expanded="false"></button>
    <div id="slider-editor-panel" class="hidden" data-download="./download/file.php?sid=abc" data-image-extensions="jpg,jpeg,png,gif">
      <button type="button" id="slider-editor-close"></button>
      <label><input type="checkbox" id="slider-editor-all"> all</label>
      <ul id="slider-editor-list"></ul>
      <p id="slider-editor-empty">empty</p>
      <input type="file" id="slider-editor-file" multiple>
      <input type="number" id="slider-editor-height" min="1" max="2000" data-default="400">
      <input type="checkbox" id="slider-editor-autoplay-on">
      <input type="number" id="slider-editor-autoplay" min="1" max="60" data-default="0">
      <input type="checkbox" id="slider-editor-thumbnails" data-default="1">
      <select id="slider-editor-caption" data-default="comment">
        <option value="comment">comment</option><option value="name">name</option><option value="none">none</option>
      </select>
      <button type="button" id="slider-editor-insert" disabled></button>
    </div>
  </span>
  <table><tbody id="file-list">
    ${row(10, 'IMG_10.jpg')}${row(11, 'route.gpx')}${row(9, 'IMG_9.JPG')}${row(12, 'beach.png')}${row(13, 'notes.pdf')}${row(14, 'Alps.jpeg')}
  </tbody></table>
  <textarea id="message"></textarea>
</body></html>`;

const dom = new JSDOM(html, { runScripts: 'outside-only', pretendToBeVisual: true });
const { window } = dom;
const doc = window.document;

const inserted = [];
window.insert_text = (t) => inserted.push(t);

const uploaded = [];
window.phpbb = { plupload: { uploader: { addFile: (f) => uploaded.push(f.name) } } };

window.eval(code);

let failures = 0;
function assert(name, cond) {
	console.log((cond ? 'PASS' : 'FAIL') + ' - ' + name);
	if (!cond) {
		failures++;
	}
}

const sleep = () => new Promise((r) => setTimeout(r, 20));

const $ = (id) => doc.getElementById(id);
const button = $('slider-editor-button');
const panel = $('slider-editor-panel');
const list = $('slider-editor-list');
const all = $('slider-editor-all');
const insert = $('slider-editor-insert');
const height = $('slider-editor-height');
const autoplay = $('slider-editor-autoplay');
const autoplayOn = $('slider-editor-autoplay-on');
const thumbnails = $('slider-editor-thumbnails');
const caption = $('slider-editor-caption');

function isOpen() {
	return !panel.classList.contains('hidden');
}

function boxes() {
	return [...list.querySelectorAll('input[type="checkbox"]')];
}

function ids() {
	return boxes().map((b) => b.value);
}

function tick(id) {
	const box = list.querySelector(`input[value="${id}"]`);
	box.checked = !box.checked;
	box.dispatchEvent(new window.Event('change', { bubbles: true }));
}

function open() {
	if (!isOpen()) {
		button.click();
	}
}

(async () => {
	// The script sets itself up on DOMContentLoaded.
	await sleep();

	// --- Listing -----------------------------------------------------------------

	assert('the panel starts closed', !isOpen());
	button.click();
	assert('the button opens the panel', isOpen() && button.getAttribute('aria-expanded') === 'true');

	assert('only image attachments are listed', ids().length === 4 && !ids().includes('11') && !ids().includes('13'));
	assert('images are in filename order, natural and case-insensitive', ids().join() === '14,12,9,10');

	const firstImg = list.querySelector('li img');
	assert('each image shows its thumbnail', firstImg && firstImg.getAttribute('src') === './download/file.php?sid=abc&id=14&t=1');
	assert('each image shows its filename', list.querySelector('li').textContent.includes('Alps.jpeg'));

	firstImg.dispatchEvent(new window.Event('error'));
	assert('without a phpBB thumbnail the image itself is shown', !firstImg.hidden && firstImg.getAttribute('src') === './download/file.php?sid=abc&id=14');
	firstImg.dispatchEvent(new window.Event('error'));
	assert('an image that fails to load is hidden, the filename stays', firstImg.hidden && list.querySelector('li').textContent.includes('Alps.jpeg'));

	// --- Fields ------------------------------------------------------------------

	assert('the fields start at the board defaults', height.value === '400' && !autoplayOn.checked && autoplay.disabled && thumbnails.checked && caption.value === 'comment');

	// --- Selecting ---------------------------------------------------------------

	assert('insert is disabled while nothing is ticked', insert.disabled);
	tick(9);
	assert('ticking an image enables insert', !insert.disabled);
	tick(9);
	assert('unticking the last image disables insert again', insert.disabled);

	all.checked = true;
	all.dispatchEvent(new window.Event('change', { bubbles: true }));
	assert('select all ticks every image', boxes().every((b) => b.checked) && !insert.disabled);
	tick(12);
	assert('unticking one image clears select all', !all.checked);
	tick(12);
	assert('ticking every image sets select all', all.checked);
	all.checked = false;
	all.dispatchEvent(new window.Event('change', { bubbles: true }));
	assert('clearing select all unticks every image', boxes().every((b) => !b.checked) && insert.disabled);

	// --- Uploads and deletions -----------------------------------------------------

	tick(10);
	$('file-list').insertAdjacentHTML('beforeend', row(15, 'IMG_2.jpg') + row(16, 'track.gpx'));
	await sleep();
	assert('a new image upload appears in the list, in order', ids().join() === '14,12,15,9,10');
	assert('ticked images stay ticked after a refresh', list.querySelector('input[value="10"]').checked);

	$('file-list').querySelector('[data-attach-id="10"]').remove();
	await sleep();
	assert('a deleted attachment disappears from the list', !ids().includes('10'));
	assert('insert is disabled again when the ticked image is deleted', insert.disabled);

	const fileInput = $('slider-editor-file');
	Object.defineProperty(fileInput, 'files', { configurable: true, value: [{ name: 'a.JPG' }, { name: 'b.gpx' }, { name: 'c.png' }] });
	fileInput.dispatchEvent(new window.Event('change'));
	assert('upload hands only images to phpBB\'s uploader', uploaded.join() === 'a.JPG,c.png');

	// --- Inserting -----------------------------------------------------------------

	tick(9);
	tick(14);
	tick(15);
	insert.click();
	assert('insert writes the ids in filename order with every option at its default',
		inserted[0] === '[slider=14,15,9 height=400 autoplay=0 thumbnails=1 caption=comment]');
	assert('the panel closes after inserting', !isOpen());

	open();
	assert('reopening starts with nothing ticked', boxes().every((b) => !b.checked) && insert.disabled);
	tick(12);
	height.value = '300';
	autoplayOn.click();
	assert('switching autoplay on suggests 5 seconds', autoplayOn.checked && !autoplay.disabled && autoplay.value === '5');
	autoplay.value = '4';
	thumbnails.checked = false;
	caption.value = 'name';
	insert.click();
	assert('insert writes the edited options', inserted[1] === '[slider=12 height=300 autoplay=4 thumbnails=0 caption=name]');

	open();
	assert('reopening resets the fields to the board defaults', height.value === '400' && !autoplayOn.checked && autoplay.disabled && thumbnails.checked && caption.value === 'comment');
	tick(12);
	height.value = '0';
	autoplayOn.click();
	autoplay.value = 'abc';
	insert.click();
	assert('an invalid field falls back to the board default', inserted[2] === '[slider=12 height=400 autoplay=0 thumbnails=1 caption=comment]');

	// Autoplay switched on, then off again: the interval is kept but not written.
	open();
	tick(12);
	autoplayOn.click();
	autoplay.value = '8';
	autoplayOn.click();
	assert('switching autoplay off disables the seconds field', !autoplayOn.checked && autoplay.disabled);
	autoplayOn.click();
	assert('switching it on again keeps the chosen interval', autoplay.value === '8');
	autoplayOn.click();
	insert.click();
	assert('autoplay switched off is written as 0', inserted[3] === '[slider=12 height=400 autoplay=0 thumbnails=1 caption=comment]');

	// --- Closing -------------------------------------------------------------------

	open();
	assert('opening moves focus into the panel', panel.contains(doc.activeElement));
	$('slider-editor-close').click();
	assert('the close button closes the panel', !isOpen());
	assert('closing returns focus to the Slider button', doc.activeElement === button);

	open();
	doc.dispatchEvent(new window.KeyboardEvent('keydown', { key: 'Escape' }));
	assert('Escape closes the panel', !isOpen());

	open();
	doc.body.dispatchEvent(new window.MouseEvent('click', { bubbles: true }));
	assert('a click outside closes the panel', !isOpen());

	// --- Selection survives closing ---------------------------------------------------

	open();
	tick(12);
	tick(9);
	height.value = '250';
	doc.body.dispatchEvent(new window.MouseEvent('click', { bubbles: true }));
	$('file-list').querySelector('[data-attach-id="9"]').remove();
	await sleep();
	open();
	assert('ticked images stay ticked after closing, e.g. to delete an attachment', list.querySelector('input[value="12"]').checked && !ids().includes('9'));
	assert('edited fields are kept until a tag is inserted', height.value === '250');

	// phpBB's uploader adds the attach row first and sets its id once uploaded.
	$('file-list').insertAdjacentHTML('beforeend', '<tr class="attach-row" id="upload-row"><td><span class="file-name">IMG_1.jpg</span></td></tr>');
	await sleep();
	$('upload-row').setAttribute('data-attach-id', '17');
	await sleep();
	assert('an upload appears once phpBB gives it its id', ids().includes('17'));

	// --- Same order as the server ---------------------------------------------------

	// The order PHP's strnatcasecmp gives (slides::select), from usort in PHP 8.
	const names = ['IMG_10.jpg', 'img_9.jpg', 'IMG-1.jpg', 'IMG_1.jpg', '_start.jpg', 'Zebra.jpg', 'alps.jpg', 'été.jpg', 'ete.jpg',
		'photo 2.jpg', 'photo 10.jpg', 'photo02.jpg', 'Photo2.jpg', '10.jpg', '9.jpg', '  space.jpg'];
	const phpOrder = ['9.jpg', '10.jpg', 'alps.jpg', 'ete.jpg', 'IMG-1.jpg', 'IMG_1.jpg', 'img_9.jpg', 'IMG_10.jpg', 'photo02.jpg',
		'photo 2.jpg', 'Photo2.jpg', 'photo 10.jpg', '  space.jpg', 'Zebra.jpg', '_start.jpg', 'été.jpg'];

	$('file-list').innerHTML = names.map((name, i) => row(100 + i, name)).join('');
	await sleep();
	const listed = [...list.querySelectorAll('.slider-editor-name')].map((el) => el.textContent);
	assert('the list is in the same order as the Slider (PHP strnatcasecmp)', listed.join('|') === phpOrder.map((n) => n.trim()).join('|'));

	process.exitCode = failures ? 1 : 0;
	if (!failures) {
		console.log('All slider panel assertions passed.');
	}
})();
