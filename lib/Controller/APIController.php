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

namespace OCA\AnnouncementCenter\Controller;

use InvalidArgumentException;
use OCA\AnnouncementCenter\BackgroundJob;
use OCA\AnnouncementCenter\Manager;
use OCA\AnnouncementCenter\Model\Announcement;
use OCA\AnnouncementCenter\Model\AnnouncementDoesNotExistException;
use OCA\Text\Event\LoadEditor;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCSController;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJobList;
use OCP\DB\Exception;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;

class APIController extends OCSController
{
	protected IJobList $jobList;
	protected IGroupManager $groupManager;
	protected IUserManager $userManager;
	protected IL10N $l;
	protected Manager $manager;
	protected ITimeFactory $timeFactory;
	protected IUserSession $userSession;
	protected LoggerInterface $logger;

	public function __construct(
		string $appName,
		IRequest $request,
		IGroupManager $groupManager,
		IUserManager $userManager,
		IJobList $jobList,
		IL10N $l,
		Manager $manager,
		ITimeFactory $timeFactory,
		IUserSession $userSession,
		LoggerInterface $logger
	) {
		parent::__construct($appName, $request);
		$this->groupManager = $groupManager;
		$this->userManager = $userManager;
		$this->jobList = $jobList;
		$this->l = $l;
		$this->manager = $manager;
		$this->timeFactory = $timeFactory;
		$this->userSession = $userSession;
		$this->logger = $logger;
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @param int $page
	 * @param int $pageSize
	 * @return DataResponse
	 */
	public function get(int $page = 1, int $pageSize = 10): DataResponse
	{
		$announcements = $this->manager->getAnnouncements($page, $pageSize);
		$announcements['data'] = array_map([$this, 'renderAnnouncement'], $announcements['data']);
		return new DataResponse($announcements);
	}
	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @param string $filterKey
	 * @param int $page
	 * @param int $pageSize
	 * @return DataResponse
	 */
	public function search(string $filterKey = '', int $page = 1, int $pageSize = 10): DataResponse
	{
		$announcements = $this->manager->searchAnnouncements($filterKey, $page, $pageSize);
		$announcements['data'] = array_map([$this, 'renderAnnouncement'], $announcements['data']);
		return new DataResponse($announcements);
	}
	/**
	 * @NoAdminRequired
	 *
	 * @param string $subject
	 * @param string $message
	 * @param string $plainMessage
	 * @param string[] $groups,
	 * @param bool $activities
	 * @param bool $notifications
	 * @param bool $emails
	 * @param bool $comments
	 * @return DataResponse
	 */
	public function add(string $subject, string $message, string $plainMessage, array $groups, bool $activities, bool $notifications, bool $emails, bool $comments): DataResponse
	{
		if (!$this->manager->checkIsAdmin()) {
			return new DataResponse(
				['message' => 'Logged in user must be an admin'],
				Http::STATUS_FORBIDDEN
			);
		}
		$user = $this->userSession->getUser();
		$userId = $user instanceof IUser ? $user->getUID() : '';

		try {
			$announcement = $this->manager->announce($subject, $message, $plainMessage, $userId, $this->timeFactory->getTime(), $groups, $comments);
		} catch (InvalidArgumentException $e) {
			return new DataResponse(
				['error' => $this->l->t('The subject is too long or empty')],
				Http::STATUS_BAD_REQUEST
			);
		}

		if ($activities || $notifications || $emails) {
			$this->jobList->add(BackgroundJob::class, [
				'id' => $announcement->getId(),
				'activities' => $activities,
				'notifications' => $notifications,
				'emails' => $emails,
			]);
		}

		$this->logger->info('Admin ' . $userId . ' posted a new announcement: "' . $announcement->getSubject() . '"');

		return new DataResponse($this->renderAnnouncement($announcement));
	}

	/**
	 * @param int $id
	 * @param string|null $subject
	 * @param string|null $message
	 * @param string|null $plainMessage
	 * @return DataResponse
	 * @throws AnnouncementDoesNotExistException
	 * @throws Exception
	 */
	public function update(int $id, string $subject = null, string $message = null, string $plainMessage = null): DataResponse
	{
		$announcement = $this->manager->getAnnouncement($id);
		if ($this->userSession->getUser()->getUID() != $announcement->getUser()) {
			return new DataResponse(
				['message' => 'Logged in user must be an author'],
				Http::STATUS_FORBIDDEN
			);
		}
		if ($subject != null)
			$announcement->setSubject(trim($subject));
		if ($message != null)
			$announcement->setMessage(trim($message));
		if ($plainMessage != null)
			$announcement->setPlainMessage(trim($plainMessage));
		$this->logger->warning('announcemnt:' . $announcement->getId());
		$result = $this->manager->updateAnnouncement($announcement);
		return new DataResponse($this->renderAnnouncement($result));
	}
	protected function renderAnnouncement(Announcement $announcement): array
	{
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
		];

		if ($this->manager->checkIsAdmin()) {
			$groupIds = $this->manager->getGroups($announcement);
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

	/**
	 * @NoAdminRequired
	 *
	 * @param int $id
	 * @return DataResponse
	 */
	public function delete(int $id): DataResponse
	{
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
		} catch (AnnouncementDoesNotExistException $e) {
		}

		return new DataResponse();
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param int $id
	 * @return DataResponse
	 */
	public function removeNotifications(int $id): DataResponse
	{
		if (!$this->manager->checkIsAdmin()) {
			return new DataResponse(
				['message' => 'Logged in user must be an admin'],
				Http::STATUS_FORBIDDEN
			);
		}

		$this->manager->removeNotifications($id);

		return new DataResponse();
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param string $search
	 * @return DataResponse
	 */
	public function searchGroups(string $search): DataResponse
	{
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
