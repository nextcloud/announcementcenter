<?php
/**
 * ownCloud
 *
 * @author Joas Schilling
 * @copyright 2015 Joas Schilling nickvergessen@owncloud.com
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA\AnnouncementCenter;

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
	 * @return int
	 * @throws \RuntimeException when the subject is empty or invalid
	 */
	public function announce($subject, $message, $user, $time) {
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
		$query = $queryBuilder->select('announcement_id')
			->from('announcements')
			->where($queryBuilder->expr()->eq('announcement_time', $queryBuilder->createParameter('time')))
			->andWhere($queryBuilder->expr()->eq('announcement_user', $queryBuilder->createParameter('user')))
			->orderBy('announcement_id', 'DESC')
			->setParameter('time', (int) $time)
			->setParameter('user', $user);
		$result = $query->execute();
		$row = $result->fetch();
		$result->closeCursor();

		return (int) $row['announcement_id'];
	}

	public function delete($id) {
		$queryBuilder = $this->connection->getQueryBuilder();
		$queryBuilder->delete('announcements')
			->where($queryBuilder->expr()->eq('announcement_id', $queryBuilder->createParameter('id')))
			->setParameter('id', (int) $id)
			->execute();
	}

	/**
	 * @param int $id
	 * @return array
	 * @throws \RuntimeException when the id is invalid
	 */
	public function getAnnouncement($id) {
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
			'author'	=> $row['announcement_user'],
			'time'		=> (int) $row['announcement_time'],
			'subject'	=> $row['announcement_subject'],
			'message'	=> $row['announcement_message'],
		];
	}

	/**
	 * @param int $limit
	 * @param int $offset
	 * @return array
	 */
	public function getAnnouncements($limit = 15, $offset = 0) {
		$queryBuilder = $this->connection->getQueryBuilder();
		$query = $queryBuilder->select('*')
			->from('announcements')
			->orderBy('announcement_time', 'DESC')
			->setFirstResult($offset)
			->setMaxResults($limit);
		$result = $query->execute();


		$announcements = [];
		while ($row = $result->fetch()) {
			$announcements[] = [
				'author'	=> $row['announcement_user'],
				'time'		=> $row['announcement_time'],
				'subject'	=> $row['announcement_subject'],
				'message'	=> $row['announcement_message'],
			];
		}
		$result->closeCursor();


		return $announcements;
	}
}
