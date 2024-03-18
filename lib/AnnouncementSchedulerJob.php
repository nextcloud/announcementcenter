<?php

namespace OCA\AnnouncementCenter;

use OCA\AnnouncementCenter\Service\AnnouncementSchedulerProcessor;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use Psr\Log\LoggerInterface;

class AnnouncementSchedulerJob extends TimedJob {

	public function __construct(
		ITimeFactory $time,
		protected AnnouncementSchedulerProcessor $asp) {
		parent::__construct($time);

		// Run every time the cron runs
		$this->setInterval(60);
	}

	protected function run($argument) {
		$this->asp->doCron($argument);
	}

}
