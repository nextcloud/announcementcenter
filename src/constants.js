/**
 * @copyright Copyright (c) 2023 insiinc <insiinc@outlook.com>
 *
 * @author insiinc <insiinc@outlook.com>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
// Circle lember levels
export const memberLevels = {
	LEVEL_MEMBER: 1,
	LEVEL_MODERATOR: 4,
	LEVEL_ADMIN: 8,
	LEVEL_OWNER: 9,
};

// Circle member types
export const circlesMemberTypes = {
	TYPE_USER: 1,
	TYPE_GROUP: 2,
	TYPE_MAIL: 4,
	TYPE_CIRCLE: 16,
};

export const autocompleteSourcesToCircleMemberTypes = {
	users: "TYPE_USER",
	groups: "TYPE_GROUP",
	circles: "TYPE_CIRCLE",
};

// Nextcloud share types
export const shareTypes = {
	TYPE_USER: 0,
	TYPE_GROUP: 1,
	TYPE_EMAIL: 4,
	TYPE_REMOTE: 6,
	TYPE_CIRCLE: 7,
};

// Page modes
export const pageModes = {
	MODE_VIEW: 0,
	MODE_EDIT: 1,
};
