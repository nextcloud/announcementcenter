<?php
/**
 * @copyright Copyright (c) 2016 Joas Schilling <coding@schilljs.com>
 * @copyright Copyright (c) 2016 Arthur Schiwon <blizzz@arthur-schiwon.de>
 *
 * @author Joas Schilling <coding@schilljs.com>
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
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
use OCP\Comments\ICommentsManager;
use OCP\Comments\NotFoundException;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserManager;
use OCP\L10N\IFactory;
use OCP\Notification\INotification;
use OCP\Notification\INotifier;

class Notifier implements INotifier {

	/** @var Manager */
	protected $manager;

	/** @var ICommentsManager */
	protected $commentsManager;

	/** @var IFactory */
	protected $l10nFactory;

	/** @var IUserManager */
	protected $userManager;

	/** @var IURLGenerator */
	protected $urlGenerator;

	/**
	 * @param Manager $manager
	 * @param ICommentsManager $commentsManager
	 * @param IFactory $l10nFactory
	 * @param IUserManager $userManager
	 * @param IURLGenerator $urlGenerator
	 */
	public function __construct(Manager $manager, ICommentsManager $commentsManager, IFactory $l10nFactory, IUserManager $userManager, IURLGenerator $urlGenerator) {
		$this->manager = $manager;
		$this->commentsManager = $commentsManager;
		$this->userManager = $userManager;
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
					$displayName = $user->getDisplayName();
				} else {
					$displayName = $params[0];
				}

				$announcement = $this->manager->getAnnouncement((int) $notification->getObjectId(), false, false, false);
				$subject = str_replace("\n", ' ', $announcement['subject']);
				$parsedParameters = [$displayName, $subject];

				$notification->setParsedMessage($announcement['message'])
					->setRichSubject(
						$l->t('{user} announced “{announcement}”'),
						[
							'user' => [
								'type' => 'user',
								'id' => $params[0],
								'name' => $displayName,
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

			/**
			 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
			 */
			case 'mention':
				try {
					$comment = $this->commentsManager->get($notification->getObjectId());
				} catch(NotFoundException $e) {
					// needs to be converted to InvalidArgumentException, otherwise none Notifications will be shown at all
					throw new \InvalidArgumentException('Comment not found', 0, $e);
				}

				$displayName = $comment->getActorId();
				$isDeletedActor = $comment->getActorType() === ICommentsManager::DELETED_USER;
				if ($comment->getActorType() === 'users') {
					$commenter = $this->userManager->get($comment->getActorId());
					if ($commenter instanceof IUser) {
						$displayName = $commenter->getDisplayName();
					}
				}

				$parameters = $notification->getSubjectParameters();
				if ($parameters[0] !== 'announcement') {
					throw new \InvalidArgumentException('Unsupported comment object');
				}

				$announcement = $this->manager->getAnnouncement((int) $parameters[1], false, false, false);
				$announcementSubject = str_replace("\n", ' ', $announcement['subject']);
				if ($isDeletedActor) {
					$subject = $l->t(
						'A (now) deleted user mentioned you in a comment on “%1$s”.',
						[$announcementSubject]
					);
				} else {
					$subject = $l->t(
						'%1$s mentioned you in a comment on “%2$s”.',
						[$displayName, $announcementSubject]
					);
				}
				$notification->setParsedMessage($comment->getMessage())
					->setParsedSubject($subject);

				return $notification;

			default:
				// Unknown subject => Unknown notification => throw
				throw new \InvalidArgumentException();
		}
	}
}
