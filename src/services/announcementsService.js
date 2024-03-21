/**
 * @copyright Copyright (c) 2020 Joas Schilling <coding@schilljs.com>
 *
 * @license AGPL-3.0-or-later
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

import axios from '@nextcloud/axios'
import { generateOcsUrl } from '@nextcloud/router'

/**
 * Gets the announcements
 *
 * @param {number} [offset] The last announcement id loaded
 * @return {object} The axios response
 */
const getAnnouncements = async function(offset) {
	return axios.get(generateOcsUrl('apps/announcementcenter/api/v1/announcements'), {
		params: {
			offset: offset || 0,
		},
	})
}

/**
 * Get the groups for posting an announcement
 *
 * @param {string} [search] Search term to autocomplete a group
 * @return {object} The axios response
 */
const searchGroups = async function(search) {
	return axios.get(generateOcsUrl('apps/announcementcenter/api/v1/groups'), {
		params: {
			search: search || '',
		},
	})
}

/**
 * Post an announcement
 *
 * @param {string} subject Short title of the announcement
 * @param {string} message Markdown body of the announcement
 * @param {string} plainMessage Plain body of the announcement
 * @param {string[]} groups List of groups that can read the announcement
 * @param {boolean} activities Should activities be generated
 * @param {boolean} notifications Should notifications be generated
 * @param {boolean} emails Should emails be sent
 * @param {boolean} comments Are comments allowed
 * @param {number} scheduleTime Time, when the announcement is scheduled
 * @param {number} deleteTime Time, when the announcement should be deleted
 * @return {object} The axios response
 */
const postAnnouncement = async function(subject, message, plainMessage, groups, activities, notifications, emails, comments, scheduleTime = null, deleteTime = null) {
	return axios.post(generateOcsUrl('apps/announcementcenter/api/v1/announcements'), {
		subject,
		message,
		plainMessage,
		groups,
		activities,
		notifications,
		emails,
		comments,
		scheduleTime,
		deleteTime,
	})
}

/**
 * Delete an announcement
 *
 * @param {number} id The announcement id to delete
 * @return {object} The axios response
 */
const deleteAnnouncement = async function(id) {
	return axios.delete(generateOcsUrl('apps/announcementcenter/api/v1/announcements/{id}', { id }))
}

/**
 * Remove notifications for an announcement
 *
 * @param {number} id The announcement id to delete
 * @return {object} The axios response
 */
const removeNotifications = async function(id) {
	return axios.delete(generateOcsUrl('apps/announcementcenter/api/v1/announcements/{id}/notifications', { id }))
}

export {
	getAnnouncements,
	searchGroups,
	postAnnouncement,
	deleteAnnouncement,
	removeNotifications,
}
