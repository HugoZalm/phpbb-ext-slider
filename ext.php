<?php
/**
 *
 * Slider. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2026 Hugo
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace hugozalm\slider;

/**
 * Slider Extension base
 */
class ext extends \phpbb\extension\base
{
	/**
	 * Slides open in the vse/lightbox, so that extension must be enabled first.
	 *
	 * @return bool|string
	 */
	public function is_enableable()
	{
		if ($this->container->get('ext.manager')->is_enabled('vse/lightbox'))
		{
			return true;
		}

		$language = $this->container->get('language');
		$language->add_lang('common', 'hugozalm/slider');

		return $language->lang('SLIDER_REQUIRES_LIGHTBOX');
	}
}
