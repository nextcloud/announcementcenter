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
		$subject = (isset($subject[512])) ? substr($subject, 0, 512) : $subject;

		if ($subject === '') {
			throw new \RuntimeException('INVALID SUBJECT');
		}

		$this->connection->executeQuery(
			'INSERT INTO `*PREFIX*announcements` (`announcement_time`, `announcement_user`, `announcement_subject`, `announcement_message`) VALUES (?,?,?,?)',
			[$time, $user, $subject, $message]
		);

		return $this->connection->lastInsertId();
	}

	/**
	 * @param int $id
	 * @return string
	 */
	public function translateSubject($id) {
		$result = $this->connection->executeQuery('SELECT * FROM `*PREFIX*announcements` WHERE `announcement_id` = ?', [(int) $id]);
		$row = $result->fetch();
		return $row['announcement_subject'];
	}
}
