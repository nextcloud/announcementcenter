<?php
/**
 * @copyright Copyright (c) 2016, Joas Schilling <coding@schilljs.com>
 *
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

namespace OCA\AnnouncementCenter\Tests\Service;

use OCA\AnnouncementCenter\Manager;
use OCA\AnnouncementCenter\Model\Announcement;
use OCA\AnnouncementCenter\Model\AnnouncementMapper;
use OCA\AnnouncementCenter\Service\AnnouncementSchedulerProcessor;
use OCA\AnnouncementCenter\Tests\TestCase;
use OCP\AppFramework\Utility\ITimeFactory;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\MockObject\MockObject;

class AnnouncementSchedulerProcessorTest extends TestCase
{
    protected AnnouncementMapper|MockObject $mapper;
    protected Manager|MockObject $manager;
    protected ITimeFactory|MockObject $timeFactory;
    protected LoggerInterface|MockObject $logger;
    protected Announcement|MockObject $announcement;
    protected AnnouncementSchedulerProcessor $asp;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mapper = $this->createMock(AnnouncementMapper::class);
        $this->manager = $this->createMock(Manager::class);
        $this->timeFactory = $this->createMock(ITimeFactory::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->announcement = $this->getMockBuilder(Announcement::class)
            ->setMethods(['getScheduleTime', 'getDeleteTime', 'getId'])
            ->getMock();

        $this->asp = new AnnouncementSchedulerProcessor(
            $this->mapper,
            $this->manager,
            $this->timeFactory,
            $this->logger,
        );
    }

    public function data()
    {
        return [
            [1, 1, 2, true, true],
            [2, 2, 1, false, false],
            [1, 3, 2, true, false],
            [3, 1, 2, false, false], // don't delete when unpublished
            [2, 1, 3, true, true], // delete after being published, even when late
            [0, 1, 2, false, true], // 0 means already published, but also delete directly published notifications
            [1, 0, 2, true, false], // 0 means no deletion time!
            [0, 0, 2, false, false], // No scheduling configured!
        ];
    }

    public function getMatcher($value)
    {
        if ($value) {
            return $this->once();
        }
        return $this->never();
    }

    /**
     * @test
     * @dataProvider data
     */
    public function testDoCron($publishTime, $deleteTime, $currentTime, $expectedPublish, $expectedDelete)
    {
        $this->logger->expects($this->any())
            ->method('debug');

        // Setup times
        $this->announcement->expects($this->any())
            ->method('getScheduleTime')
            ->willReturn($publishTime);
        $this->announcement->expects($this->any())
            ->method('getDeleteTime')
            ->willReturn($deleteTime);
        $this->timeFactory->expects($this->any())
            ->method('getTime')
            ->willReturn($currentTime);
        $test = $publishTime === 0 ? true : false;
        // setup an announcement
        $this->mapper->expects($this->once())
            ->method('getAnnouncementsScheduled')
            ->willReturn($publishTime != 0 ? array($this->announcement) : []);
        $this->mapper->expects($this->once())
            ->method('getAnnouncementsScheduledDelete')
            ->willReturn($deleteTime != 0 ? array($this->announcement) : []);

        $this->announcement->expects($this->any())
            ->method('getId')
            ->willReturn(1);

        // publish part
        $this->manager->expects($this->getMatcher($expectedPublish))
            ->method('publishAnnouncement');

        $this->mapper->expects($this->getMatcher($expectedPublish))
            ->method('resetScheduleTimeById');

        // delete part
        $this->manager->expects($this->getMatcher($expectedDelete))
            ->method('delete');
        $this->asp->doCron(null);
    }
}
