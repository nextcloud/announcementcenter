<?php

/**
 * @copyright Copyright (c) 2023 insiinc <insiinc@outlook.com>
 *
 * @author insiinc <insiinc@outlook.com>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

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
