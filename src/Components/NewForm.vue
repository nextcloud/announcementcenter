<!--
  - @copyright Copyright (c) 2020 Joas Schilling <coding@schilljs.com>
  -
  - @author Joas Schilling <coding@schilljs.com>
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
-->

<template>
	<div class="announcement__form">
		<input
			v-model="subject"
			class="announcement__form__subject"
			type="text"
			name="subject"
			:placeholder="t('announcementcenter', 'New announcement subject')">

		<textarea
			v-model="message"
			class="announcement__form__message"
			name="message"
			rows="4"
			:placeholder="t('announcementcenter', 'Write announcement text, Markdown can be used …')" />

		<div class="announcement__form__buttons">
			<button
				class="button primary"
				@click="onAnnounce">
				{{ t('announcementcenter', 'Announce') }}
			</button>

			<Multiselect
				v-model="groups"
				:options="groupOptions"
				label="label"
				track-by="id"
				:multiple="true"
				:placeholder="t('announcementcenter', 'Everyone')"
				:title="t('announcementcenter', 'These groups will be able to see the announcement. If no group is selected, all users can see it.')"
				@search-change="onSearchChanged" />

			<Actions>
				<ActionCheckbox
					value="1"
					:checked.sync="createActivities">
					{{ t('announcementcenter', 'Create activities') }}
				</ActionCheckbox>
				<ActionCheckbox
					value="1"
					:checked.sync="createNotifications">
					{{ t('announcementcenter', 'Create notifications') }}
				</ActionCheckbox>
				<ActionCheckbox
					value="1"
					:checked.sync="allowComments">
					{{ t('announcementcenter', 'Allow comments') }}
				</ActionCheckbox>
			</Actions>
		</div>
	</div>
</template>

<script>
import Actions from '@nextcloud/vue/dist/Components/Actions'
import ActionCheckbox from '@nextcloud/vue/dist/Components/ActionCheckbox'
import Multiselect from '@nextcloud/vue/dist/Components/Multiselect'
import debounce from 'debounce'
import { loadState } from '@nextcloud/initial-state'
import {
	postAnnouncement,
	searchGroups,
} from '../services/announcementsService'
import { showError } from '@nextcloud/dialogs'

export default {
	name: 'NewForm',

	components: {
		Actions,
		ActionCheckbox,
		Multiselect,
	},

	data() {
		return {
			subject: '',
			message: '',
			createActivities: loadState('announcementcenter', 'createActivities'),
			createNotifications: loadState('announcementcenter', 'createNotifications'),
			allowComments: loadState('announcementcenter', 'allowComments'),
			groups: [],
			groupOptions: [],
		}
	},

	mounted() {
		this.searchGroups('')
	},

	methods: {
		onSearchChanged: debounce(function(search) {
			this.searchGroups(search)
		}, 300),

		async searchGroups(search) {
			const response = await searchGroups(search)
			this.groupOptions = response.data.ocs.data
		},

		async onAnnounce() {
			const groups = this.groups.map(group => {
				return group.id
			})

			try {
				const response = await postAnnouncement(
					this.subject,
					this.message,
					groups,
					this.createActivities,
					this.createNotifications,
					this.allowComments
				)
				this.$store.dispatch('addAnnouncement', response.data.ocs.data)
			} catch (e) {
				console.error(e)
				showError(t('announcementcenter', 'An error occurred while posting the announcement'))
			}
		},
	},
}
</script>

<style lang="scss" scoped>
.announcement__form {
	max-width: 690px;
	padding: 0 10px;
	margin: 70px auto 35px;
	font-size: 15px;

	&__subject {
		width: 100%;
		font-size: 20px;
		font-weight: bold;
	}

	&__message {
		width: 100%;
		font-size: 15px;
	}

	&__buttons {
		text-align: right;

		.button {
			height: 44px;
			font-size: 15px;
			padding: 6px 18px;
			margin: 0 3px;
		}
	}
}
</style>
