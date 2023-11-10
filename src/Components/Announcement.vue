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
			<h2 class="announcement__header__subject"
				:title="subject">
				{{ subject }}
			</h2>

			<div class="announcement__header__details">
				<div class="announcement__header__details__info">
					<NcAvatar :user="authorId"
						:display-name="author"
						:size="16"
						:show-user-status="false" />
					{{ author }}
					·
					<span class="live-relative-timestamp"
						:data-timestamp="timestamp"
						:title="dateFormat">{{ dateRelative }}</span>

					<template v-if="isAdmin">
						·
						<template v-if="isVisibleToEveryone">
							{{ visibilityLabel }}
						</template>
						<span v-else
							:title="visibilityTitle">
							{{ visibilityLabel }}
						</span>
					</template>
				</div>

				<NcActions v-if="isAdmin"
					:force-menu="true"
					:boundaries-element="boundariesElement">
					<NcActionButton v-if="notifications"
						icon="icon-notifications-off"
						:close-after-click="true"
						:name="t('announcementcenter', 'Clear notifications')"
						@click="onRemoveNotifications" />
					<NcActionButton icon="icon-delete"
						:name="t('announcementcenter', 'Delete announcement')"
						@click="onDeleteAnnouncement" />
				</NcActions>
			</div>
		</div>

		<div v-if="message"
			class="announcement__message"
			@click="onClickFoldedMessage">
			<NcRichText :text="message"
				:arguments="{}"
				:autolink="true"
				:use-markdown="true"
				:class="{'announcement__message--folded': isMessageFolded}" />

			<div v-if="isMessageFolded"
				class="announcement__message__overlay" />
		</div>

		<NcButton v-if="comments !== false"
			type="tertiary"
			class="announcement__comments"
			@click="onClickCommentCount">
			{{ commentsCount }}
		</NcButton>
	</div>
</template>

<script>
import NcActions from '@nextcloud/vue/dist/Components/NcActions.js'
import NcActionButton from '@nextcloud/vue/dist/Components/NcActionButton.js'
import NcAvatar from '@nextcloud/vue/dist/Components/NcAvatar.js'
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcRichText from '@nextcloud/vue/dist/Components/NcRichText.js'
import moment from '@nextcloud/moment'
import {
	showError,
} from '@nextcloud/dialogs'
import {
	deleteAnnouncement,
	removeNotifications,
} from '../services/announcementsService.js'

export default {
	name: 'Announcement',
	components: {
		NcActions,
		NcActionButton,
		NcAvatar,
		NcButton,
		NcRichText,
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
			return this.time * 1000
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

		isVisibleToEveryone() {
			return this.groups.length === 0
				|| this.groups.filter(({ id }) => {
					return id === 'everyone'
				}).length === 1
		},

		visibilityLabel() {
			if (this.isVisibleToEveryone) {
				return t('announcementcenter', 'visible to everyone')
			}

			if (this.groups.length === 1) {
				return t('announcementcenter', 'visible to group {name}', this.groups[0])
			}
			if (this.groups.length === 2) {
				return t('announcementcenter', 'visible to groups {name1} and {name2}', {
					name1: this.groups[0].name,
					name2: this.groups[1].name,
				})
			}
			return n(
				'announcementcenter',
				'visible to group {name} and %n more',
				'visible to group {name} and %n more',
				this.groups.length - 1,
				this.groups[0],
			)
		},

		visibilityTitle() {
			if (this.isVisibleToEveryone) {
				return ''
			}

			return this.groups.map(({ name }) => {
				return name
			}).join(t('announcementcenter', ', '))
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
			this.$emit('click', this.id)
		},
		onClickFoldedMessage() {
			this.isMessageFolded = false
			if (this.comments !== false) {
				this.$emit('click', this.id)
			}
		},
		async onRemoveNotifications() {
			try {
				await removeNotifications(this.id)
				this.$store.dispatch('removeNotifications', this.id)
			} catch (e) {
				console.error(e)
				showError(t('announcementcenter', 'An error occurred while removing the notifications of the announcement'))
			}
		},
		async onDeleteAnnouncement() {
			try {
				await deleteAnnouncement(this.id)
				this.$store.dispatch('deleteAnnouncement', this.id)
			} catch (e) {
				console.error(e)
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

		&:nth-child(1) {
			margin-top: 70px;
		}

		&__header {
			&__details {
				display: flex;

				&__info {
					color: var(--color-text-maxcontrast);
					flex: 1 1 auto;
					display: flex;
					align-items: center;

					:deep(.avatardiv) {
						margin-right: 4px;
					}

					span {
						margin-left: 4px;
						margin-right: 4px;
					}
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
			margin-top: 20px;

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
			margin-left: -16px;
		}
	}
</style>
