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
	<NcAppContentList ref="contentList" style="height: 100%">
		<div class="page-list-headerbar p-2">
			<NcTextField
				name="pageFilter"
				:label="t('announcementcenter', 'Search Announcements')"
				:value.sync="filterString"
				class="page-filter"
				:placeholder="
					t('announcementcenter', 'Search Announcements')
				" />
			<NcButton
				class="ml-2"
				:title="t('announcementcenter', 'post announcement')"
				v-if="isAdmin || canCreate"
				type="primary"
				@click="addAnnouncement">
				<template #icon>
					<PlusIcon :size="20" />
				</template>
			</NcButton>
			<NcButton
				class="ml-2"
				:title="t('announcementcenter', 'load more announcements')"
				v-if="isAdmin || canCreate"
				type="success"
				@click="onScrollToBottom">
				<template #icon>
					<SyncIcon :size="20" />
				</template>
			</NcButton>
		</div>
		<div style="height: calc(100% - 60px)">
			<div v-for="time in Object.keys(groupedAnnouncements)">
				<template v-if="groupedAnnouncements[time].length > 0">
					<div
						@click="toggleCollapse(time)"
						class="time-stage flex items-center text-sm font-bold p-1 hover:cursor-pointer">
						<RightArrowIcon
							:class="{ rotate90: collapseShow[time] }"
							:size="20"></RightArrowIcon>
						{{ time }}
					</div>

					<collapse-transition>
						<div v-show="collapseShow[time]">
							<VirtualList
								class="virtual-list"
								wrap-class="list-wrap"
								style="overflow-y: auto"
								:data-key="'id'"
								:data-sources="groupedAnnouncements[time]"
								:data-component="AnnouncementComponent"
								:page-mode="true">
							</VirtualList>
						</div>
					</collapse-transition>
				</template>
			</div>

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
import CloseIcon from "vue-material-design-icons/Close.vue";
import SortAlphabeticalAscendingIcon from "vue-material-design-icons/SortAlphabeticalAscending.vue";
import SortAscendingIcon from "vue-material-design-icons/SortAscending.vue";
import PlusIcon from "./icons/PlusIcon.vue";
import SyncIcon from "./icons/SyncIcon.vue";
import RightArrowIcon from "./icons/RightArrowIcon.vue";
import SortClockAscendingOutlineIcon from "vue-material-design-icons/SortClockAscendingOutline.vue";
import Announcement from "./Announcement";
import { loadState } from "@nextcloud/initial-state";
import { emit, subscribe, unsubscribe } from "@nextcloud/event-bus";
import VirtualList from "vue-virtual-scroll-list";
import { CollapseTransition } from "@ivanv/vue-collapse-transition";
import Vue from "vue";
import TimeClassify from "../mixins/timeClassify";
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
		RightArrowIcon,
		SyncIcon,
	},
	mixins: [TimeClassify],
	data() {
		return {
			AnnouncementComponent: Announcement,
			filterString: "",
			isAdmin: loadState("announcementcenter", "isAdmin", false),
			canCreate: loadState("announcementcenter", "canCreate", true),
			isLoading: false,
			page: 1,
			collapseShow: {},
		};
	},
	mounted() {
		for (let key of Object.values(this.timeClassify)) {
			this.$set(this.collapseShow, key, false);
		}
		console.log(this.collapseShow);
		this.$refs.contentList.$el.addEventListener(
			"scroll",
			this.handleScroll
		);
		if (Object.keys(this.$route.query).length > 0) {
			this.setCurrentAnnouncementId(
				parseInt(this.$route.query.announcement)
			);
			setTimeout(() => {
				let time = this.findAnnoucementTime(
					parseInt(this.$route.query.announcement)
				);

				if (time !== "") {
					this.toggleCollapse(time);
				}
			}, 1000);
		}
	},
	beforeDestroy() {
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
		sortAnnouncements() {
			let res = this.announcements.sort((a1, a2) => {
				return a2.time - a1.time;
			});
			return res;
		},
		groupedAnnouncements() {
			// 定义一个空对象，用于存储分组结果
			let result = {};
			// 定义一个数组，用于存储分组的键

			// 遍历每个键，初始化为空数组
			for (let key of Object.values(this.timeClassify)) {
				result[key] = [];
			}
			// 获取当前时间的moment对象
			let now = moment(new Date());
			// 遍历announcements数组，根据时间戳判断属于哪个分组，并添加到相应的数组中
			for (let announcement of this.sortAnnouncements) {
				// 获取announcement的时间的moment对象
				let time = moment(announcement.time * 1000);
				// 定义一个变量，用于存储分组的键，默认为Other Time
				let group = this.timeClassify.OtherTime;

				// 判断是否是今天
				if (time.isSame(moment(), "day")) {
					group = this.timeClassify.Today;
				}
				// 判断是否是昨天
				else if (time.isSame(moment().subtract(1, "day"), "day")) {
					group = this.timeClassify.Yesterday;
				}
				// 判断是否是本周
				else if (time.isSame(moment(), "week")) {
					group = this.timeClassify.ThisWeek;
				}
				// 判断是否是上周
				else if (time.isSame(moment().subtract(1, "week"), "week")) {
					group = this.timeClassify.LastWeek;
				}
				// 判断是否是两周前
				else if (time.isSame(moment().subtract(2, "week"), "week")) {
					group = this.timeClassify.TwoWeeksAgo;
				}
				// 判断是否是三周前
				else if (time.isSame(moment().subtract(3, "week"), "week")) {
					group = this.timeClassify.ThreeWeeksAgo;
				}
				// 判断是否是本月初
				else if (time.isSame(moment(), "month") && time.date() <= 7) {
					group = this.timeClassify.EarlierThisMonth;
				}
				// 判断是否是上个月
				else if (time.isSame(moment().subtract(1, "month"), "month")) {
					group = this.timeClassify.LastMonth;
				}
				// 判断是否是两个月前
				else if (time.isSame(moment().subtract(2, "month"), "month")) {
					group = this.timeClassify.TwoMonthsAgo;
				}
				// 判断是否是三个月前
				else if (time.isSame(moment().subtract(3, "month"), "month")) {
					group = this.timeClassify.ThreeMonthsAgo;
				}
				// 判断是否是四个月前
				else if (time.isSame(moment().subtract(4, "month"), "month")) {
					group = this.timeClassify.FourMonthsAgo;
				}
				// 判断是否是五个月前
				else if (time.isSame(moment().subtract(5, "month"), "month")) {
					group = this.timeClassify.FiveMonthsAgo;
				}
				// 判断是否是半年前
				else if (time.isSame(moment().subtract(6, "month"), "month")) {
					group = this.timeClassify.HalfAYearAgo;
				}
				// 判断是否是七个月前
				else if (time.isSame(moment().subtract(7, "month"), "month")) {
					group = this.timeClassify.SevenMonthsAgo;
				}
				// 判断是否是八个月前
				else if (time.isSame(moment().subtract(8, "month"), "month")) {
					group = this.timeClassify.EightMonthsAgo;
				}
				// 判断是否是九个月前
				else if (time.isSame(moment().subtract(9, "month"), "month")) {
					group = this.timeClassify.NineMonthsAgo;
				}
				// 判断是否是十个月前
				else if (time.isSame(moment().subtract(10, "month"), "month")) {
					group = this.timeClassify.TenMonthsAgo;
				}
				// 判断是否是十一个月前
				else if (time.isSame(moment().subtract(11, "month"), "month")) {
					group = this.timeClassify.ElevenMonthsAgo;
				}
				// 判断是否是一年前
				else if (time.isSame(moment().subtract(1, "year"), "year")) {
					group = this.timeClassify.OneYearAgo;
				}
				// 判断是否是两年前
				else if (time.isSame(moment().subtract(2, "year"), "year")) {
					group = this.timeClassify.TwoYearsAgo;
				}
				result[group].push(announcement);
			}

			// 返回分组结果对象
			return result;
		},
	},
	watch: {
		async filterString(oldValue, newValue) {
			this.page = 1;
			this.clearAnnoucements();
			await this.loadAnnouncements({
				filterKey: this.filterString,
				page: this.page,
				pageSize: 14,
			});
			if (this.announcements.length > 0) {
				this.setCurrentAnnouncementId(this.announcements[0].id);
			}
		},
	},
	methods: {
		...mapMutations(["setCurrentAnnouncementId", "clearAnnoucements"]),
		...mapActions(["loadAnnouncements"]),
		toggleCollapse(time) {
			console.log(time);
			if (!this.collapseShow[time]) {
				Object.keys(this.collapseShow).forEach((key) => {
					this.$set(this.collapseShow, key, false);
				});
				this.$set(this.collapseShow, time, true);
			} else {
				this.$set(this.collapseShow, time, false);
			}
			console.log(this.collapseShow);
		},
		findAnnoucementTime(id) {
			let timeKeys = Object.keys(this.groupedAnnouncements);
			for (let i = 0; i < timeKeys.length; i++) {
				let key = timeKeys[i];
				for (
					let j = 0;
					j < this.groupedAnnouncements[key].length;
					j++
				) {
					if (this.groupedAnnouncements[key][j].id === id) return key;
				}
			}

			return "";
		},
		handleScroll(e) {
			const { scrollTop, scrollHeight, clientHeight } = e.target;
			if (scrollTop + clientHeight >= scrollHeight) {
				this.onScrollToBottom();
			}
		},
		async onScrollToBottom() {
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
				pageSize: 30,
			});
			this.isLoading = false;
		},
		addAnnouncement() {
			emit("newAnnouncement");
		},

		sortPagesAndScroll() {},
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
.rotate90 {
	rotate: 90deg;
}
.time-stage {
	border-left: 0.3rem solid var(--color-primary);
	border-bottom: 1px solid var(--color-background-darker);
}
.time-stage:hover {
	background: var(--color-background-hover);
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
