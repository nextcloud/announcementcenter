<?php

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\AnnouncementCenter\Command;

use OCA\AnnouncementCenter\Manager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Terminal;

class AnnouncementList extends Command {
	protected Manager $manager;
	public function __construct(Manager $manager) {
		parent::__construct();
		$this->manager = $manager;
	}

	protected function configure(): void {
		$this
			->setName('announcementcenter:list')
			->setDescription('List all announcements')
			->addArgument(
				'limit',
				InputArgument::OPTIONAL,
				'Maximal number of announcements listed',
				10,
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$ulimit = $input->getArgument('limit');
		if (!is_numeric($ulimit)) {
			$output->writeln('"' . $ulimit . '" is not numeric');
			return 1;
		}
		$ulimit = intval($ulimit);
		$announcements = $this->manager->getAnnouncements(0, $ulimit + 1);

		// Calculate table size
		$terminal = new Terminal();
		$width = $terminal->getWidth();
		$minimalWidth = 6;
		$minimalWidthText = 10;
		$widthSubject = max($minimalWidthText, intdiv($width - $minimalWidth, 3));
		$widthMessage = max($minimalWidthText, $width - $minimalWidth - $widthSubject);

		$widths = [$minimalWidth - 2, $widthSubject, $widthMessage];
		$text = $this->formatTableRow(['ID', 'Subject', 'Message'], $widths);
		$output->writeln($text);
		$text = $this->formatTableRow(['', '', ''], $widths, '-');
		$output->writeln($text);

		foreach ($announcements as $index => $ann) {
			if ($index === $ulimit) {
				$output->writeln('And more ...');
				break;
			}
			$texts = [$ann->getId(), $ann->getParsedSubject(), $ann->getPlainMessage()];

			$text = $this->formatTableRow($texts, $widths);
			$output->writeln($text);
		}
		return 0;
	}

	private function ellipseAndPadText(string $text, int $width, string $sep = ' '): string {
		$text = str_replace(["\r", "\n"], ' ', $text);
		$text = str_pad($text, $width, $sep, STR_PAD_RIGHT);
		$text = strlen($text) > $width ? substr($text, 0, $width - 2) . ' …' : $text;
		return $text;
	}

	private function formatTableRow(array $texts, array $widths, string $sep = ' '): string {
		$callback = function ($a, $b) use ($sep) {
			return $this->ellipseAndPadText($a, $b, $sep);
		};
		$formattedTexts = array_map(
			$callback,
			$texts,
			$widths
		);
		return implode('|', $formattedTexts);
	}
}
