<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\AnnouncementCenter\Controller;

use InvalidArgumentException;
use OCA\AnnouncementCenter\Manager;
use OCA\AnnouncementCenter\Model\Announcement;
use OCA\AnnouncementCenter\Model\AnnouncementDoesNotExistException;
use OCA\AnnouncementCenter\Model\NotificationType;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCSController;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;

class APIController extends OCSController {
	public function __construct(
		string $appName,
		IRequest $request,
		protected IGroupManager $groupManager,
		protected IUserManager $userManager,
		protected IL10N $l,
		protected Manager $manager,
		protected ITimeFactory $timeFactory,
		protected IUserSession $userSession,
		protected NotificationType $notificationType,
		protected LoggerInterface $logger,
	) {
		parent::__construct($appName, $request);
	}

	#[NoAdminRequired]
	public function get(int $offset = 0): DataResponse {
		$announcements = $this->manager->getAnnouncements($offset);
		$data = array_map($this->renderAnnouncement(...), $announcements);
		return new DataResponse($data);
	}

	/**
	 * @param list<string> $groups
	 */
	#[NoAdminRequired]
	public function add(string $subject, string $message, string $plainMessage, array $groups, bool $activities, bool $notifications, bool $emails, bool $comments, ?int $scheduleTime = null, ?int $deleteTime = null): DataResponse {
		if (!$this->manager->checkIsAdmin()) {
			return new DataResponse(
				['message' => 'Logged in user must be an admin'],
				Http::STATUS_FORBIDDEN
			);
		}
		$user = $this->userSession->getUser();
		$userId = $user instanceof IUser ? $user->getUID() : '';
		$notificationOptions = $this->notificationType->setNotificationTypes($activities, $notifications, $emails);

		try {
			$announcement = $this->manager->announce($subject, $message, $plainMessage, $userId, $this->timeFactory->getTime(), $groups, $comments, $notificationOptions, $scheduleTime, $deleteTime);
		} catch (InvalidArgumentException) {
			return new DataResponse(
				['error' => $this->l->t('The subject is too long or empty')],
				Http::STATUS_BAD_REQUEST
			);
		}

		$this->logger->info('Admin ' . $userId . ' posted a new announcement: "' . $announcement->getSubject() . '"');

		return new DataResponse($this->renderAnnouncement($announcement));
	}

	protected function renderAnnouncement(Announcement $announcement): array {
		$displayName = $this->userManager->getDisplayName($announcement->getUser()) ?? $announcement->getUser();

		$result = [
			'id' => $announcement->getId(),
			'author_id' => $announcement->getUser(),
			'author' => $displayName,
			'time' => $announcement->getTime(),
			'subject' => $announcement->getParsedSubject(),
			'message' => $announcement->getMessage(),
			'groups' => null,
			'comments' => $announcement->getAllowComments() ? $this->manager->getNumberOfComments($announcement) : false,
			'schedule_time' => $announcement->getScheduleTime(),
			'delete_time' => $announcement->getDeleteTime(),
		];

		if ($this->manager->checkIsAdmin()) {
			if ($announcement->getScheduleTime()) {
				$groupIds = json_decode($announcement->getGroups(), true);
				if (!is_array($groupIds)) {
					$groupIds = [];
				}
			} else {
				$groupIds = $this->manager->getGroups($announcement);
			}
			$groups = [];
			foreach ($groupIds as $groupId) {
				if ($groupId === 'everyone') {
					$groups[] = [
						'id' => 'everyone',
						'name' => 'everyone',
					];
					continue;
				}
				$group = $this->groupManager->get($groupId);
				if (!$group instanceof IGroup) {
					continue;
				}

				$groups[] = [
					'id' => $group->getGID(),
					'name' => $group->getDisplayName(),
				];
			}
			$result['groups'] = $groups;
			$result['notifications'] = $this->manager->hasNotifications($announcement);
		}

		return $result;
	}

	#[NoAdminRequired]
	public function delete(int $id): DataResponse {
		if (!$this->manager->checkIsAdmin()) {
			return new DataResponse(
				['message' => 'Logged in user must be an admin'],
				Http::STATUS_FORBIDDEN
			);
		}

		try {
			$announcement = $this->manager->getAnnouncement($id);

			$this->manager->delete($id);

			$user = $this->userSession->getUser();
			$userId = $user instanceof IUser ? $user->getUID() : '';

			$this->logger->info('Admin ' . $userId . ' deleted announcement: "' . $announcement->getSubject() . '"');
		} catch (AnnouncementDoesNotExistException) {
		}

		return new DataResponse();
	}

	#[NoAdminRequired]
	public function removeNotifications(int $id): DataResponse {
		if (!$this->manager->checkIsAdmin()) {
			return new DataResponse(
				['message' => 'Logged in user must be an admin'],
				Http::STATUS_FORBIDDEN
			);
		}

		$this->manager->removeNotifications($id);

		return new DataResponse();
	}

	#[NoAdminRequired]
	public function searchGroups(string $search): DataResponse {
		if (!$this->manager->checkIsAdmin()) {
			return new DataResponse(
				['message' => 'Logged in user must be an admin'],
				Http::STATUS_FORBIDDEN
			);
		}

		$groups = $this->groupManager->search($search, 25);
		$results = [];
		foreach ($groups as $group) {
			$results[] = [
				'id' => $group->getGID(),
				'label' => $group->getDisplayName(),
			];
		}

		return new DataResponse($results);
	}
}
