<?php
namespace OCA\AnnouncementCenter\Service;

use OCA\AnnouncementCenter\Model\AnnouncementMapper;
use OCP\DB\Exception;
use OCP\AppFramework\Utility\ITimeFactory;

class AnnouncementSchedulerProcessor
{
    private AnnouncementMapper $mapper;
    private Manager $manager;
    private ITimeFactory $timeFactory;
    /**
     * Create cron that is fetching the b2share communities api
     * with dependency injection
     */
    function __construct(AnnouncementMapper $mapper, Manager $manager, ITimeFactory $time)
    {
        $this->mapper = $mapper;
        $this->manager = $manager;
        $this->timeFactory = $time;
    }

    function doCron($arguments)
    {
        //first schedule then delete because e-mails might be send
        $this->scheduleAnnouncements($arguments);
        $this->deleteAnnouncements($arguments);
    }

    function scheduleAnnouncements($arguments)
    {
        $scheduledAnnouncements = $this->mapper->getAnnouncementsScheduled();
        foreach ($scheduledAnnouncements as $ann) {
            if ($ann->getScheduledTime() && $ann->getScheduledTime() > 0 && $ann->getScheduledTime() > $this->timeFactory->getTime())
                break; //They are sorted and scheduled in the future
            $this->manager->publishAnnouncement($ann);
            $ann->setScheduledTime(0);
        }
    }

    function deleteAnnouncements($arguments)
    {
        $deleteAnnouncements = $this->mapper->getAnnouncementsScheduledDelete();
        foreach ($deleteAnnouncements as $ann) {
            // don't delete unannounced announcements
            if ($ann->getScheduledTime() && $ann->getScheduledTime() > 0 && $ann->getScheduledTime() > $this->timeFactory->getTime())
                continue;
            if ($ann->getDeleteTime() && $ann->getDeleteTime() > 0 && $ann->getDeleteTime() > $this->timeFactory->getTime())
                break; //They are sorted and scheduled to be deleted in the future
            $this->manager->delete($ann->getId());
        }
    }
}