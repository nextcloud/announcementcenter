<!--
  - SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcContent app-name="announcementcenter">
		<NcAppContent>
			<NewForm v-if="isAdmin" />

			<transition-group name="fade-collapse" tag="div">
				<Announcement v-for="announcement in announcements"
					:key="announcement.id"
					:is-admin="isAdmin"
					:author-id="announcement.author_id"
					:schedule-time="announcement.schedule_time"
					v-bind="announcement"
					@click="onClickAnnouncement" />
			</transition-group>

			<NcEmptyContent v-if="!announcements.length"
				:name="t('announcementcenter', 'No announcements')"
				:description="t('announcementcenter', 'There are currently no announcements …')">
				<template #icon>
					<span class="icon-announcementcenter-dark" />
				</template>
			</NcEmptyContent>
		</NcAppContent>
		<NcAppSidebar v-show="activeId !== 0 && activateAnnouncementHasComments"
			:name="activeAnnouncementTitle + ' - ' + t('announcementcenter', 'Comments')"
			@close="onClickAnnouncement(0)">
			<div ref="sidebar"
				class="comments" />
		</NcAppSidebar>
	</NcContent>
</template>

<script>
import NcAppContent from '@nextcloud/vue/dist/Components/NcAppContent.js'
import NcAppSidebar from '@nextcloud/vue/dist/Components/NcAppSidebar.js'
import NcContent from '@nextcloud/vue/dist/Components/NcContent.js'
import NcEmptyContent from '@nextcloud/vue/dist/Components/NcEmptyContent.js'
import { loadState } from '@nextcloud/initial-state'
import Announcement from './Components/Announcement.vue'
import NewForm from './Components/NewForm.vue'
import { getAnnouncements } from './services/announcementsService.js'

export default {
	name: 'App',

	components: {
		Announcement,
		NcAppContent,
		NcAppSidebar,
		NcContent,
		NcEmptyContent,
		NewForm,
	},

	data() {
		return {
			isAdmin: loadState('announcementcenter', 'isAdmin'),
			commentsView: null,
			activeId: 0,
		}
	},

	computed: {
		announcements() {
			const announcements = this.$store.getters.announcements
			return announcements.sort((a1, a2) => {
				return a2.time - a1.time
			})
		},

		activeAnnouncement() {
			return this.$store.getters.announcement(this.activeId)
		},

		activeAnnouncementTitle() {
			if (this.activeId === 0) {
				return ''
			}
			return this.activeAnnouncement?.subject
		},

		activateAnnouncementHasComments() {
			return this.activeAnnouncement?.comments === 0 || this.activeAnnouncement?.comments > 0
		},
	},

	async mounted() {
		await this.loadAnnouncements()

		const activeId = loadState('announcementcenter', 'activeId', 0)
		if (activeId !== 0) {
			await this.onClickAnnouncement(activeId)
		}
	},

	methods: {
		async loadAnnouncements() {
			const response = await getAnnouncements()
			const announcements = response.data?.ocs?.data || []

			announcements.forEach(announcement => {
				this.$store.dispatch('addAnnouncement', announcement)
			})
		},

		/**
		 * Load the comments of the announcements
		 *
		 * @param {number} id the announcement
		 */
		async onClickAnnouncement(id) {
			if (id === this.activeId) {
				return
			}

			this.activeId = id

			if (!this.activateAnnouncementHasComments) {
				return
			}

			if (id === 0) {
				// Destroy the comments view as the sidebar is destroyed
				this.commentsView = null
				return
			}

			if (!this.commentsView) {
				// Create a new comments view when there is none
				this.commentsView = new OCA.Comments.View('announcement')
			}

			await this.commentsView.update(id)
			this.commentsView.$mount(this.$refs.sidebar)
		},
	},
}
</script>

<style lang="scss" scoped>
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
	transition: opacity var(--animation-quick), max-height var(--animation-quick);
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
