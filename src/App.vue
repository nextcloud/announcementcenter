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
		await this.loadAnnouncements({ filterKey: "", page: 1, pageSize: 30 });
		console.log(this.announcements);
	},
	async mounted() {
		const activeId = loadState("announcementcenter", "activeId", 0);
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
