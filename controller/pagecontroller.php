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
	 * @param int $page
	 * @return JSONResponse
	 */
	public function get($page = 1) {
		$rows = $this->manager->getAnnouncements(self::PAGE_LIMIT, self::PAGE_LIMIT * (max(1, $page) - 1));

		$announcements = [];
		foreach ($rows as $row) {
			$displayName = $row['author'];
			$user = $this->userManager->get($displayName);
			if ($user instanceof IUser) {
				$displayName = $user->getDisplayName();
			}

			$announcements[] = [
				'author'	=> $displayName,
				'author_id'	=> $row['author'],
				'time'		=> $row['time'],
				'subject'	=> $this->parseSubject($row['subject']),
				'message'	=> $this->parseMessage($row['message']),
			];
		}

		return new JSONResponse($announcements);
	}

	/**
	 * @param string $message
	 * @return string
	 */
	protected function parseMessage($message) {
		return str_replace("\n", '<br />', str_replace(['<', '>'], ['&lt;', '&gt;'], $message));
	}

	/**
	 * @param string $subject
	 * @return string
	 */
	protected function parseSubject($subject) {
		return str_replace(['<', '>'], ['&lt;', '&gt;'], $subject);
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
				['error' => (string)$this->l->t('The subject is too long or empty')],
				Http::STATUS_BAD_REQUEST
			);
		}

		$this->publishActivities($id, $this->userId, $timeStamp);

		return new DataResponse();
	}

	/**
	 * @param int $id
	 * @param string $authorId
	 * @param int $timeStamp
	 */
	protected function publishActivities($id, $authorId, $timeStamp) {
		$users = $this->userManager->search('');
		$event = $this->activityManager->generateEvent();
		$event->setApp('announcementcenter')
			->setType('announcementcenter')
			->setAuthor($authorId)
			->setTimestamp($timeStamp)
			->setSubject('announcementsubject#' . $id, [$authorId])
			->setMessage('announcementmessage#' . $id, [$authorId])
			->setObject('announcement', $id);

		foreach ($users as $user) {
			$event->setAffectedUser($user->getUID());
			$this->activityManager->publish($event);
		}
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @return TemplateResponse
	 */
	public function index() {
		return new TemplateResponse('announcementcenter', 'main', [
			'user'		=> $this->userId,
			'is_admin'	=> $this->groupManager->isAdmin($this->userId),
			'template'	=> '',

			'u_add'		=> $this->urlGenerator->linkToRoute('announcementcenter.page.add'),
			'u_index'	=> $this->urlGenerator->linkToRoute('announcementcenter.page.index'),
		]);
		$jsonResponse = $this->get(1);
		return $this->templateResponse('part.content', ['announcements' => $jsonResponse->getData()]);
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
