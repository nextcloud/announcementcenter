<?php

/**
 * ownCloud - Activity App
 *
 * @author Joas Schilling
 * @copyright 2014 Joas Schilling nickvergessen@owncloud.com
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace OCA\AnnouncementCenter\AppInfo;

use OC\Files\View;
use OCA\Activity\Consumer;
use OCA\Activity\Controller\Activities;
use OCA\Activity\Controller\Feed;
use OCA\Activity\Controller\Settings;
use OCA\Activity\Data;
use OCA\Activity\DataHelper;
use OCA\Activity\GroupHelper;
use OCA\Activity\FilesHooks;
use OCA\Activity\MailQueueHandler;
use OCA\Activity\Navigation;
use OCA\Activity\ParameterHelper;
use OCA\Activity\UserSettings;
use OCA\AnnouncementCenter\Controller\PageController;
use OCA\AnnouncementCenter\Manager;
use OCP\AppFramework\App;
use OCP\IContainer;

class Application extends App {
	public function __construct (array $urlParams = array()) {
		parent::__construct('announcementcenter', $urlParams);
		$container = $this->getContainer();

		$container->registerService('PageController', function(IContainer $c) {
			/** @var \OC\Server $server */
			$server = $c->query('ServerContainer');
			return new PageController(
				$c->query('AppName'),
				$server->getRequest(),
				$server->getDatabaseConnection(),
				$server->getGroupManager(),
				$server->getUserManager(),
				$server->getActivityManager(),
				$server->getL10N('announcementcenter'),
				$server->getURLGenerator(),
				new Manager($server->getDatabaseConnection()),
				$c->query('CurrentUID')
			);
		});
	}
}
