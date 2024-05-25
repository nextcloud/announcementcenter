<?php

namespace OCA\AnnouncementCenter\Service;

use League\CommonMark\Exception\CommonMarkException;
use League\CommonMark\GithubFlavoredMarkdownConverter;

class Markdown {
	private GithubFlavoredMarkdownConverter $converter;

	public function __construct() {
		$this->converter = new GithubFlavoredMarkdownConverter([
			'allow_unsafe_links' => false
		]);
	}

	/**
	 * @throws CommonMarkException
	 */
	public function convert(string $text): string {
		return $this->converter->convert($text)->getContent();
	}
}
