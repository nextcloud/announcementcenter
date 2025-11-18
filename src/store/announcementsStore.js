/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
const state = () => ({
	announcements: {},
})

const getters = {
	announcements: state => Object.values(state.announcements),
	announcement: state => id => state.announcements[id],
}

const mutations = {
	/**
	 * Adds an announcement to the store
	 *
	 * @param {object} state current store state
	 * @param {object} announcement the announcement
	 */
	addAnnouncement(state, announcement) {
		state.announcements[announcement.id] = announcement
	},

	/**
	 * Deletes an announcement from the store
	 *
	 * @param {object} state current store state
	 * @param {number} id the id of the announcement to delete
	 */
	deleteAnnouncement(state, id) {
		delete state.announcements[id]
	},

	/**
	 * Remove the notifications of an announcement
	 *
	 * @param {object} state current store state
	 * @param {number} id the id of the announcement to remove the notifications of
	 */
	removeNotifications(state, id) {
		if (!state.announcements[id]) {
			return
		}

		state.announcements[id].notifications = false
	},
}

const actions = {
	/**
	 * Add an announcement to the store
	 *
	 * @param {object} context default store context
	 * @param {object} announcement the announcement
	 */
	addAnnouncement(context, announcement) {
		context.commit('addAnnouncement', announcement)
	},

	/**
	 * Delete an announcement
	 *
	 * @param {object} context default store context
	 * @param {number} id the id of the announcement to delete
	 */
	deleteAnnouncement(context, id) {
		context.commit('deleteAnnouncement', id)
	},

	/**
	 * Remove the notifications of an announcement
	 *
	 * @param {object} context default store context
	 * @param {number} id the id of the announcement to remove the notifications of
	 */
	removeNotifications(context, id) {
		context.commit('removeNotifications', id)
	},
}

export default { state, mutations, getters, actions }
