<?php

/**
 * @copyright Copyright (c) 2023 insiinc <insiinc@outlook.com>
 *
 * @author insiinc <insiinc@outlook.com>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */


declare(strict_types=1);


namespace OCA\AnnouncementCenter\Service;

use OCA\AnnouncementCenter\NoPermissionException;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IUserSession;

class ConfigService
{


	private IConfig $config;
	private ?string $userId = null;


	public function __construct(
		IConfig $config,

	) {

		$this->config = $config;
	}

	public function getUserId(): ?string
	{
		if (!$this->userId) {
			// We cannot use DI for the userId or UserSession as the ConfigService
			// is initiated too early before the session is actually loaded
			$user = \OCP\Server::get(IUserSession::class)->getUser();
			$this->userId = $user ? $user->getUID() : null;
		}

		return $this->userId;
	}
	public function getAttachmentFolder(string $userId = null): string
	{
		if ($userId === null && $this->getUserId() === null) {
			throw new NoPermissionException('Must be logged in get the attachment folder');
		}

		return $this->config->getUserValue($userId ?? $this->getUserId(), 'AnnouncementCenter', 'attachment_folder', '/AnnouncementCenter');
	}
}
