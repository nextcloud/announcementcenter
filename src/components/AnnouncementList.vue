<template>
	<NcAppContentList :show-details="true">
		<div class="page-list-headerbar">
			<NcTextField
				name="pageFilter"
				:label="t('collectives', 'Search pages')"
				:value.sync="filterString"
				class="page-filter"
				:placeholder="t('collectives', 'Search pages ...')" />

			<NcActions
				class="toggle"
				:aria-label="t('collectives', 'Sort order')"
				:title="t('collectives', 'Sort order')">
				<!-- <template #icon>
					<SortAscendingIcon v-if="sortedBy('byOrder')" :size="16" />
					<SortAlphabeticalAscendingIcon
						v-else-if="sortedBy('byTitle')"
						:size="16" />
					<SortClockAscendingOutlineIcon v-else :size="16" />
				</template> -->
				<NcActionButton
					class="toggle-button"
					:close-after-click="true"
					@click="sortPagesAndScroll('byOrder')">
					<template #icon>
						<SortAscendingIcon :size="20" />
					</template>
					{{ t("collectives", "Sort by custom order") }}
				</NcActionButton>
				<NcActionButton
					class="toggle-button"
					:close-after-click="true"
					@click="sortPagesAndScroll('byTimestamp')">
					<template #icon>
						<SortClockAscendingOutlineIcon :size="20" />
					</template>
					{{ t("collectives", "Sort recently changed first") }}
				</NcActionButton>
				<NcActionButton
					class="toggle-button"
					:close-after-click="true"
					@click="sortPagesAndScroll('byTitle')">
					<template #icon>
						<SortAlphabeticalAscendingIcon :size="20" />
					</template>
					{{ t("collectives", "Sort by title") }}
				</NcActionButton>
			</NcActions>
		</div>

		<div class="p-2">
			<transition-group name="fade-collapse" tag="div">
				<Announcement
					v-for="announcement in announcements"
					:key="announcement.id"
					:id="announcement.id"
					:is-admin="isAdmin"
					:author-id="announcement.author_id"
					v-bind="announcement"
					@click="onClickAnnouncement" />
			</transition-group>
		</div>
	</NcAppContentList>
</template>

<script>
import { mapActions, mapState, mapGetters, mapMutations } from "vuex";
import {
	NcActionButton,
	NcActions,
	NcAppContentList,
	NcButton,
	NcTextField,
} from "@nextcloud/vue";
import { showError } from "@nextcloud/dialogs";
import CloseIcon from "vue-material-design-icons/Close.vue";
import SortAlphabeticalAscendingIcon from "vue-material-design-icons/SortAlphabeticalAscending.vue";
import SortAscendingIcon from "vue-material-design-icons/SortAscending.vue";
import SortClockAscendingOutlineIcon from "vue-material-design-icons/SortClockAscendingOutline.vue";
import Announcement from "./Announcement";
import { loadState } from "@nextcloud/initial-state";
export default {
	name: "AnnouncementList",

	components: {
		NcActions,
		NcActionButton,
		NcAppContentList,
		NcButton,
		NcTextField,
		SortAlphabeticalAscendingIcon,
		SortAscendingIcon,
		SortClockAscendingOutlineIcon,
		Announcement,
	},

	data() {
		return {
			filterString: "",
			isAdmin: loadState("announcementcenter", "isAdmin"),
		};
	},
	mounted() {},
	computed: {
		...mapGetters(["announcements", "currentAnnouncement"]),
	},

	methods: {
		...mapMutations(["setCurrentAnnouncementId"]),
		sortPagesAndScroll() {},
		async onClickAnnouncement(id) {
			
			this.setCurrentAnnouncementId(id);
			// if (id === this.activeId) {
			// 	return;
			// }

			// this.activeId = id;
	
			if (!this.activateAnnouncementHasComments) {
				return;
			}

			if (id === 0) {
				// Destroy the comments view as the sidebar is destroyed
				this.commentsView = null;
				return;
			}

			if (!this.commentsView) {
				// Create a new comments view when there is none
				this.commentsView = new OCA.Comments.View("announcement");
			}

			await this.commentsView.update(id);
			this.commentsView.$mount(this.$refs.sidebar);
		},
	},
};
</script>

<style lang="scss" scoped>
.app-content-list {
	// nextcloud-vue component sets `max-height: unset` on mobile.
	// Overwrite this to fix stickyness of header and landingpage.
	max-height: calc(100vh - 50px);
}

.page-list-headerbar {
	display: flex;
	flex-direction: row;
	position: sticky;
	top: 0;
	z-index: 2;
	background-color: var(--color-main-background);
	align-items: center;
	justify-content: space-between;
	margin-right: 4px;

	.page-filter {
		margin-left: 50px !important;
	}
}

.toggle {
	height: 44px;
	width: 44px;
	padding: 0;
}

.toggle:hover {
	opacity: 1;
}

.action-item--single.toggle-push-to-right {
	margin-left: auto;
}

li.toggle-button.selected {
	background-color: var(--color-primary-element-light);
}

.page-list-landing-page {
	position: sticky;
	top: 44px;
	z-index: 1;
	background-color: var(--color-main-background);
}

.sort-order-container {
	display: flex;
	align-items: center;

	position: sticky;
	top: 92px; // 2x 44px + 4px border-bottom
	z-index: 1;
	background-color: var(--color-main-background);
	border-bottom: 4px solid var(--color-main-background);

	.sort-order-chip {
		display: flex;
		flex-direction: row;
		align-items: center;

		height: 24px;
		padding: 7px;
		margin-left: 33px; // 40px - 7px
		background-color: var(--color-primary-element-light);
		border-radius: var(--border-radius-pill);

		overflow: hidden;
		white-space: nowrap;
		text-overflow: ellipsis;

		.sort-oder-chip-button {
			min-height: 20px;
			min-width: 20px;
			height: 20px;
			width: 20px !important;
			padding: 7px;
			margin-left: 10px;
		}
	}
}
</style>
