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
		<input v-model="subject"
			class="announcement__form__subject"
			type="text"
			name="subject"
			minlength="1"
			maxlength="512"
			:placeholder="t('announcementcenter', 'New announcement subject')">

		<textarea v-model="message"
			class="announcement__form__message"
			name="message"
			rows="4"
			:placeholder="t('announcementcenter', 'Write announcement text, Markdown can be used …')" />

		<div class="announcement__form__buttons">
			<NcButton type="primary"
				:disabled="!subject"
				@click="onAnnounce">
				{{ t('announcementcenter', 'Announce') }}
			</NcButton>

			<NcActions>
				<NcActionInput v-model="groups"
					icon="icon-group"
					type="multiselect"
					:options="groupOptions"
					track-by="id"
					:multiple="true"
					:input-label="t('announcementcenter', 'Visibility')"
					:placeholder="t('announcementcenter', 'Everyone')"
					:title="t('announcementcenter', 'These groups will be able to see the announcement. If no group is selected, all users can see it.')"
					@search="onSearchChanged">
					{{ t('announcementcenter', 'Everyone') }}
				</NcActionInput>
				<NcActionCheckbox value="1"
					:checked.sync="createActivities">
					{{ t('announcementcenter', 'Create activities') }}
				</NcActionCheckbox>
				<NcActionCheckbox value="1"
					:checked.sync="createNotifications">
					{{ t('announcementcenter', 'Create notifications') }}
				</NcActionCheckbox>
				<NcActionCheckbox value="1"
					:checked.sync="sendEmails">
					{{ t('announcementcenter', 'Send emails') }}
				</NcActionCheckbox>
				<NcActionCheckbox value="1"
					:checked.sync="allowComments">
					{{ t('announcementcenter', 'Allow comments') }}
				</NcActionCheckbox>
				<NcActionSeparator />
				<NcActionCheckbox value="0"
					:checked.sync="scheduleEnabled">
					{{ t('announcementcenter', 'Schedule announcement time (optional)') }}
				</NcActionCheckbox>
				<NcDateTimePicker v-model="scheduleTime"
					class="announcement__form__timepicker"
					:disabled="!scheduleEnabled"
					:clearable="true"
					:disabled-date="disabledInPastDate"
					:disabled-time="disabledInPastTime"
					:show-second="false"
					type="datetime" />
				<NcActionSeparator />
				<NcActionCheckbox value="0"
					:checked.sync="deleteEnabled">
					{{ t('announcementcenter', 'Schedule deletion time (optional)') }}
				</NcActionCheckbox>
				<NcDateTimePicker v-model="deleteTime"
					class="announcement__form__timepicker"
					:disabled="!deleteEnabled"
					:clearable="true"
					:disabled-date="disabledInPastDate"
					:disabled-time="disabledInPastTime"
					:show-second="false"
					type="datetime" />
			</NcActions>
		</div>
	</div>
</template>

<script>
import NcActions from '@nextcloud/vue/dist/Components/NcActions.js'
import NcActionCheckbox from '@nextcloud/vue/dist/Components/NcActionCheckbox.js'
import NcActionInput from '@nextcloud/vue/dist/Components/NcActionInput.js'
import NcActionSeparator from '@nextcloud/vue/dist/Components/NcActionSeparator.js'
import NcDateTimePicker from '@nextcloud/vue/dist/Components/NcDateTimePicker.js'
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import debounce from 'debounce'
import { loadState } from '@nextcloud/initial-state'
import {
	postAnnouncement,
	searchGroups,
} from '../services/announcementsService.js'
import { showError } from '@nextcloud/dialogs'
import { remark } from 'remark'
import strip from 'strip-markdown'

export default {
	name: 'NewForm',

	components: {
		NcActions,
		NcActionCheckbox,
		NcActionInput,
		NcActionSeparator,
		NcDateTimePicker,
		NcButton,
	},

	data() {
		return {
			subject: '',
			message: '',
			createActivities: loadState('announcementcenter', 'createActivities'),
			createNotifications: loadState('announcementcenter', 'createNotifications'),
			sendEmails: loadState('announcementcenter', 'sendEmails'),
			allowComments: loadState('announcementcenter', 'allowComments'),
			groups: [],
			groupOptions: [],
			scheduleEnabled: false,
			deleteEnabled: false,
			scheduleTime: null,
			deleteTime: null,
		}
	},

	mounted() {
		this.searchGroups('')
	},

	methods: {
		resetForm() {
			this.subject = ''
			this.message = ''
			this.createActivities = loadState('announcementcenter', 'createActivities')
			this.createNotifications = loadState('announcementcenter', 'createNotifications')
			this.sendEmails = loadState('announcementcenter', 'sendEmails')
			this.allowComments = loadState('announcementcenter', 'allowComments')
			this.groups = []
			this.scheduleEnabled = false
			this.deleteEnabled = false
			this.scheduleTime = null
			this.deleteTime = null
		},

		disabledInPastDate(date) {
			const today = new Date()
			today.setHours(0, 0, 0, 0)
			return date < today
		},

		disabledInPastTime(date) {
			const today = new Date()
			return date < today
		},

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

			const plainMessage = await remark()
				.use(strip, {
					keep: ['blockquote', 'link', 'listItem'],
				})
				.process(this.message)

			try {
				const response = await postAnnouncement(
					this.subject,
					this.message,
					plainMessage.value,
					groups,
					this.createActivities,
					this.createNotifications,
					this.sendEmails,
					this.allowComments,
					new Date(this.scheduleTime).getTime() / 1000, // time in seconds
					new Date(this.deleteTime).getTime() / 1000,
				)
				this.$store.dispatch('addAnnouncement', response.data.ocs.data)

				this.resetForm()
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
		display: flex;
		justify-content: right;

		:deep(.button-vue) {
			margin-right: 10px;
		}
	}

	&__timepicker {
		width: 100%;
	}
}
</style>
