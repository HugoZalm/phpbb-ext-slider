(function () {
	'use strict';

	// The Slider panel: pick the post's image attachments and insert a Slider
	// tag with every display option written out (pre-filled with the board's
	// defaults), so the Slider keeps looking the same when the defaults change.

	function isDigit(c) {
		return c >= 48 && c <= 57;
	}

	function isSpace(c) {
		return c === 32 || (c >= 9 && c <= 13);
	}

	function upper(c) {
		return (c >= 97 && c <= 122) ? c - 32 : c;
	}

	function utf8Bytes(text) {
		var binary = unescape(encodeURIComponent(text));
		var bytes = [];

		for (var i = 0; i < binary.length; i++) {
			bytes.push(binary.charCodeAt(i));
		}

		return bytes;
	}

	// Compare digit runs starting at a[i] and b[j]: by value when neither has a
	// leading zero (the longer run wins), else digit by digit from the left.
	function compareDigits(a, i, b, j) {
		var fractional = a[i] === 48 || b[j] === 48;
		var bias = 0;

		for (;; i++, j++) {
			var aDigit = i < a.length && isDigit(a[i]);
			var bDigit = j < b.length && isDigit(b[j]);

			if (!aDigit && !bDigit) {
				return { result: fractional ? 0 : bias, i: i, j: j };
			}

			if (!aDigit) {
				return { result: -1 };
			}

			if (!bDigit) {
				return { result: 1 };
			}

			if (a[i] !== b[j]) {
				if (fractional) {
					return { result: a[i] < b[j] ? -1 : 1 };
				}

				bias = bias || (a[i] < b[j] ? -1 : 1);
			}
		}
	}

	// PHP's strnatcasecmp() on the UTF-8 bytes, so the panel lists the images
	// in exactly the order the Slider will show them (slides::select()).
	function natcasecmp(x, y) {
		var a = utf8Bytes(x);
		var b = utf8Bytes(y);
		var i = 0;
		var j = 0;

		if (!a.length || !b.length) {
			return a.length === b.length ? 0 : (a.length > b.length ? 1 : -1);
		}

		// Leading zeros before a digit are skipped.
		while (a[i] === 48 && i + 1 < a.length && isDigit(a[i + 1])) {
			i++;
		}

		while (b[j] === 48 && j + 1 < b.length && isDigit(b[j + 1])) {
			j++;
		}

		for (;;) {
			// Whitespace is skipped.
			while (i < a.length && isSpace(a[i])) {
				i++;
			}

			while (j < b.length && isSpace(b[j])) {
				j++;
			}

			if (i < a.length && j < b.length && isDigit(a[i]) && isDigit(b[j])) {
				var run = compareDigits(a, i, b, j);

				if (run.result !== 0) {
					return run.result;
				}

				i = run.i;
				j = run.j;

				if (i >= a.length && j >= b.length) {
					return 0;
				}

				if (i >= a.length) {
					return -1;
				}

				if (j >= b.length) {
					return 1;
				}
			}

			var ca = i < a.length ? upper(a[i]) : 0;
			var cb = j < b.length ? upper(b[j]) : 0;

			if (ca !== cb) {
				return ca < cb ? -1 : 1;
			}

			i++;
			j++;

			if (i >= a.length && j >= b.length) {
				return 0;
			}

			if (i >= a.length) {
				return -1;
			}

			if (j >= b.length) {
				return 1;
			}
		}
	}

	function init() {
		var button = document.getElementById('slider-editor-button');
		var panel = document.getElementById('slider-editor-panel');

		if (!button || !panel) {
			return;
		}

		var list = document.getElementById('slider-editor-list');
		var empty = document.getElementById('slider-editor-empty');
		var selectAll = document.getElementById('slider-editor-all');
		var fileInput = document.getElementById('slider-editor-file');
		var closeButton = document.getElementById('slider-editor-close');
		var insertButton = document.getElementById('slider-editor-insert');
		var fileList = document.getElementById('file-list');
		var textarea = document.getElementById('message');

		var height = document.getElementById('slider-editor-height');
		var autoplay = document.getElementById('slider-editor-autoplay');
		var thumbnails = document.getElementById('slider-editor-thumbnails');
		var caption = document.getElementById('slider-editor-caption');

		var download = panel.getAttribute('data-download') || '';
		var imageExtensions = (panel.getAttribute('data-image-extensions') || '').toLowerCase().split(',');

		// Ids of the ticked images. Kept across refreshes and closing the panel
		// (e.g. to delete an attachment) until a tag is inserted.
		var ticked = {};

		function isImage(name) {
			var match = /\.([^.]+)$/.exec(String(name || '').trim());

			return !!match && imageExtensions.indexOf(match[1].toLowerCase()) !== -1;
		}

		function isOpen() {
			return !panel.classList.contains('hidden');
		}

		function imageUrl(id, thumbnail) {
			return download + (download.indexOf('?') === -1 ? '?' : '&') + 'id=' + id + (thumbnail ? '&t=1' : '');
		}

		// The post's image attachments from phpBB's own attach list, in the order
		// the Slider shows them: by filename, natural and case-insensitive, then id.
		function collect() {
			var items = [];

			if (!fileList) {
				return items;
			}

			var rows = fileList.querySelectorAll('.attach-row[data-attach-id]');

			for (var i = 0; i < rows.length; i++) {
				var nameEl = rows[i].querySelector('.file-name');
				var name = nameEl ? nameEl.textContent.trim() : '';

				if (isImage(name)) {
					items.push({ id: rows[i].getAttribute('data-attach-id'), name: name });
				}
			}

			items.sort(function (a, b) {
				return natcasecmp(a.name, b.name) || a.id - b.id;
			});

			return items;
		}

		function refresh() {
			var items = collect();
			var present = {};

			list.innerHTML = '';

			items.forEach(function (item) {
				var entry = document.createElement('li');
				var label = document.createElement('label');
				var box = document.createElement('input');
				var img = document.createElement('img');
				var name = document.createElement('span');

				present[item.id] = true;

				box.type = 'checkbox';
				box.value = item.id;
				box.checked = !!ticked[item.id];

				// phpBB's thumbnail, else the image itself (boards may not make
				// thumbnails), else just the filename.
				img.src = imageUrl(item.id, true);
				img.alt = '';
				img.loading = 'lazy';
				img.addEventListener('error', function () {
					if (img.getAttribute('src') === imageUrl(item.id, true)) {
						img.src = imageUrl(item.id, false);
					} else {
						img.hidden = true;
					}
				});

				name.className = 'slider-editor-name';
				name.textContent = item.name;

				label.className = 'slider-editor-item';
				label.appendChild(box);
				label.appendChild(img);
				label.appendChild(name);
				entry.appendChild(label);
				list.appendChild(entry);
			});

			// Forget images that are no longer attached.
			Object.keys(ticked).forEach(function (id) {
				if (!present[id]) {
					delete ticked[id];
				}
			});

			empty.classList.toggle('hidden', items.length > 0);
			list.classList.toggle('hidden', items.length === 0);
			updateState();
		}

		function boxes() {
			return Array.prototype.slice.call(list.querySelectorAll('input[type="checkbox"]'));
		}

		// Insert needs at least one image; "select all" shows whether all are ticked.
		function updateState() {
			var allBoxes = boxes();
			var count = allBoxes.filter(function (box) {
				return box.checked;
			}).length;

			insertButton.disabled = count === 0;

			if (selectAll) {
				selectAll.checked = allBoxes.length > 0 && count === allBoxes.length;
			}
		}

		function resetFields() {
			height.value = height.getAttribute('data-default');
			autoplay.value = autoplay.getAttribute('data-default');
			thumbnails.checked = thumbnails.getAttribute('data-default') === '1';
			caption.value = caption.getAttribute('data-default');
		}

		// Opening moves focus into the panel; closing with the close button or
		// Escape gives it back to the toolbar button.
		function setOpen(open, returnFocus) {
			panel.classList.toggle('hidden', !open);
			button.setAttribute('aria-expanded', open ? 'true' : 'false');

			if (open) {
				refresh();
				(selectAll || closeButton || insertButton).focus();
			} else if (returnFocus) {
				button.focus();
			}
		}

		// A whole number within the field's min and max, else the board default.
		function numberOption(input) {
			var raw = String(input.value).trim();
			var value = parseInt(raw, 10);
			var min = parseInt(input.getAttribute('min'), 10);
			var max = parseInt(input.getAttribute('max'), 10);

			if (!/^\d+$/.test(raw) || (!isNaN(min) && value < min) || (!isNaN(max) && value > max)) {
				return input.getAttribute('data-default');
			}

			return String(value);
		}

		function buildTag() {
			var ids = boxes().filter(function (box) {
				return box.checked;
			}).map(function (box) {
				return box.value;
			});

			return '[slider=' + ids.join(',')
				+ ' height=' + numberOption(height)
				+ ' autoplay=' + numberOption(autoplay)
				+ ' thumbnails=' + (thumbnails.checked ? '1' : '0')
				+ ' caption=' + (caption.value || caption.getAttribute('data-default')) + ']';
		}

		function insert() {
			// insert_text() is phpBB's global editor helper (editor.js).
			if (typeof insert_text === 'function') {
				insert_text(buildTag());
			}

			if (textarea) {
				textarea.focus();
			}

			// The next Slider starts afresh.
			ticked = {};
			resetFields();
			setOpen(false);
		}

		// Hand the chosen images to phpBB's own uploader; its attach rows then
		// show up in the list through the observer below.
		function upload(files) {
			var uploader = window.phpbb && phpbb.plupload && phpbb.plupload.uploader;

			if (!uploader || !files) {
				return;
			}

			for (var i = 0; i < files.length; i++) {
				if (isImage(files[i].name)) {
					uploader.addFile(files[i]);
				}
			}
		}

		button.addEventListener('click', function (e) {
			e.preventDefault();
			setOpen(!isOpen());
		});

		list.addEventListener('change', function (e) {
			if (e.target.type === 'checkbox') {
				if (e.target.checked) {
					ticked[e.target.value] = true;
				} else {
					delete ticked[e.target.value];
				}

				updateState();
			}
		});

		if (selectAll) {
			selectAll.addEventListener('change', function () {
				boxes().forEach(function (box) {
					box.checked = selectAll.checked;

					if (selectAll.checked) {
						ticked[box.value] = true;
					} else {
						delete ticked[box.value];
					}
				});

				updateState();
			});
		}

		insertButton.addEventListener('click', insert);

		if (closeButton) {
			closeButton.addEventListener('click', function () {
				setOpen(false, true);
			});
		}

		document.addEventListener('click', function (e) {
			if (isOpen() && !panel.contains(e.target) && !button.contains(e.target)) {
				setOpen(false);
			}
		});

		document.addEventListener('keydown', function (e) {
			if (e.key === 'Escape' && isOpen()) {
				setOpen(false, true);
			}
		});

		if (fileInput) {
			fileInput.addEventListener('change', function () {
				upload(fileInput.files);
				fileInput.value = '';
			});
		}

		// Keep the panel in step with uploads and deletions in the attach list.
		// phpBB's uploader adds a row first and sets its data-attach-id later.
		if (fileList && typeof MutationObserver === 'function') {
			new MutationObserver(function () {
				if (isOpen()) {
					refresh();
				}
			}).observe(fileList, { childList: true, subtree: true, attributes: true, attributeFilter: ['data-attach-id'] });
		}

		resetFields();
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
