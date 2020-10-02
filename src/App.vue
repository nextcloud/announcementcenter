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

			<Announcement v-for="announcement in announcements"
				:key="announcement.id"
				:is-admin="isAdmin"
				:author-id="announcement.author_id"
				v-bind="announcement" />

			<EmptyContent
				v-if="!announcements.length"
				icon="icon-announcementcenter-dark">
				{{ t('announcementcenter', 'No announcements') }}
				<template #desc>
					{{ t('announcementcenter', 'There are currently no announcements…') }}
				</template>
			</EmptyContent>
		</AppContent>
	</Content>
</template>

<script>
import AppContent from '@nextcloud/vue/dist/Components/AppContent'
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
		Content,
		EmptyContent,
		NewForm,
	},

	data() {
		return {
			isAdmin: loadState('announcementcenter', 'isAdmin'),
		}
	},

	computed: {
		announcements() {
			const announcements = this.$store.getters.announcements
			return announcements.sort((a1, a2) => {
				return a2.time - a1.time
			})
		},
	},

	beforeDestroy() {
	},

	beforeMount() {
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
	},
}
</script>

<!--<style lang="scss" scoped>-->
<!--</style>-->
