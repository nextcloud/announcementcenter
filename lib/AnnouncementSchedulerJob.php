<?php

namespace OCA\AnnouncementCenter;

use OCA\AnnouncementCenter\Service\AnnouncementSchedulerProcessor;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use Psr\Log\LoggerInterface;

class AnnouncementSchedulerJob extends TimedJob {

	protected AnnouncementSchedulerProcessor $asp;
	protected LoggerInterface $logger;

	public function __construct(ITimeFactory $time, LoggerInterface $logger, AnnouncementSchedulerProcessor $service) {
		parent::__construct($time);
		$this->asp = $service;

		// Run once every 10 minutes
		$this->setInterval(60 * 10);
	}

	protected function run($argument) {
		$this->asp->doCron($argument);
	}

}
