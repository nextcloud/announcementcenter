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
