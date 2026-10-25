# Slider

A phpBB extension that shows a post's image attachments as a Slider inside the post. Authors tick images in the Slider panel of the editor and insert a `[slider=…]` tag; readers swipe or step through the images in filename order and click one to open it full-size in the lightbox.

- phpBB: 3.3.x (`>=3.3,<3.4`)
- PHP: 7.2 or newer (8.x supported)
- Requires: the **Lightbox** extension (`vse/lightbox`), enabled
- Languages: English and Dutch

No new permissions are introduced: Sliders follow the board's existing post and attachment permissions.

## Installing and enabling

1. Install and enable the **Lightbox** extension (`vse/lightbox`) first. The Slider refuses to enable without it.
2. Place this directory at `ext/hugozalm/slider/` in your phpBB installation.
3. Go to **ACP → Customise → Manage extensions**.
4. Find **Slider** under *Disabled extensions* and click **Enable**.

Enabling installs, in one step:

- the `[slider]` custom BBCode. It is not shown among the BBCode buttons on the posting page; authors use the extension's own **Slider** button instead,
- the four default display settings and the **Slider settings** ACP module.

Nothing about your existing attachment groups, forums or permissions is changed.

To remove everything, click **Disable** and then **Delete data** (purge): the BBCode, the settings and the ACP module are removed. (Post text keeps its `[slider=…]` tags, but they no longer render.)

## Admin settings

Find them at **ACP → Extensions → Slider → Slider settings** (administrators who may change board settings).

| Setting | Default | What it does |
| --- | --- | --- |
| **Height** | `400` | Height of a Slider, in pixels (1–2000). |
| **Autoplay** | `0` | Seconds between images when a Slider plays by itself (0–60); `0` turns autoplay off. |
| **Thumbnails** | Yes | Shows a strip of clickable thumbnails under a Slider. |
| **Caption** | Attachment comment | The text under each image: the attachment comment, the file name (without extension), or none. |

The Slider panel starts with these values and **writes all four into every tag it inserts**. Changing a setting therefore affects new Sliders only; existing posts keep the look their author saw in the preview. A hand-typed tag that leaves an option out uses the setting for that option. Every save is recorded in the admin log.

> **Images open in the Lightbox extension's lightbox.** On pages with a Slider, the Slider loads that lightbox from the Lightbox extension even when Lightbox's own settings would not load it.

## Adding a Slider (author flow)

1. Attach your images to the post as usual, or upload them from the panel in step 3.
2. Click the **Slider** button in the editor toolbar. It appears when you write or edit a post (not a private message) in a forum where you may attach files.
3. The Slider panel opens as **Insert slider**. In it:
   - tick the images to show (each with a thumbnail and its file name), or use **Select all**,
   - **Upload images** to attach more through phpBB's normal uploader; they appear in the list,
   - adjust **Height**, **Autoplay**, **Thumbnails** and **Caption** for this Slider if you like. Ticking **Autoplay** suggests 5 seconds between images; change the number if you want another interval.
4. Click **Insert**. A tag like `[slider=12,15,9 height=400 autoplay=0 thumbnails=1 caption=comment]` is placed at the cursor. Move it wherever the Slider should appear; a post can hold several Sliders.
5. Use **Preview** to check, then submit.

### Image order

Images always appear in **filename order**: natural and case-insensitive, so `IMG_9.jpg` comes before `IMG_10.jpg`; images with the same file name follow the order they were uploaded in. The order of the ids in the tag does not matter. To change the order, rename the files before uploading them; for images already attached, delete them and upload them again under new names.

### Editing a Slider

Double-click inside a Slider tag, or put the cursor in it (or select it) and click the **Slider** button. While the cursor is in a Slider tag, the button is outlined to show that clicking it will edit that Slider. The Slider panel then opens as **Edit slider**, with that Slider's images ticked and its options filled in. Change them and click **Save**: only that tag is rewritten, in place.

## The Slider tag

```text
[slider=IDs height=H autoplay=S thumbnails=T caption=C]
```

| Part | Values | Meaning |
| --- | --- | --- |
| `IDs` | attachment ids, comma-separated, no spaces | The images to show. Only image attachments of **this post** are shown; other ids are ignored. |
| `height` | `1`–`2000` | Height in pixels. |
| `autoplay` | `0`–`60` | Seconds between images; `0` is off. |
| `thumbnails` | `0` or `1` | Thumbnail strip off or on. |
| `caption` | `comment`, `name`, `none` | The attachment comment, the file name without extension, or no caption. |

Every option is optional. A missing or invalid option value uses the board setting, so a typo in an option never breaks a Slider. The ids, however, must be plain numbers separated by commas: a tag like `[slider=12; 15]` is not recognised and shows as plain text.

A Slider without any valid image shows nothing, which is also what happens to a quoted Slider tag (its images belong to the other post). A Slider with one image shows just that image: no arrows, thumbnails or autoplay.

## What readers see

- One image at a time: swipe, use the arrow buttons, or the left/right keys when the Slider has focus.
- Optional thumbnail strip and captions under the images.
- Autoplay, when set, wraps from the last image to the first. It holds while the mouse is over the Slider or keyboard focus is in it, stops for good once the reader touches it on a touch screen, has a pause/play button, and starts paused for readers whose system asks for reduced motion.
- Clicking an image opens it in the lightbox, browsing only that Slider's images.
- Images shown in a Slider are left out of the attachment box under the post, like inline attachments (in the topic and the posting preview).

## Notes

- Images are served through phpBB's download page, so download permissions apply. A reader without permission to download attachments sees no Slider.
- Sliders are shown in topics, in the posting preview and in the topic review under the reply form. Elsewhere — search results, feeds, moderator views — a Slider tag shows nothing.
- The Slider's scripts and styles are loaded only on pages that contain a Slider tag (including the posting preview).
- Recognising Slider images in the attachment box relies on the style linking attachments to `download/file.php?id=N`, as prosilver does.
- The topic review under the reply form still lists Slider images in its attachment box.

## Development

Tests live in `tests/`:

- **JavaScript** (the Slider component and the Slider panel, with jsdom):

  ```bash
  cd tests/js
  npm install
  npm test
  ```

- **PHP** (choosing, ordering and rendering the images, option validation) with PHPUnit 11, run inside the phpBB web container because it needs PHP 8.2+:

  ```bash
  bash tests/php/run.sh
  ```

  The script downloads `phpunit.phar` on first use (kept out of git) and runs it in the container `phpbb-dev-environment-web-1`; set `PHPBB_CONTAINER` to use another container.
- **Manual check**: open `tests/fixture.html` in a browser to try the Slider (arrows, thumbnails, captions, autoplay) without a board.
