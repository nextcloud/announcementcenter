<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2016 Joas Schilling <coding@schilljs.com>
 *
 * @author Joas Schilling <coding@schilljs.com>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
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
	 * the admin section. The forms are arranged in ascending order of the
	 * priority values. It is required to return a value between 0 and 100.
	 *
	 * E.g.: 70
	 */
	public function getPriority(): int {
		return 55;
	}
}
