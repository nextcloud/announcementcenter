<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\AnnouncementCenter;

use OCA\AnnouncementCenter\Model\Announcement;
use OCA\AnnouncementCenter\Model\AnnouncementDoesNotExistException;
use OCA\Guests\UserBackend;
use OCP\Activity\IEvent;
use OCP\Activity\IManager as IActivityManager;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\QueuedJob;
use OCP\IConfig;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Mail\IEMailTemplate;
use OCP\Mail\IMailer;
use OCP\Mail\IMessage;
use OCP\Notification\IManager as INotificationManager;
use OCP\Notification\INotification;
use Psr\Log\LoggerInterface;

class NotificationQueueJob extends QueuedJob {
	/** @var IConfig */
	protected $config;

	/** @var INotificationManager */
	protected $notificationManager;

	/** @var IMailer */
	private $mailer;

	/** @var LoggerInterface */
	private $logger;

	/** @var IUserManager */
	private $userManager;

	/** @var IGroupManager */
	private $groupManager;

	/** @var Manager */
	private $manager;

	/** @var IActivityManager */
	private $activityManager;

	/** @var array */
	protected $notifiedUsers = [];

	/** @var bool */
	protected $enabledForGuestsUsers;

	public function __construct(
		IConfig $config,
		ITimeFactory $time,
		IUserManager $userManager,
		IGroupManager $groupManager,
		IActivityManager $activityManager,
		INotificationManager $notificationManager,
		IMailer $mailer,
		LoggerInterface $logger,
		Manager $manager) {
		parent::__construct($time);
		$this->config = $config;
		$this->userManager = $userManager;
		$this->groupManager = $groupManager;
		$this->activityManager = $activityManager;
		$this->notificationManager = $notificationManager;
		$this->mailer = $mailer;
		$this->logger = $logger;
		$this->manager = $manager;
	}

	/**
	 * @param array $argument
	 */
	public function run($argument): void {
		try {
			$announcement = $this->manager->getAnnouncement($argument['id'], true);
		} catch (AnnouncementDoesNotExistException $e) {
			// Announcement was deleted in the meantime, so no need to announce it anymore
			// So we die silently
			return;
		}

		$guestsWhiteList = $this->config->getAppValue('guests', 'whitelist');
		$this->enabledForGuestsUsers = strpos($guestsWhiteList, 'announcementcenter') !== false;

		$this->createPublicity($announcement, $argument);
	}

	/**
	 * @param Announcement $announcement
	 * @param array $publicity
	 */
	protected function createPublicity(Announcement $announcement, array $publicity): void {
		$event = $this->activityManager->generateEvent();
		$event->setApp('announcementcenter')
			->setType('announcementcenter')
			->setAuthor($announcement->getUser())
			->setTimestamp($announcement->getTime())
			->setSubject('announcementsubject', ['author' => $announcement->getUser(), 'announcement' => $announcement->getId()])
			->setMessage('announcementmessage')
			->setObject('announcement', $announcement->getId());

		$dateTime = $this->time->getDateTime();
		$dateTime->setTimestamp($announcement->getTime());

		$notification = $this->notificationManager->createNotification();
		$notification->setApp('announcementcenter')
			->setDateTime($dateTime)
			->setObject('announcement', (string)$announcement->getId())
			->setSubject('announced', [$announcement->getUser()]);

		$template = $this->mailer->createEMailTemplate('announcementcenter::sendMail');
		$template->setSubject($announcement->getSubject());
		$template->addHeader();
		$template->addHeading($announcement->getSubject());
		$this->setMailBody($template, $announcement->getPlainMessage());
		$template->addFooter();
		$email = $this->mailer->createMessage();
		$email->useTemplate($template);

		$groups = $this->manager->getGroups($announcement);
		if (\in_array('everyone', $groups, true)) {
			$this->createPublicityEveryone($announcement->getUser(), $event, $notification, $email, $publicity);
		} else {
			$this->notifiedUsers = [];
			$this->createPublicityGroups($announcement->getUser(), $event, $notification, $email, $groups, $publicity);
		}
	}

	/**
	 * Special-treat list items and strip empty lines
	 *
	 * @param IEMailTemplate $template
	 * @param string $message
	 *
	 * @return void
	 */
	protected function setMailBody(IEMailTemplate $template, string $message): void {
		$lines = explode("\n", $message);

		foreach ($lines as $line) {
			if (trim($line) === '') {
				continue;
			}

			if (strpos(trim($line), '* ') === 0) {
				$template->addBodyListItem(trim(substr($line, strpos($line, '*') + 1)), '', '', '', false);
			} else {
				$template->addBodyText($line);
			}
		}
	}

	/**
	 * @param string $authorId
	 * @param IEvent $event
	 * @param INotification $notification
	 * @param array $publicity
	 */
	protected function createPublicityEveryone(string $authorId, IEvent $event, INotification $notification, IMessage $email, array $publicity): void {
		$this->userManager->callForSeenUsers(function (IUser $user) use ($authorId, $event, $notification, $email, $publicity) {
			if (!$this->enabledForGuestsUsers && $user->getBackend() instanceof UserBackend) {
				return;
			}

			if (!empty($publicity['activities'])) {
				$event->setAffectedUser($user->getUID());
				$this->activityManager->publish($event);
			}

			if (!empty($publicity['notifications']) && $authorId !== $user->getUID()) {
				$notification->setUser($user->getUID());
				$this->notificationManager->notify($notification);
			}

			if (!empty($publicity['emails']) && $authorId !== $user->getUID() && $user->getEMailAddress() && $user->isEnabled()) {
				if (!$this->mailer->validateMailAddress($user->getEMailAddress())) {
					$this->logger->warning('User has no valid email address: ' . $user->getUID());
					return;
				}
				$email->setTo([$user->getEMailAddress()]);
				try {
					$this->mailer->send($email);
				} catch (\Exception $e) {
					$this->logger->error($e->getMessage(), ['exception' => $e]);
				}
			}
		});
	}

	/**
	 * @param string $authorId
	 * @param IEvent $event
	 * @param INotification $notification
	 * @param string[] $groups
	 * @param array $publicity
	 */
	protected function createPublicityGroups(string $authorId, IEvent $event, INotification $notification, IMessage $email, array $groups, array $publicity): void {
		foreach ($groups as $gid) {
			$group = $this->groupManager->get($gid);
			if (!($group instanceof IGroup)) {
				continue;
			}

			$users = $group->getUsers();
			foreach ($users as $user) {
				if (!$this->enabledForGuestsUsers && $user->getBackend() instanceof UserBackend) {
					continue;
				}

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

				if (!empty($publicity['emails']) && $authorId !== $user->getUID() && $user->getEMailAddress() && $user->isEnabled()) {
					$email->setTo([$user->getEMailAddress()]);
					try {
						$this->mailer->send($email);
					} catch (\Exception $e) {
						$this->logger->error($e->getMessage(), ['exception' => $e]);
					}
				}

				$this->notifiedUsers[$uid] = true;
			}
		}
	}
}
