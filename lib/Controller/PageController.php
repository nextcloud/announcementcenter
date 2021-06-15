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

use OCA\AnnouncementCenter\AppInfo\Application;
use OCA\AnnouncementCenter\Manager;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Services\IInitialState;
use OCP\IConfig;
use OCP\IRequest;

class PageController extends Controller {


	/** @var Manager */
	protected $manager;

	/** @var IConfig */
	protected $config;

	/** @var IInitialState */
	protected $initialState;

	public function __construct(string $AppName,
								IRequest $request,
								Manager $manager,
								IConfig $config,
								IInitialState $initialState) {
		parent::__construct($AppName, $request);

		$this->manager = $manager;
		$this->config = $config;
		$this->initialState = $initialState;
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @param int $announcement
	 * @return TemplateResponse
	 */
	public function index(int $announcement = 0): TemplateResponse {
		if ($announcement) {
			$this->manager->markNotificationRead($announcement);
		}

		$this->initialState->provideInitialState(
			'isAdmin',
			$this->manager->checkIsAdmin()
		);
		$this->initialState->provideInitialState(
			'createActivities',
			$this->config->getAppValue(Application::APP_ID, 'create_activities', 'yes') === 'yes'
		);
		$this->initialState->provideInitialState(
			'createNotifications',
			$this->config->getAppValue(Application::APP_ID, 'create_notifications', 'yes') === 'yes'
		);
		$this->initialState->provideInitialState(
			'allowComments',
			$this->config->getAppValue(Application::APP_ID, 'allow_comments', 'yes') === 'yes'
		);

		return new TemplateResponse(Application::APP_ID, 'main');
	}
}
