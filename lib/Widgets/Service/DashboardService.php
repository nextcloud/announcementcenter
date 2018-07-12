<?php
/**
 * Nextcloud - Announcement Widget for Dashboard
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
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

namespace OCA\AnnouncementCenter\Widgets\Service;

use OCA\AnnouncementCenter\Widgets\AnnouncementWidget;
use OCA\Dashboard\Api\v1\Dashboard;
use OCA\Dashboard\Service\MiscService;
use OCP\Activity\IEvent;
use OCP\App\IAppManager;
use OCP\AppFramework\QueryException;

class DashboardService {

	/** @var string */
	private $userId;

	/** @var IAppManager */
	private $appManager;

	/** @var AnnouncementService */
	private $filesActivityService;


	/**
	 * ProviderService constructor.
	 *
	 * @param string $userId
	 * @param IAppManager $appManager
	 * @param AnnouncementService $filesActivityService
	 */
	public function __construct(
		$userId, IAppManager $appManager, AnnouncementService $filesActivityService
	) {
		$this->userId = $userId;
		$this->appManager = $appManager;
		$this->filesActivityService = $filesActivityService;
	}


	/**
	 * @param array $announcement
	 */
	public function dispatchDashboardEvent($announcement) {
		if (!$this->appManager->isInstalled('dashboard')) {
			return;
		}

		if (sizeof($announcement['groups']) === 1 && $announcement['groups'][0] === 'everyone') {
			Dashboard::createGlobalEvent(
				AnnouncementWidget::WIDGET_ID, ['announcement' => 'refresh']
			);
		} else {
			Dashboard::createGroupEvent(
				AnnouncementWidget::WIDGET_ID, $announcement['groups'],
				['announcement' => 'refresh']
			);
		}
	}

}