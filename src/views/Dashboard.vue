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
	<NcDashboardWidget
		id="announcementcenter_panel"
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
import NcDashboardWidget from "@nextcloud/vue/dist/Components/NcDashboardWidget.js";
import { loadState } from "@nextcloud/initial-state";
import { generateUrl, imagePath } from "@nextcloud/router";
import moment from "@nextcloud/moment";

export default {
	name: "Dashboard",

	components: {
		NcDashboardWidget,
	},

	data() {
		return {
			announcements: [],
			loading: true,
		};
	},

	computed: {
		items() {
			return this.announcements.map((item) => {
				return {
					mainText: item.subject,
					avatarUsername: item.author_id,
					targetUrl:
						generateUrl("/apps/announcementcenter") +
						"?announcement=" +
						item.id,
					overlayIconUrl: imagePath(
						"announcementcenter",
						"empty.svg"
					),
					subText: t(
						"announcementcenter",
						"{author}, {timestamp}",
						{
							author: item.author,
							timestamp: moment(item.time, "X")
								.locale("zh_CN")
								.fromNow(),
						},
						null,
						{ escape: false, sanitize: false }
					),
				};
			});
		},
	},

	mounted() {
		try {
			this.announcements = loadState(
				"announcementcenter",
				"announcementcenter_dashboard"
			);
			this.loading = false;
		} catch (e) {
			console.error(e);
		}
	},
};
</script>

<style lang="scss" scoped>
.icon-announcementcenter-dark {
	background-size: 64px;
	width: 64px;
	height: 64px;
}
</style>
