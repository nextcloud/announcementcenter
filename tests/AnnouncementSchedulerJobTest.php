<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\AnnouncementCenter\Tests;

use OCA\AnnouncementCenter\AnnouncementSchedulerJob;
use OCA\AnnouncementCenter\Service\AnnouncementSchedulerProcessor;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJobList;
use PHPUnit\Framework\MockObject\MockObject;

class AnnouncementSchedulerJobTest extends TestCase {
	protected ITimeFactory&MockObject $time;
	protected AnnouncementSchedulerProcessor&MockObject $asp;
	protected IJobList&MockObject $joblist;
	protected AnnouncementSchedulerJob $asj;

	protected function setUp(): void {
		parent::setUp();

		$this->time = $this->createMock(ITimeFactory::class);
		$this->asp = $this->createMock(AnnouncementSchedulerProcessor::class);
		$this->joblist = $this->createMock(IJobList::class);

		$this->asj = new AnnouncementSchedulerJob(
			$this->time,
			$this->asp,
		);
	}

	/**
	 * Test this because this happened in development
	 */
	public function testJobName(): void {
		//Read job name out of app info
		$infoFile = file_get_contents('appinfo/info.xml');
		$info = simplexml_load_string($infoFile);
		$backgroundJobs = $info->{'background-jobs'};
		$job = (string)$backgroundJobs[0]->job;

		$expected = get_class($this->asj);
		self::assertEquals($expected, $job);
	}
}
