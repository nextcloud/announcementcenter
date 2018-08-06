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

namespace OCA\AnnouncementCenter;

use OCP\BackgroundJob\IJobList;
use OCP\Comments\ICommentsManager;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IGroupManager;
use OCP\Notification\IManager as INotificationManager;
use OCP\IUser;
use OCP\IUserSession;

class Manager {

	/** @var IConfig */
	protected $config;

	/** @var IDBConnection */
	protected $connection;

	/** @var IGroupManager */
	protected $groupManager;

	/** @var INotificationManager */
	protected $notificationManager;

	/** @var ICommentsManager */
	protected $commentsManager;

	/** @var IJobList */
	protected $jobList;

	/** @var IUserSession */
	protected $userSession;

	/**
	 * @param IConfig $config
	 * @param IDBConnection $connection
	 * @param IGroupManager $groupManager
	 * @param INotificationManager $notificationManager
	 * @param ICommentsManager $commentsManager
	 * @param IJobList $jobList
	 * @param IUserSession $userSession
	 */
	public function __construct(IConfig $config,
								IDBConnection $connection,
								IGroupManager $groupManager,
								INotificationManager $notificationManager,
								ICommentsManager $commentsManager,
								IJobList $jobList,
								IUserSession $userSession) {
		$this->config = $config;
		$this->connection = $connection;
		$this->groupManager = $groupManager;
		$this->notificationManager = $notificationManager;
		$this->commentsManager = $commentsManager;
		$this->jobList = $jobList;
		$this->userSession = $userSession;
	}

	/**
	 * @param string $subject
	 * @param string $message
	 * @param string $user
	 * @param int $time
	 * @param string[] $groups
	 * @param bool $comments
	 * @return array
	 * @throws \InvalidArgumentException when the subject is empty or invalid
	 */
	public function announce($subject, $message, $user, $time, array $groups, $comments) {
		$subject = trim($subject);
		$message = trim($message);
		if (isset($subject[512])) {
			throw new \InvalidArgumentException('Invalid subject', 1);
		}

		if ($subject === '') {
			throw new \InvalidArgumentException('Invalid subject', 2);
		}

		$queryBuilder = $this->connection->getQueryBuilder();
		$queryBuilder->insert('announcements')
			->values([
				'announcement_time' => $queryBuilder->createNamedParameter($time),
				'announcement_user' => $queryBuilder->createNamedParameter($user),
				'announcement_subject' => $queryBuilder->createNamedParameter($subject),
				'announcement_message' => $queryBuilder->createNamedParameter($message),
				'allow_comments' => $queryBuilder->createNamedParameter((int) $comments),
			]);
		$queryBuilder->execute();

		$id = $queryBuilder->getLastInsertId();

		$addedGroups = 0;
		foreach ($groups as $group) {
			if ($this->groupManager->groupExists($group)) {
				$this->addGroupLink((int) $id, $group);
				$addedGroups++;
			}
		}

		if ($addedGroups === 0) {
			$this->addGroupLink((int) $id, 'everyone');
		}

		return $this->getAnnouncement($id, true, true);
	}

	/**
	 * @param int $announcementId
	 * @param string $group
	 */
	protected function addGroupLink($announcementId, $group) {
		$query = $this->connection->getQueryBuilder();
		$query->insert('announcements_groups')
			->values([
				'announcement_id' => $query->createNamedParameter($announcementId),
				'gid' => $query->createNamedParameter($group),
			]);
		$query->execute();
	}

	/**
	 * @param int $id
	 */
	public function delete($id) {
		// Delete notifications
		$notification = $this->notificationManager->createNotification();
		$notification->setApp('announcementcenter')
			->setObject('announcement', $id);
		$this->notificationManager->markProcessed($notification);

		// Delete comments
		$this->commentsManager->deleteCommentsAtObject('announcement', (string) $id);

		$query = $this->connection->getQueryBuilder();
		$query->delete('announcements')
			->where($query->expr()->eq('announcement_id', $query->createNamedParameter((int) $id)));
		$query->execute();

		$query = $this->connection->getQueryBuilder();
		$query->delete('announcements_groups')
			->where($query->expr()->eq('announcement_id', $query->createNamedParameter((int) $id)));
		$query->execute();
	}

	/**
	 * @param int $id
	 * @param bool $parseStrings
	 * @param bool $ignorePermissions
	 * @param bool $returnGroups
	 * @return array
	 * @throws \InvalidArgumentException when the id is invalid
	 */
	public function getAnnouncement($id, $parseStrings = true, $ignorePermissions = false, $returnGroups = true) {
		if (!$ignorePermissions) {
			$user = $this->userSession->getUser();
			if ($user instanceof IUser) {
				$userGroups = $this->groupManager->getUserGroupIds($user);
				$userGroups[] = 'everyone';
			} else {
				$userGroups = ['everyone'];
			}
			$isInAdminGroups = array_intersect($this->getAdminGroups(), $userGroups);

			if (empty($isInAdminGroups)) {
				$query = $this->connection->getQueryBuilder();
				$query->select('*')
					->from('announcements_groups')
					->where($query->expr()->eq('announcement_id', $query->createNamedParameter((int) $id)))
					->andWhere($query->expr()->in('gid', $query->createNamedParameter($userGroups, IQueryBuilder::PARAM_STR_ARRAY)))
					->setMaxResults(1);
				$result = $query->execute();
				$entry = $result->fetch();
				$result->closeCursor();

				if (!$entry) {
					throw new \InvalidArgumentException('Invalid ID');
				}
			}
		}

		$queryBuilder = $this->connection->getQueryBuilder();
		$query = $queryBuilder->select('*')
			->from('announcements')
			->where($queryBuilder->expr()->eq('announcement_id', $queryBuilder->createParameter('id')))
			->setParameter('id', (int) $id);
		$result = $query->execute();
		$row = $result->fetch();
		$result->closeCursor();

		if ($row === false) {
			throw new \InvalidArgumentException('Invalid ID');
		}

		$groups = null;
		if ($returnGroups && ($ignorePermissions || !empty($isInAdminGroups))) {
			$groups = $this->getGroups((int) $id);
		}

		$announcement = [
			'id'		=> (int) $row['announcement_id'],
			'author'	=> $row['announcement_user'],
			'time'		=> (int) $row['announcement_time'],
			'subject'	=> $parseStrings ? $this->parseSubject($row['announcement_subject']) : $row['announcement_subject'],
			'message'	=> $parseStrings ? $this->parseMessage($row['announcement_message']) : $row['announcement_message'],
			'groups'	=> $groups,
			'comments'	=> $row['allow_comments'] ? 0 : false,
		];

		if ($ignorePermissions || !empty($isInAdminGroups)) {
			$announcement['notifications'] = $this->hasNotifications((int) $id);
		}

		return $announcement;
	}

	/**
	 * @param int $limit
	 * @param int $offset
	 * @param bool $parseStrings
	 * @return array
	 */
	public function getAnnouncements($limit = 15, $offset = 0, $parseStrings = true) {
		$query = $this->connection->getQueryBuilder();
		$query->select('a.announcement_id', 'a.announcement_time', 'a.announcement_user', 'a.announcement_subject', 'a.announcement_message', 'a.allow_comments')
			->from('announcements', 'a')
			->orderBy('a.announcement_time', 'DESC')
			->groupBy('a.announcement_id', 'a.announcement_time', 'a.announcement_user', 'a.announcement_subject', 'a.announcement_message', 'a.allow_comments')
			->setMaxResults($limit);

		$user = $this->userSession->getUser();
		if ($user instanceof IUser) {
			$userGroups = $this->groupManager->getUserGroupIds($user);
			$userGroups[] = 'everyone';
		} else {
			$userGroups = ['everyone'];
		}

		$isInAdminGroups = array_intersect($this->getAdminGroups(), $userGroups);
		if (empty($isInAdminGroups)) {
			$query->leftJoin('a', 'announcements_groups', 'ag', $query->expr()->eq(
					'a.announcement_id', 'ag.announcement_id'
				))
				->andWhere($query->expr()->in('ag.gid', $query->createNamedParameter($userGroups, IQueryBuilder::PARAM_STR_ARRAY)));
		}

		if ($offset > 0) {
			$query->andWhere($query->expr()->lt('a.announcement_id', $query->createNamedParameter($offset, IQueryBuilder::PARAM_INT)));
		}

		$result = $query->execute();

		$announcements = [];
		while ($row = $result->fetch()) {
			$id = (int) $row['announcement_id'];
			$announcements[$id] = [
				'id'		=> $id,
				'author'	=> $row['announcement_user'],
				'time'		=> (int) $row['announcement_time'],
				'subject'	=> $parseStrings ? $this->parseSubject($row['announcement_subject']) : $row['announcement_subject'],
				'message'	=> $parseStrings ? $this->parseMessage($row['announcement_message']) : $row['announcement_message'],
				'groups'	=> null,
				'comments'	=> $row['allow_comments'] ? $this->getNumberOfComments($id) : false,
			];

			if (!empty($isInAdminGroups)) {
				$announcements[$id]['notifications'] = $this->hasNotifications($id);
			}
		}
		$result->closeCursor();

		if (!empty($isInAdminGroups)) {
			$allGroups = $this->getGroups(array_keys($announcements));
			foreach ($allGroups as $id => $groups) {
				$announcements[$id]['groups'] = $groups;
			}
		}

		return $announcements;
	}

	/**
	 * Return the groups (or string everyone) which have access to the announcement(s)
	 *
	 * @param int|int[] $ids
	 * @return string[]|array[]
	 */
	public function getGroups($ids) {
		$returnSingleResult = false;
		if (is_int($ids)) {
			$ids = [$ids];
			$returnSingleResult = true;
		}

		$query = $this->connection->getQueryBuilder();
		$query->select('*')
			->from('announcements_groups')
			->where($query->expr()->in('announcement_id', $query->createNamedParameter($ids, IQueryBuilder::PARAM_INT_ARRAY)));
		$result = $query->execute();

		$groups = [];
		while ($row = $result->fetch()) {
			if (!isset($groups[(int) $row['announcement_id']])) {
				$groups[(int) $row['announcement_id']] = [];
			}
			$groups[(int) $row['announcement_id']][] = $row['gid'];
		}
		$result->closeCursor();

		return $returnSingleResult ? (array) array_pop($groups) : $groups;
	}

	/**
	 * @param int $id
	 */
	public function markNotificationRead($id) {
		$user = $this->userSession->getUser();

		if ($user instanceof IUser) {
			$notification = $this->notificationManager->createNotification();
			$notification->setApp('announcementcenter')
				->setUser($user->getUID())
				->setObject('announcement', $id);
			$this->notificationManager->markProcessed($notification);
		}
	}

	/**
	 * @param int $id
	 * @return int
	 */
	protected function getNumberOfComments($id) {
		return $this->commentsManager->getNumberOfCommentsForObject('announcement', (string) $id);
	}

	/**
	 * @param int $id
	 * @return bool
	 */
	protected function hasNotifications($id) {
		$hasJob = $this->jobList->has('OCA\AnnouncementCenter\BackgroundJob', [
			'id' => $id,
			'activities' => true,
			'notifications' => true,
		]);

		$hasJob = $hasJob || $this->jobList->has('OCA\AnnouncementCenter\BackgroundJob', [
			'id' => $id,
			'activities' => false,
			'notifications' => true,
		]);

		if ($hasJob) {
			return true;
		}

		$notification = $this->notificationManager->createNotification();
		$notification->setApp('announcementcenter')
			->setObject('announcement', $id);
		return $this->notificationManager->getCount($notification) > 0;
	}

	/**
	 * @param int $id
	 */
	public function removeNotifications($id) {
		if ($this->jobList->has('OCA\AnnouncementCenter\BackgroundJob', [
			'id' => $id,
			'activities' => true,
			'notifications' => true,
		])) {
			// Delete the current background job and add a new one without notifications
			$this->jobList->remove('OCA\AnnouncementCenter\BackgroundJob', [
				'id' => $id,
				'activities' => true,
				'notifications' => true,
			]);
			$this->jobList->add('OCA\AnnouncementCenter\BackgroundJob', [
				'id' => $id,
				'activities' => true,
				'notifications' => false,
			]);

		} else {
			$this->jobList->remove('OCA\AnnouncementCenter\BackgroundJob', [
				'id' => $id,
				'activities' => false,
				'notifications' => true,
			]);
		}

		$notification = $this->notificationManager->createNotification();
		$notification->setApp('announcementcenter')
			->setObject('announcement', $id);
		$this->notificationManager->markProcessed($notification);
	}

	/**
	 * @param string $message
	 * @return string
	 */
	protected function parseMessage($message) {
		return str_replace("\n", '<br />', str_replace(['<', '>'], ['&lt;', '&gt;'], $message));
	}

	/**
	 * @param string $subject
	 * @return string
	 */
	protected function parseSubject($subject) {
		return str_replace("\n", ' ', str_replace(['<', '>'], ['&lt;', '&gt;'], $subject));
	}

	/**
	 * Check if the user is in the admin group
	 * @return bool
	 */
	public function checkIsAdmin() {
		$user = $this->userSession->getUser();

		if ($user instanceof IUser) {
			$groups = $this->getAdminGroups();
			foreach ($groups as $group) {
				if ($this->groupManager->isInGroup($user->getUID(), $group)) {
					return true;
				}
			}
		}

		return false;
	}

	protected function getAdminGroups() {
		$adminGroups = $this->config->getAppValue('announcementcenter', 'admin_groups', '["admin"]');
		$adminGroups = json_decode($adminGroups, true);
		return $adminGroups;
	}
}
