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
 * @method void setGroup(string $group)
 * @method string getGroup()
 */
class Group extends Entity {

	/** @var string */
	protected $group;

	public function __construct() {
		$this->addType('group', 'string');
	}

	/**
	 * @param string $columnName the name of the column
	 * @return string the property name
	 */
	public function columnToProperty($columnName): string {
		switch ($columnName) {
			case 'announcement_id':
				return 'id';
			case 'gid':
				return 'group';
		}

		return parent::columnToProperty($columnName);
	}

	/**
	 * @param string $property the name of the property
	 * @return string the column name
	 */
	public function propertyToColumn($property): string {
		switch ($property) {
			case 'id':
				return 'announcement_id';
			case 'group':
				return 'gid';
		}

		return parent::propertyToColumn($property);
	}
}
