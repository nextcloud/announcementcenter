<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\AnnouncementCenter\Model;

use OCP\AppFramework\Db\DoesNotExistException;

class AnnouncementDoesNotExistException extends DoesNotExistException {
	public function __construct() {
		parent::__construct('Announcement does not exist');
	}
}
