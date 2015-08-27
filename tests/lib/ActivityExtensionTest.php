<?php

/**
 * @author Joas Schilling <nickvergessen@owncloud.com>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\AnnouncementCenter\Tests\Lib;

use OCA\AnnouncementCenter\ActivityExtension;
use OCA\AnnouncementCenter\Manager;
use OCA\AnnouncementCenter\Tests\TestCase;
use OCP\Activity\IExtension;
use OCP\Activity\IManager;
use OCP\IL10N;
use OCP\L10N\IFactory;

class ActivityExtensionTest extends TestCase {
	/** @var Manager|\PHPUnit_Framework_MockObject_MockObject */
	protected $manager;
	/** @var IManager|\PHPUnit_Framework_MockObject_MockObject */
	protected $activity;
	/** @var IFactory|\PHPUnit_Framework_MockObject_MockObject */
	protected $factory;
	/** @var IL10N|\PHPUnit_Framework_MockObject_MockObject */
	protected $l;
	/** @var ActivityExtension */
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

		$this->extension = new ActivityExtension(
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
			], 'user', 'You announced Subject #10'],
			['announcementcenter', 'announcementsubject#10', ['author2'], false, false, '', [
				'subject' => 'Subject #10',
				'author' => 'author2',
				'time' => 1440672792,
				'message' => 'Message #10',
			], 'user', 'author2 announced Subject #10'],
			['announcementcenter', 'announcementsubject#10', ['<strong>author2</strong>'], false, true, '', [
				'subject' => 'Subject #10',
				'author' => 'author2',
				'time' => 1440672792,
				'message' => 'Message #10',
			], 'user', '<strong>author2</strong> announced <strong>Subject #10</strong>'],
			['announcementcenter', 'announcementsubject#10', [], false, false, '', false, null, 'Announcement does not exist anymore'],
			['announcementcenter', 'announcementmessage#10', [], false, false, '', null, null, ''],
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
					->with(10)
					->willThrowException(new \InvalidArgumentException());
			} else {
				$this->manager->expects($this->once())
					->method('getAnnouncement')
					->with(10)
					->willReturn($managerReturn);
			}
		}

		$this->assertSame($expected, $this->extension->translate($app, $text, $params, $stripPath, $highlightParams, $languageCode));
	}

	public function dataGetSpecialParameterList() {
		return [
			['announcementcenter', [0 => 'username']],
			['files', false]
		];
	}

	/**
	 * @dataProvider dataGetSpecialParameterList
	 * @param string $app
	 * @param mixed $expected
	 */
	public function testGetSpecialParameterList($app, $expected) {
		$this->assertSame($expected, $this->extension->getSpecialParameterList($app, ''));
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
