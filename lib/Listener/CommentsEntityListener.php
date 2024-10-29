<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\AnnouncementCenter\Listener;

use OCA\AnnouncementCenter\Manager;
use OCA\AnnouncementCenter\Model\AnnouncementDoesNotExistException;
use OCP\Comments\CommentsEntityEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;

/**
 * @template-implements IEventListener<Event>
 */
class CommentsEntityListener implements IEventListener {
	protected Manager $manager;

	public function __construct(
		Manager $manager,
	) {
		$this->manager = $manager;
	}

	public function handle(Event $event): void {
		if (!$event instanceof CommentsEntityEvent) {
			return;
		}

		$event->addEntityCollection('announcement', function ($name) {
			try {
				$announcement = $this->manager->getAnnouncement((int)$name);
			} catch (AnnouncementDoesNotExistException $e) {
				return false;
			}
			return (bool)$announcement->getAllowComments();
		});
	}
}
