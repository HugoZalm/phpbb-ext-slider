// Behaviour tests for editing an existing Slider tag with the Slider panel
// (styles/all/template/sliderpanel.js): double-click, caret or selection on a
// tag opens the panel with that tag's images and options; saving replaces the
// tag in place with every option written.

const fs = require('fs');
const path = require('path');
const { JSDOM } = require('jsdom');

const SRC = path.join(__dirname, '..', '..', 'styles', 'all', 'template', 'sliderpanel.js');
const code = fs.readFileSync(SRC, 'utf8');

function row(id, name) {
	return `<tr class="attach-row" data-attach-id="${id}"><td class="attach-name"><span class="file-name">${name}</span></td></tr>`;
}

const html = `<!DOCTYPE html><html><body>
  <span class="slider-editor-wrap">
    <button type="button" id="slider-editor-button" aria-expanded="false" title="Insert slider" data-title-edit="Edit slider"></button>
    <div id="slider-editor-panel" class="hidden" data-download="./download/file.php" data-image-extensions="jpg,png">
      <strong id="slider-editor-title" data-text-insert="Insert slider" data-text-edit="Edit slider">Insert slider</strong>
      <button type="button" id="slider-editor-close"></button>
      <p id="slider-editor-hint" data-text-insert="insert hint" data-text-edit="edit hint">insert hint</p>
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
      <button type="button" id="slider-editor-insert" data-text-insert="Insert" data-text-edit="Save" disabled>Insert</button>
    </div>
  </span>
  <table><tbody id="file-list">${row(1, 'a.jpg')}${row(2, 'b.jpg')}${row(3, 'c.jpg')}${row(4, 'd.jpg')}</tbody></table>
  <textarea id="message"></textarea>
</body></html>`;

const dom = new JSDOM(html, { runScripts: 'outside-only', pretendToBeVisual: true });
const { window } = dom;
const doc = window.document;

const inserted = [];
window.insert_text = (t) => inserted.push(t);

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
const action = $('slider-editor-insert');
const title = $('slider-editor-title');
const message = $('message');
const height = $('slider-editor-height');
const autoplay = $('slider-editor-autoplay');
const autoplayOn = $('slider-editor-autoplay-on');
const thumbnails = $('slider-editor-thumbnails');
const caption = $('slider-editor-caption');

const BEFORE = 'Day 1 ';
const TAG = '[slider=3,1 height=300 autoplay=4 thumbnails=0 caption=name]';
// The same tag as the panel writes it back unchanged: ids in filename order.
const TAG_SAVED = '[slider=1,3 height=300 autoplay=4 thumbnails=0 caption=name]';
const MIDDLE = ' and day 2 ';
const OTHER = '[slider=2]';
const AFTER = ' end.';
const TEXT = BEFORE + TAG + MIDDLE + OTHER + AFTER;
const TAG_START = BEFORE.length;
const TAG_END = TAG_START + TAG.length;
const OTHER_START = TAG_END + MIDDLE.length;

function isOpen() {
	return !panel.classList.contains('hidden');
}

function isEdit() {
	return title.textContent === 'Edit slider' && action.textContent === 'Save';
}

function ticked() {
	return [...list.querySelectorAll('input:checked')].map((b) => b.value).join();
}

function select(start, end) {
	message.focus();
	message.setSelectionRange(start, end);
	message.dispatchEvent(new window.Event('select'));
}

function close() {
	$('slider-editor-close').click();
}

function reset() {
	message.value = TEXT;
	inserted.length = 0;
}

(async () => {
	await sleep();
	reset();

	// --- Opening for edit ----------------------------------------------------------

	select(TAG_START + 5, TAG_START + 5);
	message.dispatchEvent(new window.MouseEvent('dblclick', { bubbles: true }));
	assert('double-clicking inside a tag opens the panel in edit mode', isOpen() && isEdit());
	assert('the tag\'s images are ticked', ticked() === '1,3');
	assert('the tag\'s options fill the fields', height.value === '300' && autoplayOn.checked && !autoplay.disabled && autoplay.value === '4' && !thumbnails.checked && caption.value === 'name');
	assert('save is enabled', !action.disabled);
	close();

	select(TAG_START + 10, TAG_START + 10);
	button.click();
	assert('caret inside a tag + button opens edit mode', isOpen() && isEdit() && ticked() === '1,3');
	close();

	select(TAG_START, TAG_END);
	assert('the button shows its edit state for a selected tag', button.classList.contains('slider-editor-button--edit') && button.getAttribute('title') === 'Edit slider');
	button.click();
	assert('selecting the whole tag + button opens edit mode', isOpen() && isEdit());
	close();

	select(2, TAG_START + 3);
	button.click();
	assert('a selection overlapping the tag opens edit mode for it', isOpen() && isEdit() && ticked() === '1,3');
	close();

	select(TAG_START + 5, TAG_START + 5);
	message.value = 'Pasted. ' + message.value;
	message.dispatchEvent(new window.Event('input'));
	assert('the edit state follows text changes such as a paste', !button.classList.contains('slider-editor-button--edit'));
	reset();

	select(TAG_START + 5, TAG_START + 5);
	button.click();
	assert('the panel is labelled for editing', panel.getAttribute('aria-label') === 'Edit slider');
	close();

	select(0, TAG_START);
	assert('the edit state is off for a selection that only touches the tag', !button.classList.contains('slider-editor-button--edit') && button.getAttribute('title') === 'Insert slider');
	button.click();
	assert('a selection ending at the tag\'s "[" opens insert mode', isOpen() && !isEdit() && ticked() === '');
	assert('insert mode starts with the board defaults', height.value === '400' && !autoplayOn.checked && autoplay.disabled && thumbnails.checked && caption.value === 'comment');
	close();

	select(TAG_END, TAG_END);
	button.click();
	assert('a caret right after the tag opens insert mode', isOpen() && !isEdit());
	close();

	select(OTHER_START + 3, OTHER_START + 3);
	button.click();
	assert('a hand-typed tag without options opens with the board defaults', isEdit() && ticked() === '2'
		&& height.value === '400' && !autoplayOn.checked && autoplay.disabled && thumbnails.checked && caption.value === 'comment');
	close();

	// --- Saving --------------------------------------------------------------------

	select(TAG_START + 5, TAG_START + 5);
	button.click();
	list.querySelector('input[value="2"]').click();
	height.value = '250';
	caption.value = 'comment';
	let inputs = 0;
	message.addEventListener('input', () => inputs++);
	action.click();

	const SAVED = '[slider=1,2,3 height=250 autoplay=4 thumbnails=0 caption=comment]';
	assert('saving replaces only that tag, with every option', message.value === BEFORE + SAVED + MIDDLE + OTHER + AFTER);
	assert('saving does not insert a second tag', inserted.length === 0);
	assert('the caret ends after the saved tag', message.selectionStart === TAG_START + SAVED.length);
	assert('saving fires an input event', inputs === 1);
	assert('the panel closes after saving', !isOpen());

	// Ids no longer attached are not listed, so saving drops them.
	reset();
	message.value = 'x [slider=4,9,1] y';
	select(5, 5);
	button.click();
	assert('ids that are no longer attached are not ticked', ticked() === '1,4');
	action.click();
	assert('saving drops ids that are no longer attached', message.value === 'x [slider=1,4 height=400 autoplay=0 thumbnails=1 caption=comment] y');

	// The tag moved while the panel was open: save finds it again by its text.
	reset();
	select(TAG_START + 5, TAG_START + 5);
	button.click();
	message.value = 'Moved: ' + message.value;
	action.click();
	assert('a tag that moved while the panel was open is still replaced in place',
		inserted.length === 0 && message.value === 'Moved: ' + BEFORE + TAG_SAVED + MIDDLE + OTHER + AFTER);

	// Two identical tags, the second edited while text was added before both:
	// save replaces the copy nearest to where the edited tag was.
	reset();
	const TWIN = '[slider=2]';
	message.value = 'A ' + TWIN + ' B ' + TWIN + ' C';
	const secondTwin = message.value.lastIndexOf(TWIN);
	select(secondTwin + 3, secondTwin + 3);
	button.click();
	height.value = '222';
	message.value = 'Moved. ' + message.value;
	action.click();
	assert('with two identical tags, save replaces the one that was edited',
		message.value === 'Moved. A ' + TWIN + ' B [slider=2 height=222 autoplay=0 thumbnails=1 caption=comment] C');

	// Option values and names as authors may type them by hand.
	reset();
	message.value = '[slider=1 caption=Name height=310 xheight=9]';
	select(3, 3);
	button.click();
	assert('option values are read case-insensitively', caption.value === 'name');
	assert('only whole option names count (xheight is not height)', height.value === '310');
	close();

	// The tag was deleted while the panel was open: save inserts a new one.
	reset();
	select(TAG_START + 5, TAG_START + 5);
	button.click();
	message.value = 'All gone.';
	action.click();
	assert('a tag deleted while the panel was open is inserted anew', inserted.length === 1 && inserted[0] === TAG_SAVED);

	// --- Switching between targets -----------------------------------------------------

	reset();
	select(TAG_START + 5, TAG_START + 5);
	button.click();
	height.value = '999';
	close();
	select(0, 0);
	button.click();
	assert('switching from editing a tag to inserting starts fresh', !isEdit() && ticked() === '' && height.value === '400');
	list.querySelector('input[value="4"]').click();
	close();
	button.click();
	assert('reopening for the same target keeps the selection', !isEdit() && ticked() === '4');
	close();
	select(TAG_START + 5, TAG_START + 5);
	button.click();
	assert('switching back to the tag shows that tag again', isEdit() && ticked() === '1,3' && height.value === '300');
	close();

	process.exitCode = failures ? 1 : 0;
	if (!failures) {
		console.log('All slider panel edit assertions passed.');
	}
})();
