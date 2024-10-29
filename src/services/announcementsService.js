/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
