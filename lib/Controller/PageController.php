<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\AnnouncementCenter\Controller;

use OCA\AnnouncementCenter\AppInfo\Application;
use OCA\AnnouncementCenter\Manager;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\Comments\ICommentsManager;
use OCP\IConfig;
use OCP\IRequest;
use OCP\Util;

class PageController extends Controller {
	public function __construct(
		string $appName,
		IRequest $request,
		protected Manager $manager,
		protected ICommentsManager $commentsManager,
		protected IConfig $config,
		protected IInitialState $initialState,
	) {
		parent::__construct($appName, $request);
	}

	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function index(int $announcement = 0): TemplateResponse {
		if ($announcement) {
			$this->manager->markNotificationRead($announcement);
		}

		$this->initialState->provideInitialState(
			'isAdmin',
			$this->manager->checkIsAdmin()
		);
		$this->initialState->provideInitialState(
			'createActivities',
			$this->config->getAppValue(Application::APP_ID, 'create_activities', 'yes') === 'yes'
		);
		$this->initialState->provideInitialState(
			'createNotifications',
			$this->config->getAppValue(Application::APP_ID, 'create_notifications', 'yes') === 'yes'
		);
		$this->initialState->provideInitialState(
			'sendEmails',
			$this->config->getAppValue(Application::APP_ID, 'send_emails', 'yes') === 'yes'
		);
		$this->initialState->provideInitialState(
			'allowComments',
			$this->config->getAppValue(Application::APP_ID, 'allow_comments', 'yes') === 'yes'
		);
		$this->initialState->provideInitialState(
			'activeId',
			$announcement
		);

		$this->commentsManager->load();
		Util::addScript('announcementcenter', 'announcementcenter-main', 'comments');

		return new TemplateResponse(Application::APP_ID, 'main', [
			'app' => Application::APP_ID,
			'id-app-content' => '#app-content-vue',
			'id-app-navigation' => null,
		]);
	}
}
