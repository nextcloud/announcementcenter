<?php
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

namespace OCA\AnnouncementCenter\AppInfo;

use OCP\AppFramework\App;
use OCP\Comments\CommentsEntityEvent;

class Application extends App {
	public function __construct (array $urlParams = array()) {
		parent::__construct('announcementcenter', $urlParams);
		$container = $this->getContainer();

		$container->registerAlias('PageController', 'OCA\AnnouncementCenter\Controller\PageController');
	}

	public function register() {
		$this->registerNavigationEntry();
		$this->registerAdminPanel();
		$this->registerActivityExtension();
		$this->registerNotificationNotifier();
		$this->registerCommentsEntity();
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

	protected function registerAdminPanel() {
		\OCP\App::registerAdmin('announcementcenter', 'settings/admin');
	}

	protected function registerActivityExtension() {
		$this->getContainer()->getServer()->getActivityManager()->registerExtension(function() {
			return $this->getContainer()->query('OCA\AnnouncementCenter\Activity\Extension');
		});
	}

	protected function registerCommentsEntity() {
		$this->getContainer()->getServer()->getEventDispatcher()->addListener(CommentsEntityEvent::EVENT_ENTITY, function(CommentsEntityEvent $event) {
			$event->addEntityCollection('announcement', function($name) {
				/** @var \OCA\AnnouncementCenter\Manager $manager */
				$manager = $this->getContainer()->query('OCA\AnnouncementCenter\Manager');
				try {
					$announcement = $manager->getAnnouncement((int) $name);
				} catch (\InvalidArgumentException $e) {
					return false;
				}
				return $announcement['comments'];
			});
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
}
