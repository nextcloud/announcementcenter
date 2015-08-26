<?php
/**
 * ownCloud - announcementcenter
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Joas Schilling <nickvergessen@gmx.de>
 * @copyright Joas Schilling 2015
 */

namespace OCA\AnnouncementCenter\Controller;

use OCA\AnnouncementCenter\Manager;
use OCP\Activity\IManager;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Controller;
use OCP\IDBConnection;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserManager;

class PageController extends Controller {
	/** @var int */
	const PAGE_LIMIT = 15;

	/** @var IDBConnection */
	private $connection;

	/** @var IGroupManager */
	private $groupManager;

	/** @var IUserManager */
	private $userManager;

	/** @var IL10N */
	private $l;

	/** @var IURLGenerator */
	private $urlGenerator;

	/** @var Manager */
	private $manager;

	/** @var IManager */
	private $activityManager;

	/** @var string */
	private $userId;

	/**
	 * @param string $AppName
	 * @param IRequest $request
	 * @param IDBConnection $connection
	 * @param IGroupManager $groupManager
	 * @param IUserManager $userManager
	 * @param IManager $activityManager
	 * @param IL10N $l
	 * @param IURLGenerator $urlGenerator
	 * @param Manager $manager
	 * @param string $UserId
	 */
	public function __construct($AppName, IRequest $request, IDBConnection $connection, IGroupManager $groupManager, IUserManager $userManager, IManager $activityManager, IL10N $l, IURLGenerator $urlGenerator, Manager $manager, $UserId){
		parent::__construct($AppName, $request);

		$this->connection = $connection;
		$this->groupManager = $groupManager;
		$this->userManager = $userManager;
		$this->activityManager = $activityManager;
		$this->l = $l;
		$this->urlGenerator = $urlGenerator;
		$this->manager = $manager;
		$this->userId = $UserId;
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @return TemplateResponse
	 */
	public function index() {
		$jsonResponse = $this->get(1);
		return $this->templateResponse('part.content', ['announcements' => $jsonResponse->getData()]);
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @param int $page
	 * @return JSONResponse
	 */
	public function get($page = 1) {
		$queryBuilder = $this->connection->getQueryBuilder();
		$query = $queryBuilder->select('*')
			->from('announcements')
			->orderBy('announcement_time', 'DESC')
			->setFirstResult(self::PAGE_LIMIT * (max(1, $page) - 1))
			->setMaxResults(self::PAGE_LIMIT);
		$result = $query->execute();


		$announcements = [];
		while ($row = $result->fetch()) {
			$user = $this->userManager->get($row['announcement_user']);
			$announcements[] = [
				'author'	=> ($user instanceof IUser) ? $user->getDisplayName() : $row['announcement_user'],
				'author_id'	=> $row['announcement_user'],
				'time'		=> $row['announcement_time'],
				'subject'	=> $row['announcement_subject'],
				'message'	=> str_replace("\n", '<br />', str_replace(['<', '>'], ['&lt;', '&gt;'], $row['announcement_message'])),
			];
		}
		$result->closeCursor();

		return new JSONResponse($announcements);
	}

	/**
	 * @NoCSRFRequired
	 *
	 * @return TemplateResponse
	 */
	public function add() {
		return $this->templateResponse('part.add');
	}

	/**
	 * @param string $subject
	 * @param string $message
	 * @return DataResponse
	 */
	public function addSubmit($subject, $message) {
		$timeStamp = time();
		try {
			$id = $this->manager->announce($subject, $message, $this->userId, $timeStamp);
		} catch (\InvalidArgumentException $e) {
			return new DataResponse(
				['error' => (string) $this->l->t('The subject must not be empty.')],
				Http::STATUS_BAD_REQUEST
			);
		}

		$users = $this->userManager->search('');
		$event = $this->activityManager->generateEvent();
		$event->setApp('announcementcenter')
			->setType('announcementcenter')
			->setAffectedUser($this->userId)
			->setAuthor($this->userId)
			->setTimestamp($timeStamp)
			->setSubject('announcementsubject#' . $id, [$this->userId])
			->setMessage('announcementmessage#' . $id, [$this->userId])
			->setObject('announcement', $id);

		foreach ($users as $user) {
			$event->setAffectedUser($user->getUID());
			$this->activityManager->publish($event);
		}

		return new DataResponse();
	}

	/**
	 * @param string $templateFile
	 * @param array $templateData
	 * @return TemplateResponse
	 */
	protected function templateResponse($templateFile = 'part.content', array $templateData = []) {
		return new TemplateResponse('announcementcenter', 'main', array_merge([
			'user'		=> $this->userId,
			'is_admin'	=> $this->groupManager->isAdmin($this->userId),
			'template'	=> $templateFile,

			'u_add'		=> $this->urlGenerator->linkToRoute('announcementcenter.page.add'),
			'u_index'	=> $this->urlGenerator->linkToRoute('announcementcenter.page.index'),
		], $templateData));
	}
}
