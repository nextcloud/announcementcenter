<?php
/**
 * @author Joas Schilling <nickvergessen@owncloud.com>
 *
 * @copyright Copyright (c) 2016, Joas Schilling <nickvergessen@owncloud.com>
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\AnnouncementCenter;

use OC\BackgroundJob\QueuedJob;
use OCP\Activity\IManager;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Notification\IManager as INotificationManager;

class BackgroundJob extends QueuedJob {
	/** @var INotificationManager */
	protected $notificationManager;

	/** @var IUserManager */
	private $userManager;

	/** @var IURLGenerator */
	private $urlGenerator;

	/** @var Manager */
	private $manager;

	/** @var IManager */
	private $activityManager;

	/**
	 * @param IUserManager $userManager
	 * @param IManager $activityManager
	 * @param INotificationManager $notificationManager
	 * @param IURLGenerator $urlGenerator
	 * @param Manager $manager
	 */
	public function __construct(IUserManager $userManager, IManager $activityManager, INotificationManager $notificationManager, IURLGenerator $urlGenerator, Manager $manager) {
		$this->userManager = $userManager;
		$this->activityManager = $activityManager;
		$this->notificationManager = $notificationManager;
		$this->urlGenerator = $urlGenerator;
		$this->manager = $manager;
	}

	/**
	 * @param array $argument
	 */
	public function run($argument) {
		try {
			$announcement = $this->manager->getAnnouncement($argument['id'], false);
		} catch (\InvalidArgumentException $e) {
			// Announcement was deleted in the meantime, so no need to announce it anymore
			// So we die silently
			return;
		}

		$this->createPublicity($announcement['id'], $announcement['author'], $announcement['time']);
	}

	/**
	 * @param int $id
	 * @param string $authorId
	 * @param int $timeStamp
	 */
	protected function createPublicity($id, $authorId, $timeStamp) {
		$event = $this->activityManager->generateEvent();
		$event->setApp('announcementcenter')
			->setType('announcementcenter')
			->setAuthor($authorId)
			->setTimestamp($timeStamp)
			->setSubject('announcementsubject#' . $id, [$authorId])
			->setMessage('announcementmessage#' . $id, [$authorId])
			->setObject('announcement', $id);

		$dateTime = new \DateTime();
		$dateTime->setTimestamp($timeStamp);

		$notification = $this->notificationManager->createNotification();
		$notification->setApp('announcementcenter')
			->setDateTime($dateTime)
			->setObject('announcement', $id)
			->setSubject('announced', [$authorId])
			->setLink($this->urlGenerator->linkToRoute('announcementcenter.page.index'));

		$this->userManager->callForAllUsers(function(IUser $user) use ($authorId, $event, $notification) {
			$event->setAffectedUser($user->getUID());
			$this->activityManager->publish($event);

			if ($authorId !== $user->getUID()) {
				$notification->setUser($user->getUID());
				$this->notificationManager->notify($notification);
			}
		});
	}
}
