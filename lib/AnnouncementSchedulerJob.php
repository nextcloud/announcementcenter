<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\AnnouncementCenter;

use OCA\AnnouncementCenter\Service\AnnouncementSchedulerProcessor;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;

class AnnouncementSchedulerJob extends TimedJob {

	public function __construct(
		ITimeFactory $time,
		protected AnnouncementSchedulerProcessor $asp,
	) {
		parent::__construct($time);

		// Run every time the cron runs
		$this->setInterval(60);
	}

	protected function run($argument) {
		$this->asp->doCron($argument);
	}

}
