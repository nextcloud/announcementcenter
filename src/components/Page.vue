<template>
	<div class="p-8" style="height: 100%">
		<h1 id="titleform" class="flex sticky top-0 items-center">
			<!-- Page title -->
			<form @submit.prevent="focusEditor()">
				<input
					ref="title"
					v-model="newSubject"
					v-tooltip="titleIfTruncated(newSubject)"
					class="overflow-hidden overflow-ellipsis announcement_title"
					:class="{ mobile: isMobile }"
					:placeholder="t('announcementcenter', 'Title')"
					:disabled="!isTextEdit"
					type="text" />
			</form>

			<div class="flex">
				<!-- Edit button if editable -->
				<EditButton v-if="isAuthor" :mobile="isMobile" />
			</div>
		</h1>
		<div
			style="border-left: solid 2px var(--color-primary)"
			:title="t('announcementcenter', 'receivers')"
			class="px-3 my-1 flex items-center">
			<GroupIcon :size="20" class="mr-2"></GroupIcon>
			<div
				class="mr-2 px-2 rounded"
				v-for="group in currentAnnouncement.groups"
				style="color: white; background: var(--color-primary)">
				{{ group.name }}
			</div>
		</div>
		<div
			style="border-left: solid 2px var(--color-primary)"
			v-if="currentAnnouncement.attachmentCount > 0">
			<div
				@click="toggleCollapse"
				class="time-stage flex items-center text-sm font-bold p-1 hover:cursor-pointer">
				<RightArrowIcon
					:class="{ rotate90: fileCollapseShow }"
					:size="20"></RightArrowIcon>
				{{
					n(
						"announcementcenter",
						"%n attachment",
						"%n attachments",
						currentAnnouncement.attachmentCount
					)
				}}
			</div>
			<collapse-transition>
				<div v-show="fileCollapseShow">
					<ul class="attachment-list grid grid-cols-3 gap-2">
						<li
							v-for="attachment in attachments"
							:key="attachment.id"
							class="attachment"
							:class="{
								'attachment--deleted': attachment.deletedAt > 0,
							}">
							<a
								class="fileicon"
								:style="mimetypeForAttachment(attachment)" />
							<div class="flex items-center justify-between">
								<a
									class="hover:cursor-pointer mr-2 truncate"
									@click.prevent="showViewer(attachment)"
									style="width: 200px">
									{{ attachment.data }}
								</a>
								<div v-if="attachment.deletedAt === 0">
									<span class="filesize">{{
										formattedFileSize(
											attachment.extendedData.filesize
										)
									}}</span>
								</div>
								<div v-else>
									<span class="attachment--info">{{
										t("announcementcenter", "Pending share")
									}}</span>
								</div>

								<a :href="downloadLink(attachment)"
									><DownloadIcon :size="20"></DownloadIcon
								></a>
							</div>
						</li>
					</ul>
				</div>
			</collapse-transition>
		</div>
		<div class="rounded mb-2" style="height: 60%">
			<div style="height: 100%; overflow-y: auto" v-if="textAppAvailable">
				<div ref="editor" />
			</div>
			<template v-else>
				<NcRichText
					:text="newMessage"
					:arguments="{}"
					:autolink="true"
					:use-markdown="true" />
			</template>
		</div>
		<div class="rounded p-2" style="height: 30%">
			<div
				class="font-bold text-xl"
				style="color: var(--color-text-maxcontrast)">
				{{ t("announcementcenter", "comments") }}
			</div>
			<div style="height: 90%; overflow-y: auto">
				<div ref="commentsView"></div>
			</div>
		</div>
		<NcModal
			v-if="modalShow"
			:title="t('announcementcenter', 'Choose attachment')"
			@close="modalShow = false">
			<div class="modal__content">
				<h3>{{ t("announcementcenter", "Choose attachment") }}</h3>
				<AttachmentList
					:announcement-id="currentAnnouncement.id"
					:selectable="true"
					@select-attachment="addAttachment" />
			</div>
		</NcModal>
	</div>
</template>

<script>
import isMobile from "@nextcloud/vue/dist/Mixins/isMobile.js";
import GroupIcon from "./icons/GroupIcon.vue";
import {
	NcActions,
	NcActionButton,
	NcButton,
	NcAvatar,
	NcUserBubble,
	NcModal,
} from "@nextcloud/vue";
import EditButton from "./Page/EditButton.vue";
import AttachmentList from "./Page/AttachmentList.vue";
import { mapActions, mapGetters, mapMutations } from "vuex";
import { showError } from "@nextcloud/dialogs";
import { updateAnnouncement } from "../services/announcementsService.js";
import { remark } from "remark";
import relativeDate from "../mixins/relativeDate.js";
import strip from "strip-markdown";
import { emit, subscribe, unsubscribe } from "@nextcloud/event-bus";
import { generateUrl, generateRemoteUrl } from "@nextcloud/router";
import { formatFileSize } from "@nextcloud/files";
import { getCurrentUser } from "@nextcloud/auth";
import { CollapseTransition } from "@ivanv/vue-collapse-transition";
import RightArrowIcon from "./icons/RightArrowIcon.vue";
import AttachmentIcon from "./icons/AttachmentIcon.vue";
import DownloadIcon from "./icons/DownloadIcon.vue";
export default {
	name: "Page",

	components: {
		EditButton,
		NcActionButton,
		NcActions,
		NcButton,
		NcAvatar,
		NcUserBubble,
		AttachmentList,
		NcModal,
		GroupIcon,
		CollapseTransition,
		RightArrowIcon,
		AttachmentIcon,
		DownloadIcon,
	},

	mixins: [isMobile, relativeDate],

	data() {
		return {
			newSubject: "",
			titleIsTruncated: false,
			editor: null,
			textAppAvailable: !!window.OCA?.Text?.createEditor,
			newMessage: "",
			commentsView: null,
			modalShow: false,
			fileCollapseShow: false,
		};
	},

	computed: {
		...mapGetters(["currentAnnouncement", "isTextEdit"]),
		attachments() {
			// FIXME sort propertly by last modified / deleted at
			return [
				...this.$store.getters.attachmentsByannouncement(
					this.currentAnnouncement.id
				),
			]
				.filter((attachment) => attachment.deletedAt >= 0)
				.sort((a, b) => b.id - a.id);
		},
		mimetypeForAttachment() {
			return (attachment) => {
				if (!attachment) {
					return {};
				}
				const url = attachment.extendedData.hasPreview
					? this.attachmentPreview16(attachment)
					: OC.MimeType.getIconUrl(attachment.extendedData.mimetype);
				const styles = {
					"background-image": `url("${url}")`,
				};
				return styles;
			};
		},
		attachmentPreview16() {
			return (attachment) =>
				attachment.extendedData.fileid
					? generateUrl(
							`/core/preview?fileId=${attachment.extendedData.fileid}&x=16&y=16`
					  )
					: null;
		},
		attachmentPreview() {
			return (attachment) =>
				attachment.extendedData.fileid
					? generateUrl(
							`/core/preview?fileId=${attachment.extendedData.fileid}&x=600&y=600&a=true`
					  )
					: null;
		},
		attachmentUrl() {
			return (attachment) =>
				generateUrl(
					`/apps/announcementcenter/announcements/${attachment.announcementId}/attachment/${attachment.id}`
				);
		},
		internalLink() {
			return (attachment) =>
				generateUrl("/f/" + attachment.extendedData.fileid);
		},
		downloadLink() {
			return (attachment) =>
				generateRemoteUrl(
					`dav/files/${getCurrentUser().uid}/${
						attachment.extendedData.path
					}`
				);
		},
		formattedFileSize() {
			return (filesize) => formatFileSize(filesize);
		},
		isAuthor() {
			return (
				OC.getCurrentUser().uid === this.currentAnnouncement.author_id
			);
		},
		titleIfTruncated() {
			return (title) => (this.titleIsTruncated ? title : null);
		},
	},

	watch: {
		newSubject() {
			this.$nextTick(() => {
				if (this.$refs.title) {
					this.titleIsTruncated =
						this.$refs.title.scrollWidth >
						this.$refs.title.clientWidth;
				} else if (this.$refs.landingPageTitle) {
					this.titleIsTruncated =
						this.$refs.landingPageTitle.scrollWidth >
						this.$refs.landingPageTitle.clientWidth;
				}
			});
		},
		async "currentAnnouncement.id"() {
			this.initTitleEntry();
			this.newMessage = this.currentAnnouncement.message;
			this.editor.setContent(this.newMessage);
			this.setTextView();
			await this.commentsView.update(this.currentAnnouncement.id);
			await this.fetchAttachments(this.currentAnnouncement.id);
		},
		async isTextEdit() {
			// this.editor.setReadOnly(!this.isTextEdit);
			await this.setupEditor(!this.isTextEdit);
			if (!this.isTextEdit) {
				try {
					const plainMessage = await remark()
						.use(strip, {
							keep: ["blockquote", "link", "listItem"],
						})
						.process(this.newMessage);
					const response = await updateAnnouncement(
						this.currentAnnouncement.id,
						this.newSubject,
						this.newMessage,
						plainMessage.value
					);
					this.$store.dispatch(
						"updateAnnouncement",
						response.data.ocs.data
					);
				} catch (e) {
					console.error(e);
					showError(
						t(
							"announcementcenter",
							"An error occurred while updating the announcement"
						)
					);
				}
			}
			// this.editor.focus();
		},
	},

	async mounted() {
		// document.title = this.documentTitle;
		this.initTitleEntry();
		this.newMessage = this.currentAnnouncement.message;
		if (!this.commentsView) {
			// Create a new comments view when there is none
			this.commentsView = new OCA.Comments.View("announcement");
		}
		await this.commentsView.update(this.currentAnnouncement.id);
		this.commentsView.$mount(this.$refs.commentsView);

		await this.setupEditor();
		this.fetchAttachments(this.currentAnnouncement.id);
	},
	beforeDestroy() {
		unsubscribe("clickAnnouncement");
	},
	methods: {
		...mapMutations(["setTextEdit", "setTextView"]),
		...mapActions(["fetchAttachments"]),
		showViewer(attachment) {
			if (
				attachment.extendedData.fileid &&
				window.OCA.Viewer.availableHandlers
					.map((handler) => handler.mimes)
					.flat()
					.includes(attachment.extendedData.mimetype)
			) {
				window.OCA.Viewer.open({ path: attachment.extendedData.path });
				return;
			}

			if (attachment.extendedData.fileid) {
				window.location = generateUrl(
					"/f/" + attachment.extendedData.fileid
				);
				return;
			}

			window.location = generateUrl(
				`/apps/announcementcenter/announcements/${attachment.announcementId}/attachment/${attachment.id}`
			);
		},

		toggleCollapse() {
			this.fileCollapseShow = !this.fileCollapseShow;
		},
		showAttachmentModal() {
			this.modalShow = true;
		},

		addAttachment(attachment) {
			const asImage =
				(attachment.type === "file" &&
					attachment.extendedData.hasPreview) ||
				attachment.extendedData.mimetype.includes("image");
			if (this.editor) {
				this.editor.insertAtCursor(
					asImage
						? `<a href="${this.attachmentPreview(
								attachment
						  )}"><img src="${this.attachmentPreview(
								attachment
						  )}" alt="${attachment.data}" /></a>`
						: `<a href="${this.attachmentPreview(attachment)}">${
								attachment.data
						  }</a>`
				);
				return;
			}
			this.modalShow = false;
		},
		async setupEditor(readOnly = true) {
			this?.editor?.destroy();
			this.editor = await window.OCA.Text.createEditor({
				el: this.$refs.editor,
				content: this.newMessage,
				readOnly: readOnly,
				onUpdate: ({ markdown }) => {
					this.newMessage = markdown;
				},
				onFileInsert: () => {
					this.showAttachmentModal();
				},
			});
		},
		initTitleEntry() {
			this.newSubject = this.currentAnnouncement.subject;
		},
	},
};
</script>

<style lang="scss" scoped>
.modal__content {
	width: 25vw;
	min-width: 250px;
	min-height: 120px;
	margin: 20px;
	padding-bottom: 20px;
	display: flex;
	flex-direction: column;

	&:deep(.attachment-list) {
		flex-shrink: 1;
	}
}
#titleform {
	form {
		flex: auto;
	}
}

#titleform input[type="text"]:disabled {
	color: var(--color-text-maxcontrast);
}
#titleform input[type="text"] {
	font-size: 30px;
	border: none;
	color: var(--color-main-text);
	width: 100%;
	height: 43px;
	opacity: 0.8;
	text-overflow: unset;

	&.mobile {
		font-size: 30px;
		// Less padding to save some extra space
		padding: 0;
		padding-right: 4px;
	}
}
.rotate90 {
	rotate: 90deg;
}
</style>

<style lang="scss">
@import "../css/editor";

@media print {
	/* Don't print emoticon button (if page doesn't have an emoji set) */
	.edit-button,
	.action-item,
	.emoji-picker-emoticon {
		display: none !important;
	}
}
.announcement_title:disabled {
	color: var(--color-primary) !important;
}
.attachment-list {
	&.selector {
		padding: 10px;
		position: absolute;
		width: 30%;
		max-width: 500px;
		min-width: 200px;
		max-height: 50%;
		top: 50%;
		left: 50%;
		transform: translate(-50%, -50%);
		background-color: #eee;
		z-index: 2;
		border-radius: 3px;
		box-shadow: 0 0 3px darkgray;
		overflow: scroll;
	}
	h3.attachment-selector {
		margin: 0 0 10px;
		padding: 0;
		.icon-close {
			display: inline-block;
			float: right;
		}
	}

	li.attachment {
		display: flex;
		padding: 3px;
		min-height: 44px;

		&.deleted {
			opacity: 0.5;
		}

		.fileicon {
			display: inline-block;
			min-width: 32px;
			width: 32px;
			height: 32px;
			background-size: contain;
		}

		.attachment--info,
		.filesize,
		.filedate {
			font-size: 90%;
			color: var(--color-text-maxcontrast);
		}
		.app-popover-menu-utils {
			position: relative;
			right: -10px;
			button {
				height: 32px;
				width: 42px;
			}
		}
		button.icon-history {
			width: 44px;
		}
		progress {
			margin-top: 3px;
		}
	}
}
</style>
