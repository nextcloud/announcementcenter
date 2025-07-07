<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\AnnouncementCenter\Notification;

use OCA\AnnouncementCenter\Manager;
use OCA\AnnouncementCenter\Model\AnnouncementDoesNotExistException;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\L10N\IFactory;
use OCP\Notification\AlreadyProcessedException;
use OCP\Notification\IManager as INotificationManager;
use OCP\Notification\INotification;
use OCP\Notification\INotifier;
use OCP\Notification\UnknownNotificationException;

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
			if (class_exists(UnknownNotificationException::class)) {
				throw new UnknownNotificationException('Unknown app');
			}
			throw new \InvalidArgumentException('Unknown app');
		}

		// Read the language from the notification
		$l = $this->l10nFactory->get('announcementcenter', $languageCode);

		$i = $notification->getSubject();
		if ($i !== 'announced') {
			// Unknown subject => Unknown notification => throw
			if (class_exists(UnknownNotificationException::class)) {
				throw new UnknownNotificationException('Unknown subject');
			}
			throw new \InvalidArgumentException('Unknown subject');
		}

		try {
			$announcement = $this->manager->getAnnouncement((int)$notification->getObjectId(), $this->notificationManager->isPreparingPushNotification());
		} catch (AnnouncementDoesNotExistException $e) {
			throw new AlreadyProcessedException();
		}

		$params = $notification->getSubjectParameters();
		$displayName = $this->userManager->getDisplayName($params[0]) ?? $params[0];

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
