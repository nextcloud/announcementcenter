<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2020 Julius Härtl <jus@bitgrid.net>
 *
 * @author Julius Härtl <jus@bitgrid.net>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *tests/Core/Controller/AvatarControllerTest.php
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\AnnouncementCenter\Dashboard;

use OCA\AnnouncementCenter\AppInfo\Application;
use OCA\AnnouncementCenter\Manager;
use OCA\AnnouncementCenter\Model\Announcement;
use OCP\AppFramework\Services\IInitialState;
use OCP\Dashboard\IWidget;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Util;

class Widget implements IWidget {

	/** @var Manager */
	private $manager;
	/** @var IUserManager */
	private $userManager;
	/** @var IURLGenerator */
	private $url;
	/** @var IInitialState */
	private $initialState;
	/** @var IL10N */
	private $l10n;

	public function __construct(
		Manager $manager,
		IUserManager $userManager,
		IURLGenerator $url,
		IInitialState $initialState,
		IL10N $l10n
	) {
		$this->manager = $manager;
		$this->userManager = $userManager;
		$this->url = $url;
		$this->initialState = $initialState;
		$this->l10n = $l10n;
	}

	/**
	 * @inheritDoc
	 */
	public function getId(): string {
		return Application::APP_ID;
	}

	/**
	 * @inheritDoc
	 */
	public function getTitle(): string {
		return $this->l10n->t('Announcements');
	}

	/**
	 * @inheritDoc
	 */
	public function getOrder(): int {
		return 1;
	}

	/**
	 * @inheritDoc
	 */
	public function getIconClass(): string {
		return 'icon-announcementcenter-dark';
	}

	/**
	 * @inheritDoc
	 */
	public function getUrl(): ?string {
		return $this->url->linkToRouteAbsolute('announcementcenter.page.index');
	}

	/**
	 * @inheritDoc
	 */
	public function load(): void {
		$this->initialState->provideLazyInitialState(Application::APP_ID . '_dashboard', function () {
			$announcements = $this->manager->getAnnouncements();
			return array_map([$this, 'renderAnnouncement'], $announcements);
		});
		Util::addStyle(Application::APP_ID, 'dashboard');
		Util::addScript(Application::APP_ID, Application::APP_ID . '-dashboard');
	}

	protected function renderAnnouncement(Announcement $announcement): array {
		$displayName = $announcement->getUser();
		$user = $this->userManager->get($announcement->getUser());
		if ($user instanceof IUser) {
			$displayName = $user->getDisplayName();
		}

		$result = [
			'id' => $announcement->getId(),
			'author_id' => $announcement->getUser(),
			'author' => $displayName,
			'time' => $announcement->getTime(),
			'subject' => $announcement->getParsedSubject(),
			'message' => $announcement->getParsedMessage(),
			'groups' => null,
			'comments' => $announcement->getAllowComments() ? $this->manager->getNumberOfComments($announcement) : false,
		];

		if ($this->manager->checkIsAdmin()) {
			$result['groups'] = $this->manager->getGroups($announcement);
			$result['notifications'] = $this->manager->hasNotifications($announcement);
		}

		return $result;
	}
}
