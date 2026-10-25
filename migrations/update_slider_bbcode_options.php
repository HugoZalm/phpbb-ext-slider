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

/**
 * Add the display options to the Slider tag. Each one is optional. The parser
 * drops a value of the wrong type; the listener falls back to the default for
 * a missing or out-of-range value.
 */
class update_slider_bbcode_options extends \phpbb\db\migration\container_aware_migration
{
	const SLIDER_BBCODE_MATCH = '[slider={REGEXP=/^\d+(?:,\d+)*$/} height={UINT1;optional} autoplay={UINT2;optional} thumbnails={UINT3;optional} caption={CHOICE=none,name,comment;optional}]';
	const SLIDER_BBCODE_TPL = '<image-slider data-ids="{REGEXP}" data-height="{UINT1}" data-autoplay="{UINT2}" data-thumbnails="{UINT3}" data-caption="{CHOICE}"></image-slider>';

	const SLIDER_BBCODE_MATCH_PREVIOUS = '[slider={REGEXP=/^\d+(?:,\d+)*$/}]';
	const SLIDER_BBCODE_TPL_PREVIOUS = '<image-slider data-ids="{REGEXP}"></image-slider>';

	public static function depends_on()
	{
		return [
			'\hugozalm\slider\migrations\install_slider_bbcode',
		];
	}

	public function effectively_installed()
	{
		return (bool) strpos($this->slider_bbcode_match(), 'caption');
	}

	public function update_data()
	{
		return [
			['custom', [[$this, 'upgrade_slider_bbcode']]],
		];
	}

	public function revert_data()
	{
		return [
			['custom', [[$this, 'restore_slider_bbcode']]],
		];
	}

	public function upgrade_slider_bbcode($last_result = null)
	{
		$this->set_slider_bbcode(self::SLIDER_BBCODE_MATCH, self::SLIDER_BBCODE_TPL);

		return true;
	}

	public function restore_slider_bbcode($last_result = null)
	{
		$this->set_slider_bbcode(self::SLIDER_BBCODE_MATCH_PREVIOUS, self::SLIDER_BBCODE_TPL_PREVIOUS);

		return true;
	}

	protected function set_slider_bbcode($match, $tpl)
	{
		$sql = 'UPDATE ' . BBCODES_TABLE . '
			SET ' . $this->db->sql_build_array('UPDATE', [
				'bbcode_match'	=> $match,
				'bbcode_tpl'	=> $tpl,
			]) . "
			WHERE LOWER(bbcode_tag) = 'slider'";
		$this->db->sql_query($sql);

		$this->invalidate_bbcode_caches();

		return true;
	}

	protected function slider_bbcode_match()
	{
		$sql = 'SELECT bbcode_match
			FROM ' . BBCODES_TABLE . "
			WHERE LOWER(bbcode_tag) = 'slider'";
		$result = $this->db->sql_query_limit($sql, 1);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		return $row ? (string) $row['bbcode_match'] : '';
	}

	protected function invalidate_bbcode_caches()
	{
		$this->container->get('cache')->destroy('sql', BBCODES_TABLE);
		$this->container->get('text_formatter.cache')->invalidate();
	}
}
