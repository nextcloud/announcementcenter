<?php
/**
 * @copyright Copyright (c) 2016 Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @copyright Copyright (c) 2016 Joas Schilling <coding@schilljs.com>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
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

namespace OCA\AnnouncementCenter\Comments;


use OCP\Comments\CommentsEvent;
use OCP\Comments\IComment;
use OCP\Comments\ICommentsEventHandler;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\Notification\IManager as INotificationManager;
use OCP\Notification\INotification;

class EventHandler implements ICommentsEventHandler {

	/** @var IUserManager */
	protected $userManager;
	/** @var INotificationManager */
	protected $notificationManager;
	/** @var IURLGenerator */
	protected $urlGenerator;

	/**
	 * EventHandler constructor.
	 *
	 * @param IUserManager $userManager
	 * @param INotificationManager $notificationManager
	 * @param IURLGenerator $urlGenerator
	 */
	public function __construct(IUserManager $userManager, INotificationManager $notificationManager, IURLGenerator $urlGenerator) {
		$this->userManager = $userManager;
		$this->notificationManager = $notificationManager;
		$this->urlGenerator = $urlGenerator;
	}

	/**
	 * @param CommentsEvent $event
	 * @since 9.2.0
	 */
	public function handle(CommentsEvent $event) {
		if ($event->getComment()->getObjectType() !== 'announcement') {
			return;
		}

		$eventType = $event->getEvent();
		if (in_array($eventType, [
			CommentsEvent::EVENT_ADD,
			CommentsEvent::EVENT_PRE_UPDATE,
			CommentsEvent::EVENT_UPDATE,
			CommentsEvent::EVENT_DELETE,
		])) {
			$this->evaluateMentions($event);
		}
	}

	/**
	 * @param CommentsEvent $event
	 */
	public function evaluateMentions(CommentsEvent $event) {
		$comment = $event->getComment();
		$mentions = $this->extractMentions($comment->getMessage());
		if (empty($mentions)) {
			// no one to notify
			return;
		}

		$notification = $this->instantiateNotification($comment);

		foreach ($mentions as $mention) {
			$user = substr($mention, 1); // @username → username
			if( ($comment->getActorType() === 'users' && $user === $comment->getActorId())
				|| !$this->userManager->userExists($user)
			) {
				// do not notify unknown users or yourself
				continue;
			}

			$notification->setUser($user);
			if (in_array($event->getEvent(), [CommentsEvent::EVENT_DELETE, CommentsEvent::EVENT_PRE_UPDATE])) {
				$this->notificationManager->markProcessed($notification);
			} else {
				$this->notificationManager->notify($notification);
			}
		}
	}

	/**
	 * creates a notification instance and fills it with comment data
	 *
	 * @param IComment $comment
	 * @return INotification
	 */
	public function instantiateNotification(IComment $comment) {
		$notification = $this->notificationManager->createNotification();
		$notification
			->setApp('announcementcenter')
			->setObject('comment', $comment->getId())
			->setSubject('mention', [$comment->getObjectType(), $comment->getObjectId()])
			->setDateTime($comment->getCreationDateTime())
			->setLink($this->urlGenerator->linkToRouteAbsolute(
				'announcementcenter.page.followComment',
					['id' => $comment->getId()]
			));

		return $notification;
	}

	/**
	 * extracts @-mentions out of a message body.
	 *
	 * @param string $message
	 * @return string[] containing the mentions, e.g. ['@alice', '@bob']
	 */
	public function extractMentions($message) {
		$ok = preg_match_all('/\B@[a-z0-9_\-@\.\']+/i', $message, $mentions);

		if (!$ok || !isset($mentions[0]) || !is_array($mentions[0])) {
			return [];
		}

		return array_unique($mentions[0]);
	}
}
