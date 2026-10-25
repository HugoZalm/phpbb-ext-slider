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
		];
	}

	private static function ids(array $slides): array
	{
		return array_map(function ($slide) {
			return $slide['attach_id'];
		}, $slides);
	}

	public static function url(int $id): string
	{
		return 'file.php?id=' . $id;
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
		$html = '<p>Day 1</p><image-slider data-ids="2,1"></image-slider><p>end</p>';

		$out = slides::fill($html, $rows, self::EXTENSIONS, 'slider-p7', [self::class, 'url']);

		$this->assertSame(
			'<p>Day 1</p><image-slider class="image-slider">'
			. '<a class="image-slider-slide" href="file.php?id=1" data-lightbox="slider-p7-1" data-title="a.jpg"><img src="file.php?id=1" alt="a.jpg" loading="lazy"></a>'
			. '<a class="image-slider-slide" href="file.php?id=2" data-lightbox="slider-p7-1" data-title="b.jpg"><img src="file.php?id=2" alt="b.jpg" loading="lazy"></a>'
			. '</image-slider><p>end</p>',
			$out
		);
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

		$this->assertStringContainsString('data-title="Tom &amp; Jerry &quot;1&quot;.jpg"', $out);
		$this->assertStringContainsString('alt="Tom &amp; Jerry &quot;1&quot;.jpg"', $out);
	}

	public function test_unescaped_markup_in_filenames_is_escaped(): void
	{
		$rows = [self::row(1, '"><script>x</script>.jpg')];

		$out = slides::fill('<image-slider data-ids="1"></image-slider>', $rows, self::EXTENSIONS, 'g', [self::class, 'url']);

		$this->assertStringNotContainsString('<script>', $out);
		$this->assertStringContainsString('data-title="&quot;&gt;&lt;script&gt;x&lt;/script&gt;.jpg"', $out);
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
}
