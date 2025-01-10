<?php

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\AnnouncementCenter\Command;

use InvalidArgumentException;
use OCA\AnnouncementCenter\Manager;
use OCP\AppFramework\Db\DoesNotExistException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AnnouncementDelete extends Command {
	protected Manager $manager;
	protected LoggerInterface $logger;
	public function __construct(Manager $manager, LoggerInterface $logger) {
		parent::__construct();
		$this->manager = $manager;
		$this->logger = $logger;
	}

	protected function configure(): void {
		$this
			->setName('announcementcenter:delete')
			->setDescription('Delete announcement by id')
			->addArgument(
				'id',
				InputArgument::REQUIRED,
				'Id of announcement to delete',
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		try {
			$deleteId = $this->parseId($input->getArgument('id'));
			$this->manager->delete($deleteId);
		} catch (DoesNotExistException) {
			$output->writeln('Announcement with #' . $deleteId . ' does not exist!');
			return 1;
		} catch (InvalidArgumentException $e) {
			$output->writeln($e->getMessage());
			return 1;
		}
		$output->writeln('Successfully deleted #' . $deleteId);
		$this->logger->info('Admin deleted announcement #' . $deleteId . ' over CLI');
		return 0;
	}

	private function parseId(mixed $value) {
		if (is_numeric($value)) {
			return intval($value);
		}
		throw new InvalidArgumentException('Id "' . $value . '" is not an integer');
	}
}
