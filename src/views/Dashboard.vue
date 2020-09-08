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
	<DashboardWidget
		id="announcementcenter_panel"
		:items="items"
		:loading="loading">
		<template v-slot:default="{ item }">
			<DashboardWidgetItem :item="item">
				<template v-slot:avatar>
					<Avatar
						:user="item.authorUserId"
						:display-name="item.authorUsername"
						:size="44"
						:show-user-status="false" />
				</template>
			</DashboardWidgetItem>
		</template>
		<template v-slot:empty-content>
			<EmptyContent
				id="announcementcenter-widget-empty-content"
				icon="icon-announcementcenter">
				{{ t('announcementcenter', 'No announcements') }}
			</EmptyContent>
		</template>
	</DashboardWidget>
</template>

<script>
import { DashboardWidget, DashboardWidgetItem } from '@nextcloud/vue-dashboard'
import Avatar from '@nextcloud/vue/dist/Components/Avatar'
import EmptyContent from '@nextcloud/vue/dist/Components/EmptyContent'
import { loadState } from '@nextcloud/initial-state'
import { generateUrl, imagePath } from '@nextcloud/router'
import moment from '@nextcloud/moment'

export default {
	name: 'Dashboard',
	components: {
		Avatar,
		DashboardWidget,
		DashboardWidgetItem,
		EmptyContent,
	},
	data() {
		return {
			announcements: [],
			loading: true,
		}
	},
	computed: {
		items() {
			return this.announcements.map((item) => {
				return {
					mainText: item.subject,
					authorUserId: item.author_id,
					authorUsername: item.author,
					targetUrl: generateUrl('/apps/announcementcenter') + '?announcement=' + item.id,
					overlayIconUrl: imagePath('announcementcenter', 'announcementcenter.svg'),
					subText: t('announcementcenter', '{author}, {timestamp}', {
						author: item.author,
						timestamp: moment(item.time, 'X').fromNow(),
					}, null, { escape: false, sanitize: false }),
				}
			})
		},
	},
	mounted() {
		try {
			this.announcements = loadState('announcementcenter', 'announcementcenter_dashboard')
			this.loading = false
		} catch (e) {
			console.error(e)
		}
	},
}
</script>

<style lang="scss" scoped>
::v-deep .item-list__entry {
	position: relative;
}

.empty-content {
	text-align: center;
	margin-top: 5vh;

	&.half-screen {
		margin-top: 0;
		margin-bottom: 2vh;
	}
}
</style>
