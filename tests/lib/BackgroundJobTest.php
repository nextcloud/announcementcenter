<?php
/**
 * @author Joas Schilling <nickvergessen@owncloud.com>
 *
 * @copyright Copyright (c) 2016, Joas Schilling <nickvergessen@owncloud.com>
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

use OCA\AnnouncementCenter\Manager;
use OCA\AnnouncementCenter\Tests\TestCase;
use OCP\Activity\IManager;
use OCP\AppFramework\Http;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\Notification\IManager as INotificationManager;

/**
 * Class PageController
 *
 * @package OCA\AnnouncementCenter\Tests\Controller
 * @group DB
 */
class BackgroundJobTest extends TestCase {
	/** @var IUserManager|\PHPUnit_Framework_MockObject_MockObject */
	protected $userManager;
	/** @var IManager|\PHPUnit_Framework_MockObject_MockObject */
	protected $activityManager;
	/** @var INotificationManager|\PHPUnit_Framework_MockObject_MockObject */
	protected $notificationManager;
	/** @var IURLGenerator|\PHPUnit_Framework_MockObject_MockObject */
	protected $urlGenerator;
	/** @var Manager|\PHPUnit_Framework_MockObject_MockObject */
	protected $manager;

	protected function setUp() {
		parent::setUp();

		$this->userManager = $this->getMockBuilder('OCP\IUserManager')
			->disableOriginalConstructor()
			->getMock();
		$this->activityManager = $this->getMockBuilder('OCP\Activity\IManager')
			->disableOriginalConstructor()
			->getMock();
		$this->notificationManager = $this->getMockBuilder('OCP\Notification\IManager')
			->disableOriginalConstructor()
			->getMock();
		$this->urlGenerator = $this->getMockBuilder('OCP\IURLGenerator')
			->disableOriginalConstructor()
			->getMock();
		$this->manager = $this->getMockBuilder('OCA\AnnouncementCenter\Manager')
			->disableOriginalConstructor()
			->getMock();
	}

	protected function getJob(array $methods = []) {
		if (empty($methods)) {
			return new \OCA\AnnouncementCenter\BackgroundJob(
				$this->userManager,
				$this->activityManager,
				$this->notificationManager,
				$this->urlGenerator,
				$this->manager
			);
		} else {
			return $this->getMockBuilder('OCA\AnnouncementCenter\BackgroundJob')
				->setConstructorArgs([
					$this->userManager,
					$this->activityManager,
					$this->notificationManager,
					$this->urlGenerator,
					$this->manager,
				])
				->setMethods($methods)
				->getMock();
		}
	}

	public function dataRun() {
		return [
			[23, new \InvalidArgumentException()],
			[42, ['id' => 42, 'author' => 'user', 'time' => 123456789]],
		];
	}

	/**
	 * @dataProvider dataRun
	 * @param int $id
	 * @param \Exception|array $getResult
	 */
	public function testRun($id, $getResult) {
		$job = $this->getJob(['createPublicity']);

		if ($getResult instanceof \Exception) {
			$job->expects($this->never())
				->method('createPublicity');
			$this->manager->expects($this->once())
				->method('getAnnouncement')
				->with($id, false)
				->willThrowException($getResult);
		} else {
			$this->manager->expects($this->once())
				->method('getAnnouncement')
				->with($id, false)
				->willReturn($getResult);
			$job->expects($this->once())
				->method('createPublicity')
				->with($getResult['id'], $getResult['author'], $getResult['time']);
		}

		$this->invokePrivate($job, 'run', [['id' => $id]]);

	}

	protected function getUserMock($uid, $displayName) {
		$user = $this->getMockBuilder('OCP\IUser')
			->disableOriginalConstructor()
			->getMock();
		$user->expects($this->any())
			->method('getUID')
			->willReturn($uid);
		$user->expects($this->any())
			->method('getDisplayName')
			->willReturn($displayName);
		return $user;
	}

	public function testCreatePublicity() {
		$event = $this->getMockBuilder('OCP\Activity\IEvent')
			->disableOriginalConstructor()
			->getMock();
		$event->expects($this->once())
			->method('setApp')
			->with('announcementcenter')
			->willReturnSelf();
		$event->expects($this->once())
			->method('setType')
			->with('announcementcenter')
			->willReturnSelf();
		$event->expects($this->once())
			->method('setAuthor')
			->with('author')
			->willReturnSelf();
		$event->expects($this->once())
			->method('setTimestamp')
			->with(1337)
			->willReturnSelf();
		$event->expects($this->once())
			->method('setSubject')
			->with('announcementsubject#10', ['author'])
			->willReturnSelf();
		$event->expects($this->once())
			->method('setMessage')
			->with('announcementmessage#10', ['author'])
			->willReturnSelf();
		$event->expects($this->once())
			->method('setObject')
			->with('announcement', 10)
			->willReturnSelf();
		$event->expects($this->exactly(5))
			->method('setAffectedUser')
			->willReturnSelf();

		$notification = $this->getMockBuilder('OCP\Notification\INotification')
			->disableOriginalConstructor()
			->getMock();
		$notification->expects($this->once())
			->method('setApp')
			->with('announcementcenter')
			->willReturnSelf();
		$dateTime = new \DateTime();
		$dateTime->setTimestamp(1337);
		$notification->expects($this->once())
			->method('setDateTime')
			->with($dateTime)
			->willReturnSelf();
		$notification->expects($this->once())
			->method('setSubject')
			->with('announced', ['author'])
			->willReturnSelf();
		$notification->expects($this->once())
			->method('setObject')
			->with('announcement', 10)
			->willReturnSelf();
		$notification->expects($this->once())
			->method('setLink')
			->willReturnSelf();
		$notification->expects($this->exactly(4))
			->method('setUser')
			->willReturnSelf();

		$job = $this->getJob();
		$this->activityManager->expects($this->once())
			->method('generateEvent')
			->willReturn($event);
		$this->notificationManager->expects($this->once())
			->method('createNotification')
			->willReturn($notification);
		$this->userManager->expects($this->once())
			->method('callForAllUsers')
			->with($this->anything(), '')
			->willReturnCallback(function($callback) {
				$users = [
					$this->getUserMock('author', 'User One'),
					$this->getUserMock('u2', 'User Two'),
					$this->getUserMock('u3', 'User Three'),
					$this->getUserMock('u4', 'User Four'),
					$this->getUserMock('u5', 'User Five'),
				];
				foreach ($users as $user) {
					$callback($user);
				}
			})
		;

		$this->activityManager->expects($this->exactly(5))
			->method('publish');
		$this->notificationManager->expects($this->exactly(4))
			->method('notify');

		$this->invokePrivate($job, 'createPublicity', [10, 'author', 1337]);
	}
}
