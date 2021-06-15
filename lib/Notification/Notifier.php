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

namespace OCA\AnnouncementCenter\Notification;

use OCA\AnnouncementCenter\Manager;
use OCA\AnnouncementCenter\Model\AnnouncementDoesNotExistException;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserManager;
use OCP\L10N\IFactory;
use OCP\Notification\AlreadyProcessedException;
use OCP\Notification\IManager as INotificationManager;
use OCP\Notification\INotification;
use OCP\Notification\INotifier;

class Notifier implements INotifier {

	/** @var Manager */
	protected $manager;

	/** @var IFactory */
	protected $l10nFactory;

	/** @var INotificationManager */
	protected $notificationManager;

	/** @var IUserManager */
	protected $userManager;

	/** @var IURLGenerator */
	protected $urlGenerator;

	public function __construct(Manager $manager,
								IFactory $l10nFactory,
								INotificationManager $notificationManager,
								IUserManager $userManager,
								IURLGenerator $urlGenerator) {
		$this->manager = $manager;
		$this->l10nFactory = $l10nFactory;
		$this->notificationManager = $notificationManager;
		$this->userManager = $userManager;
		$this->urlGenerator = $urlGenerator;
	}

	/**
	 * Identifier of the notifier, only use [a-z0-9_]
	 *
	 * @return string
	 * @since 17.0.0
	 */
	public function getID(): string {
		return 'announcementcenter';
	}

	/**
	 * Human readable name describing the notifier
	 *
	 * @return string
	 * @since 17.0.0
	 */
	public function getName(): string {
		return $this->l10nFactory->get('announcementcenter')->t('Announcements');
	}

	/**
	 * @param INotification $notification
	 * @param string $languageCode The code of the language that should be used to prepare the notification
	 * @return INotification
	 * @throws \InvalidArgumentException When the notification was not prepared by a notifier
	 */
	public function prepare(INotification $notification, string $languageCode): INotification {
		if ($notification->getApp() !== 'announcementcenter') {
			// Not my app => throw
			throw new \InvalidArgumentException('Unknown app');
		}

		// Read the language from the notification
		$l = $this->l10nFactory->get('announcementcenter', $languageCode);

		$i = $notification->getSubject();
		if ($i !== 'announced') {
			// Unknown subject => Unknown notification => throw
			throw new \InvalidArgumentException('Unknown subject');
		}

		try {
			$announcement = $this->manager->getAnnouncement((int)$notification->getObjectId());
		} catch (AnnouncementDoesNotExistException $e) {
			throw new AlreadyProcessedException();
		}

		$params = $notification->getSubjectParameters();
		$user = $this->userManager->get($params[0]);
		if ($user instanceof IUser) {
			$displayName = $user->getDisplayName();
		} else {
			$displayName = $params[0];
		}

		$link = $this->urlGenerator->linkToRouteAbsolute('announcementcenter.page.index', [
			'announcement' => $notification->getObjectId(),
		]);

		if ($announcement->getMessage() !== '') {
			$notification->setParsedMessage($announcement->getMessage());
		}
		$notification->setRichSubject(
				$l->t('{user} announced {announcement}'),
				[
					'user' => [
						'type' => 'user',
						'id' => $params[0],
						'name' => $displayName,
					],
					'announcement' => [
						'type' => 'announcement',
						'id' => $notification->getObjectId(),
						'name' => $announcement->getParsedSubject(),
						'link' => $link,
					],
				]
			)
			->setLink($link)
			->setIcon($this->urlGenerator->getAbsoluteURL($this->urlGenerator->imagePath('announcementcenter', 'announcementcenter-dark.svg')));

		$placeholders = $replacements = [];
		foreach ($notification->getRichSubjectParameters() as $placeholder => $parameter) {
			$placeholders[] = '{' . $placeholder . '}';
			$replacements[] = $parameter['name'];
		}

		$notification->setParsedSubject(str_replace(
			$placeholders,
			$replacements,
			$l->t('{user} announced “{announcement}”')
		));

		return $notification;
	}
}
