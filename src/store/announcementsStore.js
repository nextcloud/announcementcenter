/**
 * @copyright Copyright (c) 2019 Marco Ambrosini <marcoambrosini@pm.me>
 *
 * @author Marco Ambrosini <marcoambrosini@pm.me>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
import Vue from "vue";
import {
	getAnnouncements,
	searchAnnouncements,
} from "../services/announcementsService.js";
const state = {
	announcements: {},
	searchAnnouncements: {},
	currentAnnouncementId: null,
	total: 0,
	pages: 0,
	searchTotal: 0,
};

const getters = {
	currentAnnouncement: (state) =>
		state.announcements[state.currentAnnouncementId],
	canEdit: (state) =>
		OC.getCurrentUser().uid ===
		state.announcements[state.currentAnnouncementId].author_id,
	announcements: (state) => Object.values(state.announcements),
	searchAnnouncements: (state) => Object.values(state.searchAnnouncements),
	announcement: (state) => (id) => state.announcements[id],
	total: (state) => state.total,
	pages: (state) => state.pages,
	searchTotal: (state) => state.searchTotal,
};

const mutations = {
	/**
	 * Adds an announcement to the store
	 *
	 * @param {object} state current store state
	 * @param {object} announcement the announcement
	 */
	addAnnouncement(state, announcement) {
		Vue.set(state.announcements, announcement.id, announcement);
	},
	addSearchAnnouncement(state, announcement) {
		Vue.set(state.searchAnnouncements, announcement.id, announcement);
	},
	setTotal(state, total) {
		state.total = total;
	},
	setSearchTotal(state, total) {
		state.searchTotal = total;
	},
	setPages(state, pages) {
		state.pages = pages;
	},
	updateAnnouncement(state, announcement) {
		Vue.set(state.announcements, announcement.id, announcement);
	},
	/**
	 * Deletes an announcement from the store
	 *
	 * @param {object} state current store state
	 * @param {number} id the id of the announcement to delete
	 */
	deleteAnnouncement(state, id) {
		Vue.delete(state.announcements, id);
	},
	clearAnnoucements(state) {
		state.announcements = {};
	},
	setCurrentAnnouncementId(state, id) {
		Vue.set(state, "currentAnnouncementId", id);
	},
	/**
	 * Remove the notifications of an announcement
	 *
	 * @param {object} state current store state
	 * @param {number} id the id of the announcement to remove the notifications of
	 */
	removeNotifications(state, id) {
		if (!state.announcements[id]) {
			return;
		}

		Vue.set(state.announcements[id], "notifications", false);
	},
	announcementSetAttachmentCount(state, { announcementId, count }) {
		Vue.set(state.announcements[announcementId], "attachmentCount", count);
	},
	announcementIncreaseAttachmentCount(state, announcementId) {
		Vue.set(
			state.announcements[announcementId],
			"attachmentCount",
			state.announcements[announcementId].attachmentCount + 1
		);
	},
	announcementDecreaseAttachmentCount(state, announcementId) {
		Vue.set(
			state.announcements[announcementId],
			"attachmentCount",
			state.announcements[announcementId].attachmentCount - 1
		);
	},
};

const actions = {
	/**
	 * Add an announcement to the store
	 *
	 * @param {object} context default store context
	 * @param {object} announcement the announcement
	 */
	addAnnouncement(context, announcement) {
		context.commit("addAnnouncement", announcement);
	},
	addSearchAnnouncement(context, announcement) {
		context.commit("addSearchAnnouncement", announcement);
	},
	updateAnnouncement(context, announcement) {
		context.commit("updateAnnouncement", announcement);
	},
	async loadAnnouncements(context, { filterKey, page, pageSize }) {
		const response = await searchAnnouncements(filterKey, page, pageSize);
		console.log(response);
		context.commit("setTotal", response.data?.ocs?.data.total);
		context.commit("setPages", response.data?.ocs?.data.pages);
		let announcements = response.data?.ocs?.data.data || [];

		announcements.forEach((announcement) => {
			context.commit("addAnnouncement", announcement);
		});
	},
	async searchAnnouncements(
		context,
		filterKey = "",
		page = 1,
		pageSize = 14
	) {
		const response = await searchAnnouncements(filterKey, page, pageSize);
		console.log(response);
		context.commit("setSearchTotal", response.data?.ocs?.data.total);
		let announcements = response.data?.ocs?.data.data || [];
		announcements = announcements.sort((a1, a2) => {
			return a1.time - a2.time;
		});
		announcements.forEach((announcement) => {
			context.commit("addSearchAnnouncement", announcement);
		});
	},
	/**
	 * Delete an announcement
	 *
	 * @param {object} context default store context
	 * @param {number} id the id of the announcement to delete
	 */
	deleteAnnouncement(context, id) {
		context.commit("deleteAnnouncement", id);
	},

	/**
	 * Remove the notifications of an announcement
	 *
	 * @param {object} context default store context
	 * @param {number} id the id of the announcement to remove the notifications of
	 */
	removeNotifications(context, id) {
		context.commit("removeNotifications", id);
	},
};

export default { state, mutations, getters, actions };
