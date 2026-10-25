<?php
/**
 *
 * Slider. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2026 Hugo
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

use hugozalm\slider\slides;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../slides.php';

/**
 * Which attachments a Slider tag shows, in which order, and the markup that
 * replaces the tag's placeholder.
 */
class SlidesTest extends TestCase
{
	private const EXTENSIONS = [
		'jpg'	=> ['display_cat' => slides::IMAGE_CATEGORY],
		'png'	=> ['display_cat' => slides::IMAGE_CATEGORY],
		'gpx'	=> ['display_cat' => 0],
	];

	private static function row(int $id, string $filename, array $extra = []): array
	{
		return $extra + [
			'attach_id'		=> $id,
			'real_filename'	=> $filename,
			'extension'		=> strtolower(pathinfo($filename, PATHINFO_EXTENSION)),
			'in_message'	=> 0,
			'thumbnail'		=> 0,
			'attach_comment'	=> '',
		];
	}

	/** A placeholder as the Slider tag's BBCode template renders it; missing options are empty */
	private static function placeholder(string $ids, array $options = []): string
	{
		$options += ['height' => '', 'autoplay' => '', 'thumbnails' => '', 'caption' => ''];

		return '<image-slider data-ids="' . $ids . '" data-height="' . $options['height'] . '" data-autoplay="' . $options['autoplay']
			. '" data-thumbnails="' . $options['thumbnails'] . '" data-caption="' . $options['caption'] . '"></image-slider>';
	}

	private static function fill_one(array $rows, array $options = [], array $defaults = slides::DEFAULTS): string
	{
		$ids = implode(',', array_column($rows, 'attach_id'));

		return slides::fill(self::placeholder($ids, $options), $rows, self::EXTENSIONS, 'g', [self::class, 'url'], $defaults);
	}

	private static function ids(array $slides): array
	{
		return array_map(function ($slide) {
			return $slide['attach_id'];
		}, $slides);
	}

	public static function url(int $id, bool $thumbnail = false): string
	{
		return 'file.php?id=' . $id . ($thumbnail ? '&amp;t=1' : '');
	}

	public function test_slides_are_ordered_by_filename_naturally(): void
	{
		$rows = [self::row(1, 'IMG_10.jpg'), self::row(2, 'IMG_9.jpg'), self::row(3, 'IMG_100.jpg')];

		$this->assertSame([2, 1, 3], self::ids(slides::select('1,2,3', $rows, self::EXTENSIONS)));
	}

	public function test_order_ignores_case(): void
	{
		$rows = [self::row(1, 'beach.jpg'), self::row(2, 'Alps.jpg'), self::row(3, 'camp.JPG')];

		$this->assertSame([2, 1, 3], self::ids(slides::select('1,2,3', $rows, self::EXTENSIONS)));
	}

	public function test_identical_filenames_fall_back_to_attach_id(): void
	{
		$rows = [self::row(8, 'photo.jpg'), self::row(5, 'photo.jpg')];

		$this->assertSame([5, 8], self::ids(slides::select('8,5', $rows, self::EXTENSIONS)));
	}

	public function test_order_of_ids_in_the_tag_does_not_matter(): void
	{
		$rows = [self::row(1, 'a.jpg'), self::row(2, 'b.jpg')];

		$this->assertSame([1, 2], self::ids(slides::select('2,1', $rows, self::EXTENSIONS)));
	}

	public function test_only_ids_named_in_the_tag_are_shown(): void
	{
		$rows = [self::row(1, 'a.jpg'), self::row(2, 'b.jpg'), self::row(3, 'c.jpg')];

		$this->assertSame([1, 3], self::ids(slides::select('3,1', $rows, self::EXTENSIONS)));
	}

	public function test_ids_of_other_posts_are_dropped(): void
	{
		$rows = [self::row(1, 'a.jpg')];

		$this->assertSame([1], self::ids(slides::select('1,99', $rows, self::EXTENSIONS)));
	}

	public function test_non_image_attachments_are_dropped(): void
	{
		$rows = [self::row(1, 'a.jpg'), self::row(2, 'route.gpx'), self::row(3, 'x.exe')];

		$this->assertSame([1], self::ids(slides::select('1,2,3', $rows, self::EXTENSIONS)));
	}

	public function test_private_message_attachments_are_dropped(): void
	{
		$rows = [self::row(1, 'a.jpg', ['in_message' => 1]), self::row(2, 'b.jpg')];

		$this->assertSame([2], self::ids(slides::select('1,2', $rows, self::EXTENSIONS)));
	}

	public function test_duplicate_ids_show_once(): void
	{
		$rows = [self::row(1, 'a.jpg')];

		$this->assertSame([1], self::ids(slides::select('1,1', $rows, self::EXTENSIONS)));
	}

	public function test_no_valid_ids_gives_no_slides(): void
	{
		$this->assertSame([], slides::select('5,6', [], self::EXTENSIONS));
	}

	public function test_fill_replaces_the_placeholder_with_slides(): void
	{
		$rows = [self::row(2, 'b.jpg'), self::row(1, 'a.jpg')];
		$html = '<p>Day 1</p>' . self::placeholder('2,1') . '<p>end</p>';

		$out = slides::fill($html, $rows, self::EXTENSIONS, 'slider-p7', [self::class, 'url']);

		$this->assertSame(
			'<p>Day 1</p><image-slider class="image-slider" style="--image-slider-height:400px" data-thumbnails="1">'
			. '<a class="image-slider-slide" href="file.php?id=1" data-lightbox="slider-p7-1" data-title="a.jpg" data-thumb="file.php?id=1"><img src="file.php?id=1" alt="a.jpg" loading="lazy"></a>'
			. '<a class="image-slider-slide" href="file.php?id=2" data-lightbox="slider-p7-1" data-title="b.jpg" data-thumb="file.php?id=2"><img src="file.php?id=2" alt="b.jpg" loading="lazy"></a>'
			. '</image-slider><p>end</p>',
			$out
		);
	}

	// --- Options -----------------------------------------------------------------

	public function test_options_from_the_tag(): void
	{
		$this->assertSame(
			['height' => 300, 'autoplay' => 4, 'thumbnails' => 0, 'caption' => 'name'],
			slides::options(['height' => '300', 'autoplay' => '4', 'thumbnails' => '0', 'caption' => 'name'], slides::DEFAULTS)
		);
	}

	public function test_missing_options_use_the_defaults(): void
	{
		$this->assertSame(slides::DEFAULTS, slides::options(['height' => '', 'autoplay' => '', 'thumbnails' => '', 'caption' => ''], slides::DEFAULTS));
		$this->assertSame(slides::DEFAULTS, slides::options([], slides::DEFAULTS));
	}

	public function test_invalid_options_use_the_defaults(): void
	{
		$this->assertSame(
			slides::DEFAULTS,
			slides::options(['height' => '0', 'autoplay' => '61', 'thumbnails' => '2', 'caption' => 'bogus'], slides::DEFAULTS)
		);
		$this->assertSame(slides::DEFAULTS, slides::options(['height' => '2001', 'autoplay' => 'x', 'thumbnails' => '-1'], slides::DEFAULTS));
	}

	public function test_options_at_the_limits(): void
	{
		$this->assertSame(
			['height' => 2000, 'autoplay' => 60, 'thumbnails' => 1, 'caption' => 'none'],
			slides::options(['height' => '2000', 'autoplay' => '60', 'thumbnails' => '1', 'caption' => 'none'], slides::DEFAULTS)
		);
		$this->assertSame(1, slides::options(['height' => '1'], slides::DEFAULTS)['height']);
	}

	public function test_given_defaults_are_used(): void
	{
		$defaults = ['height' => 250, 'autoplay' => 5, 'thumbnails' => 0, 'caption' => 'none'];

		$this->assertSame($defaults, slides::options([], $defaults));
	}

	public function test_height_sets_the_slider_height(): void
	{
		$out = self::fill_one([self::row(1, 'a.jpg'), self::row(2, 'b.jpg')], ['height' => '300']);

		$this->assertStringContainsString('<image-slider class="image-slider" style="--image-slider-height:300px"', $out);
	}

	public function test_autoplay_is_passed_to_the_slider(): void
	{
		$rows = [self::row(1, 'a.jpg'), self::row(2, 'b.jpg')];

		$this->assertStringContainsString(' data-autoplay="4"', self::fill_one($rows, ['autoplay' => '4']));
		$this->assertStringNotContainsString('data-autoplay', self::fill_one($rows, ['autoplay' => '0']));
	}

	public function test_thumbnails_are_passed_to_the_slider(): void
	{
		$rows = [self::row(1, 'a.jpg'), self::row(2, 'b.jpg')];

		$this->assertStringContainsString(' data-thumbnails="1"', self::fill_one($rows, ['thumbnails' => '1']));
		$this->assertStringNotContainsString('data-thumbnails', self::fill_one($rows, ['thumbnails' => '0']));
	}

	public function test_a_single_slide_has_no_autoplay_or_thumbnails(): void
	{
		$out = self::fill_one([self::row(1, 'a.jpg')], ['autoplay' => '4', 'thumbnails' => '1']);

		$this->assertStringNotContainsString('data-autoplay', $out);
		$this->assertStringNotContainsString('data-thumbnails', $out);
	}

	public function test_slides_use_phpbb_thumbnails_when_there_are_any(): void
	{
		$out = self::fill_one([self::row(1, 'a.jpg', ['thumbnail' => 1]), self::row(2, 'b.jpg')]);

		$this->assertStringContainsString('href="file.php?id=1" data-lightbox="g-1" data-title="a.jpg" data-thumb="file.php?id=1&amp;t=1"', $out);
		$this->assertStringContainsString('data-title="b.jpg" data-thumb="file.php?id=2"', $out);
	}

	public function test_caption_name_shows_the_filename_without_extension(): void
	{
		$out = self::fill_one([self::row(1, 'Tom &amp; Jerry.beach.jpg')], ['caption' => 'name']);

		$this->assertStringContainsString('<span class="image-slider-caption">Tom &amp; Jerry.beach</span></a>', $out);
	}

	public function test_caption_comment_shows_the_attachment_comment(): void
	{
		$out = self::fill_one([self::row(1, 'a.jpg', ['attach_comment' => 'Col du &lt;Galibier&gt; &amp; more'])], ['caption' => 'comment']);

		$this->assertStringContainsString('<span class="image-slider-caption">Col du &lt;Galibier&gt; &amp; more</span></a>', $out);
	}

	public function test_caption_comment_without_a_comment_shows_nothing(): void
	{
		$this->assertStringNotContainsString('image-slider-caption', self::fill_one([self::row(1, 'a.jpg')], ['caption' => 'comment']));
	}

	public function test_caption_none_shows_nothing(): void
	{
		$out = self::fill_one([self::row(1, 'a.jpg', ['attach_comment' => 'x'])], ['caption' => 'none']);

		$this->assertStringNotContainsString('image-slider-caption', $out);
	}

	public function test_two_sliders_keep_their_own_options(): void
	{
		$rows = [self::row(1, 'a.jpg'), self::row(2, 'b.jpg'), self::row(3, 'c.jpg'), self::row(4, 'd.jpg')];
		$html = self::placeholder('1,2', ['height' => '200', 'autoplay' => '3']) . self::placeholder('3,4', ['height' => '500', 'thumbnails' => '0']);

		$out = slides::fill($html, $rows, self::EXTENSIONS, 'g', [self::class, 'url']);

		$this->assertStringContainsString('style="--image-slider-height:200px" data-autoplay="3" data-thumbnails="1"', $out);
		$this->assertStringContainsString('style="--image-slider-height:500px">', $out);
	}

	public function test_shown_ids_read_placeholders_with_options(): void
	{
		$rows = [self::row(1, 'a.jpg'), self::row(2, 'b.jpg')];

		$this->assertSame([2], slides::shown_ids(self::placeholder('2', ['height' => '300', 'caption' => 'name']), $rows, self::EXTENSIONS));
	}

	public function test_each_slider_gets_its_own_lightbox_group(): void
	{
		$rows = [self::row(1, 'a.jpg'), self::row(2, 'b.jpg')];
		$html = '<image-slider data-ids="1"></image-slider><image-slider data-ids="2"></image-slider>';

		$out = slides::fill($html, $rows, self::EXTENSIONS, 'slider-p7', [self::class, 'url']);

		$this->assertStringContainsString('data-lightbox="slider-p7-1" data-title="a.jpg"', $out);
		$this->assertStringContainsString('data-lightbox="slider-p7-2" data-title="b.jpg"', $out);
	}

	public function test_a_slider_without_valid_slides_is_removed(): void
	{
		$html = '<p>a</p><image-slider data-ids="99"></image-slider><p>b</p>';

		$this->assertSame('<p>a</p><p>b</p>', slides::fill($html, [], self::EXTENSIONS, 'slider-p7', [self::class, 'url']));
	}

	public function test_stored_filenames_are_not_escaped_twice(): void
	{
		// phpBB stores real_filename HTML-escaped, as it arrived through the request.
		$rows = [self::row(1, 'Tom &amp; Jerry &quot;1&quot;.jpg')];

		$out = slides::fill('<image-slider data-ids="1"></image-slider>', $rows, self::EXTENSIONS, 'g', [self::class, 'url']);

		$this->assertStringContainsString('alt="Tom &amp; Jerry &quot;1&quot;.jpg"', $out);
	}

	public function test_unescaped_markup_in_filenames_is_escaped(): void
	{
		$rows = [self::row(1, '"><script>x</script>.jpg')];

		$out = slides::fill('<image-slider data-ids="1"></image-slider>', $rows, self::EXTENSIONS, 'g', [self::class, 'url']);

		$this->assertStringNotContainsString('<script>', $out);
		$this->assertStringContainsString('alt="&quot;&gt;&lt;script&gt;x&lt;/script&gt;.jpg"', $out);
	}

	public function test_the_lightbox_title_is_escaped_for_html_insertion(): void
	{
		// Lightbox3 puts the decoded data-title into the page with innerHTML, so
		// the attribute holds the title as escaped HTML, escaped once more.
		$rows = [self::row(1, '&lt;img src=x onerror=alert(1)&gt; &amp; co.jpg')];

		$out = slides::fill('<image-slider data-ids="1"></image-slider>', $rows, self::EXTENSIONS, 'g', [self::class, 'url']);

		$this->assertStringContainsString('data-title="&amp;lt;img src=x onerror=alert(1)&amp;gt; &amp;amp; co.jpg"', $out);
	}

	public function test_order_uses_the_unescaped_filename(): void
	{
		// '"' sorts before '#', but its stored form "&quot;" would sort after it.
		$rows = [self::row(1, '#a.jpg'), self::row(2, '&quot;b.jpg')];

		$this->assertSame([2, 1], self::ids(slides::select('1,2', $rows, self::EXTENSIONS)));
	}

	public function test_text_without_a_slider_is_unchanged(): void
	{
		$html = '<p>no slider here</p>';

		$this->assertSame($html, slides::fill($html, [], self::EXTENSIONS, 'g', [self::class, 'url']));
	}

	public function test_shown_ids_are_the_slides_of_every_slider(): void
	{
		$rows = [self::row(1, 'a.jpg'), self::row(2, 'b.jpg'), self::row(3, 'c.jpg')];
		$html = '<image-slider data-ids="3,1"></image-slider><p>x</p><image-slider data-ids="1"></image-slider>';

		$shown = slides::shown_ids($html, $rows, self::EXTENSIONS);
		sort($shown);

		$this->assertSame([1, 3], $shown);
	}

	public function test_ids_not_shown_as_slides_are_not_shown_ids(): void
	{
		$rows = [self::row(1, 'a.jpg'), self::row(2, 'route.gpx')];
		$html = '<image-slider data-ids="1,2,99"></image-slider>';

		$this->assertSame([1], slides::shown_ids($html, $rows, self::EXTENSIONS));
	}

	public function test_text_without_a_slider_shows_no_ids(): void
	{
		$this->assertSame([], slides::shown_ids('<p>none</p>', [self::row(1, 'a.jpg')], self::EXTENSIONS));
	}

	public function test_attachment_id_of_an_inline_image(): void
	{
		$html = '<dl class="file"><dt class="attach-image"><img src="./download/file.php?id=37&amp;sid=abc" class="postimage" alt="01.jpeg" /></dt></dl>';

		$this->assertSame(37, slides::attachment_id($html));
	}

	public function test_attachment_id_of_a_thumbnail(): void
	{
		$html = '<dl class="thumbnail"><dt><a href="./download/file.php?id=370&amp;mode=view"><img src="./download/file.php?id=370&amp;t=1" class="postimage" /></a></dt></dl>';

		$this->assertSame(370, slides::attachment_id($html));
	}

	public function test_attachment_id_of_a_file_link(): void
	{
		$html = '<dl class="file"><dt><a class="postlink" href="./download/file.php?id=46">mar_lvl.gpx</a></dt></dl>';

		$this->assertSame(46, slides::attachment_id($html));
	}

	public function test_attachment_id_without_a_download_link(): void
	{
		$this->assertNull(slides::attachment_id('<span>denied</span>'));
	}
}
