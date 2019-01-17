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

namespace OCA\AnnouncementCenter;

use OC\BackgroundJob\QueuedJob;
use OCP\Activity\IEvent;
use OCP\Activity\IManager;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Mail\IEMailTemplate;
use OCP\Mail\IMailer;
use OCP\Mail\IMessage;
use OCP\Notification\IManager as INotificationManager;
use OCP\Notification\INotification;

class BackgroundJob extends QueuedJob {
	/** @var INotificationManager */
	protected $notificationManager;

	/** @var IMailer */
	protected $mailer;

	/** @var IUserManager */
	private $userManager;

	/** @var IGroupManager */
	private $groupManager;

	/** @var IURLGenerator */
	private $urlGenerator;

	/** @var Manager */
	private $manager;

	/** @var IManager */
	private $activityManager;

	/** @var array */
	protected $notifiedUsers = [];

	public function __construct(
		IUserManager $userManager,
		IGroupManager $groupManager,
		IManager $activityManager,
		INotificationManager $notificationManager,
		IMailer $mailer,
		IURLGenerator $urlGenerator,
		Manager $manager) {
		$this->userManager = $userManager;
		$this->groupManager = $groupManager;
		$this->activityManager = $activityManager;
		$this->notificationManager = $notificationManager;
		$this->mailer = $mailer;
		$this->urlGenerator = $urlGenerator;
		$this->manager = $manager;
	}

	/**
	 * @param array $arguments
	 */
	public function run($arguments) {
		try {
			$announcement = $this->manager->getAnnouncement($arguments['id'], false, true);
		} catch (\InvalidArgumentException $e) {
			// Announcement was deleted in the meantime, so no need to announce it anymore
			// So we die silently
			return;
		}

		$this->createPublicity($announcement, $arguments);
	}

	/**
	 * @param array $announcement
	 * @param array $publicity
	 */
	protected function createPublicity(array $announcement, array $publicity) {
		$event = $this->activityManager->generateEvent();
		$event->setApp('announcementcenter')
			->setType('announcementcenter')
			->setAuthor($announcement['author'])
			->setTimestamp($announcement['time'])
			->setSubject('announcementsubject', ['author' => $announcement['author'], 'announcement' => $announcement['id']])
			->setMessage('announcementmessage')
			->setObject('announcement', $announcement['id']);

		$dateTime = new \DateTime();
		$dateTime->setTimestamp($announcement['time']);

		$notification = $this->notificationManager->createNotification();
		$notification->setApp('announcementcenter')
			->setDateTime($dateTime)
			->setObject('announcement', $announcement['id'])
			->setSubject('announced', [$announcement['author']])
			->setLink($this->urlGenerator->linkToRouteAbsolute('announcementcenter.page.index', [
				'announcement' => $announcement['id'],
			]));

		// Nextcloud 11+
		if (method_exists($notification, 'setIcon')) {
			$notification->setIcon($this->urlGenerator->getAbsoluteURL($this->urlGenerator->imagePath('announcementcenter', 'announcementcenter-dark.svg')));
		}

		$template = $this->mailer->createEMailTemplate('announcementcenter::sendEmail');
		$template->setSubject($announcement['subject']);
		$template->addHeader();
		$template->addHeading($announcement['subject']);
		$template->addBodyText($announcement['message']);
//		$this->setMessageBody($template, $announcement['message']);
		$template->addFooter();
		$email = $this->mailer->createMessage();
		$email->useTemplate($template);

		if (\in_array('everyone', $announcement['groups'], true)) {
			$this->createPublicityEveryone($announcement['author'], $event, $notification, $email, $publicity);
		} else {
			$this->createPublicityGroups($announcement['author'], $event, $notification, $email, $announcement['groups'], $publicity);
		}
	}

	protected function setMessageBody(IEMailTemplate $template, string $message) {
		$lines = explode("\n", $message);

		foreach ($lines as $line) {
			if (trim($line) === '') {
				continue;
			}

			if (strpos(trim($line), '* ') === 0) {
				$template->addBodyListItem(trim(substr($line, strpos($line, '*') + 1)));
			} else {
				$template->addBodyText($line);
			}
		}
	}

	protected function createPublicityEveryone(string $authorId, IEvent $event, INotification $notification, IMessage $email, array $publicity) {
		$this->userManager->callForSeenUsers(function(IUser $user) use ($authorId, $event, $notification, $email, $publicity) {
			if (!empty($publicity['activities'])) {
				$event->setAffectedUser($user->getUID());
				$this->activityManager->publish($event);
			}

			if (!empty($publicity['notifications']) && $authorId !== $user->getUID()) {
				$notification->setUser($user->getUID());
				$this->notificationManager->notify($notification);
			}

			if (!empty($publicity['emails']) && $authorId !== $user->getUID() && $user->getEMailAddress()) {
				$email->setTo([$user->getEMailAddress()]);
				try {
					$this->mailer->send($email);
				} catch (\Exception $e) {
					\OC::$server->getLogger()->logException($e);
				}
			}
		});
	}

	protected function createPublicityGroups($authorId, IEvent $event, INotification $notification, IMessage $email, array $groups, array $publicity) {
		foreach ($groups as $gid) {
			$group = $this->groupManager->get($gid);
			if (!($group instanceof IGroup)) {
				continue;
			}

			$users = $group->getUsers();
			foreach ($users as $user) {
				$uid = $user->getUID();
				if (isset($this->notifiedUsers[$uid]) || $user->getLastLogin() === 0) {
					continue;
				}

				if (!empty($publicity['activities'])) {
					$event->setAffectedUser($uid);
					$this->activityManager->publish($event);
				}

				if (!empty($publicity['notifications']) && $authorId !== $uid) {
					$notification->setUser($uid);
					$this->notificationManager->notify($notification);
				}

				if (!empty($publicity['email']) && $authorId !== $uid && $user->getEMailAddress()) {
					$email->setTo([$user->getEMailAddress()]);
					try {
						$this->mailer->send($email);
					} catch (\Exception $e) {
						\OC::$server->getLogger()->logException($e);
					}
				}

				$this->notifiedUsers[$uid] = true;
			}
		}
	}
}
