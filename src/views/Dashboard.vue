<!--
  - SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcDashboardWidget id="announcementcenter_panel"
		:items="items"
		:loading="loading"
		empty-content-icon="icon-announcementcenter-dark"
		:empty-content-message="t('announcementcenter', 'No announcements')">
		<template #emptyContentIcon>
			<div class="icon-announcementcenter-dark" />
		</template>
	</NcDashboardWidget>
</template>

<script>
import NcDashboardWidget from '@nextcloud/vue/components/NcDashboardWidget'
import { loadState } from '@nextcloud/initial-state'
import { formatRelativeTime } from '@nextcloud/l10n'
import { generateUrl, imagePath } from '@nextcloud/router'

export default {
	name: 'Dashboard',

	components: {
		NcDashboardWidget,
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
					avatarUsername: item.author_id,
					targetUrl: generateUrl('/apps/announcementcenter') + '?announcement=' + item.id,
					overlayIconUrl: imagePath('announcementcenter', 'empty.svg'),
					subText: t('announcementcenter', '{author}, {timestamp}', {
						author: item.author,
						timestamp: formatRelativeTime(item.time * 1000, { ignoreSeconds: true }),
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
.icon-announcementcenter-dark {
	background-size: 64px;
	width: 64px;
	height: 64px;
}
</style>
