<?php

namespace OCA\AnnouncementCenter\Service;

use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IUserManager;
use OCP\IL10N;
use Psr\Log\LoggerInterface;

class UserService
{

	private $groupManager;
	private $userManager;
	private IL10N $l;
	private LoggerInterface $logger;
	public function __construct(IUserManager $userManager, IGroupManager $groupManager, IL10N $l, LoggerInterface $logger)
	{
		$this->userManager = $userManager;
		$this->groupManager = $groupManager;
		$this->l = $l;
		$this->logger = $logger;
	}

	public function createGroupWithAllUsers(): IGroup
	{
		// 根据用户的语言设置确定组名
		$groupName = $this->l->t("everyone");
		$this->logger->warning(" l10n:" . $this->l->t("everyone"));
		// 检查组是否存在
		$group = $this->groupManager->get($groupName);
		// 如果组不存在，创建它并添加所有用户
		if ($group === null) {
			// 创建新组
			$this->logger->warning("group add:" . $groupName);
			$group = $this->groupManager->createGroup($groupName);
		}
		
		// 获取所有用户
		$users = $this->userManager->search('');
		// 将每个用户添加到新组
		foreach ($users as $lazyuser) {
			if (!$group->inGroup($lazyuser)) {
				$userId = $lazyuser->getUID();
				$user = $this->userManager->get($userId);
				$group->addUser($user);
			}
		}

		return $group;
	}
}
