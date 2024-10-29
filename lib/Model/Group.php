<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
