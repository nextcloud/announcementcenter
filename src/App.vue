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
	<Content app-name="announcementcenter">
		<AppContent>
			<NewForm v-if="isAdmin" />

			<transition-group name="fade-collapse" tag="li">
				<Announcement v-for="announcement in announcements"
					:key="announcement.id"
					:is-admin="isAdmin"
					:author-id="announcement.author_id"
					v-bind="announcement"
					@click="onClickAnnouncement" />
			</transition-group>

			<EmptyContent
				v-if="!announcements.length"
				icon="icon-announcementcenter-dark">
				{{ t('announcementcenter', 'No announcements') }}
				<template #desc>
					{{ t('announcementcenter', 'There are currently no announcements…') }}
				</template>
			</EmptyContent>
		</AppContent>
		<AppSidebar
			v-if="activeId !== 0"
			:title="activeAnnouncementTitle"
			@close="onClickAnnouncement(0)">
			<div
				ref="sidebar"
				class="comments" />
		</AppSidebar>
	</Content>
</template>

<script>
import AppContent from '@nextcloud/vue/dist/Components/AppContent'
import AppSidebar from '@nextcloud/vue/dist/Components/AppSidebar'
import Content from '@nextcloud/vue/dist/Components/Content'
import EmptyContent from '@nextcloud/vue/dist/Components/EmptyContent'
import { loadState } from '@nextcloud/initial-state'
import Announcement from './Components/Announcement'
import NewForm from './Components/NewForm'
import { getAnnouncements } from './services/announcementsService'

export default {
	name: 'App',

	components: {
		Announcement,
		AppContent,
		AppSidebar,
		Content,
		EmptyContent,
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
	},

	mounted() {
		this.loadAnnouncements()
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
		 * @param {number} id the announcement
		 */
		async onClickAnnouncement(id) {
			if (id === this.activeId) {
				return
			}

			this.activeId = id

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
