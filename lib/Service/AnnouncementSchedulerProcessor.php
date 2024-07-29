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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\AnnouncementCenter\Service;

use OCA\AnnouncementCenter\Manager;
use OCA\AnnouncementCenter\Model\AnnouncementMapper;
use OCP\AppFramework\Utility\ITimeFactory;
use Psr\Log\LoggerInterface;

class AnnouncementSchedulerProcessor {
	private AnnouncementMapper $mapper;
	private Manager $manager;
	private ITimeFactory $timeFactory;
	private LoggerInterface $logger;
	/**
	 * Create cron that is fetching the b2share communities api
	 * with dependency injection
	 */
	public function __construct(AnnouncementMapper $mapper, Manager $manager, ITimeFactory $time, LoggerInterface $logger) {
		$this->mapper = $mapper;
		$this->manager = $manager;
		$this->timeFactory = $time;
		$this->logger = $logger;
	}

	public function doCron($argument) {
		$this->logger->debug('Started announcement scheduler');
		//first schedule then delete because e-mails might be send
		$this->scheduleAnnouncements($argument);
		$this->deleteAnnouncements($argument);
		$this->logger->debug('Finished announcement scheduler');
	}

	private function scheduleAnnouncements($argument) {
		$scheduledAnnouncements = $this->mapper->getAnnouncementsScheduled();
		foreach ($scheduledAnnouncements as $ann) {
			if ($ann->getScheduleTime() > $this->timeFactory->getTime()) {
				break;
			} //They are sorted and scheduled in the future
			$this->manager->publishAnnouncement($ann);
			$this->mapper->resetScheduleTimeById($ann->getId());
			$this->logger->info('Posted scheduled announcement: "' . $ann->getSubject() . '"');
		}
	}

	private function deleteAnnouncements($argument) {
		$deleteAnnouncements = $this->mapper->getAnnouncementsScheduledDelete();
		foreach ($deleteAnnouncements as $ann) {
			// don't delete unannounced announcements
			if ($ann->getScheduleTime() && $ann->getScheduleTime() > 0 && $ann->getScheduleTime() > $this->timeFactory->getTime()) {
				continue;
			}
			if ($ann->getDeleteTime() > $this->timeFactory->getTime()) {
				break;
			} //They are sorted and scheduled to be deleted in the future
			$this->logger->info('Deleting expired announcement: "' . $ann->getSubject() . '"');
			$this->manager->delete($ann->getId());
		}
	}
}
