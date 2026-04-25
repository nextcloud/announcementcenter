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
	#[\Override]
	public function columnToProperty($columnName): string {
		return match ($columnName) {
			'announcement_id' => 'id',
			'gid' => 'group',
			default => parent::columnToProperty($columnName),
		};
	}

	/**
	 * @param string $property the name of the property
	 * @return string the column name
	 */
	#[\Override]
	public function propertyToColumn($property): string {
		return match ($property) {
			'id' => 'announcement_id',
			'group' => 'gid',
			default => parent::propertyToColumn($property),
		};
	}
}
