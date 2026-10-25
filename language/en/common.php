<?php
/**
 *
 * Slider. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2026 Hugo
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

if (!defined('IN_PHPBB'))
{
	exit;
}

if (empty($lang) || !is_array($lang))
{
	$lang = [];
}

// DEVELOPERS PLEASE NOTE
//
// All language files should use UTF-8 as their encoding and the files must not contain a BOM.
//
// Placeholders can now contain order information, e.g. instead of
// 'Page %s of %s' you can (and should) write 'Page %1$s of %2$s', this allows
// translators to re-order the output of data while ensuring it remains correct
//
// You do not need this where single placeholders are used, e.g. 'Message %d' is fine
// equally where a string contains only two placeholders which are used to wrap text
// in a url you again do not need to specify an order e.g., 'Click %sHERE%s' is fine
//
// Some characters you may want to copy&paste:
// ’ » “ ” …
//

$lang = array_merge($lang, [
	'SLIDER_PREVIOUS'			=> 'Previous image',
	'SLIDER_NEXT'				=> 'Next image',
	'SLIDER_SHOW_IMAGE'			=> 'Show image %d',
	'SLIDER_THUMBNAILS'			=> 'Thumbnails',
	'SLIDER_PAUSE'				=> 'Pause slideshow',
	'SLIDER_PLAY'				=> 'Play slideshow',
	'SLIDER_BUTTON'					=> 'Slider',
	'SLIDER_BUTTON_EXPLAIN'			=> 'Insert a slider of this post’s images',
	'SLIDER_BUTTON_EDIT_EXPLAIN'	=> 'Edit the slider at the cursor',
	'SLIDER_PANEL_TITLE'			=> 'Insert slider',
	'SLIDER_PANEL_TITLE_EDIT'		=> 'Edit slider',
	'SLIDER_PANEL_HINT'				=> 'Tick the images to show. They appear in filename order; rename files to change it.',
	'SLIDER_PANEL_HINT_EDIT'		=> 'Change the images or options and click Save.',
	'SLIDER_PANEL_SAVE'				=> 'Save',
	'SLIDER_PANEL_SELECT_ALL'		=> 'Select all',
	'SLIDER_PANEL_EMPTY'			=> 'No images are attached to this post yet.',
	'SLIDER_PANEL_UPLOAD'			=> 'Upload images',
	'SLIDER_PANEL_HEIGHT'			=> 'Height (px)',
	'SLIDER_PANEL_AUTOPLAY'			=> 'Autoplay',
	'SLIDER_PANEL_AUTOPLAY_SECONDS'	=> 'Seconds between images',
	'SLIDER_PANEL_SECONDS'			=> 's',
	'SLIDER_PANEL_THUMBNAILS'		=> 'Thumbnails',
	'SLIDER_PANEL_CAPTION'			=> 'Caption',
	'SLIDER_PANEL_CAPTION_COMMENT'	=> 'Attachment comment',
	'SLIDER_PANEL_CAPTION_NAME'		=> 'File name',
	'SLIDER_PANEL_CAPTION_NONE'		=> 'None',
	'SLIDER_PANEL_INSERT'			=> 'Insert',
	'SLIDER_PANEL_CLOSE'			=> 'Close',
	'SLIDER_REQUIRES_LIGHTBOX'	=> 'The Slider extension needs the Lightbox extension (vse/lightbox). Enable Lightbox first.',
]);
