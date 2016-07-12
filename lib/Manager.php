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

namespace OCA\AnnouncementCenter;

use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserSession;

class Manager {

	/** @var IDBConnection */
	private $connection;

	/** @var IGroupManager */
	private $groupManager;


	/** @var IUserSession */
	private $userSession;

	/**
	 * @param IDBConnection $connection
	 * @param IGroupManager $groupManager
	 * @param IUserSession $userSession
	 */
	public function __construct(IDBConnection $connection, IGroupManager $groupManager, IUserSession $userSession) {
		$this->connection = $connection;
		$this->groupManager = $groupManager;
		$this->userSession = $userSession;
	}

	/**
	 * @param string $subject
	 * @param string $message
	 * @param string $user
	 * @param int $time
	 * @param string[] $groups
	 * @return array
	 * @throws \InvalidArgumentException when the subject is empty or invalid
	 */
	public function announce($subject, $message, $user, $time, array $groups) {
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
	 * @return array
	 * @throws \InvalidArgumentException when the id is invalid
	 */
	public function getAnnouncement($id, $parseStrings = true, $ignorePermissions = false) {
		if (!$ignorePermissions) {
			$user = $this->userSession->getUser();
			if ($user instanceof IUser) {
				$userGroups = $this->groupManager->getUserGroupIds($user);
				$userGroups[] = 'everyone';
			} else {
				$userGroups = ['everyone'];
			}

			if (!in_array('admin', $userGroups)) {
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
		if ($ignorePermissions || (isset($userGroups) && in_array('admin', $userGroups))) {
			$groups = $this->getGroups($id);
		}

		return [
			'id'		=> (int) $row['announcement_id'],
			'author'	=> $row['announcement_user'],
			'time'		=> (int) $row['announcement_time'],
			'subject'	=> ($parseStrings) ? $this->parseSubject($row['announcement_subject']) : $row['announcement_subject'],
			'message'	=> ($parseStrings) ? $this->parseMessage($row['announcement_message']) : $row['announcement_message'],
			'groups'	=> $groups,
		];
	}

	/**
	 * @param int $limit
	 * @param int $offset
	 * @param bool $parseStrings
	 * @return array
	 */
	public function getAnnouncements($limit = 15, $offset = 0, $parseStrings = true) {
		$query = $this->connection->getQueryBuilder();
		$query->select('a.*')
			->from('announcements', 'a')
			->orderBy('a.announcement_time', 'DESC')
			->groupBy('a.announcement_id')
			->setMaxResults($limit);

		$user = $this->userSession->getUser();
		if ($user instanceof IUser) {
			$userGroups = $this->groupManager->getUserGroupIds($user);
			$userGroups[] = 'everyone';
		} else {
			$userGroups = ['everyone'];
		}

		if (!in_array('admin', $userGroups)) {
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
				'subject'	=> ($parseStrings) ? $this->parseSubject($row['announcement_subject']) : $row['announcement_subject'],
				'message'	=> ($parseStrings) ? $this->parseMessage($row['announcement_message']) : $row['announcement_message'],
				'groups'	=> null,
			];
		}
		$result->closeCursor();

		if (in_array('admin', $userGroups)) {
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
}
