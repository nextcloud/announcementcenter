<!--
  - @copyright Copyright (c) 2018 John Molakvoæ <skjnldsv@protonmail.com>
  -
  - @author John Molakvoæ <skjnldsv@protonmail.com>
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
	<NcAppNavigation :class="{ 'icon-loading': loading }">
		<template #list>
			<NcAppNavigationNew
				@click="newAnnoucement"
				:text="t('deck', 'New annoucement')">
				<template #icon>
					<Plus :size="20" />
				</template>
			</NcAppNavigationNew>
			<NcAppNavigationItem
				:title="t('deck', 'All annoucements')"
				:exact="true"
				:allow-collapse="true">
				<NcAppNavigationItem
					title="text1"
					:exact="true"
					:allow-collapse="true">
				</NcAppNavigationItem>
				<NcAppNavigationItem
					title="text2"
					:exact="true"
					:allow-collapse="true">
				</NcAppNavigationItem>
				<template #icon>
					<AnnouncementIcon :size="20" />
				</template>
			</NcAppNavigationItem>
		</template>

		<template #footer>
			<NcAppNavigationSettings :title="t('deck', 'Deck settings')">
				<div>
					<div>
						<input
							id="toggle-modal"
							type="checkbox"
							class="checkbox" />
						<label for="toggle-modal">
							{{ t("deck", "Use bigger card view") }}
						</label>
					</div>

					<div>
						<input
							id="toggle-idbadge"
							type="checkbox"
							class="checkbox" />
						<label for="toggle-idbadge">
							{{ t("deck", "Show card ID badge") }}
						</label>
					</div>

					<NcMultiselect
						v-if="isAdmin"
						v-model="groupLimit"
						:class="{ 'icon-loading-small': groupLimitDisabled }"
						open-direction="bottom"
						:options="groups"
						:multiple="true"
						:disabled="groupLimitDisabled"
						:placeholder="
							t('deck', 'Limit board creation to some groups')
						"
						label="displayname"
						track-by="id"
						@input="updateConfig" />
					<p v-if="isAdmin">
						{{
							t(
								"deck",
								"Users outside of those groups will not be able to create their own boards, but will still be able to work on boards that have been shared with them."
							)
						}}
					</p>
				</div>
			</NcAppNavigationSettings>
		</template>
	</NcAppNavigation>
</template>

<script>
import axios from "@nextcloud/axios";
import { mapGetters } from "vuex";
import NcModal from "@nextcloud/vue/dist/Components/NcModal.js";
import ClickOutside from "vue-click-outside";
import Plus from "vue-material-design-icons/Plus";
import {
	NcAppNavigation,
	NcAppNavigationItem,
	NcAppNavigationSettings,
	NcMultiselect,
	NcAppNavigationNew,
	NcButton,
} from "@nextcloud/vue";
import AppNavigationAddBoard from "./AppNavigationAddBoard.vue";
import AppNavigationBoardCategory from "./AppNavigationBoardCategory.vue";
import { loadState } from "@nextcloud/initial-state";
import { generateOcsUrl } from "@nextcloud/router";
import { getCurrentUser } from "@nextcloud/auth";
import ArchiveIcon from "vue-material-design-icons/Archive.vue";
import CalendarIcon from "vue-material-design-icons/Calendar.vue";
import AnnouncementIcon from "../icons/AnnouncementIcon.vue";
import ShareVariantIcon from "vue-material-design-icons/Share.vue";
import { emit, subscribe, unsubscribe } from "@nextcloud/event-bus";
const canCreateState = true; // loadState("deck", "canCreate");

export default {
	name: "AppNavigation",
	components: {
		NcAppNavigation,
		NcAppNavigationSettings,
		AppNavigationAddBoard,
		AppNavigationBoardCategory,
		NcMultiselect,
		NcAppNavigationItem,
		ArchiveIcon,
		CalendarIcon,
		AnnouncementIcon,
		ShareVariantIcon,
		Plus,
		NcAppNavigationNew,
		NcButton,
	},
	directives: {
		ClickOutside,
	},
	props: {
		loading: {
			type: Boolean,
			default: false,
		},
	},
	data() {
		return {
			opened: false,
			groups: [],
			groupLimit: [],
			groupLimitDisabled: true,
			canCreate: canCreateState,
		};
	},
	computed: {
		isAdmin() {
			return !!getCurrentUser()?.isAdmin;
		},
	},
	beforeMount() {},
	methods: {
		async updateConfig() {
			await this.$store.dispatch("setConfig", {
				groupLimit: this.groupLimit,
			});
			this.groupLimitDisabled = false;
		},
		newAnnoucement() {
			emit("newAnnouncement");
		},
	},
};
</script>
<style scoped lang="scss">
#app-settings-content {
	p {
		margin-top: 20px;
		margin-bottom: 20px;
		color: var(--color-text-light);
	}
}
</style>
