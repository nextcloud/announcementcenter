<!--
  - @copyright Copyright (c) 2020 Georg Ehrke <oc.list@georgehrke.com>
  - @author Georg Ehrke <oc.list@georgehrke.com>
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
  -
  -->

<template>
	<div
		@click="onClick"
		class="flex items-center p-2 justify-between announce_item"
		style="
			width: 100%;
			border-bottom: 1px solid var(--color-background-darker);
		"
		:class="{ selected_announcement: isSelected }">
		<div class="flex items-center" style="width: 100%">
			<NcAvatar
				:user="source.author_id"
				:display-name="source.author"
				:size="40"
				:show-user-status="true" />
			<div
				class="ml-2 flex items-center justify-between"
				style="width: calc(100% - 40px)">
				<div class="flex-col" style="width: calc(100% - 50px)">
					<span class="text-xl">{{ source.author }}</span>
					<div class="flex text-sm justify-between items-center">
						<div style="color: var(--color-primary)">
							{{ source.subject }}
						</div>

						<div
							class="live-relative-timestamp"
							style="color: var(--color-text-maxcontrast)"
							:data-timestamp="timestamp"
							:title="dateFormat">
							{{ dateRelative }}
						</div>
					</div>
					<div
						class="text-xs announce_message"
						style="
							color: var(--color-text-maxcontrast);
							width: 90%;
						">
						{{ source.message }}
					</div>
				</div>
				<div class="flex justify-center">
					<NcActions
						v-if="isAuthor"
						:force-menu="true"
						:boundaries-element="boundariesElement">
						<NcActionButton
							v-if="source.notifications"
							icon="icon-notifications-off"
							:close-after-click="true"
							:title="
								t('announcementcenter', 'Clear notifications')
							"
							@click="onRemoveNotifications" />
						<NcActionButton
							icon="icon-delete"
							:title="
								t('announcementcenter', 'Delete announcement')
							"
							@click="onDeleteAnnouncement" />
					</NcActions>
				</div>
			</div>
		</div>
	</div>
</template>

<script>
import Delete from "vue-material-design-icons/Delete";
import NcActions from "@nextcloud/vue/dist/Components/NcActions.js";
import NcActionButton from "@nextcloud/vue/dist/Components/NcActionButton.js";
import NcAvatar from "@nextcloud/vue/dist/Components/NcAvatar.js";
import NcButton from "@nextcloud/vue/dist/Components/NcButton.js";
import NcRichText from "@nextcloud/vue/dist/Components/NcRichText.js";
import moment from "@nextcloud/moment";
import { showError } from "@nextcloud/dialogs";
import {
	deleteAnnouncement,
	removeNotifications,
} from "../services/announcementsService.js";
import { mapGetters, mapMutations } from "vuex";
import { emit } from "@nextcloud/event-bus";
export default {
	name: "Announcement",
	components: {
		NcActions,
		NcActionButton,
		NcAvatar,
		NcButton,
		NcRichText,
		Delete,
	},
	props: {
		source: {
			type: Object,
			default() {
				return {};
			},
		},
	},

	data() {
		return {
			isMessageFolded: true,
			editor: null,
			textAppAvailable: !!window.OCA?.Text?.createEditor,
		};
	},
	computed: {
		...mapGetters(["currentAnnouncement"]),
		isAuthor() {
			return OC.getCurrentUser().uid === this.source.author_id;
		},
		isSelected() {
			if (this.currentAnnouncement) {
				return this.currentAnnouncement.id === this.source.id;
			} else {
				return false;
			}
		},
		boundariesElement() {
			return document.querySelector(this.$el);
		},
		timestamp() {
			return this.source.time * 1000;
		},
		dateFormat() {
			return moment(this.timestamp).format("LLL");
		},
		dateRelative() {
			const diff = moment().diff(moment(this.timestamp));
			if (diff >= 0 && diff < 45000) {
				return t("core", "seconds ago");
			}
			return moment(this.timestamp).fromNow();
		},

		isVisibleToEveryone() {
			return (
				this.source.groups.length === 0 ||
				this.source.groups.filter(({ id }) => {
					return id === "everyone";
				}).length === 1
			);
		},

		visibilityLabel() {
			if (this.isVisibleToEveryone) {
				return t("announcementcenter", "visible to everyone");
			}

			if (this.source.groups.length === 1) {
				return t(
					"announcementcenter",
					"visible to group {name}",
					this.source.groups[0]
				);
			}
			if (this.source.groups.length === 2) {
				return t(
					"announcementcenter",
					"visible to groups {name1} and {name2}",
					{
						name1: this.source.groups[0].name,
						name2: this.source.groups[1].name,
					}
				);
			}
			return n(
				"announcementcenter",
				"visible to group {name} and %n more",
				"visible to group {name} and %n more",
				this.source.groups.length - 1,
				this.source.groups[0]
			);
		},

		visibilityTitle() {
			if (this.isVisibleToEveryone) {
				return "";
			}

			return this.source.groups
				.map(({ name }) => {
					return name;
				})
				.join(t("announcementcenter", ", "));
		},

		commentsCount() {
			return n(
				"announcementcenter",
				"%n comment",
				"%n comments",
				this.source.comments
			);
		},
	},

	async mounted() {
		if (this.source.message.length <= 200) {
			this.isMessageFolded = false;
		}
	},

	methods: {
		...mapMutations(["setCurrentAnnouncementId"]),
		onClick() {
			this.setCurrentAnnouncementId(this.source.id);
			emit("clickAnnouncement", this.source.id);
		},
		onClickFoldedMessage() {
			this.isMessageFolded = false;
			// if (this.comments !== false) {
			// 	this.$emit("click", this.id);
			// }
		},
		async onRemoveNotifications() {
			try {
				await removeNotifications(this.source.id);
				this.$store.dispatch("removeNotifications", this.source.id);
			} catch (e) {
				console.error(e);
				showError(
					t(
						"announcementcenter",
						"An error occurred while removing the notifications of the announcement"
					)
				);
			}
		},
		async onDeleteAnnouncement() {
			try {
				await deleteAnnouncement(this.source.id);
				this.$store.dispatch("deleteAnnouncement", this.source.id);
			} catch (e) {
				console.error(e);
				showError(
					t(
						"announcementcenter",
						"An error occurred while deleting the announcement"
					)
				);
			}
		},
	},
};
</script>

<style lang="scss" scoped>
.selected_announcement {
	border-left: 0.3rem solid var(--color-primary);
	background: var(--color-background-hover);
}
.announcement_info {
	width: calc(100% - 30px);
}
.announce_message {
	overflow: hidden;
	white-space: nowrap;
	text-overflow: ellipsis;
}
.announce_item:hover {
	background: var(--color-background-hover);
}

.announcement {
	max-width: 690px;
	padding: 0 10px;
	margin: 0 auto 3em;
	font-size: 15px;

	&:nth-child(1) {
		margin-top: 70px;
	}

	&__header {
		&__details {
			display: flex;

			&__info {
				color: var(--color-text-maxcontrast);
				flex: 1 1 auto;
				display: flex;
				align-items: center;

				:deep(.avatardiv) {
					margin-right: 4px;
				}

				span {
					margin-left: 4px;
					margin-right: 4px;
				}
			}

			.action-item {
				display: flex;
				flex: 0 0 44px;
				position: relative;
			}
		}
	}

	&__message {
		position: relative;
		margin-top: 20px;

		&--folded {
			overflow: hidden;
			text-overflow: ellipsis;
			display: -webkit-box;
			-webkit-line-clamp: 7;
			-webkit-box-orient: vertical;
			cursor: pointer;
		}

		&__overlay {
			position: absolute;
			bottom: 0;
			height: 3.2em;
			width: 100%;
			cursor: pointer;
			background: linear-gradient(
				rgba(255, 255, 255, 0),
				var(--color-main-background)
			);
		}
	}

	&__comments {
		margin-left: -16px;
	}
}
</style>
