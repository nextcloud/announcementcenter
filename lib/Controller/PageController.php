<?php
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
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Controller;
use OCP\BackgroundJob\IJobList;
use OCP\IDBConnection;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\Notification\IManager as INotificationManager;

class PageController extends Controller {
	/** @var int */
	const PAGE_LIMIT = 5;

	/** @var INotificationManager */
	protected $notificationManager;

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

	/** @var IUserSession */
	protected $userSession;

	/**
	 * @param string $AppName
	 * @param IRequest $request
	 * @param IDBConnection $connection
	 * @param IGroupManager $groupManager
	 * @param IUserManager $userManager
	 * @param IJobList $jobList
	 * @param INotificationManager $notificationManager
	 * @param IL10N $l
	 * @param Manager $manager
	 * @param IUserSession $userSession
	 */
	public function __construct($AppName, IRequest $request, IDBConnection $connection, IGroupManager $groupManager, IUserManager $userManager, IJobList $jobList, INotificationManager $notificationManager, IL10N $l, Manager $manager, IUserSession $userSession) {
		parent::__construct($AppName, $request);

		$this->connection = $connection;
		$this->groupManager = $groupManager;
		$this->userManager = $userManager;
		$this->jobList = $jobList;
		$this->notificationManager = $notificationManager;
		$this->l = $l;
		$this->manager = $manager;
		$this->userSession = $userSession;
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @param int $offset
	 * @return JSONResponse
	 */
	public function get($offset = 0) {
		$rows = $this->manager->getAnnouncements(self::PAGE_LIMIT, $offset);

		$announcements = [];
		foreach ($rows as $row) {
			$displayName = $row['author'];
			$user = $this->userManager->get($displayName);
			if ($user instanceof IUser) {
				$displayName = $user->getDisplayName();
			}

			$announcements[] = [
				'id'		=> $row['id'],
				'author'	=> $displayName,
				'author_id'	=> $row['author'],
				'time'		=> $row['time'],
				'subject'	=> $row['subject'],
				'message'	=> $row['message'],
				'groups'	=> $row['groups'],
			];
		}

		return new JSONResponse($announcements);
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param string $subject
	 * @param string $message
	 * @param string[] $groups
	 * @return JSONResponse
	 */
	public function add($subject, $message, array $groups) {
		if (!$this->manager->checkIsAdmin()) {
			return new JSONResponse(
				['message' => 'Logged in user must be an admin'],
				Http::STATUS_FORBIDDEN
			);
		}

		$timeStamp = time();
		try {
			$announcement = $this->manager->announce($subject, $message, $this->userSession->getUser()->getUID(), $timeStamp, $groups);
		} catch (\InvalidArgumentException $e) {
			return new JSONResponse(
				['error' => (string)$this->l->t('The subject is too long or empty')],
				Http::STATUS_BAD_REQUEST
			);
		}

		$this->jobList->add('OCA\AnnouncementCenter\BackgroundJob', ['id' => $announcement['id']]);

		$announcement['author_id'] = $announcement['author'];
		$announcement['author'] = $this->userManager->get($announcement['author_id'])->getDisplayName();

		return new JSONResponse($announcement);
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param int $id
	 * @return Response
	 */
	public function delete($id) {
		if (!$this->manager->checkIsAdmin()) {
			return new JSONResponse(
				['message' => 'Logged in user must be an admin'],
				Http::STATUS_FORBIDDEN
			);
		}

		$this->manager->delete($id);

		$notification = $this->notificationManager->createNotification();
		$notification->setApp('announcementcenter')
			->setObject('announcement', $id);
		$this->notificationManager->markProcessed($notification);

		return new Response();
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @return TemplateResponse
	 */
	public function index() {
		return new TemplateResponse('announcementcenter', 'main', [
			'is_admin'	=> $this->manager->checkIsAdmin(),
		]);
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param string $pattern
	 * @return JSONResponse
	 */
	public function searchGroups($pattern) {
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
