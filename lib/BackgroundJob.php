<?php
declare(strict_types=1);
/**
 * @copyright Copyright (c) 2016, Joas Schilling <coding@schilljs.com>
 *
 * @author Joas Schilling <coding@schilljs.com>
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

namespace OCA\AnnouncementCenter;

use OC\BackgroundJob\QueuedJob;
use OCA\AnnouncementCenter\Model\Announcement;
use OCA\AnnouncementCenter\Model\AnnouncementDoesNotExistException;
use OCP\Activity\IEvent;
use OCP\Activity\IManager as IActivityManager;
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

	/** @var IActivityManager */
	private $activityManager;

	/** @var array */
	protected $notifiedUsers = [];

	public function __construct(
		IUserManager $userManager,
		IGroupManager $groupManager,
		IActivityManager $activityManager,
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
	 * @param array $arguments
	 */
	public function run($arguments) {
		try {
			$announcement = $this->manager->getAnnouncement($arguments['id'], true);
		} catch (AnnouncementDoesNotExistException $e) {
			// Announcement was deleted in the meantime, so no need to announce it anymore
			// So we die silently
			return;
		}

		$this->createPublicity($announcement, $arguments);
	}

	/**
	 * @param Announcement $announcement
	 * @param array $publicity
	 */
	protected function createPublicity(Announcement $announcement, array $publicity) {
		$event = $this->activityManager->generateEvent();
		$event->setApp('announcementcenter')
			->setType('announcementcenter')
			->setAuthor($announcement->getUser())
			->setTimestamp($announcement->getTime())
			->setSubject('announcementsubject', ['author' => $announcement->getUser(), 'announcement' => $announcement->getId()])
			->setMessage('announcementmessage')
			->setObject('announcement', $announcement->getId());

		$dateTime = new \DateTime();
		$dateTime->setTimestamp($announcement->getTime());

		$notification = $this->notificationManager->createNotification();
		$notification->setApp('announcementcenter')
			->setDateTime($dateTime)
			->setObject('announcement', $announcement->getId())
			->setSubject('announced', [$announcement->getUser()]);

		$groups = $this->manager->getGroups($announcement);
		if (\in_array('everyone', $groups, true)) {
			$this->createPublicityEveryone($announcement->getUser(), $event, $notification, $publicity);
		} else {
			$this->createPublicityGroups($announcement->getUser(), $event, $notification, $groups, $publicity);
		}
	}

	/**
	 * @param string $authorId
	 * @param IEvent $event
	 * @param INotification $notification
	 * @param array $publicity
	 */
	protected function createPublicityEveryone(string $authorId, IEvent $event, INotification $notification, array $publicity) {
		$this->userManager->callForSeenUsers(function(IUser $user) use ($authorId, $event, $notification, $publicity) {
			if (!empty($publicity['activities'])) {
				$event->setAffectedUser($user->getUID());
				$this->activityManager->publish($event);
			}

			if (!empty($publicity['notifications']) && $authorId !== $user->getUID()) {
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
	 * @param array $publicity
	 */
	protected function createPublicityGroups($authorId, IEvent $event, INotification $notification, array $groups, array $publicity) {
		foreach ($groups as $gid) {
			$group = $this->groupManager->get($gid);
			if (!($group instanceof IGroup)) {
				continue;
			}

			$users = $group->getUsers();
			foreach ($users as $user) {
				$uid = $user->getUID();
				if (isset($this->notifiedUsers[$uid]) || $user->getLastLogin() === 0) {
					continue;
				}

				if (!empty($publicity['activities'])) {
					$event->setAffectedUser($uid);
					$this->activityManager->publish($event);
				}

				if ($authorId !== $uid && !empty($publicity['notifications'])) {
					$notification->setUser($uid);
					$this->notificationManager->notify($notification);
				}

				$this->notifiedUsers[$uid] = true;
			}
		}
	}
}
