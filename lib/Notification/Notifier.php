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

namespace OCA\AnnouncementCenter\Notification;


use OCA\AnnouncementCenter\Manager;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserManager;
use OCP\L10N\IFactory;
use OCP\Notification\INotification;
use OCP\Notification\INotifier;

class Notifier implements INotifier {

	/** @var IFactory */
	protected $l10nFactory;

	/** @var Manager */
	protected $manager;

	/** @var IUserManager */
	protected $userManager;

	/** @var IURLGenerator */
	protected $urlGenerator;

	/**
	 * @param Manager $manager
	 * @param IFactory $l10nFactory
	 * @param IUserManager $userManager
	 * @param IURLGenerator $urlGenerator
	 */
	public function __construct(Manager $manager, IFactory $l10nFactory, IUserManager $userManager, IURLGenerator $urlGenerator) {
		$this->manager = $manager;
		$this->l10nFactory = $l10nFactory;
		$this->userManager = $userManager;
		$this->urlGenerator = $urlGenerator;
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
				$user = $this->userManager->get($params[0]);
				if ($user instanceof IUser) {
					$parsedParameters = [$user->getDisplayName()];
				} else {
					$parsedParameters = [$params[0]];
				}

				$announcement = $this->manager->getAnnouncement((int) $notification->getObjectId(), false, false, false);
				$subject = str_replace("\n", ' ', $announcement['subject']);
				$parsedParameters[] = $subject;

				$notification->setParsedMessage($announcement['message'])
					->setRichSubject(
						$l->t('{user} announced “{announcement}”'),
						[
							'user' => [
								'type' => 'user',
								'id' => $params[0],
								'name' => $user->getDisplayName(),
								'path' => '/path/to/file.txt',
							],
							'announcement' => [
								'type' => 'announcement',
								'id' => $notification->getObjectId(),
								'name' => $subject,
								'link' => $this->urlGenerator->linkToRouteAbsolute('announcementcenter.page.index') . '#' . $notification->getObjectId(),
							],
						]
					)
					->setParsedSubject(
						(string) $l->t('%1$s announced “%2$s”', $parsedParameters)
					);
				return $notification;

			default:
				// Unknown subject => Unknown notification => throw
				throw new \InvalidArgumentException();
		}
	}
}
