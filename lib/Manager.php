<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\AnnouncementCenter;

use OCA\AnnouncementCenter\Model\Announcement;
use OCA\AnnouncementCenter\Model\AnnouncementDoesNotExistException;
use OCA\AnnouncementCenter\Model\AnnouncementMapper;
use OCA\AnnouncementCenter\Model\Group;
use OCA\AnnouncementCenter\Model\GroupMapper;
use OCA\AnnouncementCenter\Model\NotificationType;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\BackgroundJob\IJobList;
use OCP\Comments\ICommentsManager;
use OCP\DB\Exception;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserSession;
use OCP\Notification\IManager as INotificationManager;

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

	/** @var NotificationType */
	protected $notificationType;

	public function __construct(IConfig $config,
		AnnouncementMapper $announcementMapper,
		GroupMapper $groupMapper,
		IGroupManager $groupManager,
		INotificationManager $notificationManager,
		ICommentsManager $commentsManager,
		IJobList $jobList,
		IUserSession $userSession,
		NotificationType $notificationType) {
		$this->config = $config;
		$this->announcementMapper = $announcementMapper;
		$this->groupMapper = $groupMapper;
		$this->groupManager = $groupManager;
		$this->notificationManager = $notificationManager;
		$this->commentsManager = $commentsManager;
		$this->jobList = $jobList;
		$this->userSession = $userSession;
		$this->notificationType = $notificationType;
	}

	/**
	 * @param string $subject
	 * @param string $message
	 * @param string $plainMessage
	 * @param string $user
	 * @param int $time
	 * @param string[] $groups
	 * @param bool $comments
	 * @return Announcement
	 * @throws \InvalidArgumentException when the subject is empty or invalid
	 */
	public function announce(string $subject, string $message, string $plainMessage, string $user, int $time, array $groups, bool $comments, int $notificationOptions, ?int $scheduledTime = null, ?int $deleteTime = null): Announcement {
		$subject = trim($subject);
		$message = trim($message);
		$plainMessage = trim($plainMessage);
		if (isset($subject[512])) {
			throw new \InvalidArgumentException('Invalid subject', 1);
		}

		if ($subject === '') {
			throw new \InvalidArgumentException('Invalid subject', 2);
		}

		$announcement = new Announcement();
		$announcement->setSubject($subject);
		$announcement->setMessage($message);
		$announcement->setPlainMessage($plainMessage);
		$announcement->setUser($user);
		$announcement->setTime($time);
		$announcement->setAllowComments((int)$comments);
		$announcement->setGroupsEncode($groups);
		$announcement->setScheduleTime($scheduledTime);
		$announcement->setDeleteTime($deleteTime);
		$announcement->setNotTypes($notificationOptions);
		$this->announcementMapper->insert($announcement);

		if (is_null($scheduledTime) || $scheduledTime === 0) {
			$this->publishAnnouncement($announcement);
		}
		return $announcement;
	}

	public function publishAnnouncement(Announcement $announcement) : void {
		$addedGroups = 0;
		$groups = $announcement->getGroupsDecode();
		foreach ($groups as $group) {
			if ($this->groupManager->groupExists($group)) {
				$this->addGroupLink($announcement, $group);
				$addedGroups++;
			}
		}

		if ($addedGroups === 0) {
			$this->addGroupLink($announcement, 'everyone');
		}

		$notificationOptions = $announcement->getNotTypes();
		if ($notificationOptions) {
			$this->jobList->add(NotificationQueueJob::class, [
				'id' => $announcement->getId(),
				'activities' => $this->notificationType->getActivities($notificationOptions),
				'notifications' => $this->notificationType->getNotifications($notificationOptions),
				'emails' => $this->notificationType->getEmail($notificationOptions),
			]);
		}
	}

	protected function addGroupLink(Announcement $announcement, string $gid): void {
		$group = new Group();
		$group->setId($announcement->getId());
		$group->setGroup($gid);

		try {
			$this->groupMapper->insert($group);
		} catch (Exception $e) {
			if ($e->getReason() !== Exception::REASON_UNIQUE_CONSTRAINT_VIOLATION) {
				// If the group already exists, we ignore the error
				throw $e;
			}
		}
	}

	public function delete(int $id): void {
		// Delete notifications
		$notification = $this->notificationManager->createNotification();
		$notification->setApp('announcementcenter')
			->setObject('announcement', (string)$id);
		$this->notificationManager->markProcessed($notification);

		// Delete comments
		$this->commentsManager->deleteCommentsAtObject('announcement', (string)$id);

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
	 * @param int $limit
	 * @return Announcement[]
	 */
	public function getAnnouncements(int $offsetId = 0, int $limit = 7): array {
		$userGroups = $this->getUserGroups();
		$memberOfAdminGroups = array_intersect($this->getAdminGroups(), $userGroups);
		if (!empty($memberOfAdminGroups)) {
			$userGroups = [];
		}

		$announcements = $this->announcementMapper->getAnnouncements($userGroups, $offsetId, $limit);

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
		return $this->commentsManager->getNumberOfCommentsForObject('announcement', (string)$announcement->getId());
	}

	public function hasNotifications(Announcement $announcement): bool {
		$jobMatrix = [
			['id' => $announcement->getId(), 'activities' => true, 'notifications' => true, 'emails' => true],
			['id' => $announcement->getId(), 'activities' => true, 'notifications' => true, 'emails' => false],
			['id' => $announcement->getId(), 'activities' => false, 'notifications' => true, 'emails' => true],
			['id' => $announcement->getId(), 'activities' => false, 'notifications' => true, 'emails' => false],
		];

		foreach ($jobMatrix as $jobArguments) {
			if ($hasJob = $this->jobList->has(NotificationQueueJob::class, $jobArguments)) {
				break;
			}
		}

		if ($hasJob) {
			return true;
		}

		$notification = $this->notificationManager->createNotification();
		$notification->setApp('announcementcenter')
			->setObject('announcement', (string)$announcement->getId());
		return $this->notificationManager->getCount($notification) > 0;
	}

	public function removeNotifications(int $id): void {
		$jobMatrix = [
			['id' => $id, 'activities' => true, 'notifications' => true, 'emails' => true],
			['id' => $id, 'activities' => true, 'notifications' => true, 'emails' => false],
			['id' => $id, 'activities' => false, 'notifications' => true, 'emails' => true],
		];

		$jobArguments = ['id' => $id, 'activities' => false, 'notifications' => true, 'emails' => false];
		if ($this->jobList->has(NotificationQueueJob::class, $jobArguments)) {
			// Delete the current background job as it was only for notifications
			$this->jobList->remove(NotificationQueueJob::class, $jobArguments);
		} else {
			foreach ($jobMatrix as $jobArguments) {
				if ($this->jobList->has(NotificationQueueJob::class, $jobArguments)) {
					// Delete the current background job and add a new one without notifications
					$this->jobList->remove(NotificationQueueJob::class, $jobArguments);
					$jobArguments['notifications'] = false;
					$this->jobList->add(NotificationQueueJob::class, $jobArguments);
					break;
				}
			}
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
