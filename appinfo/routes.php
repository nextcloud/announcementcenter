<?php

/**
 * SPDX-FileCopyrightText: 2015-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

return [
	'routes' => [
		['name' => 'page#index', 'url' => '/', 'verb' => 'GET'],
	],
	'ocs' => [
		['name' => 'API#get', 'url' => '/api/v1/announcements', 'verb' => 'GET'],
		['name' => 'API#add', 'url' => '/api/v1/announcements', 'verb' => 'POST'],
		['name' => 'API#delete', 'url' => '/api/v1/announcements/{id}', 'verb' => 'DELETE'],
		['name' => 'API#removeNotifications', 'url' => '/api/v1/announcements/{id}/notifications', 'verb' => 'DELETE'],
		['name' => 'API#searchGroups', 'url' => '/api/v1/groups', 'verb' => 'GET'],
	]
];
