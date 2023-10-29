<template>
	<div class="p-8">
		<h1 id="titleform" class="flex sticky top-0 items-center">
			<!-- Page title -->
			<form @submit.prevent="focusEditor()">
				<input
					ref="title"
					v-model="newSubject"
					v-tooltip="titleIfTruncated(newSubject)"
					class="overflow-hidden overflow-ellipsis"
					:class="{ mobile: isMobile }"
					:placeholder="t('collectives', 'Title')"
          :disabled="!isTextEdit"
					type="text" />
			</form>

			<div class="flex">
				<!-- Edit button if editable -->
				<EditButton :mobile="isMobile" />

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
		<div class="px-3 flex items-center">
			可见人员：
			<NcAvatar
				v-if="group.name !== 'everyone'"
				v-for="group in currentAnnouncement.groups"
				:user="group.name"
				:display-name="group.name"
				:tooltip-message="group.name"
				:is-no-user="true"
				:size="36" />
			<template v-else>所有人</template>
		</div>
		<!-- <TextEditor
			:key="`text-editor-${currentAnnouncement.id}`"
			ref="texteditor" /> -->
		<div>
			<div v-if="textAppAvailable">
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
	</div>
</template>

<script>
import isMobile from "@nextcloud/vue/dist/Mixins/isMobile.js";
import { NcActions, NcActionButton, NcButton, NcAvatar } from "@nextcloud/vue";
import EditButton from "./Page/EditButton.vue";
// import PageActionMenu from "./Page/PageActionMenu.vue";
import TextEditor from "./Page/TextEditor.vue";
import { mapActions, mapGetters, mapMutations } from "vuex";
import { showError } from "@nextcloud/dialogs";
import {
  updateAnnouncement
} from "../services/announcementsService.js";
import {remark} from "remark";
import strip from "strip-markdown";
export default {
	name: "Page",

	components: {
		EditButton,
		NcActionButton,
		NcActions,
		NcButton,
		// PageActionMenu,
		TextEditor,
		NcAvatar,
	},

	mixins: [isMobile],

	data() {
		return {
			newSubject: "",
			titleIsTruncated: false,
			editor: null,
			textAppAvailable: !!window.OCA?.Text?.createEditor,
      newMessage:""
		};
	},

	computed: {
		...mapGetters(["currentAnnouncement", "isTextEdit"]),
		documentTitle() {
			// const { filePath, title } = this.currentPage;
			// const parts = [
			// 	this.currentCollective.name,
			// 	t("collectives", "Collectives"),
			// 	"Nextcloud",
			// ];
			// if (!this.isLandingPage) {
			// 	// Add parent page names in reverse order
			// 	filePath
			// 		.split("/")
			// 		.forEach((part) => part && parts.unshift(part));
			// 	if (!this.isIndexPage) {
			// 		parts.unshift(title);
			// 	}
			// }
			// return parts.join(" - ");
		},

		titleIfTruncated() {
			return (title) => (this.titleIsTruncated ? title : null);
		},
	},

	watch: {
		documentTitle() {
			// document.title = this.documentTitle;
		},

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
		"currentAnnouncement.id"() {
			this.initTitleEntry();
      this.newMessage=this.currentAnnouncement.message
			this.editor.setContent(this.newMessage);
		},
		async isTextEdit() {
			// this.editor.setReadOnly(!this.isTextEdit);
			await this.setupEditor(!this.isTextEdit);
      if(!this.isTextEdit)
      {
        try {
          const plainMessage = await remark()
              .use(strip, {
                keep: ["blockquote", "link", "listItem"],
              })
              .process(this.newMessage);
          const response=await updateAnnouncement(this.currentAnnouncement.id,this.newSubject,this.newMessage,plainMessage.value)
          this.$store.dispatch("updateAnnouncement", response.data.ocs.data);
        }
        catch (e) {
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
    this.newMessage=this.currentAnnouncement.message
		await this.setupEditor();
	},

	methods: {
		async setupEditor(readOnly = true) {
			this?.editor?.destroy();
			this.editor = await window.OCA.Text.createEditor({
				el: this.$refs.editor,
				content: this.newMessage,
				readOnly: readOnly,
				onUpdate:  ({ markdown }) => {
					this.newMessage = markdown;
				},
			});
		},
		initTitleEntry() {
			// if (this.loading("newPageTitle")) {
			// 	this.newSubject = "";
			// 	this.$nextTick(this.focusTitle);
			// 	this.done("newPageTitle");
			// 	return;
			// }
			this.newSubject = this.currentAnnouncement.subject;
		},

		focusTitle() {
			// this.$refs.title.focus();
		},

		focusEditor() {
			// this.$refs.texteditor.focusEditor();
		},
	},
};
</script>

<style lang="scss" scoped>
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
</style>
