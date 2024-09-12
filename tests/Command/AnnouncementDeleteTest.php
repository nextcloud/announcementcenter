<?php
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
