<?php
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\AnnouncementCenter\Tests\Command;

use OCA\AnnouncementCenter\Command\AnnouncementDelete;
use OCA\AnnouncementCenter\Manager;
use OCA\AnnouncementCenter\Tests\TestCase;
use OCP\AppFramework\Db\DoesNotExistException;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AnnouncementDeleteTest extends TestCase {
	protected Manager|MockObject $manager;
	protected LoggerInterface|MockObject $logger;
	protected Command $deleteCommand;
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

		$this->deleteCommand = new AnnouncementDelete(
			$this->manager,
			$this->logger,
		);
	}

	public function testDeleteSuccessfully() {
		$this->input->expects($this->once())
			->method('getArgument')
			->with('id')
			->willReturn(42);
		$this->manager->expects($this->once())
			->method('delete');
		$this->output->expects($this->atLeastOnce())
			->method('writeln');
		$result = self::invokePrivate($this->deleteCommand, 'execute', [$this->input, $this->output]);
		self::assertEquals(0, $result);
	}

	public function testDeleteInvalidId() {
		$this->input->expects($this->once())
			->method('getArgument')
			->with('id')
			->willReturn('invalid');
		$result = self::invokePrivate($this->deleteCommand, 'execute', [$this->input, $this->output]);
		self::assertEquals(true, $result > 0);
	}

	public function testDeleteDoesNotExist() {
		$this->input->expects($this->once())
			->method('getArgument')
			->with('id')
			->willReturn(42);
		$this->manager->expects($this->once())
			->method('delete')
			->willThrowException(new DoesNotExistException('message'));
		$this->output->expects($this->atLeastOnce())
			->method('writeln');
		$result = self::invokePrivate($this->deleteCommand, 'execute', [$this->input, $this->output]);
		self::assertEquals(true, $result > 0);
	}
}
