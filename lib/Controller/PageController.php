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

use OCA\AnnouncementCenter\Manager;
use OCA\AnnouncementCenter\Model\Announcement;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJobList;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use OCA\AnnouncementCenter\BackgroundJob;

class PageController extends Controller {

	/** @var IJobList */
	protected $jobList;

	/** @var IDBConnection */
	protected $connection;

	/** @var IGroupManager */
	protected $groupManager;

	/** @var IUserManager */
	protected $userManager;

	/** @var IL10N */
	protected $l;

	/** @var Manager */
	protected $manager;

	/** @var IConfig */
	protected $config;

	/** @var ITimeFactory */
	protected $timeFactory;

	/** @var IUserSession */
	protected $userSession;

	public function __construct(string $AppName,
								IRequest $request,
								IDBConnection $connection,
								IGroupManager $groupManager,
								IUserManager $userManager,
								IJobList $jobList,
								IL10N $l,
								Manager $manager,
								IConfig $config,
								ITimeFactory $timeFactory,
								IUserSession $userSession) {
		parent::__construct($AppName, $request);

		$this->connection = $connection;
		$this->groupManager = $groupManager;
		$this->userManager = $userManager;
		$this->jobList = $jobList;
		$this->l = $l;
		$this->manager = $manager;
		$this->config = $config;
		$this->timeFactory = $timeFactory;
		$this->userSession = $userSession;
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @param int $offset
	 * @return JSONResponse
	 */
	public function get($offset = 0): JSONResponse {
		$announcements = $this->manager->getAnnouncements($offset);
		$data = array_map([$this, 'renderAnnouncement'], $announcements);
		return new JSONResponse($data);
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param string $subject
	 * @param string $message
	 * @param string[] $groups,
	 * @param bool $activities
	 * @param bool $notifications
	 * @param bool $comments
	 * @return JSONResponse
	 */
	public function add($subject, $message, array $groups, $activities, $notifications, $comments): JSONResponse {
		if (!$this->manager->checkIsAdmin()) {
			return new JSONResponse(
				['message' => 'Logged in user must be an admin'],
				Http::STATUS_FORBIDDEN
			);
		}
		$user = $this->userSession->getUser();
		$userId = $user instanceof IUser ? $user->getUID() : '';

		try {
			$announcement = $this->manager->announce($subject, $message, $userId, $this->timeFactory->getTime(), $groups, $comments);
		} catch (\InvalidArgumentException $e) {
			return new JSONResponse(
				['error' => $this->l->t('The subject is too long or empty')],
				Http::STATUS_BAD_REQUEST
			);
		}

		if ($activities || $notifications) {
			$this->jobList->add(BackgroundJob::class, [
				'id' => $announcement->getId(),
				'activities' => $activities,
				'notifications' => $notifications,
			]);
		}

		return new JSONResponse($this->renderAnnouncement($announcement));
	}

	protected function renderAnnouncement(Announcement $announcement): array {
		$displayName = $announcement->getUser();
		$user = $this->userManager->get($announcement->getUser());
		if ($user instanceof IUser) {
			$displayName = $user->getDisplayName();
		}

		$result = [
			'id'		=> $announcement->getId(),
			'author_id'	=> $announcement->getUser(),
			'author'	=> $displayName,
			'time'		=> $announcement->getTime(),
			'subject'	=> $announcement->getSubject(),
			'message'	=> $announcement->getParsedMessage(),
			'groups'	=> null,
			'comments'	=> $announcement->getAllowComments() ? $this->manager->getNumberOfComments($announcement) : false,
		];

		if ($this->manager->checkIsAdmin()) {
			$result['groups'] = $this->manager->getGroups($announcement);
			$result['notifications'] = $this->manager->hasNotifications($announcement);
		}

		return $result;
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param int $id
	 * @return Response
	 */
	public function delete($id): Response {
		if (!$this->manager->checkIsAdmin()) {
			return new JSONResponse(
				['message' => 'Logged in user must be an admin'],
				Http::STATUS_FORBIDDEN
			);
		}

		$this->manager->delete($id);

		return new Response();
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param int $id
	 * @return Response
	 */
	public function removeNotifications($id): Response {
		if (!$this->manager->checkIsAdmin()) {
			return new JSONResponse(
				['message' => 'Logged in user must be an admin'],
				Http::STATUS_FORBIDDEN
			);
		}

		$this->manager->removeNotifications($id);

		return new Response();
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @param int $announcement
	 * @return TemplateResponse
	 */
	public function index($announcement = 0): TemplateResponse {
		if ($announcement) {
			$this->manager->markNotificationRead($announcement);
		}

		return new TemplateResponse('announcementcenter', 'main', [
			'isAdmin'	=> $this->manager->checkIsAdmin(),
			'createActivities' => $this->config->getAppValue('announcementcenter', 'create_activities', 'yes') === 'yes',
			'createNotifications' => $this->config->getAppValue('announcementcenter', 'create_notifications', 'yes') === 'yes',
			'allowComments' => $this->config->getAppValue('announcementcenter', 'allow_comments', 'yes') === 'yes',
		]);
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param string $pattern
	 * @return JSONResponse
	 */
	public function searchGroups($pattern): JSONResponse {
		if (!$this->manager->checkIsAdmin()) {
			return new JSONResponse(
				['message' => 'Logged in user must be an admin'],
				Http::STATUS_FORBIDDEN
			);
		}

		$groups = $this->groupManager->search($pattern, 10);
		$gids = [];
		foreach ($groups as $group) {
			$gids[] = $group->getGID();
		}

		return new JSONResponse($gids);
	}
}
