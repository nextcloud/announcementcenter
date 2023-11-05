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
					:placeholder="t('collectives', 'Title')"
					:disabled="!isTextEdit"
					type="text" />
			</form>

			<div class="flex">
				<!-- Edit button if editable -->
				<EditButton v-if="isAuthor" :mobile="isMobile" />

				<!-- Actions menu -->
				<!-- <PageActionMenu
					:show-files-link="!isPublic"
					:page-id="currentPage.id"
					:parent-id="currentPage.parentId"
					:timestamp="currentPage.timestamp"
					:last-user-id="currentPage.lastUserId"
					:last-user-display-name="currentPage.lastUserDisplayName"
					:is-landing-page="isLandingPage"
					:is-template="isTemplatePage" /> -->

				<!-- Sidebar toggle -->
				<!-- <NcActions v-if="!showing('sidebar') && !isMobile">
					<NcActionButton
						icon="icon-menu-sidebar"
						:aria-label="t('collectives', 'Open page sidebar')"
						aria-controls="app-sidebar-vue"
						:close-after-click="true"
						@click="toggle('sidebar')" />
				</NcActions> -->
			</div>
		</h1>
		<div
			:title="t('announcementcenter', 'receivers')"
			class="px-3 my-1 flex items-center">
			<NcUserBubble
				v-if="group.name !== 'everyone'"
				v-for="group in currentAnnouncement.groups"
				avatar-image="icon-group"
				:margin="4"
				:display-name="group.name"></NcUserBubble>
			<NcUserBubble
				v-else
				avatar-image="icon-group"
				:margin="4"
				:display-name="
					t('announcementcenter', 'everyone')
				"></NcUserBubble>
		</div>
		<div
			class="rounded mb-2"
			style="
				height: 60%;
				border: 1px solid var(--color-background-darker);
			">
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
		<div
			class="rounded p-2"
			style="
				height: 30%;
				border: 1px solid var(--color-background-darker);
			">
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
import strip from "strip-markdown";
import { emit, subscribe, unsubscribe } from "@nextcloud/event-bus";
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
	},

	mixins: [isMobile],

	data() {
		return {
			newSubject: "",
			titleIsTruncated: false,
			editor: null,
			textAppAvailable: !!window.OCA?.Text?.createEditor,
			newMessage: "",
			commentsView: null,
			modalShow: false,
		};
	},

	computed: {
		...mapGetters(["currentAnnouncement", "isTextEdit"]),
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
	},
	beforeDestroy() {
		unsubscribe("clickAnnouncement");
	},
	methods: {
		...mapMutations(["setTextEdit", "setTextView"]),
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
</style>
