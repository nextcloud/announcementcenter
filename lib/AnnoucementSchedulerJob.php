<?php

namespace OCA\AnnouncementCenter;

use OCA\AnnouncementCenter\Service\AnnouncementSchedulerProcessor;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;

class AnnouncementSchedulerJob extends TimedJob {

	private AnnouncementSchedulerProcessor $myService;

	public function __construct(ITimeFactory $time, AnnouncementSchedulerProcessor $service) {
		parent::__construct($time);
		$this->myService = $service;

		// Run once every 10 minutes
		$this->setInterval(60 * 10);
	}

	protected function run($argument) {
		$this->myService->doCron($argument);
	}

}
