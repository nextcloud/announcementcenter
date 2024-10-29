<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\AnnouncementCenter\Command;

use InvalidArgumentException;
use OCA\AnnouncementCenter\Manager;
use OCA\AnnouncementCenter\Model\AnnouncementDoesNotExistException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RemoveNotifications extends Command {
	public function __construct(
		protected Manager $manager,
		protected LoggerInterface $logger,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setName('announcementcenter:remove-notifications')  # others use minus sign as well
			->setDescription('Remove notifications of announcement by id')
			->addArgument(
				'id',
				InputArgument::REQUIRED,
				'Id of announcement to remove notifications from',
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		try {
			$removeNotId = $this->parseId($input->getArgument('id'));
			$this->manager->getAnnouncement($removeNotId, true);
			$this->manager->removeNotifications($removeNotId);
		} catch (AnnouncementDoesNotExistException) {
			$output->writeln('Announcement with #' . $removeNotId . ' does not exist!');
			return 1;
		} catch (InvalidArgumentException $e) {
			$output->writeln($e->getMessage());
			return 1;
		}
		$output->writeln('Successfully removed notifications from accouncement #' . $removeNotId);
		$this->logger->info('Admin removed notifications from announcement #' . $removeNotId . ' over CLI');
		return 0;
	}

	private function parseId(mixed $value) {
		if (is_numeric($value)) {
			return (int)$value;
		}
		throw new InvalidArgumentException('Id "' . $value . '" is not an integer');
	}
}
