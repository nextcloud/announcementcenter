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
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Controller;
use OCP\IDBConnection;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IURLGenerator;

class PageController extends Controller {
	/** @var int */
	private $pageLimit = 5;

	/** @var IDBConnection */
	private $connection;

	/** @var IGroupManager */
	private $groupManager;

	/** @var IURLGenerator */
	private $urlGenerator;

	/** @var Manager */
	private $manager;

	/** @var string */
	private $userId;

	/**
	 * @param string $AppName
	 * @param IRequest $request
	 * @param IDBConnection $connection
	 * @param IGroupManager $groupManager
	 * @param IURLGenerator $urlGenerator
	 * @param Manager $manager
	 * @param string $UserId
	 */
	public function __construct($AppName, IRequest $request, IDBConnection $connection, IGroupManager $groupManager, IURLGenerator $urlGenerator, Manager $manager, $UserId){
		parent::__construct($AppName, $request);
		$this->connection = $connection;
		$this->groupManager = $groupManager;
		$this->urlGenerator = $urlGenerator;
		$this->manager = $manager;
		$this->userId = $UserId;
	}

	/**
	 * CAUTION: the @Stuff turns off security checks; for this page no admin is
	 *          required and no CSRF check. If you don't know what CSRF is, read
	 *          it up in the docs or you might create a security hole. This is
	 *          basically the only required method to add this exemption, don't
	 *          add it to any other method if you don't exactly know what it does
	 *
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @return TemplateResponse
	 */
	public function index() {
		$query = $this->connection->prepare(
			'SELECT * FROM `*PREFIX*announcements` ORDER BY `announcement_time` DESC',
			$this->pageLimit
		);
		$query->execute();
		$announcements = [];
		while ($row = $query->fetch()) {
			$announcements[] = [
				'author'	=> \OCP\User::getDisplayName($row['announcement_user']),
				'time'		=> \OCP\Template::relative_modified_date($row['announcement_time']),
				'subject'	=> $row['announcement_subject'],
				'message'	=> str_replace("\n", '<br />', str_replace(['<', '>'], ['&lt;', '&gt;'], $row['announcement_message'])),
			];
		}
		return $this->templateResponse('part.content', ['announcements' => $announcements]);
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
		try {
			$id = $this->manager->announce($subject, $message, $this->userId, time());
		} catch (\RuntimeException $e) {
			$l = \OC::$server->getL10N('announcementcenter');
			return new DataResponse(
				['error' => (string) $l->t('The subject must not be empty.')],
				Http::STATUS_BAD_REQUEST
			);
		}

		\OC::$server->getActivityManager()->publishActivity(
			'announcementcenter',
			'announcementsubject#' . $id,
			[$this->userId],
			'announcementmessage#' . $id,
			[$this->userId],
			'', '',
			$this->userId,
			'announcementcenter', \OCP\Activity\IExtension::PRIORITY_MEDIUM
		);

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
