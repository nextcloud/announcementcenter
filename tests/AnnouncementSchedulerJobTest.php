<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2024 Marvin Winkens <m.winkens@fz-juelich.de>
 *
 * @author Marvin Winkens <m.winkens@fz-juelich.de>
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

namespace OCA\AnnouncementCenter\Tests;

use OCA\AnnouncementCenter\AnnouncementSchedulerJob;
use OCA\AnnouncementCenter\Service\AnnouncementSchedulerProcessor;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJobList;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class AnnouncementSchedulerJobTest extends TestCase {
	protected ITimeFactory|MockObject $time;
	protected LoggerInterface|MockObject $logger;
	protected AnnouncementSchedulerProcessor|MockObject $asp;
	protected AnnouncementSchedulerJob $asj;
	protected IJobList|MockObject $joblist;

	protected function setUp(): void {
		parent::setUp();

		$this->time = $this->createMock(ITimeFactory::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->asp = $this->createMock(AnnouncementSchedulerProcessor::class);
		$this->joblist = $this->createMock(IJobList::class);

		$this->asj = new AnnouncementSchedulerJob(
			$this->time,
			$this->logger,
			$this->asp,
		);
	}

	/**
	 * Test this because this happened in development
	 */
	public function testJobName() {
		//Read job name out of app info
		$infoFile = file_get_contents('appinfo/info.xml');
		$info = simplexml_load_string($infoFile);
		$backgroundJobs = $info->{'background-jobs'};
		$job = (string) $backgroundJobs[0]->job;

		$expected = get_class($this->asj);
		self::assertEquals($expected, $job);
	}
}
