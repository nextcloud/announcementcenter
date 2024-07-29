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

use OCA\AnnouncementCenter\Command\AnnouncementList;
use OCA\AnnouncementCenter\Manager;
use OCA\AnnouncementCenter\Tests\PHPUnitUtil;
use OCA\AnnouncementCenter\Tests\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AnnouncementListTest extends TestCase {
	protected Manager|MockObject $manager;
	protected Command $listCommand;
	protected InputInterface|MockObject $input;
	protected OutputInterface|MockObject $output;

	protected function setUp(): void {
		parent::setUp();

		$this->manager = $this->createMock(Manager::class);

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

		$this->listCommand = new AnnouncementList(
			$this->manager,
		);
	}

	public function testListSuccessfully() {
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

	public function testListInvalidLimit() {
		$this->input->expects($this->once())
			->method('getArgument')
			->with('limit')
			->willReturn('invalid');
		$result = self::invokePrivate($this->listCommand, 'execute', [$this->input, $this->output]);
		self::assertEquals(True, $result > 0);
	}
}
