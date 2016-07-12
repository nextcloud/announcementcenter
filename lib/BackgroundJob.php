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
use OCP\Activity\IEvent;
use OCP\Activity\IManager;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Notification\IManager as INotificationManager;
use OCP\Notification\INotification;

class BackgroundJob extends QueuedJob {
	/** @var INotificationManager */
	protected $notificationManager;

	/** @var IUserManager */
	private $userManager;

	/** @var IGroupManager */
	private $groupManager;

	/** @var IURLGenerator */
	private $urlGenerator;

	/** @var Manager */
	private $manager;

	/** @var IManager */
	private $activityManager;

	/** @var array */
	protected $notifiedUsers = [];

	/**
	 * @param IUserManager $userManager
	 * @param IGroupManager $groupManager
	 * @param IManager $activityManager
	 * @param INotificationManager $notificationManager
	 * @param IURLGenerator $urlGenerator
	 * @param Manager $manager
	 */
	public function __construct(
		IUserManager $userManager,
		IGroupManager $groupManager,
		IManager $activityManager,
		INotificationManager $notificationManager,
		IURLGenerator $urlGenerator,
		Manager $manager) {
		$this->userManager = $userManager;
		$this->groupManager = $groupManager;
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

		$groups = $this->manager->getGroups($argument['id']);
		$this->createPublicity($announcement['id'], $announcement['author'], $announcement['time'], $groups);
	}

	/**
	 * @param int $id
	 * @param string $authorId
	 * @param int $timeStamp
	 * @param string[] $groups
	 */
	protected function createPublicity($id, $authorId, $timeStamp, array $groups) {
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

		if (in_array('everyone', $groups)) {
			$this->createPublicityEveryone($authorId, $event, $notification);
		} else {
			$this->createPublicityGroups($authorId, $event, $notification, $groups);
		}
	}

	/**
	 * @param string $authorId
	 * @param IEvent $event
	 * @param INotification $notification
	 */
	protected function createPublicityEveryone($authorId, IEvent $event, INotification $notification) {
		$this->userManager->callForAllUsers(function(IUser $user) use ($authorId, $event, $notification) {
			$event->setAffectedUser($user->getUID());
			$this->activityManager->publish($event);

			if ($authorId !== $user->getUID()) {
				$notification->setUser($user->getUID());
				$this->notificationManager->notify($notification);
			}
		});
	}

	/**
	 * @param string $authorId
	 * @param IEvent $event
	 * @param INotification $notification
	 * @param string[] $groups
	 */
	protected function createPublicityGroups($authorId, IEvent $event, INotification $notification, array $groups) {
		foreach ($groups as $gid) {
			$group = $this->groupManager->get($gid);
			if (!($group instanceof IGroup)) {
				continue;
			}

			$users = $group->getUsers();
			foreach ($users as $user) {
				$uid = $user->getUID();
				if (isset($this->notifiedUsers[$uid])) {
					continue;
				}

				$event->setAffectedUser($uid);
				$this->activityManager->publish($event);

				if ($authorId !== $uid) {
					$notification->setUser($uid);
					$this->notificationManager->notify($notification);
				}

				$this->notifiedUsers[$uid] = true;
			}
		}
	}
}
