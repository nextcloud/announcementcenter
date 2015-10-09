<?php
/**
 * ownCloud - AnnouncementCenter App
 *
 * @author Joas Schilling
 * @copyright 2015 Joas Schilling nickvergessen@owncloud.com
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace OCA\AnnouncementCenter\Tests;

class RoutesTest extends TestCase {
	public function testRoutes() {
		$routes = include(__DIR__ . '/../../appinfo/routes.php');
		$this->assertInternalType('array', $routes);
		$this->assertCount(1, $routes);
		$this->assertArrayHasKey('routes', $routes);
		$this->assertInternalType('array', $routes['routes']);
		$this->assertGreaterThanOrEqual(1, sizeof($routes['routes']));
	}
}
