<!--
  - SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="announcement">
		<div class="announcement__header">
			<h2
				class="announcement__header__subject"
				:title="subject">
				{{ subject }}
			</h2>
			<NcActions
				v-if="isAdmin"
				:forceMenu="true"
				:boundariesElement="boundariesElement">
				<NcActionButton
					v-if="notifications"
					:closeAfterClick="true"
					:name="t('announcementcenter', 'Clear notifications')"
					@click="onRemoveNotifications">
					<template #icon>
						<IconBellOffOutline size="20" />
					</template>
				</NcActionButton>
				<NcActionButton
					:name="t('announcementcenter', 'Delete announcement')"
					class="critical"
					@click="onDeleteAnnouncement">
					<template #icon>
						<IconTrashCanOutline size="20" />
					</template>
				</NcActionButton>
			</NcActions>
		</div>

		<NcNoteCard type="info">
			<div class="announcement__header__details">
				<NcUserBubble
					:user="authorId"
					:displayName="author" />

				<span v-if="isScheduled" :title="scheduledLabel">{{ scheduledLabel }}</span>
				<span v-if="isDeleting" :title="deletingLabel">{{ deletingLabel }}</span>

				<NcDateTime
					v-else
					ignoreSeconds
					:format="{ timeStyle: 'short', dateStyle: 'long' }"
					:timestamp="time * 1000" />

				<template v-if="isAdmin">
					<template v-if="isVisibleToEveryone">
						{{ visibilityLabel }}
					</template>
					<span
						v-else
						:title="visibilityTitle">
						{{ visibilityLabel }}
					</span>
				</template>
			</div>
		</NcNoteCard>

		<div class="announcement__contents">
			<div
				v-if="message"
				class="message"
				@click="onClickFoldedMessage">
				<NcRichText
					:text="message"
					:arguments="{}"
					:autolink="true"
					:useMarkdown="true"
					:class="{'announcement__message--folded': isMessageFolded}" />

				<div
					v-if="isMessageFolded"
					class="announcement__message__overlay" />
			</div>

			<NcButton
				v-if="comments !== false"
				variant="secondary"
				@click="onClickCommentCount">
				{{ commentsCount }}
			</NcButton>
		</div>
	</div>
</template>

<script>
import {
	showError,
} from '@nextcloud/dialogs'
import { getLanguage, n, t } from '@nextcloud/l10n'
import { NcActionButton, NcActions, NcButton, NcDateTime, NcNoteCard, NcRichText, NcUserBubble } from '@nextcloud/vue'
import IconBellOffOutline from 'vue-material-design-icons/BellOffOutline.vue'
import IconTrashCanOutline from 'vue-material-design-icons/TrashCanOutline.vue'
import {
	deleteAnnouncement,
	removeNotifications,
} from '../services/announcementsService.js'

export default {
	name: 'Announcement',
	components: {
		IconBellOffOutline,
		IconTrashCanOutline,
		NcActions,
		NcActionButton,
		NcButton,
		NcDateTime,
		NcNoteCard,
		NcRichText,
		NcUserBubble,
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
			default: null,
		},

		comments: {
			type: [Boolean, Number],
			required: true,
		},

		notifications: {
			type: Boolean,
			default: false,
		},

		scheduleTime: {
			type: Number,
			default: null,
		},

		deleteTime: {
			type: Number,
			default: null,
		},
	},

	emits: ['click'],

	data() {
		return {
			isMessageFolded: true,
		}
	},

	computed: {
		boundariesElement() {
			return document.querySelector(this.$el)
		},

		scheduleDateFormat() {
			return (new Date(this.scheduleTime * 1000)).toLocaleString(getLanguage(), { dateStyle: 'long', timeStyle: 'short' })
		},

		deleteDateFormat() {
			return (new Date(this.deleteTime * 1000)).toLocaleString(getLanguage(), { dateStyle: 'long', timeStyle: 'short' })
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
				return t(
					'announcementcenter',
					'visible to group {name}',
					this.groups[0],
					undefined,
					{ escape: false, sanitize: false },
				)
			}
			if (this.groups.length === 2) {
				return t(
					'announcementcenter',
					'visible to groups {name1} and {name2}',
					{
						name1: this.groups[0].name,
						name2: this.groups[1].name,
					},
					undefined,
					{ escape: false, sanitize: false },
				)
			}
			return n(
				'announcementcenter',
				'visible to group {name} and %n more',
				'visible to group {name} and %n more',
				this.groups.length - 1,
				this.groups[0],
				undefined,
				{ escape: false, sanitize: false },
			)
		},

		isScheduled() {
			return this.scheduleTime && this.scheduleTime !== null
		},

		scheduledLabel() {
			return t('announcementcenter', 'scheduled at {time}', { time: this.scheduleDateFormat })
		},

		isDeleting() {
			return this.isAdmin && this.deleteTime && this.deleteTime !== null
		},

		deletingLabel() {
			return t('announcementcenter', 'to be hidden at {time}', { time: this.deleteDateFormat })
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
		t,
		n,

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
		margin: 0 auto 3em;
		display: flex;
		flex-direction: column;

		&:nth-child(1) {
			margin-top: 70px;
		}

		&__header {
			display: flex;
			flex-direction: row;
			align-items: center;
			justify-content: space-between;

			&__details {
				display: flex;
				flex-direction: column;
			}
		}

		&__contents {
			display: flex;
			flex-direction: column;
			gap: 15px;

			.message {
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
		}
	}

	.critical > :deep(.action-button) {
		color: var(--color-text-error);
	}
</style>
