<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\AnnouncementCenter\Tests\Command;

use OCA\AnnouncementCenter\Command\AnnouncementList;
use OCA\AnnouncementCenter\Manager;
use OCA\AnnouncementCenter\Tests\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AnnouncementListTest extends TestCase {
	protected Manager&MockObject $manager;
	protected InputInterface&MockObject $input;
	protected OutputInterface&MockObject $output;
	protected Command $listCommand;

	protected function setUp(): void {
		parent::setUp();

		$this->manager = $this->createMock(Manager::class);

		$methods = [
			'getFirstArgument',
			'hasParameterOption',
			'getParameterOption',
			'bind',
			'validate',
			'getArguments',
			'getArgument',
			'setArgument',
			'hasArgument',
			'getOptions',
			'getOption',
			'setOption',
			'hasOption',
			'isInteractive',
			'setInteractive',
		];
		// TODO Revert when dropping Nextcloud 34 and lower
		if (method_exists(InputInterface::class, '__toString')) {
			$methods[] = '__toString';
		}

		$this->input = $this->getMockBuilder(InputInterface::class)
			->onlyMethods($methods)
			->getMock();
		$this->output = $this->createMock(OutputInterface::class);

		$this->listCommand = new AnnouncementList(
			$this->manager,
		);
	}

	public function testListSuccessfully(): void {
		$this->input->expects($this->once())
			->method('getArgument')
			->with('limit')
			->willReturn(3);
		$this->manager->expects($this->once())
			->method('getAnnouncements')
			->willReturn([]);
		$this->output->expects($this->atLeast(2))
			->method('writeln');
		$result = self::invokePrivate($this->listCommand, 'execute', [$this->input, $this->output]);
		self::assertEquals(0, $result);
	}

	public function testListInvalidLimit(): void {
		$this->input->expects($this->once())
			->method('getArgument')
			->with('limit')
			->willReturn('invalid');
		$result = self::invokePrivate($this->listCommand, 'execute', [$this->input, $this->output]);
		self::assertEquals(true, $result > 0);
	}
}
