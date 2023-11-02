<template>
	<div>
		<div
			v-show="showRichText"
			id="text-container"
			:key="'text-' + currentAnnouncement.id"
			:aria-label="t('collectives', 'Page content')">
			<!-- <RichText
				:key="`reader-${currentAnnouncement.id}`"
				:current-page="currentAnnouncement"
				:page-content="currentAnnouncement.message" /> -->
		</div>
		<div
			v-if="message"
			class="announcement__message"
			@click="onClickFoldedMessage">
			<div v-if="textAppAvailable">
				<div ref="editor" />
			</div>
			<template v-else>
				<NcRichText
					:text="message"
					:arguments="{}"
					:autolink="true"
					:use-markdown="true" />
			</template>
		</div>
		<!-- <Editor
			v-if="currentCollectiveCanEdit"
			v-show="showEditor"
			:key="`editor-${currentAnnouncement.id}`"
			ref="editor"
			@ready="readyEditor" /> -->
	</div>
</template>

<script>
import { subscribe, unsubscribe } from "@nextcloud/event-bus";
import Editor from "./Editor.vue";
import RichText from "./RichText.vue";
// import WidgetHeading from './LandingPageWidgets/WidgetHeading.vue'
import { mapActions, mapGetters, mapMutations } from "vuex";
// import {
// 	GET_VERSIONS,
// 	TOUCH_PAGE,
// } from '../../store/actions.js'
// import pageContentMixin from '../../mixins/pageContentMixin.js'

export default {
	name: "TextEditor",

	components: {
		Editor,
		RichText,
		// WidgetHeading,
	},

	// mixins: [
	// 	pageContentMixin,
	// ],

	data() {
		return {
			pageContent: "",
			previousSaveTimestamp: null,
			readMode: true,
			scrollTop: 0,
			textEditWatcher: null,
			modalShow: false,
		};
	},

	computed: {
		...mapGetters(["currentAnnouncement", "isTextEdit"]),
		showRichText() {
			return this.readOnly;
		},

		showEditor() {
			return !this.readOnly;
		},

		waitForEditor() {
			return this.readMode && this.isTextEdit;
		},

		readOnly() {
			return (
				!this.currentCollectiveCanEdit ||
				this.readMode | !this.isTextEdit
			);
		},
	},

	watch: {
	
	},

	beforeMount() {
		// Change back to default view mode
		this.setTextView();
	},

	mounted() {
		this.initEditMode();
		this.getPageContent();
		console.log(this.currentAnnouncement);
		this.textEditWatcher = this.$watch("isTextEdit", (val) => {
			if (val === true) {
				this.startEdit();
			} else {
				this.stopEdit();
			}
		});
		subscribe("collectives:attachment:restore", this.addImage);
	},

	beforeDestroy() {
		unsubscribe("collectives:attachment:restore", this.addImage);
		this.textEditWatcher();
	},

	methods: {
		...mapMutations(["setTextEdit", "setTextView"]),

		// ...mapActions({
		// 	dispatchTouchPage: TOUCH_PAGE,
		// 	dispatchGetVersions: GET_VERSIONS,
		// }),

		// this is a method so it does not get cached
		wrapper() {
			return this.$refs.editor?.$children[0].$children[0];
		},

		// this is a method so it does not get cached
		syncService() {
			// `$syncService` in Nexcloud 24+, `syncService` beforehands
			return this.wrapper()?.$syncService ?? this.wrapper()?.syncService;
		},

		// this is a method so it does not get cached
		doc() {
			return this.wrapper()?.$data.document;
		},

		focusEditor() {
			if (this.wrapper()?.$editor?.commands.autofocus) {
				this.wrapper().$editor.commands.autofocus();
			} else {
				this.wrapper()?.$editor?.commands.focus?.();
			}
		},

		addImage(name) {
			// inspired by the fixedEncodeURIComponent function suggested in
			// https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/encodeURIComponent
			const src =
				".attachments." + this.currentAnnouncement.id + "/" + name;
			// simply get rid of brackets to make sure link text is valid
			// as it does not need to be unique and matching the real file name
			const alt = name.replaceAll(/[[\]]/g, "");

			this.wrapper()?.$editor?.commands.setImage({ src, alt });
		},

		initEditMode() {
			this.setTextEdit();
		},

		startEdit() {
			this.scrollTop = document.getElementById("text")?.scrollTop || 0;
			if (this.doc()) {
				this.previousSaveTimestamp = this.doc().lastSavedVersionTime;
			}
			this.$nextTick(() => {
				document.getElementById("editor")?.scrollTo(0, this.scrollTop);
			});
		},

		stopEdit() {
			this.scrollTop = document.getElementById("editor")?.scrollTop || 0;

			const pageContent = this.syncService()._getContent() || "";
			const changed = this.pageContent !== pageContent;

			// switch back to edit if there's no content
			if (!pageContent.trim()) {
				this.setTextEdit();
				this.$nextTick(() => {
					this.focusEditor();
				});
				return;
			}

			if (changed) {
				this.dispatchTouchPage();
				if (!this.isPublic && this.hasVersionsLoaded) {
					this.dispatchGetVersions(this.currentAnnouncement.id);
				}

				// Save pending changes in editor
				// TODO: detect missing connection and display warning
				this.syncService().save();

				this.pageContent = pageContent;
			}

			this.$nextTick(() => {
				document.getElementById("text")?.scrollTo(0, this.scrollTop);
			});
		},
	},
};
</script>

<style lang="scss" scoped>
.text-container-heading {
	padding-left: 14px;
}

#text-container {
	display: block;
	width: 100%;
	max-width: 100%;
	left: 0;
	margin: 0 auto;
	background-color: var(--color-main-background);
}

:deep([data-text-el="editor-container"]) {
	/* Remove scrolling mechanism from editor-container, required for menubar stickyness */
	overflow: visible;

	div.editor {
		/* Adjust to page titlebar height */
		div.text-menubar {
			margin: auto;
			top: 59px;
		}
	}
}
</style>
