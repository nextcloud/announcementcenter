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

use OCA\AnnouncementCenter\Model\Announcement;
use OCA\AnnouncementCenter\Model\AnnouncementDoesNotExistException;
use OCA\AnnouncementCenter\Model\AnnouncementMapper;
use OCA\AnnouncementCenter\Model\Group;
use OCA\AnnouncementCenter\Model\GroupMapper;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\BackgroundJob\IJobList;
use OCP\Comments\ICommentsManager;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\Notification\IManager as INotificationManager;
use OCP\IUser;
use OCP\IUserSession;

class Manager {

	/** @var IConfig */
	protected $config;

	/** @var AnnouncementMapper */
	protected $announcementMapper;

	/** @var GroupMapper */
	protected $groupMapper;

	/** @var IGroupManager */
	protected $groupManager;

	/** @var INotificationManager */
	protected $notificationManager;

	/** @var ICommentsManager */
	protected $commentsManager;

	/** @var IJobList */
	protected $jobList;

	/** @var IUserSession */
	protected $userSession;

	public function __construct(IConfig $config,
								AnnouncementMapper $announcementMapper,
								GroupMapper $groupMapper,
								IGroupManager $groupManager,
								INotificationManager $notificationManager,
								ICommentsManager $commentsManager,
								IJobList $jobList,
								IUserSession $userSession) {
		$this->config = $config;
		$this->announcementMapper = $announcementMapper;
		$this->groupMapper = $groupMapper;
		$this->groupManager = $groupManager;
		$this->notificationManager = $notificationManager;
		$this->commentsManager = $commentsManager;
		$this->jobList = $jobList;
		$this->userSession = $userSession;
	}

	/**
	 * @param string $subject
	 * @param string $message
	 * @param string $user
	 * @param int $time
	 * @param string[] $groups
	 * @param bool $comments
	 * @return Announcement
	 * @throws \InvalidArgumentException when the subject is empty or invalid
	 */
	public function announce(string $subject, string $message, string $user, int $time, array $groups, bool $comments): Announcement {
		$subject = trim($subject);
		$message = trim($message);
		if (isset($subject[512])) {
			throw new \InvalidArgumentException('Invalid subject', 1);
		}

		if ($subject === '') {
			throw new \InvalidArgumentException('Invalid subject', 2);
		}

		$announcement = new Announcement();
		$announcement->setSubject($subject);
		$announcement->setMessage($message);
		$announcement->setUser($user);
		$announcement->setTime($time);
		$announcement->setAllowComments((int) $comments);
		$this->announcementMapper->insert($announcement);

		$addedGroups = 0;
		foreach ($groups as $group) {
			if ($this->groupManager->groupExists($group)) {
				$this->addGroupLink($announcement, $group);
				$addedGroups++;
			}
		}

		if ($addedGroups === 0) {
			$this->addGroupLink($announcement, 'everyone');
		}

		return $announcement;
	}

	protected function addGroupLink(Announcement $announcement, string $gid): void {
		$group = new Group();
		$group->setId($announcement->getId());
		$group->setGroup($gid);
		$this->groupMapper->insert($group);
	}

	public function delete(int $id): void {
		// Delete notifications
		$notification = $this->notificationManager->createNotification();
		$notification->setApp('announcementcenter')
			->setObject('announcement', (string)$id);
		$this->notificationManager->markProcessed($notification);

		// Delete comments
		$this->commentsManager->deleteCommentsAtObject('announcement', (string) $id);

		$announcement = $this->announcementMapper->getById($id);
		$this->announcementMapper->delete($announcement);
		$this->groupMapper->deleteGroupsForAnnouncement($announcement);
	}

	/**
	 * @param int $id
	 * @param bool $ignorePermissions Permissions are ignored e.g. in background jobs to generate activities etc.
	 * @return Announcement
	 * @throws AnnouncementDoesNotExistException
	 */
	public function getAnnouncement(int $id, bool $ignorePermissions = false): Announcement {
		try {
			$announcement = $this->announcementMapper->getById($id);
		} catch (DoesNotExistException $e) {
			throw new AnnouncementDoesNotExistException();
		}

		if ($ignorePermissions) {
			return $announcement;
		}

		$userGroups = $this->getUserGroups();
		$memberOfAdminGroups = array_intersect($this->getAdminGroups(), $userGroups);
		if (!empty($memberOfAdminGroups)) {
			return $announcement;
		}

		$groups = $this->groupMapper->getGroupsForAnnouncement($announcement);
		$memberOfGroups = array_intersect($groups, $userGroups);

		if (empty($memberOfGroups)) {
			throw new AnnouncementDoesNotExistException();
		}

		return $announcement;
	}

	protected function getUserGroups(): array {
		$user = $this->userSession->getUser();
		if ($user instanceof IUser) {
			$userGroups = $this->groupManager->getUserGroupIds($user);
			$userGroups[] = 'everyone';
		} else {
			$userGroups = ['everyone'];
		}

		return $userGroups;
	}

	/**
	 * @param Announcement $announcement
	 * @return string[]
	 */
	public function getGroups(Announcement $announcement): array {
		return $this->groupMapper->getGroupsForAnnouncement($announcement);
	}

	/**
	 * @param int $offsetId
	 * @return Announcement[]
	 */
	public function getAnnouncements(int $offsetId = 0): array {
		$userGroups = $this->getUserGroups();
		$memberOfAdminGroups = array_intersect($this->getAdminGroups(), $userGroups);
		if (!empty($memberOfAdminGroups)) {
			$userGroups = [];
		}

		$announcements = $this->announcementMapper->getAnnouncements($userGroups, $offsetId);

		return $announcements;
	}

	public function markNotificationRead(int $id): void {
		$user = $this->userSession->getUser();

		if ($user instanceof IUser) {
			$notification = $this->notificationManager->createNotification();
			$notification->setApp('announcementcenter')
				->setUser($user->getUID())
				->setObject('announcement', (string)$id);
			$this->notificationManager->markProcessed($notification);
		}
	}

	public function getNumberOfComments(Announcement $announcement): int {
		return $this->commentsManager->getNumberOfCommentsForObject('announcement', (string) $announcement->getId());
	}

	public function hasNotifications(Announcement $announcement): bool {
		$hasJob = $this->jobList->has(BackgroundJob::class, [
			'id' => $announcement->getId(),
			'activities' => true,
			'notifications' => true,
		]);

		$hasJob = $hasJob || $this->jobList->has(BackgroundJob::class, [
			'id' => $announcement->getId(),
			'activities' => false,
			'notifications' => true,
		]);

		if ($hasJob) {
			return true;
		}

		$notification = $this->notificationManager->createNotification();
		$notification->setApp('announcementcenter')
			->setObject('announcement', (string)$announcement->getId());
		return $this->notificationManager->getCount($notification) > 0;
	}

	public function removeNotifications(int $id): void {
		if ($this->jobList->has(BackgroundJob::class, [
			'id' => $id,
			'activities' => true,
			'notifications' => true,
		])) {
			// Delete the current background job and add a new one without notifications
			$this->jobList->remove(BackgroundJob::class, [
				'id' => $id,
				'activities' => true,
				'notifications' => true,
			]);
			$this->jobList->add(BackgroundJob::class, [
				'id' => $id,
				'activities' => true,
				'notifications' => false,
			]);
		} else {
			$this->jobList->remove(BackgroundJob::class, [
				'id' => $id,
				'activities' => false,
				'notifications' => true,
			]);
		}

		$notification = $this->notificationManager->createNotification();
		$notification->setApp('announcementcenter')
			->setObject('announcement', (string)$id);
		$this->notificationManager->markProcessed($notification);
	}

	/**
	 * Check if the user is in the admin group
	 */
	public function checkIsAdmin(): bool {
		$user = $this->userSession->getUser();

		if ($user instanceof IUser) {
			$groups = $this->getAdminGroups();
			foreach ($groups as $group) {
				if ($this->groupManager->isInGroup($user->getUID(), $group)) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * @return string[]
	 */
	protected function getAdminGroups(): array {
		$adminGroups = $this->config->getAppValue('announcementcenter', 'admin_groups', '["admin"]');
		$adminGroups = json_decode($adminGroups, true);
		return $adminGroups;
	}
}
