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
	'ACP_SLIDER_EXPLAIN'			=> 'De standaard weergave-opties voor Sliders. Het Slider-paneel in de editor begint met deze waarden en schrijft ze in elke Slider-tag die het invoegt, dus wijzigen heeft alleen invloed op nieuwe Sliders. Een met de hand getypte tag zonder een optie gebruikt de waarde van hier.',

	'ACP_SLIDER_DEFAULTS'			=> 'Standaard weergave-opties',
	'ACP_SLIDER_HEIGHT'				=> 'Hoogte',
	'ACP_SLIDER_HEIGHT_EXPLAIN'		=> 'De hoogte van een Slider in pixels.',
	'ACP_SLIDER_HEIGHT_INVALID'		=> 'De hoogte moet tussen 1 en 2000 pixels liggen.',
	'ACP_SLIDER_AUTOPLAY'			=> 'Automatisch afspelen',
	'ACP_SLIDER_AUTOPLAY_EXPLAIN'	=> 'Seconden tussen de afbeeldingen als een Slider vanzelf afspeelt; 0 zet dit uit.',
	'ACP_SLIDER_AUTOPLAY_INVALID'	=> 'Automatisch afspelen moet tussen 0 en 60 seconden liggen.',
	'ACP_SLIDER_THUMBNAILS'			=> 'Miniaturen',
	'ACP_SLIDER_THUMBNAILS_EXPLAIN'	=> 'Toon een strook aanklikbare miniaturen onder een Slider.',
	'ACP_SLIDER_THUMBNAILS_INVALID'	=> 'Kies of miniaturen getoond worden.',
	'ACP_SLIDER_CAPTION'			=> 'Onderschrift',
	'ACP_SLIDER_CAPTION_EXPLAIN'	=> 'De tekst onder elke afbeelding.',
	'ACP_SLIDER_CAPTION_INVALID'	=> 'Kies een onderschrift.',
	'ACP_SLIDER_CAPTION_COMMENT'	=> 'Opmerking bij de bijlage',
	'ACP_SLIDER_CAPTION_NAME'		=> 'Bestandsnaam',
	'ACP_SLIDER_CAPTION_NONE'		=> 'Geen',
	'ACP_SLIDER_PIXELS'				=> 'pixels',
	'ACP_SLIDER_SECONDS'			=> 'seconden',

	'ACP_SLIDER_SETTING_SAVED'		=> 'De Slider-instellingen zijn opgeslagen.',
]);
