<?php
/**
 * ownCloud - announcementcenter
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Joas Schilling <nickvergessen@gmx.de>
 * @copyright Joas Schilling 2015
 */

namespace OCA\AnnouncementCenter;


use OC\Notification\INotification;
use OC\Notification\INotifier;
use OCP\L10N\IFactory;

class NotificationsNotifier implements INotifier {

	/** @var IFactory */
	protected $l10nFactory;

	/** @var Manager */
	protected $manager;

	/**
	 * @param Manager $manager
	 * @param IFactory $l10nFactory
	 */
	public function __construct(Manager $manager, IFactory $l10nFactory) {
		$this->manager = $manager;
		$this->l10nFactory = $l10nFactory;
	}

	/**
	 * @param INotification $notification
	 * @param string $languageCode The code of the language that should be used to prepare the notification
	 * @return INotification
	 * @throws \InvalidArgumentException When the notification was not prepared by a notifier
	 */
	public function prepare(INotification $notification, $languageCode) {
		if ($notification->getApp() !== 'announcementcenter') {
			// Not my app => throw
			throw new \InvalidArgumentException();
		}

		// Read the language from the notification
		$l = $this->l10nFactory->get('announcementcenter', $languageCode);

		switch ($notification->getSubject()) {
			// Deal with known subjects
			case 'announced':
				$params = $notification->getSubjectParameters();

				$announcement = $this->manager->getAnnouncement($notification->getObjectId(), true);
				$params[] = $announcement['subject'];

				$notification->setParsedMessage($announcement['message'])
					->setParsedSubject(
						(string) $l->t('%1$s announced “%2$s”', $params)
					);
				return $notification;

			default:
				// Unknown subject => Unknown notification => throw
				throw new \InvalidArgumentException();
		}
	}
}
