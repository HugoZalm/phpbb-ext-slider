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
	'SLIDER_PREVIOUS'			=> 'Vorige afbeelding',
	'SLIDER_NEXT'				=> 'Volgende afbeelding',
	'SLIDER_SHOW_IMAGE'			=> 'Toon afbeelding %d',
	'SLIDER_THUMBNAILS'			=> 'Miniaturen',
	'SLIDER_PAUSE'				=> 'Diavoorstelling pauzeren',
	'SLIDER_PLAY'				=> 'Diavoorstelling afspelen',
	'SLIDER_BUTTON'					=> 'Slider',
	'SLIDER_BUTTON_EXPLAIN'			=> 'Voeg een slider met de afbeeldingen van dit bericht in',
	'SLIDER_BUTTON_EDIT_EXPLAIN'	=> 'Bewerk de slider bij de cursor',
	'SLIDER_PANEL_TITLE'			=> 'Slider invoegen',
	'SLIDER_PANEL_TITLE_EDIT'		=> 'Slider bewerken',
	'SLIDER_PANEL_HINT'				=> 'Vink de afbeeldingen aan die je wilt tonen. Ze verschijnen op bestandsnaam; hernoem bestanden om de volgorde te wijzigen.',
	'SLIDER_PANEL_HINT_EDIT'		=> 'Wijzig de afbeeldingen of opties en klik op Opslaan.',
	'SLIDER_PANEL_SAVE'				=> 'Opslaan',
	'SLIDER_PANEL_SELECT_ALL'		=> 'Alles selecteren',
	'SLIDER_PANEL_EMPTY'			=> 'Er zijn nog geen afbeeldingen aan dit bericht toegevoegd.',
	'SLIDER_PANEL_UPLOAD'			=> 'Afbeeldingen uploaden',
	'SLIDER_PANEL_HEIGHT'			=> 'Hoogte (px)',
	'SLIDER_PANEL_AUTOPLAY'			=> 'Automatisch afspelen',
	'SLIDER_PANEL_AUTOPLAY_SECONDS'	=> 'Seconden tussen afbeeldingen',
	'SLIDER_PANEL_SECONDS'			=> 's',
	'SLIDER_PANEL_THUMBNAILS'		=> 'Miniaturen',
	'SLIDER_PANEL_CAPTION'			=> 'Onderschrift',
	'SLIDER_PANEL_CAPTION_COMMENT'	=> 'Opmerking bij de bijlage',
	'SLIDER_PANEL_CAPTION_NAME'		=> 'Bestandsnaam',
	'SLIDER_PANEL_CAPTION_NONE'		=> 'Geen',
	'SLIDER_PANEL_INSERT'			=> 'Invoegen',
	'SLIDER_PANEL_CLOSE'			=> 'Sluiten',
	'SLIDER_REQUIRES_LIGHTBOX'	=> 'De Slider-extensie heeft de Lightbox-extensie (vse/lightbox) nodig. Schakel eerst Lightbox in.',
]);
