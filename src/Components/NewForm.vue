<!--
  - SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
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
			<NcButton variant="primary"
				:disabled="!subject"
				@click="onAnnounce">
				{{ t('announcementcenter', 'Announce') }}
			</NcButton>

			<NcActions>
				<NcActionCheckbox v-model="createActivities" value="1">
					{{ t('announcementcenter', 'Create activities') }}
				</NcActionCheckbox>
				<NcActionCheckbox v-model="createNotifications" value="1">
					{{ t('announcementcenter', 'Create notifications') }}
				</NcActionCheckbox>
				<NcActionCheckbox v-model="sendEmails" value="1">
					{{ t('announcementcenter', 'Send emails') }}
				</NcActionCheckbox>
				<NcActionCheckbox v-model="allowComments" value="1">
					{{ t('announcementcenter', 'Allow comments') }}
				</NcActionCheckbox>
				<NcActionSeparator />
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
				<NcActionSeparator />
				<NcActionInput type="datetime-local"
					:label="t('announcementcenter', 'Schedule announcement time')"
					:disabled="!scheduleEnabled"
					is-native-picker
					hide-label
					:model-value="scheduleTime"
					:min="new Date()"
					@update:modelValue="setScheduleTime">
					<template #icon>
						<IconClockStart :size="20" />
					</template>
				</NcActionInput>
				<NcActionSeparator />
				<NcActionInput type="datetime-local"
					:label="t('announcementcenter', 'Schedule deletion time')"
					:disabled="!deleteEnabled"
					is-native-picker
					hide-label
					:model-value="deleteTime"
					:min="getMinDeleteTime()"
					id-native-date-time-picker="date-time-picker-delete_id"
					@update:modelValue="setDeleteTime">
					<template #icon>
						<IconClockEnd :size="20" />
					</template>
				</NcActionInput>
			</NcActions>
		</div>
	</div>
</template>

<script>
import NcActions from '@nextcloud/vue/components/NcActions'
import NcActionCheckbox from '@nextcloud/vue/components/NcActionCheckbox'
import NcActionInput from '@nextcloud/vue/components/NcActionInput'
import NcActionSeparator from '@nextcloud/vue/components/NcActionSeparator'
import NcButton from '@nextcloud/vue/components/NcButton'
import debounce from 'debounce'
import { loadState } from '@nextcloud/initial-state'
import {
	postAnnouncement,
	searchGroups,
} from '../services/announcementsService.js'
import { showError } from '@nextcloud/dialogs'
import { t } from '@nextcloud/l10n'
import { remark } from 'remark'
import strip from 'strip-markdown'
import IconClockStart from 'vue-material-design-icons/ClockStart.vue'
import IconClockEnd from 'vue-material-design-icons/ClockEnd.vue'

export default {
	name: 'NewForm',

	components: {
		IconClockEnd,
		IconClockStart,
		NcActions,
		NcActionCheckbox,
		NcActionInput,
		NcActionSeparator,
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
			scheduleEnabled: true,
			deleteEnabled: true,
			scheduleTime: null,
			deleteTime: null,
		}
	},

	mounted() {
		this.searchGroups('')
	},

	methods: {
		t,

		resetForm() {
			this.subject = ''
			this.message = ''
			this.createActivities = loadState('announcementcenter', 'createActivities')
			this.createNotifications = loadState('announcementcenter', 'createNotifications')
			this.sendEmails = loadState('announcementcenter', 'sendEmails')
			this.allowComments = loadState('announcementcenter', 'allowComments')
			this.groups = []
			this.scheduleEnabled = true
			this.deleteEnabled = true
			this.scheduleTime = null
			this.deleteTime = null
		},

		onSearchChanged: debounce(function(search) {
			this.searchGroups(search)
		}, 300),

		setScheduleTime(value) {
			this.scheduleTime = new Date(value)
			if (this.deleteTime && this.scheduleTime > this.deleteTime) {
				this.deleteTime = this.scheduleTime
			}
		},

		setDeleteTime(value) {
			this.deleteTime = new Date(value)
			if (this.scheduleTime && this.scheduleTime > this.deleteTime) {
				this.deleteTime = this.scheduleTime
			}
		},

		getMinDeleteTime() {
			if (this.scheduleTime) {
				return this.scheduleTime
			}
			return new Date()
		},

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
		font-weight: bold;
	}

	&__message {
		width: 100%;
		resize: vertical;
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
