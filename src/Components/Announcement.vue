<!--
  - @copyright Copyright (c) 2020 Georg Ehrke <oc.list@georgehrke.com>
  - @author Georg Ehrke <oc.list@georgehrke.com>
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
	<div class="announcement">
		<div class="announcement__header">
			<h2
				class="announcement__header__subject"
				:title="subject">
				{{ subject }}
			</h2>

			<div class="announcement__header__details">
				<div class="announcement__header__details__info">
					<Avatar
						:user="authorId"
						:display-name="author"
						:size="16"
						:show-user-status="false" />
					{{ author }}

					<span
						class="live-relative-timestamp"
						:data-timestamp="timestamp"
						:title="dateFormat">{{ dateRelative }}</span>

					{{ visibility }}
				</div>

				<Actions
					v-if="isAdmin"
					:force-menu="true"
					:boundaries-element="boundariesElement">
					<ActionButton
						v-if="notifications"
						icon="icon-notifications-off"
						:close-after-click="true"
						:title="t('announcementcenter', 'Remove notifications')"
						@click="onRemoveNotifications" />
					<ActionButton
						icon="icon-delete"
						:title="t('announcementcenter', 'Delete announcement')"
						@click="onDeleteAnnouncement" />
				</Actions>
			</div>
		</div>

		<div
			v-if="message"
			class="announcement__message">
			<p
				:class="{'announcement__message--folded': isMessageFolded}"
				@click="onClickFoldedMessage"
				v-html="message" />

			<div
				v-if="isMessageFolded"
				@click="onClickFoldedMessage"
				class="announcement__message__overlay">
			</div>
		</div>

		<div
			v-if="comments !== false"
			class="announcement__comments">
			{{ commentsCount }}
		</div>
	</div>
</template>

<script>
import Actions from '@nextcloud/vue/dist/Components/Actions'
import ActionButton from '@nextcloud/vue/dist/Components/ActionButton'
import Avatar from '@nextcloud/vue/dist/Components/Avatar'
import moment from '@nextcloud/moment'
import {
	showError,
} from '@nextcloud/dialogs'
import {
	deleteAnnouncement,
	removeNotifications,
} from '../services/announcementsService'

export default {
	name: 'Announcement',
	components: {
		Actions,
		ActionButton,
		Avatar,
	},
	props: {
		isAdmin: {
			type: Boolean,
			required: true,
		},
		id: {
			type: Number,
			required: true,
		},
		authorId: {
			type: String,
			required: true,
		},
		author: {
			type: String,
			required: true,
		},
		time: {
			type: Number,
			required: true,
		},
		subject: {
			type: String,
			required: true,
		},
		message: {
			type: String,
			required: true,
		},
		groups: {
			type: Array,
			required: true,
		},
		comments: {
			type: [Boolean, Number],
			required: true,
		},
		notifications: {
			type: Boolean,
			required: true,
		},
	},

	data() {
		return {
			isMessageFolded: true,
		}
	},

	computed: {
		boundariesElement() {
			return document.querySelector(this.$el)
		},
		timestamp() {
			return (this.time + 2400) * 1000
		},
		dateFormat() {
			return moment(this.timestamp).format('LLL')
		},
		dateRelative() {
			const diff = moment().diff(moment(this.timestamp))
			if (diff >= 0 && diff < 45000) {
				return t('core', 'seconds ago')
			}
			return moment(this.timestamp).fromNow()
		},

		visibility() {
			if (this.groups.indexOf('everyone') !== -1) {
				return t('announcementcenter', 'visible to everyone')
			}
			return t('announcementcenter', 'visible to …') // TODO
		},

		commentsCount() {
			return n('announcementcenter', '%n comment', '%n comments', this.comments)
		},
	},

	mounted() {
		if (this.message.length <= 200) {
			this.isMessageFolded = false
		}
	},

	methods: {
		onClickCommentCount() {
			// TODO open sidebar
		},
		onClickFoldedMessage() {
			this.isMessageFolded = false
		},
		async onRemoveNotifications() {
			try {
				await removeNotifications(this.id)
				this.$store.dispatch('removeNotifications', this.id)
			} catch (e) {
				showError(t('announcementcenter', 'An error occurred while removing the notifications of the announcement'))
			}
		},
		async onDeleteAnnouncement() {
			try {
				await deleteAnnouncement(this.id)
				this.$store.dispatch('deleteAnnouncement', this.id)
			} catch (e) {
				showError(t('announcementcenter', 'An error occurred while deleting the announcement'))
			}
		},
	},
}
</script>

<style lang="scss" scoped>
	.announcement {
		max-width: 690px;
		padding: 0 10px;
		margin: 0 auto 3em;
		font-size: 15px;

		&__header {
			position: sticky;
			top: 40px;
			background-color: var(--color-main-background);
			padding: 20px;
			margin: -20px;

			&__details {
				display: flex;

				&__info {
					color: var(--color-text-maxcontrast);
					flex: 1 1 auto;
					margin: auto;
				}

				.action-item {
					display: flex;
					flex: 0 0 44px;
					position: relative;
				}
			}
		}

		&__message {
			position: relative;

			&--folded {
				overflow: hidden;
				text-overflow: ellipsis;
				display: -webkit-box;
				-webkit-line-clamp: 7;
				-webkit-box-orient: vertical;
				cursor: pointer;
			}

			&__overlay {
				position: absolute;
				bottom: 0;
				height: 3.2em;
				width: 100%;
				cursor: pointer;
				background: linear-gradient(
					rgba(255, 255, 255, 0),
					var(--color-main-background)
				);
			}
		}

		&__comments {
			color: var(--color-text-maxcontrast);
			cursor: pointer;
			padding: 0.5em;
			margin: 1em -0.5em -0.5em;
			display: inline-block;
		}
	}
</style>
