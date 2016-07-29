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

namespace OCA\AnnouncementCenter\Tests\Activity;

use OCA\AnnouncementCenter\Activity\Extension;
use OCA\AnnouncementCenter\Manager;
use OCA\AnnouncementCenter\Tests\TestCase;
use OCP\Activity\IExtension;
use OCP\Activity\IManager;
use OCP\IL10N;
use OCP\L10N\IFactory;

class ExtensionTest extends TestCase {
	/** @var Manager|\PHPUnit_Framework_MockObject_MockObject */
	protected $manager;
	/** @var IManager|\PHPUnit_Framework_MockObject_MockObject */
	protected $activity;
	/** @var IFactory|\PHPUnit_Framework_MockObject_MockObject */
	protected $factory;
	/** @var IL10N|\PHPUnit_Framework_MockObject_MockObject */
	protected $l;
	/** @var Extension */
	protected $extension;

	protected function setUp() {
		parent::setUp();

		$this->manager = $this->getMockBuilder('OCA\AnnouncementCenter\Manager')
			->disableOriginalConstructor()
			->getMock();
		$this->activity = $this->getMockBuilder('OCP\Activity\IManager')
			->disableOriginalConstructor()
			->getMock();
		$this->l = $this->getMockBuilder('OCP\IL10N')
			->disableOriginalConstructor()
			->getMock();
		$this->l->expects($this->any())
			->method('t')
			->willReturnCallback(function($string, $args) {
				return vsprintf($string, $args);
			});
		$this->factory = $this->getMockBuilder('OCP\L10N\IFactory')
			->disableOriginalConstructor()
			->getMock();
		$this->factory->expects($this->any())
			->method('get')
			->willReturn($this->l);

		$this->extension = new Extension(
			$this->manager,
			$this->activity,
			$this->factory
		);
	}

	public function testGetNotificationTypes() {
		$this->assertFalse($this->extension->getNotificationTypes('en'));
	}

	public function dataGetDefaultTypes() {
		return [
			[IExtension::METHOD_MAIL, ['announcementcenter']],
			[IExtension::METHOD_STREAM, ['announcementcenter']],
		];
	}

	/**
	 * @dataProvider dataGetDefaultTypes
	 * @param string $method
	 * @param mixed $expected
	 */
	public function testGetDefaultTypes($method, $expected) {
		$this->assertSame($expected, $this->extension->getDefaultTypes($method));
	}

	public function dataGetTypeIcon() {
		return [
			['announcementcenter', 'icon-info'],
			['unknownType', false],
		];
	}

	/**
	 * @dataProvider dataGetTypeIcon
	 * @param string $type
	 * @param mixed $expected
	 */
	public function testGetTypeIcon($type, $expected) {
		$this->assertSame($expected, $this->extension->getTypeIcon($type));
	}

	public function dataTranslate() {
		return [
			['announcementcenter', 'announcementsubject#10', ['author'], false, false, '', [
				'subject' => 'Subject #10',
				'author' => 'user',
				'time' => 1440672792,
				'message' => 'Message #10',
			], 'user', 'You announced <parameter>Subject #10</parameter>'],
			['announcementcenter', 'announcementsubject#10', ['author2'], false, false, '', [
				'subject' => 'Subject #10',
				'author' => 'author2',
				'time' => 1440672792,
				'message' => 'Message #10',
			], 'user', 'author2 announced <parameter>Subject #10</parameter>'],
			['announcementcenter', 'announcementsubject#10', ['<user display-name="Author Two">author2</user>'], false, true, '', [
				'subject' => 'Subject #10',
				'author' => 'author2',
				'time' => 1440672792,
				'message' => 'Message #10',
			], 'user', '<user display-name="Author Two">author2</user> announced <parameter>Subject #10</parameter>'],
			['announcementcenter', 'announcementsubject#10', [], false, false, '', false, null, 'Announcement does not exist anymore'],
			['announcementcenter', 'announcementmessage#10', [], false, false, '', [
				'subject' => 'Subject #10',
				'author' => 'author2',
				'time' => 1440672792,
				'message' => 'Message #10',
			], null, 'Message #10'],
			['files', '', [], false, false, '', null, null, false],
		];
	}

	/**
	 * @dataProvider dataTranslate
	 *
	 * @param string $app
	 * @param string $text
	 * @param array $params
	 * @param bool $stripPath
	 * @param bool $highlightParams
	 * @param string $languageCode
	 * @param mixed $managerReturn
	 * @param string $currentUser
	 * @param mixed $expected
	 */
	public function testTranslate($app, $text, $params, $stripPath, $highlightParams, $languageCode, $managerReturn, $currentUser, $expected) {
		if ($managerReturn === null) {
			$this->manager->expects($this->never())
				->method('getAnnouncement');
		} else {
			$this->factory->expects($this->any())
				->method('get')
				->with('announcementcenter', $languageCode)
				->willReturn($this->l);

			$this->activity->expects($this->any())
				->method('getCurrentUserId')
				->willReturn($currentUser);

			if ($managerReturn === false) {
				$this->manager->expects($this->once())
					->method('getAnnouncement')
					->with(10, true)
					->willThrowException(new \InvalidArgumentException());
			} else {
				$this->manager->expects($this->once())
					->method('getAnnouncement')
					->with(10, true)
					->willReturn($managerReturn);
			}
		}

		$this->assertSame($expected, $this->extension->translate($app, $text, $params, $stripPath, $highlightParams, $languageCode));
	}

	public function dataGetSpecialParameterList() {
		return [
			['announcementcenter', 'announcementsubject#10', [0 => 'username']],
			['announcementcenter', 'announcementmessage#10', false],
			['files', '', false]
		];
	}

	/**
	 * @dataProvider dataGetSpecialParameterList
	 * @param string $app
	 * @param string $text
	 * @param mixed $expected
	 */
	public function testGetSpecialParameterList($app, $text, $expected) {
		$this->assertSame($expected, $this->extension->getSpecialParameterList($app, $text));
	}

	public function testGetGroupParameter() {
		$this->assertFalse($this->extension->getGroupParameter(['app' => 'announcementcenter']));
	}

	public function testGetNavigation() {
		$this->assertFalse($this->extension->getNavigation());
	}

	public function dataKnownFilters() {
		return [
			['all'],
			['self'],
			['by'],
			['filter'],
		];
	}

	/**
	 * @dataProvider dataKnownFilters
	 *
	 * @param string $filter
	 */
	public function testIsFilterValid($filter) {
		$this->assertFalse($this->extension->isFilterValid($filter));
	}

	public function dataFilterNotificationTypes() {
		return [
			['all', ['announcementcenter']],
			['self', ['announcementcenter']],
			['by', ['announcementcenter']],
			['filter', false],
		];
	}

	/**
	 * @dataProvider dataFilterNotificationTypes
	 *
	 * @param string $filter
	 * @param mixed $expected
	 */
	public function testFilterNotificationTypes($filter, $expected) {
		$this->assertSame($expected, $this->extension->filterNotificationTypes([], $filter));
	}

	/**
	 * @dataProvider dataKnownFilters
	 *
	 * @param string $filter
	 */
	public function testGetQueryForFilter($filter) {
		$this->assertFalse($this->extension->getQueryForFilter($filter));
	}
}
