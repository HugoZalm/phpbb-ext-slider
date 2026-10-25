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
 * Turns the placeholders that Slider tags render into Slides.
 *
 * Free of phpBB dependencies so it can be unit tested: callers pass the post's
 * attachment rows, the board's attachment extensions and a URL builder.
 */
class slides
{
	/** phpBB's ATTACHMENT_CATEGORY_IMAGE */
	const IMAGE_CATEGORY = 1;

	/** What the Slider tag's BBCode template renders */
	const PLACEHOLDER = '#<image-slider data-ids="([\d,]*)"></image-slider>#';

	/**
	 * The attachments a Slider tag shows: the post's own image attachments it
	 * names, by filename (natural, case-insensitive), then by attach id.
	 *
	 * @param string	$ids		Comma-separated attach ids from the tag
	 * @param array		$rows		The post's attachment rows
	 * @param array		$extensions	Attachment extensions, as from obtain_attach_extensions()
	 * @return array	Attachment rows, in display order
	 */
	public static function select($ids, array $rows, array $extensions)
	{
		$wanted = array_flip(array_map('intval', explode(',', $ids)));
		$slides = [];

		foreach ($rows as $row)
		{
			$id = (int) $row['attach_id'];
			$extension = strtolower($row['extension']);

			if (!isset($wanted[$id]) || isset($slides[$id]) || !empty($row['in_message']))
			{
				continue;
			}

			if (!isset($extensions[$extension]['display_cat']) || (int) $extensions[$extension]['display_cat'] !== self::IMAGE_CATEGORY)
			{
				continue;
			}

			$slides[$id] = $row;
		}

		usort($slides, function ($a, $b) {
			return strnatcasecmp(self::filename($a), self::filename($b)) ?: (int) $a['attach_id'] - (int) $b['attach_id'];
		});

		return $slides;
	}

	/**
	 * Replace every Slider placeholder in a rendered post with its Slides, or
	 * remove it when it has none. Each Slider is its own lightbox gallery.
	 *
	 * @param string	$html		Rendered post text
	 * @param array		$rows		The post's attachment rows
	 * @param array		$extensions	Attachment extensions, as from obtain_attach_extensions()
	 * @param string	$group		Lightbox gallery prefix, unique per post on the page
	 * @param callable	$url		Builds an attachment's download URL (HTML-escaped) from its id
	 * @return string
	 */
	public static function fill($html, array $rows, array $extensions, $group, callable $url)
	{
		$count = 0;

		return preg_replace_callback(self::PLACEHOLDER, function ($match) use ($rows, $extensions, $group, $url, &$count) {
			$slides = self::select($match[1], $rows, $extensions);

			if (!$slides)
			{
				return '';
			}

			$gallery = $group . '-' . ++$count;
			$markup = '';

			foreach ($slides as $slide)
			{
				$src = call_user_func($url, (int) $slide['attach_id']);
				$title = htmlspecialchars(self::filename($slide), ENT_QUOTES, 'UTF-8');

				$markup .= '<a class="image-slider-slide" href="' . $src . '" data-lightbox="' . $gallery . '" data-title="' . $title . '">'
					. '<img src="' . $src . '" alt="' . $title . '" loading="lazy"></a>';
			}

			return '<image-slider class="image-slider">' . $markup . '</image-slider>';
		}, $html);
	}

	/**
	 * An attachment's original filename as plain text. phpBB stores it
	 * HTML-escaped, as it came in through the request.
	 *
	 * @param array	$row	Attachment row
	 * @return string
	 */
	protected static function filename(array $row)
	{
		return htmlspecialchars_decode($row['real_filename'], ENT_QUOTES);
	}
}
