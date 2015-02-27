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

use \OCP\IRequest;
use \OCP\AppFramework\Http\TemplateResponse;
use \OCP\AppFramework\Http\DataResponse;
use \OCP\AppFramework\Controller;

class PageController extends Controller {
	/** @var string */
	private $userId;

	/**
	 * @param string $AppName
	 * @param IRequest $request
	 * @param string $userId
	 */
	public function __construct($AppName, IRequest $request, $userId){
		parent::__construct($AppName, $request);
		$this->userId = $userId;
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
		return new TemplateResponse('announcementcenter', 'main', [
			'user' => $this->userId,
		]);
	}

	/**
	 * Simply method that posts back the payload of the request
	 * @NoAdminRequired
	 *
	 * @param string $echo
	 * @return DataResponse
	 */
	public function doEcho($echo) {
		return new DataResponse(['echo' => $echo]);
	}
}
