<!--
  - SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcSettingsSection :name="t('announcementcenter', 'Announcements')">
		<NcSettingsSelectGroup id="announcementcenter_admin_group"
			v-model="adminGroups"
			:label="t('announcementcenter', 'These groups will be able to post announcements.')"
			@input="updateGroups" />

		<NcCheckboxRadioSwitch :checked="createActivities"
			type="switch"
			@update:checked="toggleCreateActivities">
			{{ t('announcementcenter', 'Create activities by default') }}
		</NcCheckboxRadioSwitch>
		<NcCheckboxRadioSwitch :checked="createNotifications"
			type="switch"
			@update:checked="toggleCreateNotifications">
			{{ t('announcementcenter', 'Create notifications by default') }}
		</NcCheckboxRadioSwitch>
		<NcCheckboxRadioSwitch :checked="sendEmails"
			type="switch"
			@update:checked="toggleSendEmails">
			{{ t('announcementcenter', 'Send emails by default') }}
		</NcCheckboxRadioSwitch>
		<NcCheckboxRadioSwitch :checked="allowComments"
			type="switch"
			@update:checked="toggleAllowComments">
			{{ t('announcementcenter', 'Allow comments by default') }}
		</NcCheckboxRadioSwitch>
	</NcSettingsSection>
</template>

<script>
import NcCheckboxRadioSwitch from '@nextcloud/vue/dist/Components/NcCheckboxRadioSwitch.js'
import NcSettingsSection from '@nextcloud/vue/dist/Components/NcSettingsSection.js'
import NcSettingsSelectGroup from './Components/SettingsSelectGroup.vue'
import { loadState } from '@nextcloud/initial-state'
import { showError, showSuccess } from '@nextcloud/dialogs'

export default {
	name: 'AdminSettings',

	components: {
		NcCheckboxRadioSwitch,
		NcSettingsSection,
		NcSettingsSelectGroup,
	},

	data() {
		return {
			adminGroups: loadState('announcementcenter', 'admin_groups'),
			createActivities: loadState('announcementcenter', 'create_activities'),
			createNotifications: loadState('announcementcenter', 'create_notifications'),
			sendEmails: loadState('announcementcenter', 'send_emails'),
			allowComments: loadState('announcementcenter', 'allow_comments'),
		}
	},

	methods: {
		async toggleCreateActivities(config) {
			OCP.AppConfig.setValue('announcementcenter', 'create_activities', (config ? 'yes' : 'no'), {
				success: function() {
					this.createActivities = config
					showSuccess(t('announcementcenter', 'Setting changed'))
				}.bind(this),
				error() {
					showError(t('announcementcenter', 'An error occurred while changing the setting'))
				},
			})
		},
		async toggleCreateNotifications(config) {
			OCP.AppConfig.setValue('announcementcenter', 'create_notifications', (config ? 'yes' : 'no'), {
				success: function() {
					this.createNotifications = config
					showSuccess(t('announcementcenter', 'Setting changed'))
				}.bind(this),
				error() {
					showError(t('announcementcenter', 'An error occurred while changing the setting'))
				},
			})
		},
		async toggleSendEmails(config) {
			OCP.AppConfig.setValue('announcementcenter', 'send_emails', (config ? 'yes' : 'no'), {
				success: function() {
					this.sendEmails = config
					showSuccess(t('announcementcenter', 'Setting changed'))
				}.bind(this),
				error() {
					showError(t('announcementcenter', 'An error occurred while changing the setting'))
				},
			})
		},
		async toggleAllowComments(config) {
			OCP.AppConfig.setValue('announcementcenter', 'allow_comments', (config ? 'yes' : 'no'), {
				success: function() {
					this.allowComments = config
					showSuccess(t('announcementcenter', 'Setting changed'))
				}.bind(this),
				error() {
					showError(t('announcementcenter', 'An error occurred while changing the setting'))
				},
			})
		},
		async updateGroups(config) {
			OCP.AppConfig.setValue('announcementcenter', 'admin_groups', JSON.stringify(config), {
				success() {
					showSuccess(t('announcementcenter', 'Setting changed'))
				},
				error() {
					showError(t('announcementcenter', 'An error occurred while changing the setting'))
				},
			})
		},
	},
}
</script>

<style lang="scss" scoped>
</style>
