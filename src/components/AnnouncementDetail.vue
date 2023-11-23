<!--
  - @copyright Copyright (c) 2023 insiinc <insiinc@outlook.com>
  -
  - @author insiinc <insiinc@outlook.com>
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
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program. If not, see <http://www.gnu.org/licenses/>.
  -
  -->
<template>
	<NcAppContentDetails style="height: 100%">
		<Page v-if="currentAnnouncement" />
		<template v-else>
			<NcEmptyContent
				:title="
					t(
						'announcementcenter',
						'Select an announcement from the list or create a new one.'
					)
				">
			</NcEmptyContent
		></template>
	</NcAppContentDetails>
</template>
<script>
import { NcAppContentDetails, NcEmptyContent } from "@nextcloud/vue";
import Page from "./Page.vue";
import { mapActions, mapGetters, mapMutations } from "vuex";
export default {
	name: "AnnouncementDetail",
	components: { NcAppContentDetails, Page, NcEmptyContent },
	computed: {
		...mapGetters(["currentAnnouncement"]),
	},
	mounted() {},
};
</script>
