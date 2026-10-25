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
	'ACP_SLIDER_TITLE'	=> 'Slider',
	'ACP_SLIDER'		=> 'Slider settings',

	'LOG_ACP_SLIDER_SETTINGS'	=> '<strong>Slider settings updated</strong>',
]);
