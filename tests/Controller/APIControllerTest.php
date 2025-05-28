<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\AnnouncementCenter\Tests\Controller;

use OCA\AnnouncementCenter\Controller\APIController;
use OCA\AnnouncementCenter\Manager;
use OCA\AnnouncementCenter\Model\NotificationType;
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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

/**
 * @package OCA\AnnouncementCenter\Tests\Controller
 */
class APIControllerTest extends TestCase {
	protected IRequest&MockObject $request;
	protected IGroupManager&MockObject $groupManager;
	protected IUserManager&MockObject $userManager;
	protected IL10N&MockObject $l;
	protected Manager&MockObject $manager;
	protected IConfig&MockObject $config;
	protected ITimeFactory&MockObject $timeFactory;
	protected IUserSession&MockObject $userSession;
	protected IInitialStateService&MockObject $initialStateService;
	protected LoggerInterface&MockObject $logger;
	protected NotificationType&MockObject $notificationType;

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

		$this->l
			->method('t')
			->willReturnCallback(function ($string, $args) {
				return vsprintf($string, $args);
			});
	}

	/**
	 * @param array $methods
	 * @return APIController&MockObject
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
				$this->logger
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
			$this->logger
		])
			->onlyMethods($methods)
			->getMock();
	}

	protected function getUserMock(string $uid, string $displayName): IUser {
		/** @var IUser&MockObject $user */
		$user = $this->createMock(IUser::class);
		$user
			->method('getUID')
			->willReturn($uid);
		$user
			->method('getDisplayName')
			->willReturn($displayName);
		return $user;
	}

	public static function dataDelete(): array {
		return [
			[42, true, Http::STATUS_OK],
			[1337, false, Http::STATUS_FORBIDDEN],
		];
	}

	#[DataProvider('dataDelete')]
	public function testDelete(int $id, bool $isAdmin, int $statusCode): void {
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

	public static function dataAddThrows(): array {
		return [
			['', ['error' => 'The subject is too long or empty']],
			[str_repeat('a', 513), ['error' => 'The subject is too long or empty']],
		];
	}

	#[DataProvider('dataAddThrows')]
	public function testAddThrows(string $subject, array $expectedData): void {
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

		$controller = $this->getController();

		$response = $controller->add($subject, '', '', [], true, true, true, true);

		self::assertInstanceOf(DataResponse::class, $response);
		self::assertSame($expectedData, $response->getData());
	}

	public function testAddNoAdmin(): void {
		$this->manager->expects(self::once())
			->method('checkIsAdmin')
			->willReturn(false);

		$this->manager->expects(self::never())
			->method('announce');

		$controller = $this->getController();

		$response = $controller->add('subject', '', '', [], true, true, true, true);

		self::assertInstanceOf(DataResponse::class, $response);
		self::assertSame(Http::STATUS_FORBIDDEN, $response->getStatus());
	}

	public static function dataAdd(): array {
		return [
			['subject1', 'message1', 'message1', ['gid1'], true, true, true, false],
			['subject2', 'message2', 'message2', ['gid2'], true, false, true, false],
			['subject3', 'message3', 'message3', ['gid3'], false, true, true, false],
			['subject4', 'message4', 'message4', ['gid4'], false, false, true, false],
			['subject4', 'message4', 'message5', ['gid4'], false, false, true, 0],
		];
	}

	protected function getGroupMock(string $gid): IGroup {
		/** @var IGroup&MockObject $group */
		$group = $this->createMock(IGroup::class);

		$group
			->method('getGID')
			->willReturn($gid);
		$group
			->method('getDisplayName')
			->willReturn($gid . '-displayname');

		return $group;
	}

	public static function dataSearchGroup(): array {
		return [
			[true, 'gid', [], [], Http::STATUS_OK],
			[true, 'gid', ['gid1', 'gid2'], [['id' => 'gid1', 'label' => 'gid1-displayname'], ['id' => 'gid2', 'label' => 'gid2-displayname']], Http::STATUS_OK],
			[false, '', null, ['message' => 'Logged in user must be an admin'], Http::STATUS_FORBIDDEN],
		];
	}

	#[DataProvider('dataSearchGroup')]
	public function testSearchGroup(bool $isAdmin, string $pattern, ?array $gids, array $expected, int $code): void {
		$groupSearch = null;
		if ($gids !== null) {
			$groupSearch = [];
			foreach ($gids as $gid) {
				$groupSearch[] = $this->getGroupMock($gid);
			}
		}

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
