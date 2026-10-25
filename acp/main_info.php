<?php
/**
 *
 * Slider. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2026 Hugo
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace hugozalm\slider\acp;

/**
 * Slider ACP module info.
 */
class main_info
{
	public function module()
	{
		return [
			'filename'	=> '\hugozalm\slider\acp\main_module',
			'title'		=> 'ACP_SLIDER_TITLE',
			'modes'		=> [
				'settings'	=> [
					'title'	=> 'ACP_SLIDER',
					'auth'	=> 'ext_hugozalm/slider && acl_a_board',
					'cat'	=> ['ACP_SLIDER_TITLE'],
				],
			],
		];
	}
}
