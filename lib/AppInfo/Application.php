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

namespace OCA\AnnouncementCenter\AppInfo;

use OCA\AnnouncementCenter\Controller\PageController;
use OCA\AnnouncementCenter\Manager;
use OCA\AnnouncementCenter\Model\AnnouncementDoesNotExistException;
use OCA\AnnouncementCenter\Notification\Notifier;
use OCP\AppFramework\App;
use OCP\Comments\CommentsEntityEvent;

class Application extends App {

	public function __construct() {
		parent::__construct('announcementcenter');
		$container = $this->getContainer();

		$container->registerAlias('PageController', PageController::class);
	}

	public function register() {
		$this->registerNotificationNotifier();
		$this->registerCommentsEntity();
	}

	protected function registerCommentsEntity() {
		$this->getContainer()->getServer()->getEventDispatcher()->addListener(CommentsEntityEvent::EVENT_ENTITY, function(CommentsEntityEvent $event) {
			$event->addEntityCollection('announcement', function($name) {
				/** @var Manager $manager */
				$manager = $this->getContainer()->query(Manager::class);
				try {
					$announcement = $manager->getAnnouncement((int) $name);
				} catch (AnnouncementDoesNotExistException $e) {
					return false;
				}
				return (bool) $announcement->getAllowComments();
			});
		});
	}

	protected function registerNotificationNotifier() {
		$this->getContainer()->getServer()->getNotificationManager()->registerNotifierService(Notifier::class);
	}
}
