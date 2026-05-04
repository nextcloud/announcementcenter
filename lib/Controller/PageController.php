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
use OCP\AppFramework\Services\IAppConfig;
use OCP\AppFramework\Services\IInitialState;
use OCP\Comments\ICommentsManager;
use OCP\IRequest;
use OCP\Util;

class PageController extends Controller {
	public function __construct(
		string $appName,
		IRequest $request,
		protected Manager $manager,
		protected ICommentsManager $commentsManager,
		protected IInitialState $initialState,
		protected IAppConfig $appConfig,
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
			$this->appConfig->getAppValueBool('create_activities', true)
		);
		$this->initialState->provideInitialState(
			'createNotifications',
			$this->appConfig->getAppValueBool('create_notifications', true)
		);
		$this->initialState->provideInitialState(
			'sendEmails',
			$this->appConfig->getAppValueBool('send_emails', true)
		);
		$this->initialState->provideInitialState(
			'allowComments',
			$this->appConfig->getAppValueBool('allow_comments', true)
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
