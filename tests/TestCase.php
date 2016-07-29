<?php
/**
 * @copyright Copyright (c) 2016, Joas Schilling <coding@schilljs.com>
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

namespace OCA\AnnouncementCenter\Tests;

abstract class TestCase extends \Test\TestCase {
	/** @var array */
	protected $services = [];

	/**
	 * @param string $name
	 * @param mixed $newService
	 * @return bool
	 */
	public function overwriteService($name, $newService) {
		if (isset($this->services[$name])) {
			return false;
		}

		$this->services[$name] = \OC::$server->query($name);
		\OC::$server->registerService($name, function () use ($newService) {
			return $newService;
		});

		return true;
	}

	/**
	 * @param string $name
	 * @return bool
	 */
	public function restoreService($name) {
		if (isset($this->services[$name])) {
			$oldService = $this->services[$name];
			\OC::$server->registerService($name, function () use ($oldService) {
				return $oldService;
			});


			unset($this->services[$name]);
			return true;
		}

		return false;
	}
}
