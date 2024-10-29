<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\AnnouncementCenter\Tests;

class RoutesTest extends TestCase {
	public function testRoutes() {
		$routes = include __DIR__ . '/../../appinfo/routes.php';
		self::assertIsArray($routes);
		self::assertCount(2, $routes);
		self::assertArrayHasKey('routes', $routes);
		self::assertIsArray($routes['routes']);
		self::assertGreaterThanOrEqual(1, \count($routes['routes']));
		self::assertArrayHasKey('ocs', $routes);
		self::assertIsArray($routes['ocs']);
		self::assertGreaterThanOrEqual(1, \count($routes['ocs']));
	}
}
