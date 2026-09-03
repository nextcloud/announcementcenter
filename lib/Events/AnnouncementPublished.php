<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\AnnouncementCenter\Events;

use OCA\AnnouncementCenter\Model\Announcement;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IWebhookCompatibleEvent;

class AnnouncementPublished extends Event implements IWebhookCompatibleEvent {
	public function __construct(
		private Announcement $announcement,
	) {
		parent::__construct();
	}

	#[\Override]
	public function getWebhookSerializable(): array {
		return [
			'subject' => $this->announcement->getSubject(),
			'message' => $this->announcement->getMessage(),
			'plainMessage' => $this->announcement->getPlainMessage(),
			'scheduleTime' => $this->announcement->getScheduleTime(),
			'deleteTime' => $this->announcement->getDeleteTime(),
		];
	}
}
