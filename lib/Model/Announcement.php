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
 * @method void setAllowComments(int $allowComments)
 * @method int getAllowComments()
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

	/** @var int */
	protected $allowComments;

	public function __construct() {
		$this->addType('time', 'int');
		$this->addType('user', 'string');
		$this->addType('subject', 'string');
		$this->addType('message', 'string');
		$this->addType('allowComments', 'int');
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
}
