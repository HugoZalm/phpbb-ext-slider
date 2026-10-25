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

class install_slider_bbcode extends \phpbb\db\migration\container_aware_migration
{
	public static function depends_on()
	{
		return ['\phpbb\db\migration\data\v320\v320'];
	}

	public function effectively_installed()
	{
		return (bool) $this->slider_bbcode_id();
	}

	public function update_data()
	{
		return [
			['custom', [[$this, 'add_slider_bbcode']]],
		];
	}

	public function revert_data()
	{
		return [
			['custom', [[$this, 'remove_slider_bbcode']]],
		];
	}

	public function add_slider_bbcode($last_result = null)
	{
		if ($this->slider_bbcode_id())
		{
			return true;
		}

		// The tag renders an empty placeholder; the listener fills it with the
		// post's Slides once the post's attachments are known.
		$sql_ary = [
			'bbcode_id'				=> $this->next_bbcode_id(),
			'bbcode_tag'			=> 'slider',
			'bbcode_match'			=> '[slider={REGEXP=/^\d+(?:,\d+)*$/}]',
			'bbcode_tpl'			=> '<image-slider data-ids="{REGEXP}"></image-slider>',
			'bbcode_helpline'		=> '',
			'display_on_posting'	=> 0,
			'first_pass_match'		=> '/(?!)/',
			'first_pass_replace'	=> '',
			'second_pass_match'		=> '/(?!)/',
			'second_pass_replace'	=> '',
		];

		$this->db->sql_query('INSERT INTO ' . BBCODES_TABLE . ' ' . $this->db->sql_build_array('INSERT', $sql_ary));
		$this->invalidate_bbcode_caches();

		return true;
	}

	public function remove_slider_bbcode($last_result = null)
	{
		$sql = 'DELETE FROM ' . BBCODES_TABLE . "
			WHERE LOWER(bbcode_tag) = 'slider'";
		$this->db->sql_query($sql);
		$this->invalidate_bbcode_caches();

		return true;
	}

	protected function slider_bbcode_id()
	{
		$sql = 'SELECT bbcode_id
			FROM ' . BBCODES_TABLE . "
			WHERE LOWER(bbcode_tag) = 'slider'";
		$result = $this->db->sql_query_limit($sql, 1);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		return $row ? (int) $row['bbcode_id'] : 0;
	}

	protected function next_bbcode_id()
	{
		$sql = 'SELECT MAX(bbcode_id) AS max_bbcode_id
			FROM ' . BBCODES_TABLE;
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		$bbcode_id = ($row && $row['max_bbcode_id']) ? (int) $row['max_bbcode_id'] + 1 : 0;

		// Make sure it is greater than the core bbcode ids, like the ACP does
		if ($bbcode_id <= NUM_CORE_BBCODES)
		{
			$bbcode_id = NUM_CORE_BBCODES + 1;
		}

		return $bbcode_id;
	}

	protected function invalidate_bbcode_caches()
	{
		$this->container->get('cache')->destroy('sql', BBCODES_TABLE);
		$this->container->get('text_formatter.cache')->invalidate();
	}
}
