<?php
/**
 *
 * Slider. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2026 Hugo
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace hugozalm\slider\event;

/**
 * @ignore
 */
use hugozalm\slider\slides;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Slider event listener.
 *
 * A Slider tag renders an empty placeholder, because the text formatter knows
 * nothing about the post it renders. Wherever phpBB shows a post together with
 * its attachments (topic, topic review, posting preview), the placeholder is
 * filled with the post's Slides. Elsewhere it stays empty and shows nothing.
 */
class main_listener implements EventSubscriberInterface
{
	/** @var \phpbb\template\template */
	protected $template;

	/** @var \phpbb\language\language */
	protected $language;

	/** @var \phpbb\cache\service */
	protected $cache;

	/** @var \phpbb\db\driver\driver_interface */
	protected $db;

	/** @var \phpbb\config\config */
	protected $config;

	/** @var string */
	protected $root_path;

	/** @var string */
	protected $php_ext;

	/** @var array Attachment rows of the posts on the page, by post id */
	protected $attachments = [];

	/**
	 * Constructor
	 *
	 * @param \phpbb\template\template			$template	Template object
	 * @param \phpbb\language\language			$language	Language object
	 * @param \phpbb\cache\service				$cache		Cache service
	 * @param \phpbb\db\driver\driver_interface	$db			Database connection
	 * @param \phpbb\config\config				$config		Config object
	 * @param string							$root_path	phpBB root path
	 * @param string							$php_ext	PHP file extension
	 */
	public function __construct(\phpbb\template\template $template, \phpbb\language\language $language, \phpbb\cache\service $cache, \phpbb\db\driver\driver_interface $db, \phpbb\config\config $config, $root_path, $php_ext)
	{
		$this->template = $template;
		$this->language = $language;
		$this->cache = $cache;
		$this->db = $db;
		$this->config = $config;
		$this->root_path = $root_path;
		$this->php_ext = $php_ext;
	}

	/**
	 * Map phpBB core events to the listener methods that should handle those events
	 *
	 * @return array
	 */
	public static function getSubscribedEvents()
	{
		return [
			'core.text_formatter_s9e_render_before'	=> 'detect_slider_tag',
			'core.viewtopic_modify_post_data'		=> 'remember_attachments',
			'core.viewtopic_modify_post_row'		=> 'fill_viewtopic_post',
			'core.topic_review_modify_post_list'	=> 'remember_attachments',
			'core.topic_review_modify_row'			=> 'fill_topic_review_post',
			'core.posting_modify_template_vars'		=> [['fill_preview'], ['prepare_panel']],
		];
	}

	/**
	 * Make the Slider's script available on pages that render a Slider tag
	 *
	 * Runs for every text the formatter renders, including the posting preview,
	 * so the assets are only requested when a Slider is actually on the page.
	 *
	 * @param \phpbb\event\data	$event	Event object
	 */
	public function detect_slider_tag($event)
	{
		if (!preg_match('#<SLIDER[\s/>]#', $event['xml']))
		{
			return;
		}

		$this->language->add_lang('common', 'hugozalm/slider');

		$config = [
			'messages'	=> [
				'previous'	=> $this->language->lang('SLIDER_PREVIOUS'),
				'next'		=> $this->language->lang('SLIDER_NEXT'),
				'showImage'	=> $this->language->lang('SLIDER_SHOW_IMAGE'),
				'thumbnails'	=> $this->language->lang('SLIDER_THUMBNAILS'),
				'pause'		=> $this->language->lang('SLIDER_PAUSE'),
				'play'		=> $this->language->lang('SLIDER_PLAY'),
			],
		];

		$this->template->assign_vars([
			'S_SLIDER'			=> true,
			'SLIDER_SCRIPT'		=> generate_board_url() . '/ext/hugozalm/slider/assets/js/slider.js',
			'SLIDER_STYLESHEET'	=> generate_board_url() . '/ext/hugozalm/slider/assets/css/slider.css',
			'SLIDER_CONFIG'		=> json_encode($config, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT),
		]);
	}

	/**
	 * Keep the attachment rows of the posts about to be shown
	 *
	 * viewtopic and the topic review fetch them only when the user may download
	 * attachments, so without that permission Sliders stay empty.
	 *
	 * @param \phpbb\event\data	$event	Event object
	 */
	public function remember_attachments($event)
	{
		$this->attachments = $event['attachments'];
	}

	/**
	 * @param \phpbb\event\data	$event	Event object
	 */
	public function fill_viewtopic_post($event)
	{
		$forum_id = (int) $event['topic_data']['forum_id'];

		$this->hide_from_post_attachments($event, $forum_id);
		$this->fill_post_row($event, $forum_id);
	}

	/**
	 * Leave a post's Slides out of its attachment box, like inline attachments
	 *
	 * By now the post's attachments are displayed HTML, not rows, and the box is
	 * assigned from them after this event.
	 *
	 * @param \phpbb\event\data	$event		viewtopic post row event
	 * @param int				$forum_id	The post's forum
	 */
	protected function hide_from_post_attachments($event, $forum_id)
	{
		$post_id = (int) $event['row']['post_id'];
		$attachments = $event['attachments'];
		$post_row = $event['post_row'];

		if (empty($attachments[$post_id]))
		{
			return;
		}

		$shown = $this->shown_ids($post_row['MESSAGE'], $post_id, $forum_id);

		if (!$shown)
		{
			return;
		}

		$attachments[$post_id] = array_filter($attachments[$post_id], function ($html) use ($shown) {
			return !in_array(slides::attachment_id($html), $shown, true);
		});

		$post_row['S_HAS_ATTACHMENTS'] = !empty($attachments[$post_id]);
		$post_row['S_MULTIPLE_ATTACHMENTS'] = count($attachments[$post_id]) > 1;

		$event['attachments'] = $attachments;
		$event['post_row'] = $post_row;
	}

	/**
	 * @param \phpbb\event\data	$event	Event object
	 */
	public function fill_topic_review_post($event)
	{
		$this->fill_post_row($event, (int) $event['forum_id']);
	}

	/**
	 * Fill the Sliders in a post's MESSAGE with the attachments remembered for it
	 *
	 * @param \phpbb\event\data	$event		Event object carrying row and post_row
	 * @param int				$forum_id	The post's forum
	 */
	protected function fill_post_row($event, $forum_id)
	{
		$post_id = (int) $event['row']['post_id'];
		$post_row = $event['post_row'];

		$post_row['MESSAGE'] = $this->fill($post_row['MESSAGE'], $this->remembered_rows($post_id), 'slider-p' . $post_id, $forum_id);

		$event['post_row'] = $post_row;
	}

	/**
	 * @param string	$html		Rendered post text
	 * @param int		$post_id	Post id
	 * @param int		$forum_id	The post's forum
	 * @return int[]	Attach ids the post shows as Slides
	 */
	protected function shown_ids($html, $post_id, $forum_id)
	{
		if (strpos($html, '<image-slider') === false)
		{
			return [];
		}

		return slides::shown_ids($html, $this->remembered_rows($post_id), $this->cache->obtain_attach_extensions($forum_id));
	}

	/**
	 * @param int	$post_id	Post id
	 * @return array	The attachment rows remembered for the post
	 */
	protected function remembered_rows($post_id)
	{
		return isset($this->attachments[$post_id]) ? $this->attachments[$post_id] : [];
	}

	/**
	 * Fill the Sliders in the posting preview with the post's attachments
	 *
	 * The preview is already assigned to the template when this runs, so it is
	 * filled and assigned again.
	 *
	 * @param \phpbb\event\data	$event	Event object
	 */
	public function fill_preview($event)
	{
		if (!$event['preview'])
		{
			return;
		}

		$preview = $this->template->retrieve_var('PREVIEW_MESSAGE');

		if (strpos($preview, '<image-slider') === false)
		{
			return;
		}

		$comments = [];

		foreach ($event['message_parser']->attachment_data as $attachment)
		{
			$comments[(int) $attachment['attach_id']] = $attachment['attach_comment'];
		}

		// The editor's attachment data lacks the extension and thumbnail flag;
		// fetch the rows, but keep the comments as currently being edited.
		$rows = [];

		if ($comments)
		{
			$sql = 'SELECT attach_id, real_filename, extension, in_message, thumbnail
				FROM ' . ATTACHMENTS_TABLE . '
				WHERE ' . $this->db->sql_in_set('attach_id', array_keys($comments));
			$result = $this->db->sql_query($sql);

			while ($row = $this->db->sql_fetchrow($result))
			{
				$row['attach_comment'] = $comments[(int) $row['attach_id']];
				$rows[] = $row;
			}
			$this->db->sql_freeresult($result);
		}

		$extensions = $this->cache->obtain_attach_extensions((int) $event['forum_id']);

		$this->hide_from_preview_attachments(slides::shown_ids($preview, $rows, $extensions));
		$this->template->assign_var('PREVIEW_MESSAGE', $this->fill($preview, $rows, 'slider-preview', (int) $event['forum_id']));
	}

	/**
	 * Give the editor's Slider panel its strings and settings
	 *
	 * The panel's fields start at the board defaults, which it writes into every
	 * tag it inserts.
	 *
	 * @param \phpbb\event\data	$event	Event object
	 */
	public function prepare_panel($event)
	{
		$this->language->add_lang('common', 'hugozalm/slider');

		$image_extensions = [];

		foreach ($this->cache->obtain_attach_extensions((int) $event['forum_id']) as $extension => $data)
		{
			if (is_array($data) && isset($data['display_cat']) && (int) $data['display_cat'] === slides::IMAGE_CATEGORY)
			{
				$image_extensions[] = $extension;
			}
		}

		$defaults = $this->defaults();

		$this->template->assign_vars([
			'SLIDER_PANEL_DOWNLOAD'			=> append_sid($this->root_path . 'download/file.' . $this->php_ext),
			'SLIDER_PANEL_IMAGE_EXTENSIONS'	=> implode(',', $image_extensions),
			'SLIDER_PANEL_HEIGHT'			=> $defaults['height'],
			'SLIDER_PANEL_AUTOPLAY'			=> $defaults['autoplay'],
			'SLIDER_PANEL_THUMBNAILS'		=> $defaults['thumbnails'],
			'SLIDER_PANEL_CAPTION'			=> $defaults['caption'],
		]);
	}

	/**
	 * Remove the Slides from the preview's attachment box, which posting.php has
	 * already assigned as "attachment" template rows of displayed HTML.
	 *
	 * @param int[]	$shown	Attach ids shown as Slides
	 */
	protected function hide_from_preview_attachments(array $shown)
	{
		if (!$shown)
		{
			return;
		}

		$last = $this->template->find_key_index('attachment', true);

		if ($last === false)
		{
			return;
		}

		$remove = [];

		for ($row = 0; $row <= $last; $row++)
		{
			$vars = $this->template->retrieve_block_vars('attachment[' . $row . ']', ['DISPLAY_ATTACHMENT']);

			if (in_array(slides::attachment_id($vars['DISPLAY_ATTACHMENT']), $shown, true))
			{
				$remove[] = $row;
			}
		}

		// Delete back to front so the remaining row numbers stay valid.
		foreach (array_reverse($remove) as $row)
		{
			$this->template->alter_block_array('attachment', [], $row, 'delete');
		}

		if (count($remove) === $last + 1)
		{
			$this->template->assign_var('S_HAS_ATTACHMENTS', false);
		}
	}

	/**
	 * @param string	$html		Rendered post text
	 * @param array		$rows		The post's attachment rows
	 * @param string	$group		Lightbox gallery prefix, unique per post on the page
	 * @param int		$forum_id	Forum id, for the allowed attachment extensions
	 * @return string
	 */
	protected function fill($html, array $rows, $group, $forum_id)
	{
		if (strpos($html, '<image-slider') === false)
		{
			return $html;
		}

		$download = $this->root_path . 'download/file.' . $this->php_ext;

		return slides::fill($html, $rows, $this->cache->obtain_attach_extensions($forum_id), $group, function ($attach_id, $thumbnail) use ($download) {
			return append_sid($download, 'id=' . $attach_id . ($thumbnail ? '&amp;t=1' : ''));
		}, $this->defaults());
	}

	/**
	 * The display options for tags that leave one out: the ACP settings, or the
	 * built-in ones where a setting is missing or invalid.
	 *
	 * @return array	Keyed like slides::DEFAULTS
	 */
	protected function defaults()
	{
		$settings = [];

		foreach (array_keys(slides::DEFAULTS) as $name)
		{
			$settings[$name] = isset($this->config['slider_' . $name]) ? $this->config['slider_' . $name] : '';
		}

		return slides::options($settings, slides::DEFAULTS);
	}
}
