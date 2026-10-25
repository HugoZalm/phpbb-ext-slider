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

$lang = array_merge($lang, [
	'ACP_SLIDER_EXPLAIN'			=> 'The default display options for Sliders. A Slider uses the value set here for every option its tag leaves out.',

	'ACP_SLIDER_DEFAULTS'			=> 'Default display options',
	'ACP_SLIDER_HEIGHT'				=> 'Height',
	'ACP_SLIDER_HEIGHT_EXPLAIN'		=> 'The height of a Slider in pixels.',
	'ACP_SLIDER_HEIGHT_INVALID'		=> 'The height must be between 1 and 2000 pixels.',
	'ACP_SLIDER_AUTOPLAY'			=> 'Autoplay',
	'ACP_SLIDER_AUTOPLAY_EXPLAIN'	=> 'Seconds between images when a Slider plays by itself; 0 turns autoplay off.',
	'ACP_SLIDER_AUTOPLAY_INVALID'	=> 'Autoplay must be between 0 and 60 seconds.',
	'ACP_SLIDER_THUMBNAILS'			=> 'Thumbnails',
	'ACP_SLIDER_THUMBNAILS_EXPLAIN'	=> 'Show a strip of clickable thumbnails under a Slider.',
	'ACP_SLIDER_THUMBNAILS_INVALID'	=> 'Choose whether to show thumbnails.',
	'ACP_SLIDER_CAPTION'			=> 'Caption',
	'ACP_SLIDER_CAPTION_EXPLAIN'	=> 'The text under each image.',
	'ACP_SLIDER_CAPTION_INVALID'	=> 'Choose a caption.',
	'ACP_SLIDER_CAPTION_COMMENT'	=> 'Attachment comment',
	'ACP_SLIDER_CAPTION_NAME'		=> 'File name',
	'ACP_SLIDER_CAPTION_NONE'		=> 'None',
	'ACP_SLIDER_PIXELS'				=> 'pixels',
	'ACP_SLIDER_SECONDS'			=> 'seconds',

	'ACP_SLIDER_SETTING_SAVED'		=> 'Slider settings have been saved successfully.',
]);
