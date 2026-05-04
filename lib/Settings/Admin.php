<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\AnnouncementCenter\Settings;

use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IAppConfig;
use OCP\AppFramework\Services\IInitialState;
use OCP\Settings\ISettings;
use OCP\Util;

class Admin implements ISettings {
	public function __construct(
		protected IAppConfig $appConfig,
		protected IInitialState $initialState,
	) {
	}

	/**
	 * @return TemplateResponse
	 */
	#[\Override]
	public function getForm(): TemplateResponse {
		$this->initialState->provideInitialState('admin_groups', $this->appConfig->getAppValueArray('admin_groups', ['admin']));
		$this->initialState->provideInitialState('create_activities', $this->appConfig->getAppValueBool('create_activities', true));
		$this->initialState->provideInitialState('create_notifications', $this->appConfig->getAppValueBool('create_notifications', true));
		$this->initialState->provideInitialState('send_emails', $this->appConfig->getAppValueBool('send_emails', true));
		$this->initialState->provideInitialState('allow_comments', $this->appConfig->getAppValueBool('allow_comments', true));

		Util::addScript('announcementcenter', 'announcementcenter-admin');
		return new TemplateResponse('announcementcenter', 'admin', [], TemplateResponse::RENDER_AS_BLANK);
	}

	/**
	 * @return string the section ID, e.g. 'sharing'
	 */
	#[\Override]
	public function getSection(): string {
		return 'additional';
	}

	/**
	 * @return int whether the form should be rather on the top or bottom of
	 *             the admin section. The forms are arranged in ascending order of the
	 *             priority values. It is required to return a value between 0 and 100.
	 *
	 * E.g.: 70
	 */
	#[\Override]
	public function getPriority(): int {
		return 55;
	}
}
