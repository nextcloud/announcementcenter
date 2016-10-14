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
		['name' => 'page#get', 'url' => '/announcement', 'verb' => 'GET'],
		['name' => 'page#add', 'url' => '/announcement', 'verb' => 'POST'],
		['name' => 'page#delete', 'url' => '/announcement/{id}', 'verb' => 'DELETE'],
		['name' => 'page#followComment', 'url' => '/comment/{id}', 'verb' => 'GET'],
		['name' => 'page#searchGroups', 'url' => '/groups', 'verb' => 'GET'],
	]
];
