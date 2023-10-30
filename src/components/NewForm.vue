<!--
  - @copyright Copyright (c) 2020 Joas Schilling <coding@schilljs.com>
  -
  - @author Joas Schilling <coding@schilljs.com>
  -
  - @license GNU AGPL version 3 or any later version
  -
  - This program is free software: you can redistribute it and/or modify
  - it under the terms of the GNU Affero General Public License as
  - published by the Free Software Foundation, either version 3 of the
  - License, or (at your option) any later version.
  -
  - This program is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program. If not, see <http://www.gnu.org/licenses/>.
-->

<template>
	<div class="announcement__form">
		<input
			v-model="subject"
			class="announcement__form__subject"
			type="text"
			name="subject"
			minlength="1"
			maxlength="512"
			:placeholder="
				t('announcementcenter', 'New announcement subject')
			" />
		<NcMultiselect
			class="announcement__form__user"
			v-model="assignedUsers"
			:multiple="true"
			:options="formatedSharees"
			:user-select="true"
			:auto-limit="false"
			:placeholder="t('deck', 'Assign a user to this card…')"
			label="displayName"
			track-by="multiselectKey"
			@input="assignUserToAnnouncement"
			@search-change="asyncFind">
			<template #tag="scope">
				<div class="avatarlist--inline">
					<NcAvatar
						:user="scope.option.user"
						:display-name="scope.option.displayname"
						:size="30"
						:is-no-user="scope.option.isNoUser"
						:disable-menu="true" />
				</div>
			</template>
		</NcMultiselect>
		<div>
			<div v-if="textAppAvailable" class="announcement__form__text">
				<div ref="editor" />
			</div>
			<template v-else>
				<textarea
					v-model="message"
					class="announcement__form__message"
					name="message"
					rows="4"
					:placeholder="
						t(
							'announcementcenter',
							'Write announcement text, Markdown can be used …'
						)
					" />
			</template>
			<div class="announcement__form__buttons">
				<NcButton
					type="primary"
					:disabled="!subject"
					@click="onAnnounce">
					{{ t("announcementcenter", "Announce") }}
				</NcButton>
				<NcActions>
					<!-- <NcActionInput
						v-model="groups"
						icon="icon-group"
						type="multiselect"
						:options="groupOptions"
						label="label"
						track-by="id"
						:multiple="true"
						:placeholder="t('announcementcenter', 'Everyone')"
						:title="
							t(
								'announcementcenter',
								'These groups will be able to see the announcement. If no group is selected, all users can see it.'
							)
						"
						@search-change="onSearchChanged">
						{{ t("announcementcenter", "Everyone") }}
					</NcActionInput> -->
					<NcActionCheckbox
						value="1"
						:checked.sync="createActivities">
						{{ t("announcementcenter", "Create activities") }}
					</NcActionCheckbox>
					<NcActionCheckbox
						value="1"
						:checked.sync="createNotifications">
						{{ t("announcementcenter", "Create notifications") }}
					</NcActionCheckbox>
					<NcActionCheckbox value="1" :checked.sync="sendEmails">
						{{ t("announcementcenter", "Send emails") }}
					</NcActionCheckbox>
					<NcActionCheckbox value="1" :checked.sync="allowComments">
						{{ t("announcementcenter", "Allow comments") }}
					</NcActionCheckbox>
				</NcActions>
			</div>
		</div>
	</div>
</template>

<script>
import {
	NcAvatar,
	NcButton,
	NcMultiselect,
	NcActions,
	NcActionCheckbox,
	NcActionInput,
} from "@nextcloud/vue";
import { loadState } from "@nextcloud/initial-state";
import {
	postAnnouncement,
	searchGroups,
} from "../services/announcementsService.js";
import { showError } from "@nextcloud/dialogs";
import { remark } from "remark";
import strip from "strip-markdown";
import { emit, subscribe, unsubscribe } from "@nextcloud/event-bus";
import debounce from "lodash/debounce.js";
import { mapGetters, mapState } from "vuex";
export default {
	name: "NewForm",
	components: {
		NcActions,
		NcActionCheckbox,
		NcActionInput,
		NcButton,
		NcMultiselect,
		NcAvatar,
	},

	data() {
		return {
			subject: "",
			message: "",

			createActivities: loadState(
				"announcementcenter",
				"createActivities"
			),
			createNotifications: loadState(
				"announcementcenter",
				"createNotifications"
			),
			sendEmails: loadState("announcementcenter", "sendEmails"),
			allowComments: loadState("announcementcenter", "allowComments"),
			groups: [],
			groupOptions: [],
			assignedUsers: [],

			editor: null,
			textAppAvailable: !!window.OCA?.Text?.createEditor,
		};
	},

	async mounted() {
		this.setupEditor();
		this.searchGroups("");
		await this.$store.dispatch("loadSharees", "");
	},
	computed: {
		...mapState(["sharees"]),
		formatedSharees() {
			console.log(this.sharees);
			return this.sharees
				.map((item) => {
					const sharee = {
						user: item.value.shareWith,
						displayName: item.label,
						icon: "icon-user",
						multiselectKey: item.value.shareType + ":" + item.label,
					};
					if (item.value.shareType === 1) {
						sharee.icon = "icon-group";
						sharee.isNoUser = true;
					}
					if (item.value.shareType === 7) {
						sharee.icon = "icon-circles";
						sharee.isNoUser = true;
					}

					sharee.value = item.value;
					return sharee;
				})
				.slice(0, 10);
		},
	},
	methods: {
		async asyncFind(query) {
			await this.debouncedFind(query);
		},
		debouncedFind: debounce(async function (query) {
			this.isSearching = true;
			await this.$store.dispatch("loadSharees", query);
			this.isSearching = false;
		}, 300),
		assignUserToAnnouncement(item) {
			// console.log(this.assignedUsers);
			// this.assignedUsers.push(item);
			// this.$store.dispatch('assignCardToUser', {
			// 	card: this.copiedCard,
			// 	assignee: {
			// 		userId: user.uid,
			// 		type: user.type,
			// 	},
			// })
		},

		removeUserFromAnnouncement(user) {
			// this.$store.dispatch('removeUserFromCard', {
			// 	card: this.copiedCard,
			// 	assignee: {
			// 		userId: user.uid,
			// 		type: user.type,
			// 	},
			// })
		},
		async setupEditor() {
			this?.editor?.destroy();
			this.editor = await window.OCA.Text.createEditor({
				el: this.$refs.editor,
				content: this.message,
				readOnly: false,
				onUpdate: ({ markdown }) => {
					this.message = markdown;
					// this.updateDescription();
				},
				onFileInsert: () => {
					// this.showAttachmentModal();
					console.log("files");
				},
			});
		},
		resetForm() {
			this.subject = "";
			this.message = "";
			this.createActivities = loadState(
				"announcementcenter",
				"createActivities"
			);
			this.createNotifications = loadState(
				"announcementcenter",
				"createNotifications"
			);
			this.sendEmails = loadState("announcementcenter", "sendEmails");
			this.allowComments = loadState(
				"announcementcenter",
				"allowComments"
			);
			this.groups = [];
		},

		onSearchChanged: debounce(async function (search) {
			await this.searchGroups(search);
		}, 300),

		async searchGroups(search) {
			const response = await searchGroups(search);
			this.groupOptions = response.data.ocs.data;
		},

		async onAnnounce() {
			const groups = this.assignedUsers.map((item) => {
				return item.user;
			});

			// const groups = this.groups.map((group) => {
			// 	return group.id;
			// });

			const plainMessage = await remark()
				.use(strip, {
					keep: ["blockquote", "link", "listItem"],
				})
				.process(this.message);

			try {
				const response = await postAnnouncement(
					this.subject,
					this.message,
					plainMessage.value,
					groups,
					this.createActivities,
					this.createNotifications,
					this.sendEmails,
					this.allowComments
				);
				this.$store.dispatch("addAnnouncement", response.data.ocs.data);
				emit("closeNewAnnouncement");
				this.resetForm();
			} catch (e) {
				console.error(e);
				showError(
					t(
						"announcementcenter",
						"An error occurred while posting the announcement"
					)
				);
			}
		},
	},
};
</script>

<style lang="scss" scoped>
.avatarlist--inline {
	display: flex;
	align-items: center;
	margin-right: 3px;
	.avatarLabel {
		padding: 0;
	}
}
.announcement__form {
	max-width: 690px;
	padding: 0px 20px;
	margin: 70px auto 35px;
	font-size: 15px;
	&__user {
		width: 100%;
	}
	&__text:deep(.text-menubar) {
		top: -10px !important;
		z-index: 1;
	}
	&__subject {
		width: 100%;
		font-size: 20px;
		font-weight: bold;
	}

	&__message {
		width: 100%;
		font-size: 15px;
	}

	&__buttons {
		display: flex;
		justify-content: right;

		:deep(.button-vue) {
			margin-right: 10px;
		}
	}
}
</style>
