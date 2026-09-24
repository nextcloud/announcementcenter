<!--
  - SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcContent appName="announcementcenter">
		<NcAppContent>
			<NewForm v-if="isAdmin" />

			<transition-group name="fade-collapse" tag="div">
				<Announcement
					v-for="announcement in announcements"
					:key="announcement.id"
					:isAdmin="isAdmin"
					:authorId="announcement.author_id"
					:scheduleTime="announcement.schedule_time"
					:deleteTime="announcement.delete_time"
					v-bind="announcement"
					@click="onClickAnnouncement" />
			</transition-group>

			<div
				v-if="hasMore && announcements.length"
				:ref="observeLoadMore"
				class="load-more">
				<NcButton
					:disabled="loading"
					variant="secondary"
					@click="loadAnnouncements">
					{{ t('announcementcenter', 'Load more') }}
				</NcButton>
			</div>

			<NcEmptyContent
				v-if="!announcements.length"
				:name="t('announcementcenter', 'No announcements')"
				:description="t('announcementcenter', 'There are currently no announcements …')">
				<template #icon>
					<span class="icon-announcementcenter-dark" />
				</template>
			</NcEmptyContent>
		</NcAppContent>
		<NcAppSidebar
			:open="activeId !== 0 && activateAnnouncementHasComments"
			:name="activeAnnouncementTitle + ' - ' + t('announcementcenter', 'Comments')"
			noToggle
			@close="onClickAnnouncement(0)">
			<div
				ref="sidebar"
				class="comments" />
		</NcAppSidebar>
	</NcContent>
</template>

<script>
import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import NcAppContent from '@nextcloud/vue/components/NcAppContent'
import NcAppSidebar from '@nextcloud/vue/components/NcAppSidebar'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcContent from '@nextcloud/vue/components/NcContent'
import NcEmptyContent from '@nextcloud/vue/components/NcEmptyContent'
import Announcement from './Components/Announcement.vue'
import NewForm from './Components/NewForm.vue'
import { getAnnouncements } from './services/announcementsService.js'

// Page size of the announcements API
const PAGE_SIZE = 7

export default {
	name: 'App',

	components: {
		Announcement,
		NcAppContent,
		NcAppSidebar,
		NcButton,
		NcContent,
		NcEmptyContent,
		NewForm,
	},

	data() {
		return {
			isAdmin: loadState('announcementcenter', 'isAdmin'),
			commentsView: null,
			activeId: 0,
			hasMore: true,
			loading: false,
		}
	},

	computed: {
		announcements() {
			const announcements = this.$store.getters.announcements
			return announcements.sort((a1, a2) => {
				return a2.time - a1.time || a2.id - a1.id
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

	created() {
		this.observer = new IntersectionObserver(([entry]) => {
			if (entry.isIntersecting) {
				this.loadAnnouncements()
			}
		}, { rootMargin: '300px' })
	},

	beforeUnmount() {
		this.observer?.disconnect()
	},

	async mounted() {
		await this.loadAnnouncements()

		const activeId = loadState('announcementcenter', 'activeId', 0)
		if (activeId !== 0) {
			await this.onClickAnnouncement(activeId)
		}
	},

	methods: {
		t,

		/**
		 * (Re-)observe the "Load more" button, so it also triggers while it stays visible after a render
		 *
		 * @param {HTMLElement|null} el the button wrapper
		 */
		observeLoadMore(el) {
			this.observer.disconnect()
			if (el) {
				this.observer.observe(el)
			}
		},

		async loadAnnouncements() {
			if (!this.hasMore || this.loading) {
				return
			}

			this.loading = true
			try {
				const response = await getAnnouncements(this.announcements.at(-1)?.id)
				const announcements = response.data?.ocs?.data || []
				this.hasMore = announcements.length === PAGE_SIZE

				announcements.forEach((announcement) => {
					this.$store.dispatch('addAnnouncement', announcement)
				})
			} finally {
				this.loading = false
			}
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
				if (this.commentsView) {
					this.commentsView.$unmount()
				}
				// Destroy the comments view as the sidebar is destroyed
				this.commentsView = null
				return
			}

			if (!this.commentsView) {
				// Create a new comments view when there is none
				this.commentsView = new OCA.Comments.View(
					'announcement',
					{
						propsData: {
							resourceId: id,
						},
					},
				)
				this.commentsView.$mount(this.$refs.sidebar)
			}

			await this.commentsView.update(id)
		},
	},
}
</script>

<style lang="scss" scoped>
.load-more {
	display: flex;
	justify-content: center;
	margin-bottom: 3em;
}

:deep(.comments) {
	overflow: hidden auto;
	height: 100%;
}
:deep(.empty-content__icon span) {
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
