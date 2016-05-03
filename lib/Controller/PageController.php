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
use OCP\Notification\IManager as INotificationManager;

class PageController extends Controller {
	/** @var int */
	const PAGE_LIMIT = 5;

	/** @var INotificationManager */
	protected $notificationManager;

	/** @var IJobList */
	protected $jobList;

	/** @var IDBConnection */
	private $connection;

	/** @var IGroupManager */
	private $groupManager;

	/** @var IUserManager */
	private $userManager;

	/** @var IL10N */
	private $l;

	/** @var Manager */
	private $manager;

	/** @var string */
	private $userId;

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
	 * @param string $UserId
	 */
	public function __construct($AppName, IRequest $request, IDBConnection $connection, IGroupManager $groupManager, IUserManager $userManager, IJobList $jobList, INotificationManager $notificationManager, IL10N $l, Manager $manager, $UserId) {
		parent::__construct($AppName, $request);

		$this->connection = $connection;
		$this->groupManager = $groupManager;
		$this->userManager = $userManager;
		$this->jobList = $jobList;
		$this->notificationManager = $notificationManager;
		$this->l = $l;
		$this->manager = $manager;
		$this->userId = $UserId;
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
			];
		}

		return new JSONResponse($announcements);
	}

	/**
	 * @param string $subject
	 * @param string $message
	 * @return JSONResponse
	 */
	public function add($subject, $message) {
		$timeStamp = time();
		try {
			$announcement = $this->manager->announce($subject, $message, $this->userId, $timeStamp);
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
	 * @param int $id
	 * @return Response
	 */
	public function delete($id) {
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
			'is_admin'	=> $this->groupManager->isAdmin($this->userId),
		]);
	}
}
