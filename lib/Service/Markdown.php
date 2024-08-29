<?php

namespace OCA\AnnouncementCenter\Service;

use OCA\AnnouncementCenter\Vendor\League\CommonMark\Environment;
use OCA\AnnouncementCenter\Vendor\League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use OCA\AnnouncementCenter\Vendor\League\CommonMark\MarkdownConverter;

class Markdown {
	private MarkdownConverter $converter;

	public function __construct() {
		$environment = Environment::createCommonMarkEnvironment();
		$environment->addExtension(new GithubFlavoredMarkdownExtension());
		$environment->mergeConfig([
			'html_input' => 'escape',
			'allow_unsafe_links' => false,
			'max_nesting_level' => 20
		]);

		$this->converter = new MarkdownConverter($environment);
	}

	public function convert(string $text): string {
		return $this->converter->convertToHtml($text);
	}
}
