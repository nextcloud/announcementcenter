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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCA\AnnouncementCenter\Tests\Command;

use OCA\AnnouncementCenter\Command\RemoveNotifications;
use OCA\AnnouncementCenter\Manager;
use OCA\AnnouncementCenter\Model\AnnouncementDoesNotExistException;
use OCA\AnnouncementCenter\Tests\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RemoveNotificationsTest extends TestCase {
	protected Manager|MockObject $manager;
	protected LoggerInterface|MockObject $logger;
	protected Command $removeNotificationsCommand;
	protected InputInterface|MockObject $input;
	protected OutputInterface|MockObject $output;

	protected function setUp(): void {
		parent::setUp();

		$this->manager = $this->createMock(Manager::class);
		$this->logger = $this->createMock(LoggerInterface::class);

		$this->input = $this->getMockBuilder(InputInterface::class)
			->setMethods([
				'getArgument',
				'getOption',
				'getFirstArgument',
				'hasParameterOption',
				'getParameterOption',
				'bind',
				'validate',
				'getArguments',
				'setArgument',
				'hasArgument',
				'getOptions',
				'isInteractive',
				'hasOption',
				'setOption',
				'setInteractive',
			])
			->getMock();
		$this->output = $this->createMock(OutputInterface::class);

		$this->removeNotificationsCommand = new RemoveNotifications(
			$this->manager,
			$this->logger,
		);
	}

	public function testRemoveNotificationsSuccessfully() {
		$this->input->expects($this->once())
			->method('getArgument')
			->with('id')
			->willReturn(42);
		$this->manager->expects($this->once())
			->method('removeNotifications');
		$this->manager->expects($this->once())
			->method('getAnnouncement');
		$this->output->expects($this->atLeastOnce())
			->method('writeln');
		$this->logger->expects($this->atLeastOnce())
			->method('info');
		$result = self::invokePrivate($this->removeNotificationsCommand, 'execute', [$this->input, $this->output]);
		self::assertEquals(0, $result);
	}

	public function testRemoveNotificationsInvalidId() {
		$this->input->expects($this->once())
			->method('getArgument')
			->with('id')
			->willReturn('invalid');
		$result = self::invokePrivate($this->removeNotificationsCommand, 'execute', [$this->input, $this->output]);
		self::assertEquals(true, $result > 0);
	}

	public function testRemoveNotificationsDoesNotExist() {
		$this->input->expects($this->once())
			->method('getArgument')
			->with('id')
			->willReturn(42);
		$this->manager->expects($this->once())
			->method('getAnnouncement')
			->willThrowException(new AnnouncementDoesNotExistException('message'));
		$this->output->expects($this->atLeastOnce())
			->method('writeln');
		$result = self::invokePrivate($this->removeNotificationsCommand, 'execute', [$this->input, $this->output]);
		self::assertEquals(true, $result > 0);
	}
}
