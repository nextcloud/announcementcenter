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
