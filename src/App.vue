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
	<NcContent app-name="announcementcenter">
		<!-- <AppNavigation /> -->
		<NcAppContent :list-size="20" :list-min-width="15">
			<template #list>
				<AnnouncementList></AnnouncementList>
			</template>
			<AnnouncementDetail></AnnouncementDetail>
		</NcAppContent>
		<!-- <NcAppContent>
			<transition-group name="fade-collapse" tag="div">
				<Announcement
					v-for="announcement in announcements"
					:key="announcement.id"
					:is-admin="isAdmin"
					:author-id="announcement.author_id"
					v-bind="announcement"
					@click="onClickAnnouncement" />
			</transition-group>

			<NcEmptyContent
				v-if="!announcements.length"
				:title="t('announcementcenter', 'No announcements')"
				:description="
					t(
						'announcementcenter',
						'There are currently no announcements …'
					)
				">
				<template #icon>
					<span class="icon-announcementcenter-dark" />
				</template>
			</NcEmptyContent>
		</NcAppContent> -->
		<NcAppSidebar
			v-show="activeId !== 0 && activateAnnouncementHasComments"
			:title="
				activeAnnouncementTitle +
				' - ' +
				t('announcementcenter', 'Comments')
			"
			@close="onClickAnnouncement(0)">
			<div ref="sidebar" class="comments" />
		</NcAppSidebar>
		<NcModal
			v-if="showNewModal"
			ref="modalRef"
			@close="closeModal"
			name="Name inside modal">
			<NewForm />
		</NcModal>
	</NcContent>
</template>

<script>
import { mapActions, mapGetters, mapMutations } from "vuex";
import {
	NcButton,
	NcAppContent,
	NcAppSidebar,
	NcContent,
	NcEmptyContent,
	NcModal,
} from "@nextcloud/vue";
import AnnouncementList from "./components/AnnouncementList.vue";
import AnnouncementDetail from "./components/AnnouncementDetail.vue";
import { loadState } from "@nextcloud/initial-state";
import NewForm from "./components/NewForm.vue";
import AppNavigation from "./components/navigation/AppNavigation.vue";
import { emit, subscribe, unsubscribe } from "@nextcloud/event-bus";
export default {
	name: "App",

	components: {
		AnnouncementList,
		NcAppContent,
		NcAppSidebar,
		NcContent,
		NcEmptyContent,
		AppNavigation,
		AnnouncementDetail,
		NcModal,
		NcButton,
		NewForm,
	},

	data() {
		return {
			isAdmin: loadState("announcementcenter", "isAdmin"),
			commentsView: null,
			activeId: 0,
			showNewModal: false,
		};
	},

	computed: {
		...mapGetters(["announcements"]),

		activeAnnouncement() {
			return this.$store.getters.announcement(this.activeId);
		},

		activeAnnouncementTitle() {
			if (this.activeId === 0) {
				return "";
			}
			return this.activeAnnouncement?.subject;
		},

		activateAnnouncementHasComments() {
			return (
				this.activeAnnouncement?.comments === 0 ||
				this.activeAnnouncement?.comments > 0
			);
		},
	},
	async beforeMount() {
		await this.loadAnnouncements({ filterKey: "", page: 1, pageSize: 14 });
	},
	async mounted() {
		const activeId = loadState("announcementcenter", "activeId", 0);
		if (activeId !== 0) {
			await this.onClickAnnouncement(activeId);
		}
		subscribe("newAnnouncement", () => {
			this.showNewModal = true;
		});
		subscribe("closeNewAnnouncement", () => {
			this.showNewModal = false;
		});
	},

	methods: {
		...mapActions(["loadAnnouncements"]),
		closeModal() {
			this.showNewModal = false;
		},

		/**
		 * Load the comments of the announcements
		 *
		 * @param {number} id the announcement
		 */
		async onClickAnnouncement(id) {
			if (id === this.activeId) {
				return;
			}

			this.activeId = id;

			if (!this.activateAnnouncementHasComments) {
				return;
			}

			if (id === 0) {
				// Destroy the comments view as the sidebar is destroyed
				this.commentsView = null;
				return;
			}

			if (!this.commentsView) {
				// Create a new comments view when there is none
				this.commentsView = new OCA.Comments.View("announcement");
			}

			await this.commentsView.update(id);
			this.commentsView.$mount(this.$refs.sidebar);
		},
	},
};
</script>

<style>
::v-deep .comments {
	margin: 10px;
}
::v-deep .empty-content__icon span {
	width: 64px;
	height: 64px;
	background-size: 64px;
}

.fade-enter-active,
.fade-leave-active,
.fade-collapse-enter-active,
.fade-collapse-leave-active {
	transition: opacity var(--animation-quick),
		max-height var(--animation-quick);
}

.fade-collapse-enter,
.fade-collapse-leave-to {
	opacity: 0;
	max-height: 0;
}

.fade-enter,
.fade-leave-to {
	opacity: 0;
}
</style>
