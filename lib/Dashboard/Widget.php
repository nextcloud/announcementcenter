<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
		IL10N $l10n,
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
	 * @return list<WidgetButton>
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
			$announcements = $this->manager->getAnnouncements();
			return array_map([$this, 'renderAnnouncement'], $announcements);
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
			'schedule_time' => $announcement->getScheduleTime(),
		];

		return $result;
	}

	public function getItems(string $userId, ?string $since = null, int $limit = 7): array {
		$announcements = $this->manager->getAnnouncements((int)$since, $limit);
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
			(string)$data['id']
		);
	}
}
