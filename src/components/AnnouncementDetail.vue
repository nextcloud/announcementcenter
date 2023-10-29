<template>
	<NcAppContentDetails>
		<Page v-if="currentAnnouncement" />
		<template v-else>
			<NcEmptyContent
				:title="t('collectives', 'Page not found: {page}')"
				:description="
					t(
						'collectives',
						'Select a page from the list or create a new one.'
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
	mounted() {
		console.log(this.currentAnnouncement);
	},
};
</script>
