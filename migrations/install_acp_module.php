<?php
/**
 *
 * Slider. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2026 Hugo
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace hugozalm\slider\migrations;

class install_acp_module extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return isset($this->config['slider_height']);
	}

	public static function depends_on()
	{
		return ['\hugozalm\slider\migrations\update_slider_bbcode_options'];
	}

	public function update_data()
	{
		return [
			// Default display options, the same as hugozalm\slider\slides::DEFAULTS
			['config.add', ['slider_height', 400]],
			['config.add', ['slider_autoplay', 0]],
			['config.add', ['slider_thumbnails', 1]],
			['config.add', ['slider_caption', 'comment']],

			// Add a "Slider" category under the Extensions tab ...
			['module.add', [
				'acp',
				'ACP_CAT_DOT_MODS',
				'ACP_SLIDER_TITLE',
			]],
			// ... and the settings module inside it.
			['module.add', [
				'acp',
				'ACP_SLIDER_TITLE',
				[
					'module_basename'	=> '\hugozalm\slider\acp\main_module',
					'modes'				=> ['settings'],
				],
			]],
		];
	}
}
