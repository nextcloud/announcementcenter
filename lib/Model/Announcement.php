<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
		$this->addType('time', 'integer');
		$this->addType('user', 'string');
		$this->addType('subject', 'string');
		$this->addType('message', 'string');
		$this->addType('plainMessage', 'string');
		$this->addType('allowComments', 'integer');
		$this->addType('scheduleTime', 'integer');
		$this->addType('deleteTime', 'integer');
		$this->addType('groups', 'string');
		$this->addType('notTypes', 'integer');
	}

	public function getParsedSubject(): string {
		return trim(str_replace("\n", ' ', $this->getSubject()));
	}

	public function getParsedMessage(): string {
		return str_replace(['<', '>', "\n"], ['&lt;', '&gt;', '<br />'], $this->getMessage());
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
