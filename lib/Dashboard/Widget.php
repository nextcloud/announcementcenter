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
use OCP\Dashboard\IAPIWidget;
use OCP\Dashboard\IButtonWidget;
use OCP\Dashboard\IIconWidget;
use OCP\Dashboard\Model\WidgetButton;
use OCP\Dashboard\Model\WidgetItem;
use OCP\IDateTimeFormatter;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\Util;

class Widget implements IAPIWidget, IButtonWidget, IIconWidget {
	private Manager $manager;
	private IUserManager $userManager;
	private IURLGenerator $url;
	private IInitialState $initialState;
	private IDateTimeFormatter $dateTimeFormatter;
	private IL10N $l10n;

	public function __construct(
		Manager $manager,
		IUserManager $userManager,
		IURLGenerator $url,
		IInitialState $initialState,
		IDateTimeFormatter $dateTimeFormatter,
		IL10N $l10n
	) {
		$this->manager = $manager;
		$this->userManager = $userManager;
		$this->url = $url;
		$this->initialState = $initialState;
		$this->dateTimeFormatter = $dateTimeFormatter;
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
	public function getIconUrl(): string {
		return $this->url->getAbsoluteURL($this->url->imagePath('announcementcenter', 'announcementcenter-dark.svg'));
	}

	/**
	 * @return WidgetButton[]
	 */
	public function getWidgetButtons(string $userId): array {
		$buttons = [];
		$buttons[] = new WidgetButton(
			WidgetButton::TYPE_MORE,
			$this->getUrl(),
			$this->l10n->t('Read more')
		);
		return $buttons;
	}

	/**
	 * @inheritDoc
	 */
	public function load(): void {
		$this->initialState->provideLazyInitialState(Application::APP_ID . '_dashboard', function () {
			$announcements = $this->manager->getAnnouncements(1,7);
			return array_map([$this, 'renderAnnouncement'], $announcements['data']);
		});
		Util::addStyle(Application::APP_ID, 'dashboard');
		Util::addScript(Application::APP_ID, Application::APP_ID . '-dashboard');
	}

	protected function renderAnnouncement(Announcement $announcement): array {
		$displayName = $this->userManager->getDisplayName($announcement->getUser()) ?? $announcement->getUser();

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

		return $result;
	}

	public function getItems(string $userId, ?string $since = null, int $limit = 7): array {
		$announcements = $this->manager->getAnnouncements((int) $since, $limit);
		$data = array_map([$this, 'renderAnnouncementAPI'], $announcements);
		return $data;
	}

	protected function renderAnnouncementAPI(Announcement $announcement): WidgetItem {
		$data = $this->renderAnnouncement($announcement);

		return new WidgetItem(
			$data['subject'],
			str_replace(
				['{author}', '{time}'],
				[$data['author'], $this->dateTimeFormatter->formatDateTimeRelativeDay($data['time'])],
				$this->l10n->t('{author}, {time}')
			),
			$this->getUrl() . '?announcement=' . $data['id'],
			$this->url->linkToRouteAbsolute('core.avatar.getAvatar', [
				'userId' => $data['author_id'],
				'size' => 64,
			]),
			(string) $data['id']
		);
	}
}
