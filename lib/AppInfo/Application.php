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

use OCA\AnnouncementCenter\Controller\APIController;
use OCA\AnnouncementCenter\Controller\PageController;
use OCA\AnnouncementCenter\Dashboard\Widget;
use OCA\AnnouncementCenter\Listener\BeforeTemplateRenderedListener;
use OCA\AnnouncementCenter\Listener\CommentsEntityListener;
use OCA\AnnouncementCenter\Manager;
use OCA\AnnouncementCenter\Model\AnnouncementMapper;
use OCA\AnnouncementCenter\Model\GroupMapper;
use OCA\AnnouncementCenter\Model\NotificationType;
use OCA\AnnouncementCenter\Notification\Notifier;
use OCA\AnnouncementCenter\Service\AnnouncementSchedulerProcessor;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\AppFramework\Http\Events\BeforeTemplateRenderedEvent;
use OCP\AppFramework\Services\IInitialState;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJobList;
use OCP\Comments\CommentsEntityEvent;
use OCP\Comments\ICommentsManager;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\L10N\IFactory;
use OCP\Notification\IManager;
use OCP\Util;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class Application extends App implements IBootstrap {
	public const APP_ID = 'announcementcenter';

	public function __construct() {
		parent::__construct(self::APP_ID);
	}

	public function register(IRegistrationContext $context): void {
		$context->registerDashboardWidget(Widget::class);
		$context->registerEventListener(BeforeTemplateRenderedEvent::class, BeforeTemplateRenderedListener::class);
		// FIXME when Nextcloud 28+ is required
		if (Util::getVersion()[0] >= 28) {
			$context->registerEventListener(CommentsEntityEvent::class, CommentsEntityListener::class);
		} else {
			$context->registerEventListener(CommentsEntityEvent::EVENT_ENTITY, CommentsEntityListener::class);
		}
		$context->registerNotifierService(Notifier::class);

		/**
		 * Controllers
		 */
		$context->registerService(APIController::class, function (ContainerInterface $c): APIController {
			return new APIController(
				$c->get('appName'),
				$c->get(IRequest::class),
				$c->get(IGroupManager::class),
				$c->get(IUserManager::class),
				$c->get(IL10N::class),
				$c->get(Manager::class),
				$c->get(ITimeFactory::class),
				$c->get(IUserSession::class),
				$c->get(NotificationType::class),
				$c->get(LoggerInterface::class)
			);
		});

		$context->registerService(PageController::class, function (ContainerInterface $c): PageController {
			return new PageController(
				$c->get('appName'),
				$c->get(IRequest::class),
				$c->get(Manager::class),
				$c->get(ICommentsManager::class),
				$c->get(IConfig::class),
				$c->get(IInitialState::class)
			);
		});

		/**
		 * Services
		 */
		$context->registerService(Manager::class, function (ContainerInterface $c): Manager {
			return new Manager(
				$c->get(IConfig::class),
				$c->get(AnnouncementMapper::class),
				$c->get(GroupMapper::class),
				$c->get(IGroupManager::class),
				$c->get(IManager::class),
				$c->get(ICommentsManager::class),
				$c->get(IJobList::class),
				$c->get(IUserSession::class),
				$c->get(NotificationType::class),
			);
		});

		$context->registerService(AnnouncementSchedulerProcessor::class, function (ContainerInterface $c): AnnouncementSchedulerProcessor {
			return new AnnouncementSchedulerProcessor(
				$c->get(AnnouncementMapper::class),
				$c->get(Manager::class),
				$c->get(ITimeFactory::class),
				$c->get(LoggerInterface::class)
			);
		});

		/**
		 * Mappers
		 */
		$context->registerService(AnnouncementMapper::class, function (ContainerInterface $c): AnnouncementMapper {
			return new AnnouncementMapper(
				$c->get(IDBConnection::class),
			);
		});

		$context->registerService(GroupMapper::class, function (ContainerInterface $c): GroupMapper {
			return new GroupMapper(
				$c->get(IDBConnection::class),
			);
		});

		$context->registerService(NotificationType::class, function (ContainerInterface $c): NotificationType {
			return new NotificationType();
		});

		$context->registerService(Notifier::class, function (ContainerInterface $c): Notifier {
			return new Notifier(
				$c->get(Manager::class),
				$c->get(IFactory::class),
				$c->get(IManager::class),
				$c->get(IUserManager::class),
				$c->get(IURLGenerator::class),
			);
		});
	}

	public function boot(IBootContext $context): void {
	}
}
