<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\AnnouncementCenter\Tests\Service;

use OCA\AnnouncementCenter\Manager;
use OCA\AnnouncementCenter\Model\Announcement;
use OCA\AnnouncementCenter\Model\AnnouncementMapper;
use OCA\AnnouncementCenter\Service\AnnouncementSchedulerProcessor;
use OCA\AnnouncementCenter\Tests\TestCase;
use OCP\AppFramework\Utility\ITimeFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class MockAnnouncement extends Announcement {
	public function getScheduleTime(): int {
		return 0;
	}

	public function getDeleteTime(): int {
		return 0;
	}

	public function getId(): int {
		return 0;
	}
}

class AnnouncementSchedulerProcessorTest extends TestCase {
	protected AnnouncementMapper&MockObject $mapper;
	protected Manager&MockObject $manager;
	protected ITimeFactory&MockObject $timeFactory;
	protected LoggerInterface&MockObject $logger;
	protected Announcement&MockObject $announcement;
	protected AnnouncementSchedulerProcessor $asp;

	protected function setUp(): void {
		parent::setUp();

		$this->mapper = $this->createMock(AnnouncementMapper::class);
		$this->manager = $this->createMock(Manager::class);
		$this->timeFactory = $this->createMock(ITimeFactory::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->announcement = $this->getMockBuilder(MockAnnouncement::class)
			->onlyMethods(['getScheduleTime', 'getDeleteTime', 'getId'])
			->getMock();

		$this->asp = new AnnouncementSchedulerProcessor(
			$this->mapper,
			$this->manager,
			$this->timeFactory,
			$this->logger,
		);
	}

	public static function dataDoCron(): array {
		return [
			[1, 1, 2, true, true],   // publish and delete
			[2, 2, 1, false, false], // it's not time yet
			[1, 3, 2, true, false],  // time for publish, no time for delete
			[3, 1, 2, false, false], // don't delete when unpublished
			[2, 1, 3, true, true],   // delete after being published, even when late
			[0, 1, 2, false, true],  // 0 means already published, but also delete directly published notifications
			[1, 0, 2, true, false],  // 0 means no deletion time!
			[0, 0, 2, false, false], // No scheduling configured!
		];
	}

	public function getMatcher($value) {
		return $value ? $this->once() : $this->never();
	}

	#[DataProvider('dataDoCron')]
	public function testDoCron(int $publishTime, int $deleteTime, int $currentTime, bool $expectedPublish, bool $expectedDelete): void {
		$this->logger->expects($this->any())
			->method('debug');

		// setup times
		$this->announcement->expects($this->any())
			->method('getScheduleTime')
			->willReturn($publishTime);
		$this->announcement->expects($this->any())
			->method('getDeleteTime')
			->willReturn($deleteTime);
		$this->timeFactory->expects($this->any())
			->method('getTime')
			->willReturn($currentTime);

		// setup an announcement
		$this->mapper->expects($this->once())
			->method('getAnnouncementsScheduled')
			->willReturn($publishTime != 0 ? [$this->announcement] : []);
		$this->mapper->expects($this->once())
			->method('getAnnouncementsScheduledDelete')
			->willReturn($deleteTime != 0 ? [$this->announcement] : []);

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

		// execute
		$this->asp->doCron(null);
	}
}
