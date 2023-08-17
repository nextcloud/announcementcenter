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
use OCP\AppFramework\Services\IInitialState;
use OCP\IConfig;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class AdminTest extends TestCase {
	/** @var Admin */
	private $admin;

	/** @var IConfig|MockObject */
	protected $config;

	/** @var IInitialState|MockObject */
	protected $initialState;

	protected function setUp(): void {
		parent::setUp();
		$this->config = $this->createMock(IConfig::class);
		$this->initialState = $this->createMock(IInitialState::class);
		$this->admin = new Admin($this->config, $this->initialState);
	}

	public function dataGetForm() {
		return [
			[
				[
					['announcementcenter', 'create_activities', 'yes', 'yes'],
					['announcementcenter', 'create_notifications', 'yes', 'yes'],
					['announcementcenter', 'send_emails', 'yes', 'yes'],
					['announcementcenter', 'allow_comments', 'yes', 'yes'],
					['announcementcenter', 'admin_groups', json_encode(['admin']), json_encode(['admin'])],
				],
				['admin'], true, true, true, true,
			],
			[
				[
					['announcementcenter', 'create_activities', 'yes', 'no'],
					['announcementcenter', 'create_notifications', 'yes', 'yes'],
					['announcementcenter', 'send_emails', 'yes', 'yes'],
					['announcementcenter', 'allow_comments', 'yes', 'yes'],
					['announcementcenter', 'admin_groups', json_encode(['admin']), json_encode(['admin'])],
				],
				['admin'], false, true, true, true,
			],
			[
				[
					['announcementcenter', 'create_activities', 'yes', 'yes'],
					['announcementcenter', 'create_notifications', 'yes', 'no'],
					['announcementcenter', 'send_emails', 'yes', 'yes'],
					['announcementcenter', 'allow_comments', 'yes', 'yes'],
					['announcementcenter', 'admin_groups', json_encode(['admin']), json_encode(['admin'])],
				],
				['admin'], true, false, true, true,
			],
			[
				[
					['announcementcenter', 'create_activities', 'yes', 'yes'],
					['announcementcenter', 'create_notifications', 'yes', 'yes'],
					['announcementcenter', 'send_emails', 'yes', 'no'],
					['announcementcenter', 'allow_comments', 'yes', 'yes'],
					['announcementcenter', 'admin_groups', json_encode(['admin']), json_encode(['admin'])],
				],
				['admin'], true, true, false, true,
			],
			[
				[
					['announcementcenter', 'create_activities', 'yes', 'yes'],
					['announcementcenter', 'create_notifications', 'yes', 'yes'],
					['announcementcenter', 'send_emails', 'yes', 'yes'],
					['announcementcenter', 'allow_comments', 'yes', 'no'],
					['announcementcenter', 'admin_groups', json_encode(['admin']), json_encode(['admin'])],
				],
				['admin'], true, true, true, false,
			],
			[
				[
					['announcementcenter', 'create_activities', 'yes', 'no'],
					['announcementcenter', 'create_notifications', 'yes', 'no'],
					['announcementcenter', 'send_emails', 'yes', 'no'],
					['announcementcenter', 'allow_comments', 'yes', 'no'],
					['announcementcenter', 'admin_groups', json_encode(['admin']), json_encode(['admin', 'group2'])],
				],
				['admin', 'group2'], false, false, false, false,
			],
		];
	}

	/**
	 * @dataProvider dataGetForm
	 *
	 * @param array $configMap
	 * @param string $adminGroups
	 * @param bool $createActivities
	 * @param bool $createNotifications
	 * @param bool $sendEmails
	 * @param bool $allowComments
	 */
	public function testGetForm(array $configMap, $adminGroups, $createActivities, $createNotifications, $sendEmails, $allowComments) {
		$this->config->expects(self::exactly(5))
			->method('getAppValue')
			->willReturnMap($configMap);

		$this->initialState->method('provideInitialState')
			->willReturnCallback(function ($key, $data) use ($adminGroups, $createActivities, $createNotifications, $sendEmails, $allowComments) {
				switch ($key) {
					case 'admin_groups':
						self::assertEquals($adminGroups, $data);
						break;
					case 'create_activities':
						self::assertEquals($createActivities, $data);
						break;
					case 'create_notifications':
						self::assertEquals($createNotifications, $data);
						break;
					case 'send_emails':
						self::assertEquals($sendEmails, $data);
						break;
					case 'allow_comments':
						self::assertEquals($allowComments, $data);
						break;
				}
			});

		$expected = new TemplateResponse('announcementcenter', 'admin', [], '');
		self::assertEquals($expected, $this->admin->getForm());
	}

	public function testGetSection() {
		self::assertSame('additional', $this->admin->getSection());
	}

	public function testGetPriority() {
		self::assertSame(55, $this->admin->getPriority());
	}
}
