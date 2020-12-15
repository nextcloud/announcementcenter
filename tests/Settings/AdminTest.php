<?php
/**
 * @copyright Copyright (c) 2016 Joas Schilling <coding@schilljs.com>
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

namespace OCA\AnnouncementCenter\Tests\Settings;

use OCA\AnnouncementCenter\Settings\Admin;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IConfig;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class AdminTest extends TestCase {
	/** @var Admin */
	private $admin;

	/** @var IConfig|MockObject */
	protected $config;

	protected function setUp(): void {
		parent::setUp();
		$this->config = $this->createMock(IConfig::class);
		$this->admin = new Admin($this->config);
	}

	public function dataGetForm() {
		return [
			[
				[
					['announcementcenter', 'create_activities', 'yes', 'yes'],
					['announcementcenter', 'create_notifications', 'yes', 'yes'],
					['announcementcenter', 'allow_comments', 'yes', 'yes'],
					['announcementcenter', 'admin_groups', json_encode(['admin']), json_encode(['admin'])],
				],
				'admin', true, true, true,
			],
			[
				[
					['announcementcenter', 'create_activities', 'yes', 'no'],
					['announcementcenter', 'create_notifications', 'yes', 'yes'],
					['announcementcenter', 'allow_comments', 'yes', 'yes'],
					['announcementcenter', 'admin_groups', json_encode(['admin']), json_encode(['admin'])],
				],
				'admin', false, true, true,
			],
			[
				[
					['announcementcenter', 'create_activities', 'yes', 'yes'],
					['announcementcenter', 'create_notifications', 'yes', 'no'],
					['announcementcenter', 'allow_comments', 'yes', 'yes'],
					['announcementcenter', 'admin_groups', json_encode(['admin']), json_encode(['admin'])],
				],
				'admin', true, false, true,
			],
			[
				[
					['announcementcenter', 'create_activities', 'yes', 'yes'],
					['announcementcenter', 'create_notifications', 'yes', 'yes'],
					['announcementcenter', 'allow_comments', 'yes', 'no'],
					['announcementcenter', 'admin_groups', json_encode(['admin']), json_encode(['admin'])],
				],
				'admin', true, true, false,
			],
			[
				[
					['announcementcenter', 'create_activities', 'yes', 'no'],
					['announcementcenter', 'create_notifications', 'yes', 'no'],
					['announcementcenter', 'allow_comments', 'yes', 'no'],
					['announcementcenter', 'admin_groups', json_encode(['admin']), json_encode(['admin', 'group2'])],
				],
				'admin|group2', false, false, false,
			],
		];
	}

	/**
	 * @dataProvider dataGetForm
	 * @param array $configMap
	 * @param string $adminGroups
	 * @param bool $createActivities
	 * @param bool $createNotifications
	 * @param bool $allowComments
	 */
	public function testGetForm(array $configMap, $adminGroups, $createActivities, $createNotifications, $allowComments) {
		$this->config->expects(self::exactly(4))
			->method('getAppValue')
			->willReturnMap($configMap);

		$expected = new TemplateResponse('announcementcenter', 'admin', [
			'adminGroups' => $adminGroups,
			'createActivities' => $createActivities,
			'createNotifications' => $createNotifications,
			'allowComments' => $allowComments,
		], 'blank');
		self::assertEquals($expected, $this->admin->getForm());
	}

	public function testGetSection() {
		self::assertSame('additional', $this->admin->getSection());
	}

	public function testGetPriority() {
		self::assertSame(55, $this->admin->getPriority());
	}
}
