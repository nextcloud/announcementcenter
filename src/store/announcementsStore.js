/**
 * @copyright Copyright (c) 2019 Marco Ambrosini <marcoambrosini@pm.me>
 *
 * @author Marco Ambrosini <marcoambrosini@pm.me>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
import Vue from 'vue'

const state = {
	announcements: {
	},
}

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
		Vue.set(state.announcements, announcement.id, announcement)
	},

	/**
	 * Deletes an announcement from the store
	 * @param {object} state current store state
	 * @param {int} id the id of the announcement to delete
	 */
	deleteAnnouncement(state, id) {
		Vue.delete(state.announcement, id)
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
	 * @param {object} announcement the announcement to be deleted
	 */
	deleteAnnouncement(context, announcement) {
		context.commit('deleteAnnouncement', announcement.id)
	},
}

export default { state, mutations, getters, actions }
