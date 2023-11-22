/*
 * @copyright Copyright (c) 2020 Jakob Röhrl <jakob.roehrl@web.de>
 *
 * @author Jakob Röhrl <jakob.roehrl@web.de>
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

import { AttachmentApi } from "./../services/AttachmentApi.js";
import Vue from "vue";

const apiClient = new AttachmentApi();
export default {
	state: {
		attachments: {},
	},
	getters: {
		attachmentsByannouncement: (state) => (announcementId) => {
			if (typeof state.attachments[announcementId] === "undefined") {
				return [];
			}
			return state.attachments[announcementId];
		},
	},
	mutations: {
		createAttachment(state, { announcementId, attachment }) {
			if (typeof state.attachments[announcementId] === "undefined") {
				Vue.set(state.attachments, announcementId, [attachment]);
			} else {
				state.attachments[announcementId].push(attachment);
			}
		},
		createAttachments(state, { announcementId, attachments }) {
			Vue.set(state.attachments, announcementId, attachments);
		},
		updateAttachment(state, { announcementId, attachment }) {
			const existingIndex = state.attachments[
				attachment.announcementId
			].findIndex(
				(a) => a.id === attachment.id && a.type === attachment.type
			);
			if (existingIndex !== -1) {
				Vue.set(
					state.attachments[announcementId],
					existingIndex,
					attachment
				);
			}
		},
		deleteAttachment(state, deletedAttachment) {
			const existingIndex = state.attachments[
				deletedAttachment.announcementId
			].findIndex(
				(a) =>
					a.id === deletedAttachment.id &&
					a.type === deletedAttachment.type
			);
			if (existingIndex !== -1) {
				state.attachments[deletedAttachment.announcementId][
					existingIndex
				].deletedAt = (Date.now() / 1000) | 0;
			}
		},
		unshareAttachment(state, deletedAttachment) {
			const existingIndex = state.attachments[
				deletedAttachment.announcementId
			].findIndex(
				(a) =>
					a.id === deletedAttachment.id &&
					a.type === deletedAttachment.type
			);
			if (existingIndex !== -1) {
				state.attachments[deletedAttachment.announcementId][
					existingIndex
				].deletedAt = -1;
			}
		},
		restoreAttachment(state, restoredAttachment) {
			const existingIndex = state.attachments[
				restoredAttachment.announcementId
			].findIndex(
				(a) =>
					a.id === restoredAttachment.id &&
					a.type === restoredAttachment.type
			);
			if (existingIndex !== -1) {
				state.attachments[restoredAttachment.announcementId][
					existingIndex
				].deletedAt = 0;
			}
		},
	},
	actions: {
		async fetchAttachments({ commit }, announcementId) {
			const attachments = await apiClient.fetchAttachments(
				announcementId
			);
			commit("createAttachments", { announcementId, attachments });
			commit("announcementSetAttachmentCount", {
				announcementId,
				count: attachments.length,
			});
		},
		async createAttachment(
			{ commit },
			{ announcementId, formData, onUploadProgress }
		) {
			const attachment = await apiClient.createAttachment({
				announcementId,
				formData,
				onUploadProgress,
			});
			commit("createAttachment", { announcementId, attachment });
			commit("announcementIncreaseAttachmentCount", announcementId);
		},
		async updateAttachment(
			{ commit },
			{ announcementId, attachment, formData }
		) {
			const result = await apiClient.updateAttachment({
				announcementId,
				attachment,
				formData,
			});
			commit("updateAttachment", { announcementId, attachment: result });
		},

		async deleteAttachment({ commit }, attachment) {
			await apiClient.deleteAttachment(attachment);
			commit("deleteAttachment", attachment);
			commit(
				"announcementDecreaseAttachmentCount",
				attachment.announcementId
			);
		},

		async unshareAttachment({ commit }, attachment) {
			await apiClient.deleteAttachment(attachment);
			commit("unshareAttachment", attachment);
			commit(
				"announcementDecreaseAttachmentCount",
				attachment.announcementId
			);
		},

		async restoreAttachment({ commit }, attachment) {
			const restoredAttachment = await apiClient.restoreAttachment(
				attachment
			);
			commit("restoreAttachment", restoredAttachment);
			commit(
				"announcementIncreaseAttachmentCount",
				attachment.announcementId
			);
		},
	},
};
