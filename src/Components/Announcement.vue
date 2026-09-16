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

			<div class="announcement__header__details">
				<div class="announcement__header__details__info">
					<NcUserBubble
						:user="authorId"
						:displayName="author" />

					<NcChip
						v-if="!isScheduled"
						noClose>
						<template #icon>
							<IconClockOutline :size="16" />
						</template>
						<NcDateTime
							ignoreSeconds
							:format="{ timeStyle: 'short', dateStyle: 'long' }"
							:timestamp="time * 1000" />
					</NcChip>

					<NcChip
						v-if="isAdmin && isScheduled"
						:text="t('announcementcenter', 'Scheduled')"
						variant="primary"
						noClose
						:title="scheduledLabel">
						<template #icon>
							<IconCalendarClockOutline :size="16" />
						</template>
					</NcChip>

					<NcChip
						v-if="isAdmin && isScheduledForDeletion"
						:text="t('announcementcenter', 'Deletion')"
						:variant="deletionChipVariant"
						noClose
						:title="scheduledDeletionLabel">
						<template #icon>
							<IconDeleteClockOutline :size="16" />
						</template>
					</NcChip>

					<NcChip
						v-if="isAdmin"
						:text="isVisibleToEveryone ? t('announcementcenter', 'Everyone') : t('announcementcenter', 'Restricted')"
						noClose
						:title="visibilityLabel">
						<template #icon>
							<IconWeb v-if="isVisibleToEveryone" :size="16" />
							<IconLockOutline v-else :size="16" />
						</template>
					</NcChip>
				</div>

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
		</div>

		<div
			v-if="message"
			class="announcement__message"
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
			variant="tertiary"
			class="announcement__comments"
			@click="onClickCommentCount">
			{{ commentsCount }}
		</NcButton>
	</div>
</template>

<script>
import {
	showError,
} from '@nextcloud/dialogs'
import { getLanguage, n, t } from '@nextcloud/l10n'
import NcActionButton from '@nextcloud/vue/components/NcActionButton'
import NcActions from '@nextcloud/vue/components/NcActions'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcChip from '@nextcloud/vue/components/NcChip'
import NcDateTime from '@nextcloud/vue/components/NcDateTime'
import NcRichText from '@nextcloud/vue/components/NcRichText'
import NcUserBubble from '@nextcloud/vue/components/NcUserBubble'
import IconBellOffOutline from 'vue-material-design-icons/BellOffOutline.vue'
import IconCalendarClockOutline from 'vue-material-design-icons/CalendarClockOutline.vue'
import IconClockOutline from 'vue-material-design-icons/ClockOutline.vue'
import IconDeleteClockOutline from 'vue-material-design-icons/DeleteClockOutline.vue'
import IconLockOutline from 'vue-material-design-icons/LockOutline.vue'
import IconTrashCanOutline from 'vue-material-design-icons/TrashCanOutline.vue'
import IconWeb from 'vue-material-design-icons/Web.vue'
import {
	deleteAnnouncement,
	removeNotifications,
} from '../services/announcementsService.js'

export default {
	name: 'Announcement',
	components: {
		IconBellOffOutline,
		IconCalendarClockOutline,
		IconClockOutline,
		IconDeleteClockOutline,
		IconLockOutline,
		IconTrashCanOutline,
		IconWeb,
		NcActions,
		NcActionButton,
		NcButton,
		NcChip,
		NcDateTime,
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

		isScheduledForDeletion() {
			return this.deleteTime && this.deleteTime !== null
		},

		deletionChipVariant() {
			// Avoid showing two warning (yellow) chips while the announcement
			// itself is not published yet.
			return this.isScheduled ? 'tertiary' : 'warning'
		},

		scheduledLabel() {
			return t('announcementcenter', 'scheduled at {time}', { time: this.scheduleDateFormat })
		},

		scheduledDeletionLabel() {
			return t('announcementcenter', 'scheduled for deletion at {time}', { time: this.deleteDateFormat })
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
					display: flex;
					align-items: center;
					flex: 1 1 auto;
					flex-wrap: wrap;
					gap: 4px;
					color: var(--color-text-maxcontrast);
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

			:deep(img) {
				max-width: 100%;
			}
		}

		&__comments {
			margin-left: -16px;
		}
	}

	.critical > :deep(.action-button) {
		color: var(--color-text-error);
	}
</style>
