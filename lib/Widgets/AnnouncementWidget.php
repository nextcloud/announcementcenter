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

namespace OCA\AnnouncementCenter\Widgets;


use OCA\AnnouncementCenter\AppInfo\Application;
use OCA\AnnouncementCenter\Widgets\Service\AnnouncementService;
use OCA\Dashboard\IDashboardWidget;
use OCA\Dashboard\Model\WidgetRequest;
use OCA\Dashboard\Model\WidgetSettings;
use OCP\AppFramework\QueryException;

class AnnouncementWidget implements IDashboardWidget {


	const WIDGET_ID = 'announcement-center';


	/** @var AnnouncementService */
	private $announcementService;


	/**
	 * @return string
	 */
	public function getId() {
		return self::WIDGET_ID;
	}


	/**
	 * @return string
	 */
	public function getName() {
		return 'Announcement';
	}


	/**
	 * @return string
	 */
	public function getDescription() {
		return 'Display last announcements';
	}


	/**
	 * @return array
	 */
	public function getTemplate() {
		return [
			'app'      => 'announcementcenter',
			'icon'     => 'icon-announcement',
			'css'      => 'widgets/announcement',
			'js'       => 'widgets/announcement',
			'content'  => 'widgets/announcement',
			'function' => 'OCA.DashBoard.announcementCenter.init'
		];
	}


	/**
	 * @return array
	 */
	public function widgetSetup() {
		return [
			'size' => [
				'min'     => [
					'width'  => 5,
					'height' => 2
				],
				'default' => [
					'width'  => 6,
					'height' => 3
				]
			],
			'push' => 'OCA.DashBoard.announcementCenter.push'
		];
	}


	/**
	 * @param WidgetSettings $settings
	 */
	public function loadWidget($settings) {
		$app = new Application();

		$container = $app->getContainer();
		try {
			$this->announcementService = $container->query(AnnouncementService::class);
		} catch (QueryException $e) {
			return;
		}
	}


	/**
	 * @param WidgetRequest $request
	 */
	public function requestWidget(WidgetRequest $request) {
		if ($request->getRequest() === 'getLastAnnouncement') {
			$request->addResult(
				'lastAnnouncement', $this->announcementService->getLastAnnouncement()
			);
		}
	}


}