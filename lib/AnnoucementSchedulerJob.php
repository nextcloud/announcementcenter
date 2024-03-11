<?php
namespace OCA\MyApp\Cron;

use OCA\AnnouncementCenter\Service\AnnouncementSchedulerProcessor;
use OCP\BackgroundJob\TimedJob;
use OCP\AppFramework\Utility\ITimeFactory;

class SomeTask extends TimedJob {

    private AnnouncementSchedulerProcessor $myService;

    public function __construct(ITimeFactory $time, AnnouncementSchedulerProcessor $service) {
        parent::__construct($time);
        $this->myService = $service;

        // Run once every 10 minutes
        $this->setInterval(60 * 10);
    }

    protected function run($arguments) {
        $this->myService->doCron($arguments);
    }

}