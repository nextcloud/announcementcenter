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
	<div class="section">
		<input class="subject"
			type="text"
			name="subject"
			:placeholder="t('announcementcenter', 'New announcement subject')">

		<textarea
			class="message"
			name="message"
			rows="4"
			:placeholder="t('announcementcenter', 'Your announcement…')" />

		<p>
			<input
				id="groups"
				type="hidden"
				name="groups"
				:placeholder="t('announcementcenter', 'Groups …')"
				style="width: 400px;">
			<em>
				{{ t('announcementcenter', 'These groups will be able to see the announcement. If no group is selected, all users can see it.') }}
			</em>
		</p>

		<div class="buttons">
			<button
				class="button primary">
				{{ t('announcementcenter', 'Announce') }}
			</button>
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
import { loadState } from '@nextcloud/initial-state'

export default {
	name: 'NewForm',

	components: {
		Actions,
		ActionCheckbox,
	},

	data() {
		return {
			createActivities: loadState('announcementcenter', 'createActivities'),
			createNotifications: loadState('announcementcenter', 'createNotifications'),
			allowComments: loadState('announcementcenter', 'allowComments'),
		}
	},
}
</script>

<style lang="scss" scoped>
.section {
	width: 670px;
	margin: 70px auto 0;
	font-size: 15px;

	.subject {
		width: 100%;
		font-size: 20px;
		font-weight: bold;
	}

	.message {
		width: 100%;
		font-size: 15px;
	}

	.buttons {
		display: flex;
		align-content: center;

		.button {
			height: 44px;
			font-size: 15px;
			padding: 6px 18px;
			margin: 0 3px;
		}
	}
}
</style>
