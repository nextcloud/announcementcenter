<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2019 Joas Schilling <coding@schilljs.com>
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

namespace OCA\AnnouncementCenter\Model;

use OCP\AppFramework\Db\Entity;

/**
 * @method void setTime(int $time)
 * @method int getTime()
 * @method void setUser(string $user)
 * @method string getUser()
 * @method void setSubject(string $subject)
 * @method string getSubject()
 * @method void setMessage(string $message)
 * @method string getMessage()
 * @method void setPlainMessage(string $plainMessage)
 * @method string getPlainMessage()
 * @method void setAllowComments(int $allowComments)
 * @method int getAllowComments()
 * @method void setGroups(string $groups)
 * @method string getGroups()
 * @method void setScheduleTime(?int $scheduleTime)
 * @method ?int getScheduleTime()
 * @method void setDeleteTime(?int $scheduleTime)
 * @method ?int getDeleteTime()
 * @method void setNotTypes(int $notificationTypes)
 * @method int getNotTypes()
 */
class Announcement extends Entity {

	/** @var int */
	protected $time;

	/** @var string */
	protected $user;

	/** @var string */
	protected $subject;

	/** @var string */
	protected $message;

	/** @var string */
	protected $plainMessage;

	/** @var int */
	protected $allowComments;

	/** @var int */
	protected $scheduleTime;

	/** @var int */
	protected $deleteTime;

	/** @var string */
	protected $groups;

	/** @var int */
	protected $notTypes;

	public function __construct() {
		$this->addType('time', 'int');
		$this->addType('user', 'string');
		$this->addType('subject', 'string');
		$this->addType('message', 'string');
		$this->addType('plainMessage', 'string');
		$this->addType('allowComments', 'int');
		$this->addType('scheduleTime', 'int');
		$this->addType('deleteTime', 'int');
		$this->addType('groups', 'string');
		$this->addType('notTypes', 'int');
	}

	public function getParsedSubject(): string {
		return trim(str_replace("\n", ' ', $this->getSubject()));
	}

	public function getTruncatedMessage(int $length = 100): string {
		if (mb_strlen($this->getPlainMessage()) > $length) {
			return mb_substr($this->getPlainMessage(), 0, $length) . '…';
		}
		return $this->getPlainMessage();
	}

	/**
	 * @param string $columnName the name of the column
	 * @return string the property name
	 */
	public function columnToProperty($columnName): string {
		// Strip off announcement_
		if (strpos($columnName, 'announcement_') === 0) {
			$columnName = substr($columnName, strlen('announcement_'));
		}

		return parent::columnToProperty($columnName);
	}

	/**
	 * @param string $property the name of the property
	 * @return string the column name
	 */
	public function propertyToColumn($property): string {
		if ($property !== 'allowComments') {
			$property = 'announcement' . ucfirst($property);
		}

		return parent::propertyToColumn($property);
	}

	/**
	 * @param array $groups a list of groups
	 */
	public function setGroupsEncode($groups) {
		// encode groups as a single string for the database
		$this->setGroups(json_encode($groups));
	}

	/**
	 * @return array a list of groups
	 */
	public function getGroupsDecode(): array {
		return json_decode($this->getGroups());
	}
}
