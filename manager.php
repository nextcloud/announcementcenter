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

class Manager {
	/** @var IDBConnection */
	private $connection;

	/**
	 * @param IDBConnection $connection
	 */
	public function __construct(IDBConnection $connection){
		$this->connection = $connection;
	}

	/**
	 * @param string $subject
	 * @param string $message
	 * @param string $user
	 * @param int $time
	 * @param bool $parseStrings If the returned message should be parsed or not
	 * @return array
	 * @throws \InvalidArgumentException when the subject is empty or invalid
	 */
	public function announce($subject, $message, $user, $time, $parseStrings = true) {
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
				'announcement_time' => $queryBuilder->createParameter('time'),
				'announcement_user' => $queryBuilder->createParameter('user'),
				'announcement_subject' => $queryBuilder->createParameter('subject'),
				'announcement_message' => $queryBuilder->createParameter('message'),
			])
			->setParameter('time', $time)
			->setParameter('user', $user)
			->setParameter('subject', $subject)
			->setParameter('message', $message);
		$queryBuilder->execute();

		$queryBuilder = $this->connection->getQueryBuilder();
		$query = $queryBuilder->select('*')
			->from('announcements')
			->where($queryBuilder->expr()->eq('announcement_time', $queryBuilder->createParameter('time')))
			->andWhere($queryBuilder->expr()->eq('announcement_user', $queryBuilder->createParameter('user')))
			->orderBy('announcement_id', 'DESC')
			->setParameter('time', (int) $time)
			->setParameter('user', $user);
		$result = $query->execute();
		$row = $result->fetch();
		$result->closeCursor();

		return [
			'id'		=> (int) $row['announcement_id'],
			'author'	=> $row['announcement_user'],
			'time'		=> (int) $row['announcement_time'],
			'subject'	=> ($parseStrings) ? $this->parseSubject($row['announcement_subject']) : $row['announcement_subject'],
			'message'	=> ($parseStrings) ? $this->parseMessage($row['announcement_message']) : $row['announcement_message'],
		];
	}

	/**
	 * @param int $id
	 */
	public function delete($id) {
		$queryBuilder = $this->connection->getQueryBuilder();
		$queryBuilder->delete('announcements')
			->where($queryBuilder->expr()->eq('announcement_id', $queryBuilder->createParameter('id')))
			->setParameter('id', (int) $id)
			->execute();
	}

	/**
	 * @param int $id
	 * @param bool $parseStrings
	 * @return array
	 * @throws \InvalidArgumentException when the id is invalid
	 */
	public function getAnnouncement($id, $parseStrings = true) {
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

		return [
			'id'		=> (int) $row['announcement_id'],
			'author'	=> $row['announcement_user'],
			'time'		=> (int) $row['announcement_time'],
			'subject'	=> ($parseStrings) ? $this->parseSubject($row['announcement_subject']) : $row['announcement_subject'],
			'message'	=> ($parseStrings) ? $this->parseMessage($row['announcement_message']) : $row['announcement_message'],
		];
	}

	/**
	 * @param int $limit
	 * @param int $offset
	 * @param bool $parseStrings
	 * @return array
	 */
	public function getAnnouncements($limit = 15, $offset = 0, $parseStrings = true) {
		$queryBuilder = $this->connection->getQueryBuilder();
		$query = $queryBuilder->select('*')
			->from('announcements')
			->orderBy('announcement_time', 'DESC')
			->setMaxResults($limit);

		if ($offset > 0) {
			$query->where($query->expr()->lt('announcement_id', $query->createNamedParameter($offset, IQueryBuilder::PARAM_INT)));
		}

		$result = $query->execute();


		$announcements = [];
		while ($row = $result->fetch()) {
			$announcements[] = [
				'id'		=> (int) $row['announcement_id'],
				'author'	=> $row['announcement_user'],
				'time'		=> (int) $row['announcement_time'],
				'subject'	=> ($parseStrings) ? $this->parseSubject($row['announcement_subject']) : $row['announcement_subject'],
				'message'	=> ($parseStrings) ? $this->parseMessage($row['announcement_message']) : $row['announcement_message'],
			];
		}
		$result->closeCursor();


		return $announcements;
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
