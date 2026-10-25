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

	/** What the Slider tag's BBCode template renders: the ids, then the options (empty when not given) */
	const PLACEHOLDER = '#<image-slider data-ids="([\d,]*)"((?: data-[a-z]+="[^"]*")*)></image-slider>#';

	/** Display options used when a tag leaves one out or gives an invalid value */
	const DEFAULTS = [
		'height'		=> 400,
		'autoplay'		=> 0,
		'thumbnails'	=> 1,
		'caption'		=> 'comment',
	];

	/** Allowed values per option: [min, max] for numbers, a list for choices */
	const LIMITS = [
		'height'		=> [1, 2000],
		'autoplay'		=> [0, 60],
		'thumbnails'	=> [0, 1],
		'caption'		=> ['none', 'name', 'comment'],
	];

	/**
	 * A Slider's display options: the tag's valid values, the defaults otherwise.
	 *
	 * @param array	$attributes	Option values from the tag, as strings; '' when not given
	 * @param array	$defaults	Values to fall back on, keyed like DEFAULTS
	 * @return array	Keyed like DEFAULTS
	 */
	public static function options(array $attributes, array $defaults)
	{
		$options = [];

		foreach (self::LIMITS as $name => $allowed)
		{
			$value = isset($attributes[$name]) ? (string) $attributes[$name] : '';

			if (!self::is_valid($name, $value))
			{
				$options[$name] = $defaults[$name];
			}
			else
			{
				$options[$name] = is_string($allowed[0]) ? $value : (int) $value;
			}
		}

		return $options;
	}

	/**
	 * The options whose values are missing or outside LIMITS, e.g. in the ACP's
	 * default settings form.
	 *
	 * @param array	$values	Option values keyed like DEFAULTS
	 * @return string[]	Names of the invalid options, in DEFAULTS order
	 */
	public static function invalid_options(array $values)
	{
		$invalid = [];

		foreach (array_keys(self::LIMITS) as $name)
		{
			if (!self::is_valid($name, isset($values[$name]) ? (string) $values[$name] : ''))
			{
				$invalid[] = $name;
			}
		}

		return $invalid;
	}

	/**
	 * @param string	$name	Option name, a key of LIMITS
	 * @param string	$value	Option value as text
	 * @return bool
	 */
	protected static function is_valid($name, $value)
	{
		$allowed = self::LIMITS[$name];

		if (is_string($allowed[0]))
		{
			return in_array($value, $allowed, true);
		}

		return ctype_digit($value) && (int) $value >= $allowed[0] && (int) $value <= $allowed[1];
	}

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
	 * 								and whether the thumbnail is wanted
	 * @param array		$defaults	Display options for options a tag leaves out
	 * @return string
	 */
	public static function fill($html, array $rows, array $extensions, $group, callable $url, array $defaults = self::DEFAULTS)
	{
		$count = 0;

		return preg_replace_callback(self::PLACEHOLDER, function ($match) use ($rows, $extensions, $group, $url, $defaults, &$count) {
			$slides = self::select($match[1], $rows, $extensions);

			if (!$slides)
			{
				return '';
			}

			preg_match_all('#data-([a-z]+)="([^"]*)"#', $match[2], $pairs);
			$options = self::options(array_combine($pairs[1], $pairs[2]), $defaults);

			$gallery = $group . '-' . ++$count;
			$markup = '';

			foreach ($slides as $slide)
			{
				$id = (int) $slide['attach_id'];
				$src = call_user_func($url, $id, false);
				$thumb = empty($slide['thumbnail']) ? $src : call_user_func($url, $id, true);
				$title = self::escape(self::filename($slide));
				$caption = self::caption($slide, $options['caption']);

				// The lightbox inserts the decoded data-title as HTML, so escape it twice.
				$markup .= '<a class="image-slider-slide" href="' . $src . '" data-lightbox="' . $gallery . '" data-title="' . self::escape($title) . '" data-thumb="' . $thumb . '">'
					. '<img src="' . $src . '" alt="' . $title . '" loading="lazy">'
					. ($caption !== '' ? '<span class="image-slider-caption">' . self::escape($caption) . '</span>' : '')
					. '</a>';
			}

			// The component reads these; a single Slide has nothing to step through.
			$attributes = ' style="--image-slider-height:' . $options['height'] . 'px"';

			if (count($slides) > 1)
			{
				$attributes .= $options['autoplay'] ? ' data-autoplay="' . $options['autoplay'] . '"' : '';
				$attributes .= $options['thumbnails'] ? ' data-thumbnails="1"' : '';
			}

			return '<image-slider class="image-slider"' . $attributes . '>' . $markup . '</image-slider>';
		}, $html);
	}

	/**
	 * The caption shown under a Slide.
	 *
	 * @param array		$row	Attachment row
	 * @param string	$type	none, name (filename without extension) or comment
	 * @return string	Plain text, '' for no caption
	 */
	protected static function caption(array $row, $type)
	{
		if ($type === 'name')
		{
			return pathinfo(self::filename($row), PATHINFO_FILENAME);
		}

		if ($type === 'comment' && isset($row['attach_comment']))
		{
			// Stored HTML-escaped, like the filename.
			return trim(htmlspecialchars_decode($row['attach_comment'], ENT_QUOTES));
		}

		return '';
	}

	/**
	 * Escape plain text for HTML.
	 *
	 * @param string	$text	Plain text
	 * @return string	Text safe for HTML content and attribute values
	 */
	protected static function escape($text)
	{
		return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
	}

	/**
	 * The attach ids shown as Slides by the Slider placeholders in a rendered
	 * post. These are left out of the post's attachment box.
	 *
	 * @param string	$html		Rendered post text, before fill()
	 * @param array		$rows		The post's attachment rows
	 * @param array		$extensions	Attachment extensions, as from obtain_attach_extensions()
	 * @return int[]
	 */
	public static function shown_ids($html, array $rows, array $extensions)
	{
		$shown = [];

		if (preg_match_all(self::PLACEHOLDER, $html, $matches))
		{
			foreach ($matches[1] as $ids)
			{
				foreach (self::select($ids, $rows, $extensions) as $slide)
				{
					$shown[(int) $slide['attach_id']] = (int) $slide['attach_id'];
				}
			}
		}

		return array_values($shown);
	}

	/**
	 * The attach id of an attachment as phpBB displays it, from its download link.
	 *
	 * Relies on the style's attachment template linking to download/file.php?id=N,
	 * as prosilver does; without such a link the attachment is never hidden.
	 *
	 * @param string	$html	One displayed attachment
	 * @return int|null
	 */
	public static function attachment_id($html)
	{
		return preg_match('#file\.php\?id=(\d+)#', $html, $match) ? (int) $match[1] : null;
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
