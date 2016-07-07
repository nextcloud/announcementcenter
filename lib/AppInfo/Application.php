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

namespace OCA\AnnouncementCenter\AppInfo;

use OCA\AnnouncementCenter\Controller\PageController;
use OCA\AnnouncementCenter\Manager;
use OCP\AppFramework\App;
use OCP\IContainer;
use OCP\IUser;
use OCP\IUserSession;

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
				$server->getJobList(),
				$server->getNotificationManager(),
				$server->getL10N('announcementcenter'),
				new Manager($server->getDatabaseConnection()),
				$this->getCurrentUser($server->getUserSession())
			);
		});
	}

	public function register() {
		$this->registerNavigationEntry();
		$this->registerActivityExtension();
		$this->registerNotificationNotifier();
	}

	protected function registerNavigationEntry() {
		$server = $this->getContainer()->getServer();

		$server->getNavigationManager()->add(function() use ($server) {
			$urlGenerator = $server->getURLGenerator();
			$l = $server->getL10NFactory()->get('announcementcenter');
			return [
				'id' => 'announcementcenter',
				'order' => 10,
				'href' => $urlGenerator->linkToRoute('announcementcenter.page.index'),
				'icon' => $urlGenerator->imagePath('announcementcenter', 'announcementcenter.svg'),
				'name' => $l->t('Announcements'),
			];
		});
	}

	protected function registerActivityExtension() {
		$this->getContainer()->getServer()->getActivityManager()->registerExtension(function() {
			return $this->getContainer()->query('OCA\AnnouncementCenter\Activity\Extension');
		});
	}

	protected function registerNotificationNotifier() {
		$this->getContainer()->getServer()->getNotificationManager()->registerNotifier(function() {
			return $this->getContainer()->query('OCA\AnnouncementCenter\Notification\Notifier');
		}, function() {
			$l = $this->getContainer()->getServer()->getL10NFactory()->get('announcementcenter');
			return [
				'id' => 'announcementcenter',
				'name' => $l->t('Announcements'),
			];
		});
	}

	/**
	 * @param IUserSession $session
	 * @return string
	 */
	protected function getCurrentUser(IUserSession $session) {
		$user = $session->getUser();
		if ($user instanceof IUser) {
			$user = $user->getUID();
		}

		return (string) $user;
	}
}
