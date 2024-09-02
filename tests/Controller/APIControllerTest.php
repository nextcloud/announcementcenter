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

namespace OCA\AnnouncementCenter\Tests\Controller;

use OCA\AnnouncementCenter\Controller\APIController;
use OCA\AnnouncementCenter\Manager;
use OCA\AnnouncementCenter\Model\Announcement;
use OCA\AnnouncementCenter\Model\NotificationType;
use OCA\AnnouncementCenter\Service\Markdown;
use OCA\AnnouncementCenter\Tests\TestCase;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IConfig;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IInitialStateService;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

/**
 * @package OCA\AnnouncementCenter\Tests\Controller
 */
class APIControllerTest extends TestCase {
	protected IRequest|MockObject $request;
	protected IGroupManager|MockObject $groupManager;
	protected IUserManager|MockObject $userManager;
	protected IJobList|MockObject $jobList;
	protected IL10N|MockObject $l;
	protected Manager|MockObject $manager;
	protected IConfig|MockObject $config;
	protected ITimeFactory|MockObject $timeFactory;
	protected IUserSession|MockObject $userSession;
	protected IInitialStateService|MockObject $initialStateService;
	protected LoggerInterface|MockObject $logger;
	protected Markdown|MockObject $markdown;
	protected NotificationType|MockObject $notificationType;

	protected function setUp(): void {
		parent::setUp();

		$this->request = $this->createMock(IRequest::class);
		$this->groupManager = $this->createMock(IGroupManager::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->l = $this->createMock(IL10N::class);
		$this->manager = $this->createMock(Manager::class);
		$this->timeFactory = $this->createMock(ITimeFactory::class);
		$this->userSession = $this->createMock(IUserSession::class);
		$this->notificationType = $this->createMock(NotificationType::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->markdown = $this->createMock(Markdown::class);

		$this->l
			->method('t')
			->willReturnCallback(function ($string, $args) {
				return vsprintf($string, $args);
			});
	}

	/**
	 * @param array $methods
	 * @return APIController|MockObject
	 */
	protected function getController(array $methods = []): APIController {
		if (empty($methods)) {
			return new APIController(
				'announcementcenter',
				$this->request,
				$this->groupManager,
				$this->userManager,
				$this->l,
				$this->manager,
				$this->timeFactory,
				$this->userSession,
				$this->notificationType,
				$this->logger,
				$this->markdown
			);
		}

		/** @var APIController|MockBuilder $mock */
		$mock = $this->getMockBuilder(APIController::class);
		return $mock->setConstructorArgs([
			'announcementcenter',
			$this->request,
			$this->groupManager,
			$this->userManager,
			$this->l,
			$this->manager,
			$this->timeFactory,
			$this->userSession,
			$this->notificationType,
			$this->logger,
			$this->markdown
		])
			->setMethods($methods)
			->getMock();
	}

	protected function getUserMock(string $uid, string $displayName): IUser {
		/** @var IUser|MockObject $user */
		$user = $this->createMock(IUser::class);
		$user
			->method('getUID')
			->willReturn($uid);
		$user
			->method('getDisplayName')
			->willReturn($displayName);
		return $user;
	}

	public function dataGet(): array {
		return [
			[0, [], [], []],
			[1, [], [], []],
			[2, [], [], []],
			[
				1,
				[
					['id' => 1337, 'author' => 'author1', 'subject' => 'Subject #1', 'message' => 'Message #1', 'plainMessage' => 'Message #1', 'time' => 1440672792, 'groups' => ['gid1'], 'comments' => false],
				], [],
				[
					['id' => 1337, 'author' => 'author1', 'author_id' => 'author1', 'subject' => 'Subject #1', 'message' => 'Message #1', 'plainMessage' => 'Message #1', 'time' => 1440672792, 'groups' => ['gid1'], 'comments' => false],
				],
			],
			[
				1,
				[
					['id' => 23, 'author' => 'author1', 'subject' => 'Subject #1', 'message' => 'Message #1', 'plainMessage' => 'Message #1', 'time' => 1440672792, 'groups' => ['gid1'], 'comments' => false],
				],
				[
					['author1', $this->getUserMock('author1', 'Author One')],
				],
				[
					['id' => 23, 'author' => 'Author One', 'author_id' => 'author1', 'subject' => 'Subject #1', 'message' => 'Message #1', 'plainMessage' => 'Message #1', 'time' => 1440672792, 'groups' => ['gid1'], 'comments' => false],
				],
			],
			[
				1,
				[
					['id' => 42, 'author' => 'author1', 'subject' => "Subject\n<html>#1</html>", 'message' => "Message\n<html>#1</html>", 'plainMessage' => 'Message\n#1', 'time' => 1440672792, 'groups' => null, 'comments' => 31],
				],
				[],
				[
					['id' => 42, 'author' => 'author1', 'author_id' => 'author1', 'subject' => 'Subject <html>#1</html>', 'message' => 'Message<br />&lt;html&gt;#1&lt;/html&gt;', 'plainMessage' => 'Message\n#1', 'time' => 1440672792, 'groups' => null, 'comments' => 31],
				],
			],
		];
	}

	/**
	 * @dataProvider dataGet
	 */
	public function legacyTestGet(int $offset, array $announcements, array $userMap, array $expected): void {
		$this->userManager
			->method('get')
			->willReturnMap($userMap);

		$comments = [];
		$announcements = array_map(function (array $data) use (&$comments) {
			$announcement = new Announcement();
			$announcement->setId($data['id']);
			$announcement->setUser($data['author']);
			$announcement->setSubject($data['subject']);
			$announcement->setMessage($data['message']);
			$announcement->setPlainMessage($data['plainMessage']);
			$announcement->setTime($data['time']);
			$announcement->setAllowComments($data['comments'] === false ? 0 : 1);

			if ($data['comments'] !== false) {
				$comments[] = [$announcement, $data['comments']];
			}

			return $announcement;
		}, $announcements);

		$this->manager
			->method('getAnnouncements')
			->with($offset)
			->willReturn($announcements);

		$this->manager->expects(self::exactly(count($comments)))
			->method('getNumberOfComments')
			->willReturnMap($comments);

		$controller = $this->getController();
		$jsonResponse = $controller->get($offset);

		self::assertEquals($expected, $jsonResponse->getData());
	}

	public function dataDelete(): array {
		return [
			[42, true, Http::STATUS_OK],
			[1337, false, Http::STATUS_FORBIDDEN],
		];
	}

	/**
	 * @dataProvider dataDelete
	 */
	public function testDelete(int $id, bool $isAdmin, int $statusCode) {
		$this->manager->expects(self::once())
			->method('checkIsAdmin')
			->willReturn($isAdmin);

		if ($isAdmin) {
			$this->manager->expects(self::once())
				->method('getAnnouncement')
				->with($id);
			$this->manager->expects(self::once())
				->method('delete')
				->with($id);
			$this->logger->expects($this->once())
				->method('info');
		} else {
			$this->manager->expects(self::never())
				->method('delete');
		}

		$controller = $this->getController();
		$response = $controller->delete($id);

		self::assertEquals($statusCode, $response->getStatus());
	}

	public function dataAddThrows(): array {
		return [
			['', ['error' => 'The subject is too long or empty']],
			[str_repeat('a', 513), ['error' => 'The subject is too long or empty']],
		];
	}

	/**
	 * @dataProvider dataAddThrows
	 */
	public function testAddThrows(string $subject, array $expectedData) {
		$this->manager->expects(self::once())
			->method('checkIsAdmin')
			->willReturn(true);
		$this->userSession->expects(self::once())
			->method('getUser')
			->willReturn($this->getUserMock('author', 'author'));

		$this->manager->expects(self::once())
			->method('announce')
			->with($subject, '', '', 'author', self::anything())
			->willThrowException(new \InvalidArgumentException());

		$controller = $this->getController(['createPublicity']);
		$controller->expects(self::never())
			->method('createPublicity');

		$response = $controller->add($subject, '', [], true, true, true, true);

		self::assertInstanceOf(DataResponse::class, $response);
		self::assertSame($expectedData, $response->getData());
	}

	public function testAddNoAdmin() {
		$this->manager->expects(self::once())
			->method('checkIsAdmin')
			->willReturn(false);

		$this->manager->expects(self::never())
			->method('announce');

		$controller = $this->getController(['createPublicity']);
		$controller->expects(self::never())
			->method('createPublicity');

		$response = $controller->add('subject', '', [], true, true, true, true);

		self::assertInstanceOf(DataResponse::class, $response);
		self::assertSame(Http::STATUS_FORBIDDEN, $response->getStatus());
	}

	public function dataAdd(): array {
		return [
			['subject1', 'message1', 'message1', ['gid1'], true, true, true, false],
			['subject2', 'message2', 'message2', ['gid2'], true, false, true, false],
			['subject3', 'message3', 'message3', ['gid3'], false, true, true, false],
			['subject4', 'message4', 'message4', ['gid4'], false, false, true, false],
			['subject4', 'message4', 'message5', ['gid4'], false, false, true, true],
		];
	}

	/**
	 * @dataProvider dataAdd
	 */
	public function legacyTestAdd(string $subject, string $message, string $plainMessage, array $groups, bool $activities, bool $notifications, bool $emails, bool $comments) {
		$this->manager->expects(self::once())
			->method('checkIsAdmin')
			->willReturn(true);
		$this->userSession->expects(self::once())
			->method('getUser')
			->willReturn($this->getUserMock('author', 'author'));

		$this->manager->expects(self::once())
			->method('announce')
			->with($subject, $message, 'author', self::anything(), $groups, $comments)
			->willReturn([
				'author' => 'author',
				'subject' => $subject,
				'message' => $message,
				'plainMessage' => $plainMessage,
				'time' => time(),
				'id' => 10,
				'comments' => $comments,
			]);
		$this->userManager->expects(self::once())
			->method('get')
			->with('author')
			->willReturn($this->getUserMock('author', 'Author'));

		$this->logger->expects($this->once())
			->method('info');

		$controller = $this->getController();

		$response = $controller->add($subject, $plainMessage, $groups, $activities, $notifications, $emails, $comments);

		self::assertInstanceOf(DataResponse::class, $response);
		$data = $response->getData();
		self::assertArrayHasKey('time', $data);
		self::assertIsInt($data['time']);
		unset($data['time']);
		self::assertEquals([
			'author' => 'Author',
			'author_id' => 'author',
			'subject' => $subject,
			'message' => $message,
			'plainMessage' => $plainMessage,
			'id' => 10,
			'comments' => $comments,
			'notifications' => $notifications,
			'emails' => $emails,
		], $data);
	}

	protected function getGroupMock(string $gid): IGroup {
		/** @var IGroup|MockObject $group */
		$group = $this->createMock(IGroup::class);

		$group
			->method('getGID')
			->willReturn($gid);
		$group
			->method('getDisplayName')
			->willReturn($gid . '-displayname');

		return $group;
	}

	public function dataSearchGroup(): array {
		return [
			[true, 'gid', [], [], Http::STATUS_OK],
			[true, 'gid', [$this->getGroupMock('gid1'), $this->getGroupMock('gid2')], [['id' => 'gid1', 'label' => 'gid1-displayname'], ['id' => 'gid2', 'label' => 'gid2-displayname']], Http::STATUS_OK],
			[false, '', null, ['message' => 'Logged in user must be an admin'], Http::STATUS_FORBIDDEN],
		];
	}

	/**
	 * @dataProvider dataSearchGroup
	 */
	public function testSearchGroup(bool $isAdmin, string $pattern, ?array $groupSearch, array $expected, int $code) {
		$this->manager->expects(self::once())
			->method('checkIsAdmin')
			->willReturn($isAdmin);

		if ($groupSearch !== null) {
			$this->groupManager->expects(self::once())
				->method('search')
				->willReturn($groupSearch);
		} else {
			$this->groupManager->expects(self::never())
				->method('search');
		}

		$controller = $this->getController();
		$response = $controller->searchGroups($pattern);
		self::assertSame($code, $response->getStatus());
		self::assertSame($expected, $response->getData());
	}
}
