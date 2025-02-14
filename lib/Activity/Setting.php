<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\AnnouncementCenter\Activity;

use OCP\Activity\ActivitySettings;
use OCP\IL10N;

class Setting extends ActivitySettings {
	public function __construct(
		protected IL10N $l,
	) {
	}

	public function getIdentifier(): string {
		return 'announcementcenter';
	}

	public function getName(): string {
		return $this->l->t('An <strong>announcement</strong> is posted by an administrator');
	}

	/**
	 * @return int whether the filter should be rather on the top or bottom of
	 *             the admin section. The filters are arranged in ascending order of the
	 *             priority values. It is required to return a value between 0 and 100.
	 * @since 11.0.0
	 */
	public function getPriority(): int {
		return 70;
	}

	public function canChangeStream(): bool {
		return false;
	}

	public function isDefaultEnabledStream(): bool {
		return true;
	}

	public function canChangeMail(): bool {
		return false;
	}

	public function isDefaultEnabledMail(): bool {
		return false;
	}

	public function canChangeNotification(): bool {
		return false;
	}

	public function isDefaultEnabledNotification(): bool {
		return false;
	}

	public function getGroupIdentifier(): string {
		return 'other';
	}

	public function getGroupName(): string {
		return 'Other activities';
	}
}
