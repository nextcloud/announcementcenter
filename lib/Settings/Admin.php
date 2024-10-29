<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\AnnouncementCenter\Settings;

use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\IConfig;
use OCP\Settings\ISettings;
use OCP\Util;

class Admin implements ISettings {

	/** @var IConfig */
	protected $config;
	/** @var IInitialState */
	protected $initialState;

	public function __construct(IConfig $config, IInitialState $initialState) {
		$this->config = $config;
		$this->initialState = $initialState;
	}

	/**
	 * @return TemplateResponse
	 */
	public function getForm(): TemplateResponse {
		$adminGroups = json_decode($this->config->getAppValue('announcementcenter', 'admin_groups', '["admin"]'), true);

		$this->initialState->provideInitialState('admin_groups', $adminGroups);
		$this->initialState->provideInitialState('create_activities', $this->config->getAppValue('announcementcenter', 'create_activities', 'yes') === 'yes');
		$this->initialState->provideInitialState('create_notifications', $this->config->getAppValue('announcementcenter', 'create_notifications', 'yes') === 'yes');
		$this->initialState->provideInitialState('send_emails', $this->config->getAppValue('announcementcenter', 'send_emails', 'yes') === 'yes');
		$this->initialState->provideInitialState('allow_comments', $this->config->getAppValue('announcementcenter', 'allow_comments', 'yes') === 'yes');

		Util::addScript('announcementcenter', 'announcementcenter-admin');
		return new TemplateResponse('announcementcenter', 'admin', [], TemplateResponse::RENDER_AS_BLANK);
	}

	/**
	 * @return string the section ID, e.g. 'sharing'
	 */
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
	public function getPriority(): int {
		return 55;
	}
}
