<template>
	<NcAppContentList ref="contentList" style="height: 100%">
		<div class="page-list-headerbar p-2">
			<NcButton class="mr-2" type="primary" @click="addAnnouncement">
				<template #icon>
					<PlusIcon :size="20" />
				</template>
			</NcButton>
			<NcTextField
				name="pageFilter"
				:label="t('collectives', 'Search Announcements')"
				:value.sync="filterString"
				class="page-filter"
				:placeholder="t('collectives', 'Announcements ...')" />

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
		<button @click="isOpen = !isOpen">Toggle</button>
		<div style="height: calc(100% - 60px)">
			<collapse-transition>
				<div v-show="isOpen">
					<VirtualList
						class="virtual-list"
						wrap-class="list-wrap"
						style="overflow-y: auto"
						:data-key="'id'"
						:data-sources="announcements"
						:data-component="AnnouncementComponent"
						:page-mode="true">
					</VirtualList>
				</div>
			</collapse-transition>

			<div v-if="isLoading">
				<NcLoadingIcon />
			</div>
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
	NcLoadingIcon,
} from "@nextcloud/vue";
import { showError } from "@nextcloud/dialogs";
import CloseIcon from "vue-material-design-icons/Close.vue";
import SortAlphabeticalAscendingIcon from "vue-material-design-icons/SortAlphabeticalAscending.vue";
import SortAscendingIcon from "vue-material-design-icons/SortAscending.vue";
import PlusIcon from "./icons/PlusIcon.vue";
import SortClockAscendingOutlineIcon from "vue-material-design-icons/SortClockAscendingOutline.vue";
import Announcement from "./Announcement";
import { loadState } from "@nextcloud/initial-state";
import { emit, subscribe, unsubscribe } from "@nextcloud/event-bus";
import VirtualList from "vue-virtual-scroll-list";
import { CollapseTransition } from "@ivanv/vue-collapse-transition";

export default {
	name: "AnnouncementList",

	components: {
		NcActions,
		NcActionButton,
		NcAppContentList,
		NcButton,
		NcTextField,
		NcLoadingIcon,
		SortAlphabeticalAscendingIcon,
		SortAscendingIcon,
		SortClockAscendingOutlineIcon,
		Announcement,
		PlusIcon,
		CloseIcon,
		VirtualList,
		CollapseTransition,
	},

	data() {
		return {
			AnnouncementComponent: Announcement,
			filterString: "",
			isAdmin: loadState("announcementcenter", "isAdmin"),
			isLoading: false,
			page: 1,
			isOpen: false,
		};
	},
	mounted() {
		subscribe("clickAnnouncement", async (id) => {
			await this.onClickAnnouncement(id);
		});
		this.$refs.contentList.$el.addEventListener(
			"scroll",
			this.handleScroll
		);
	},
	beforeDestroy() {
		unsubscribe("clickAnnouncement");
		this.$refs.contentList.$el.removeEventListener(
			"scroll",
			this.handleScroll
		);
	},
	computed: {
		...mapGetters([
			"announcements",
			"currentAnnouncement",
			"total",
			"pages",
		]),
		groupedAnnouncements() {
			let groups = {
				Today: [],
				Yesterday: [],
				"This Week": [],
				"Last Week": [],
				"Two Weeks Ago": [],
				"Three Weeks Ago": [],
				"Earlier This Month": [],
				"Last Month": [],
				"Two Months Ago": [],
				"Three Months Ago": [],
				"Four Months Ago": [],
				"Five Months Ago": [],
				"Half A Year Ago": [],
				"Seven Months Ago": [],
				"Eight Months Ago": [],
				"Nine Months Ago": [],
				"Ten Months Ago": [],
				"Eleven Months Ago": [],
				"One Year Ago": [],
				"Two Years Ago": [],
				"Other Time": [],
			};

			this.announcements.forEach((announcement) => {
				let time = moment(announcement.time * 1000);
				let now = moment();

				if (time.isSame(now, "day")) {
					groups["Today"].push(announcement);
				} else if (time.isSame(now.subtract(1, "days"), "day")) {
					groups["Yesterday"].push(announcement);
				} else if (time.isSame(now, "week")) {
					groups["This Week"].push(announcement);
				} else if (time.isSame(now.subtract(1, "weeks"), "week")) {
					groups["Last Week"].push(announcement);
				} else if (time.isSame(now.subtract(2, "weeks"), "week")) {
					groups["Two Weeks Ago"].push(announcement);
				} else if (time.isSame(now.subtract(3, "weeks"), "week")) {
					groups["Three Weeks Ago"].push(announcement);
				} else if (time.isSame(now.startOf("month"), "month")) {
					groups["Earlier This Month"].push(announcement);
				} else if (time.isSame(now.subtract(1, "months"), "month")) {
					groups["Last Month"].push(announcement);
				} else if (time.isSame(now.subtract(2, "months"), "month")) {
					groups["Two Months Ago"].push(announcement);
				} else if (time.isSame(now.subtract(3, "months"), "month")) {
					groups["Three Months Ago"].push(announcement);
				} else if (time.isSame(now.subtract(4, "months"), "month")) {
					groups["Four Months Ago"].push(announcement);
				} else if (time.isSame(now.subtract(5, "months"), "month")) {
					groups["Five Months Ago"].push(announcement);
				} else if (time.isSame(now.subtract(6, "months"), "month")) {
					groups["Half A Year Ago"].push(announcement);
				} else if (time.isSame(now.subtract(7, "months"), "month")) {
					groups["Seven Months Ago"].push(announcement);
				} else if (time.isSame(now.subtract(8, "months"), "month")) {
					groups["Eight Months Ago"].push(announcement);
				} else if (time.isSame(now.subtract(9, "months"), "month")) {
					groups["Nine Months Ago"].push(announcement);
				} else if (time.isSame(now.subtract(10, "months"), "month")) {
					groups["Ten Months Ago"].push(announcement);
				} else if (time.isSame(now.subtract(11, "months"), "month")) {
					groups["Eleven Months Ago"].push(announcement);
				} else if (time.isSame(now.subtract(1, "years"), "year")) {
					groups["One Year Ago"].push(announcement);
				} else if (time.isSame(now.subtract(2, "years"), "year")) {
					groups["Two Years Ago"].push(announcement);
				} else {
					groups["Other Time"].push(announcement);
				}
			});

			return groups;
		},
	},
	watch: {
		async filterString(oldValue, newValue) {
			this.page = 1;
			console.log(oldValue);
			this.clearAnnoucements();
			await this.loadAnnouncements({
				filterKey: this.filterString,
				page: this.page,
				pageSize: 14,
			});
			this.setCurrentAnnouncementId(this.announcements[0].id);
		},
	},
	methods: {
		...mapMutations(["setCurrentAnnouncementId", "clearAnnoucements"]),
		...mapActions(["loadAnnouncements"]),
		handleScroll(e) {
			const { scrollTop, scrollHeight, clientHeight } = e.target;
			if (scrollTop + clientHeight >= scrollHeight) {
				this.onScrollToBottom();
			}
		},
		async onScrollToBottom() {
			console.log(this.groupedAnnouncements);
			if (
				this.announcements.length >= this.total ||
				this.page > this.pages ||
				this.isLoading
			)
				return;

			this.isLoading = true;
			this.page += 1;
			await this.loadAnnouncements({
				filterKey: this.filterString,
				page: this.page,
				pageSize: 14,
			});
			this.isLoading = false;
		},
		addAnnouncement() {
			emit("newAnnouncement");
		},
		getExtraProps(index) {
			return {
				isAdmin: this.isAdmin,
			};
		},
		sortPagesAndScroll() {},
		async onClickAnnouncement(id) {
			if (
				this.currentAnnouncement &&
				id === this.currentAnnouncement.id
			) {
				return;
			}
			this.setCurrentAnnouncementId(id);

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
.virtual-list::-webkit-scrollbar {
	display: none;
}
.virtual-list {
	::v-deep .list-wrap {
		padding: 0px 0px 0px !important;
	}
}

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
