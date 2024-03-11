<?php

namespace OCA\AnnouncementCenter\Service;

use OCA\AnnouncementCenter\Manager;
use OCA\AnnouncementCenter\Model\AnnouncementMapper;
use OCP\AppFramework\Utility\ITimeFactory;

class AnnouncementSchedulerProcessor {
	private AnnouncementMapper $mapper;
	private Manager $manager;
	private ITimeFactory $timeFactory;
	/**
	 * Create cron that is fetching the b2share communities api
	 * with dependency injection
	 */
	public function __construct(AnnouncementMapper $mapper, Manager $manager, ITimeFactory $time) {
		$this->mapper = $mapper;
		$this->manager = $manager;
		$this->timeFactory = $time;
	}

	public function doCron($argument) {
		//first schedule then delete because e-mails might be send
		$this->scheduleAnnouncements($argument);
		$this->deleteAnnouncements($argument);
	}

	public function scheduleAnnouncements($argument) {
		$scheduledAnnouncements = $this->mapper->getAnnouncementsScheduled();
		foreach ($scheduledAnnouncements as $ann) {
			if ($ann->getScheduledTime() > $this->timeFactory->getTime()) {
				break;
			} //They are sorted and scheduled in the future
			$this->manager->publishAnnouncement($ann);
			$ann->setScheduleTime(0);
		}
	}

	public function deleteAnnouncements($argument) {
		$deleteAnnouncements = $this->mapper->getAnnouncementsScheduledDelete();
		foreach ($deleteAnnouncements as $ann) {
			// don't delete unannounced announcements
			if ($ann->getScheduledTime() && $ann->getScheduledTime() > 0 && $ann->getScheduledTime() > $this->timeFactory->getTime()) {
				continue;
			}
			if ($ann->getDeleteTime() > $this->timeFactory->getTime()) {
				break;
			} //They are sorted and scheduled to be deleted in the future
			$this->manager->delete($ann->getId());
		}
	}
}
