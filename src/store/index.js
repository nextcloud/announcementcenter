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
import Vuex, { Store } from "vuex";
import announcementsStore from "./announcementsStore.js";
import attachmentStore from "./attachment.js";
import axios from "@nextcloud/axios";
import { generateOcsUrl, generateUrl } from "@nextcloud/router";
import { pageModes } from "../constants.js";

Vue.use(Vuex);

export default new Store({
	modules: {
		announcementsStore,
		attachmentStore,
	},
	state: {
		sharees: [],
		assignableUsers: [],
		currentBoard: null,
		textMode: pageModes.MODE_VIEW,
	},
	mutations: {
		setSharees(state, shareesUsersAndGroups) {
			// Vue.set(state, "sharees", shareesUsersAndGroups.exact.users);
			Vue.set(state, "sharees", shareesUsersAndGroups.exact.groups);
			// state.sharees.push(...shareesUsersAndGroups.exact.groups);
			state.sharees.push(...shareesUsersAndGroups.exact.circles);
			// state.sharees.push(...shareesUsersAndGroups.users);
			state.sharees.push(...shareesUsersAndGroups.groups);
			state.sharees.push(...shareesUsersAndGroups.circles);
		},
		setTextEdit: (state) => {
			state.textMode = pageModes.MODE_EDIT;
		},
		setTextView: (state) => {
			state.textMode = pageModes.MODE_VIEW;
		},
	},
	actions: {
		async loadSharees({ commit }, query) {
			const params = new URLSearchParams();
			if (typeof query === "undefined") {
				return;
			}
			params.append("search", query);
			params.append("format", "json");
			params.append("perPage", 20);
			params.append("itemType", [0, 1, 4, 7]); //0,1,4,7
			params.append("lookup", false);

			const response = await axios.get(
				generateOcsUrl("apps/files_sharing/api/v1/sharees"),
				{ params }
			);
			commit("setSharees", response.data.ocs.data);
		},
	},
	getters: {
		isTextEdit: (state) => state.textMode === pageModes.MODE_EDIT,
		isTextView: (state) => state.textMode === pageModes.MODE_VIEW,
		assignables: (state) => {
			return [
				...state.assignableUsers.map((user) => ({ ...user, type: 0 })),
				...state.currentBoard.acl
					.filter(
						(acl) =>
							acl.type === 1 &&
							typeof acl.participant === "object"
					)
					.map((group) => ({ ...group.participant, type: 1 })),
				...state.currentBoard.acl
					.filter(
						(acl) =>
							acl.type === 7 &&
							typeof acl.participant === "object"
					)
					.map((circle) => ({ ...circle.participant, type: 7 })),
			];
		},
	},
	strict: process.env.NODE_ENV !== "production",
});
