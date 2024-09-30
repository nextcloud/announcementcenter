<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2024 Marvin Winkens <m.winkens@fz-juelich.de>
 *
 * @author Marvin Winkens <m.winkens@fz-juelich.de>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
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
