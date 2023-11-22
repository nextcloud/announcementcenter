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
	<div class="announcement__form">
		<input
			v-model="subject"
			class="announcement__form__subject"
			type="text"
			name="subject"
			minlength="1"
			maxlength="512"
			:placeholder="
				t('announcementcenter', 'New announcement subject')
			" />
		<NcMultiselect
			class="announcement__form__user"
			v-model="assignedUsers"
			:multiple="true"
			:options="formatedSharees"
			:user-select="true"
			:auto-limit="false"
			:placeholder="
				t('announcementcenter', 'Assign announcement to groups…')
			"
			label="displayName"
			track-by="multiselectKey"
			@search-change="asyncFind">
			<template #tag="scope">
				<div class="avatarlist--inline">
					<NcAvatar
						:user="scope.option.user"
						:display-name="scope.option.displayname"
						:size="30"
						:is-no-user="scope.option.isNoUser"
						:disable-menu="true" />
				</div>
			</template>
		</NcMultiselect>
		<div>
			<div v-if="textAppAvailable" class="announcement__form__text">
				<div ref="editor" />
			</div>
			<template v-else>
				<textarea
					v-model="message"
					class="announcement__form__message"
					name="message"
					rows="4"
					:placeholder="
						t(
							'announcementcenter',
							'Write announcement text, Markdown can be used …'
						)
					" />
			</template>
			<div class="announcement__form__buttons">
				<NcButton
					type="primary"
					:disabled="!subject"
					@click="onAnnounce">
					{{ t("announcementcenter", "Announce") }}
				</NcButton>
				<NcActions>
					<NcActionCheckbox
						value="1"
						:checked.sync="createActivities">
						{{ t("announcementcenter", "Create activities") }}
					</NcActionCheckbox>
					<NcActionCheckbox
						value="1"
						:checked.sync="createNotifications">
						{{ t("announcementcenter", "Create notifications") }}
					</NcActionCheckbox>
					<NcActionCheckbox value="1" :checked.sync="sendEmails">
						{{ t("announcementcenter", "Send emails") }}
					</NcActionCheckbox>
					<NcActionCheckbox value="1" :checked.sync="allowComments">
						{{ t("announcementcenter", "Allow comments") }}
					</NcActionCheckbox>
				</NcActions>
			</div>
		</div>
		<NcModal
			v-if="modalShow"
			:title="t('announcementcenter', 'Choose attachment')"
			@close="modalShow = false">
			<div class="modal__content p-2">
				<div
					class="text-xl font-bold flex justify-center items-center p-1">
					{{ t("announcementcenter", "Choose attachment") }}
				</div>
				<div class="button-group">
					<button class="icon-upload" @click="uploadNewFile()">
						{{ t("announcementcenter", "Upload new files") }}
					</button>
					<button class="icon-folder" @click="shareFromFiles()">
						{{ t("announcementcenter", "Share from Files") }}
					</button>
				</div>
				<input
					ref="filesAttachment"
					type="file"
					style="display: none"
					multiple
					@change="handleUploadFile" />
				<ul class="attachment-list">
					<li
						v-for="attachment in uploadQueue"
						:key="attachment.name"
						class="attachment">
						<a
							class="fileicon"
							:style="mimetypeForAttachment(attachment)" />
						<div class="details">
							<a>
								<div class="filename">
									<span class="basename">{{
										attachment.name
									}}</span>
								</div>
								<progress
									:value="attachment.progress"
									max="100" />
							</a>
						</div>
					</li>
					<li
						v-for="attachment in newUploadAttachments"
						:key="attachment.fileId"
						class="attachment"
						:class="{
							'attachment--deleted': attachment.deletedAt > 0,
						}">
						<a
							class="fileicon"
							:href="internalLink(attachment)"
							:style="mimetypeForAttachment(attachment)"
							@click.prevent="showViewer(attachment)" />
						<div class="details">
							<a
								:href="internalLink(attachment)"
								@click.prevent="showViewer(attachment)">
								<div class="filename">
									<span class="basename">{{
										attachment.data
									}}</span>
								</div>
								<div v-if="attachment.deletedAt === 0">
									<span class="filesize">{{
										formattedFileSize(
											attachment.extendedData.filesize
										)
									}}</span>
									<span class="filedate">{{
										relativeDate(
											attachment.createdAt * 1000
										)
									}}</span>
									<span class="filedate">{{
										attachment.extendedData
											.attachmentCreator.displayName
									}}</span>
								</div>
								<div v-else>
									<span class="attachment--info">{{
										t("announcementcenter", "Pending share")
									}}</span>
								</div>
							</a>
						</div>
						<NcActions>
							<NcActionButton
								icon="icon-confirm"
								@click="addAttachment(attachment)">
								{{
									t(
										"announcementcenter",
										"Add this attachment"
									)
								}}
							</NcActionButton>
						</NcActions>
						<NcActions :force-menu="true">
							<NcActionLink
								v-if="attachment.extendedData.fileid"
								icon="icon-folder"
								:href="internalLink(attachment)">
								{{ t("announcementcenter", "Show in Files") }}
							</NcActionLink>
							<NcActionLink
								v-if="attachment.extendedData.fileid"
								icon="icon-download"
								:href="downloadLink(attachment)"
								download>
								{{ t("announcementcenter", "Download") }}
							</NcActionLink>
							<NcActionButton
								v-if="attachment.extendedData.fileid"
								icon="icon-delete"
								@click="unshareAttachment(attachment)">
								{{
									t("announcementcenter", "Remove attachment")
								}}
							</NcActionButton>

							<NcActionButton
								v-if="
									!attachment.extendedData.fileid &&
									attachment.deletedAt === 0
								"
								icon="icon-delete"
								@click="$emit('delete-attachment', attachment)">
								{{
									t("announcementcenter", "Delete Attachment")
								}}
							</NcActionButton>
							<NcActionButton
								v-else-if="!attachment.extendedData.fileid"
								icon="icon-history"
								@click="
									$emit('restore-attachment', attachment)
								">
								{{
									t(
										"announcementcenter",
										"Restore Attachment"
									)
								}}
							</NcActionButton>
						</NcActions>
					</li>
				</ul>
			</div>
		</NcModal>
	</div>
</template>

<script>
import {
	NcAvatar,
	NcButton,
	NcMultiselect,
	NcActions,
	NcActionCheckbox,
	NcActionInput,
	NcModal,
	NcActionButton,
} from "@nextcloud/vue";
import { loadState } from "@nextcloud/initial-state";
import {
	postAnnouncement,
	searchGroups,
} from "../services/announcementsService.js";
import { showError } from "@nextcloud/dialogs";
import { remark } from "remark";
import strip from "strip-markdown";
import { emit, subscribe, unsubscribe } from "@nextcloud/event-bus";
import debounce from "lodash/debounce.js";
import { mapGetters, mapState } from "vuex";
import { getFilePickerBuilder } from "@nextcloud/dialogs";
import relativeDate from "../mixins/relativeDate.js";
import attachmentUpload from "../mixins/attachmentUpload.js";
import {
	generateUrl,
	generateOcsUrl,
	generateRemoteUrl,
} from "@nextcloud/router";
import { formatFileSize } from "@nextcloud/files";
import { getCurrentUser } from "@nextcloud/auth";
import { AttachmentApi } from "./../services/AttachmentApi.js";
const maxUploadSizeState = loadState("announcementcenter", "maxUploadSize");
const apiClient = new AttachmentApi();
const picker = getFilePickerBuilder(t("announcementcenter", "File to share"))
	.setMultiSelect(false)
	.setModal(true)
	.setType(1)
	.allowDirectories()
	.build();

export default {
	name: "NewForm",
	components: {
		NcActions,
		NcActionCheckbox,
		NcActionInput,
		NcButton,
		NcMultiselect,
		NcAvatar,
		NcModal,
		NcActionButton,
	},
	mixins: [relativeDate, attachmentUpload],
	data() {
		return {
			subject: "",
			message: "",
			modalShow: false,
			createActivities: loadState(
				"announcementcenter",
				"createActivities"
			),
			createNotifications: loadState(
				"announcementcenter",
				"createNotifications"
			),
			sendEmails: loadState("announcementcenter", "sendEmails"),
			allowComments: loadState("announcementcenter", "allowComments"),
			groups: [],
			groupOptions: [],
			assignedUsers: [],
			attachments: [],
			editor: null,
			textAppAvailable: !!window.OCA?.Text?.createEditor,
			announcementId: "",
		};
	},

	async mounted() {
		this.setupEditor();
		this.searchGroups("");
		await this.$store.dispatch("loadSharees", "");
	},
	computed: {
		...mapState(["sharees"]),
		formatedSharees() {
			return this.sharees
				.map((item) => {
					const sharee = {
						user: item.value.shareWith,
						displayName: item.label,
						icon: "icon-user",
						multiselectKey: item.value.shareType + ":" + item.label,
					};
					if (item.value.shareType === 1) {
						sharee.icon = "icon-group";
						sharee.isNoUser = true;
					}
					if (item.value.shareType === 7) {
						sharee.icon = "icon-circles";
						sharee.isNoUser = true;
					}

					sharee.value = item.value;
					return sharee;
				})
				.slice(0, 10);
		},
		mimetypeForAttachment() {
			return (attachment) => {
				if (!attachment) {
					return {};
				}
				const url = attachment.extendedData.hasPreview
					? this.attachmentPreview64(attachment)
					: OC.MimeType.getIconUrl(attachment.extendedData.mimetype);
				const styles = {
					"background-image": `url("${url}")`,
				};
				return styles;
			};
		},
		attachmentPreview64() {
			return (attachment) =>
				attachment.extendedData.fileid
					? generateUrl(
							`/core/preview?fileId=${attachment.extendedData.fileid}&x=64&y=64`
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
	},
	methods: {
		addAttachment(attachment) {
			console.log(this.newUploadAttachments);
			console.log(attachment);
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
		uploadNewFile() {
			this.$refs.filesAttachment.click();
		},
		shareFromFiles() {
			picker.pick().then(async (path) => {
				console.log(`path ${path} selected for sharing`);
				if (!path.startsWith("/")) {
					throw new Error(t("files", "Invalid path selected"));
				}
				const res = await apiClient.makeAttachmentByPath(path);
				this.newUploadAttachments.push(res.ocs.data);
			});
		},
		async handleUploadFile(event) {
			const files = event.target.files ?? [];
			for (const file of files) {
				await this.onNewLocalAttachmentSelected(file);
			}
			event.target.value = "";
		},
		showAttachmentModal() {
			this.modalShow = true;
		},
		async asyncFind(query) {
			await this.debouncedFind(query);
		},
		debouncedFind: debounce(async function (query) {
			this.isSearching = true;
			await this.$store.dispatch("loadSharees", query);
			this.isSearching = false;
		}, 300),
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
		async setupEditor() {
			this?.editor?.destroy();
			this.editor = await window.OCA.Text.createEditor({
				el: this.$refs.editor,
				content: this.message,
				readOnly: false,
				onUpdate: ({ markdown }) => {
					this.message = markdown;
					// this.updateDescription();
				},
				onFileInsert: () => {
					this.showAttachmentModal();
				},
			});
		},
		resetForm() {
			this.subject = "";
			this.message = "";
			this.createActivities = loadState(
				"announcementcenter",
				"createActivities"
			);
			this.createNotifications = loadState(
				"announcementcenter",
				"createNotifications"
			);
			this.sendEmails = loadState("announcementcenter", "sendEmails");
			this.allowComments = loadState(
				"announcementcenter",
				"allowComments"
			);
			this.groups = [];
		},

		onSearchChanged: debounce(async function (search) {
			await this.searchGroups(search);
		}, 300),

		async searchGroups(search) {
			const response = await searchGroups(search);
			this.groupOptions = response.data.ocs.data;
		},

		async onAnnounce() {
			const groups = this.assignedUsers.map((item) => {
				return item.user;
			});

			// const groups = this.groups.map((group) => {
			// 	return group.id;
			// });

			const plainMessage = await remark()
				.use(strip, {
					keep: ["blockquote", "link", "listItem"],
				})
				.process(this.message);

			try {
				const response = await postAnnouncement(
					this.subject,
					this.message,
					plainMessage.value,
					groups,
					this.createActivities,
					this.createNotifications,
					this.sendEmails,
					this.allowComments
				);
				let annoucement = response.data.ocs.data;
				console.log(annoucement);
				this.$store.dispatch("addAnnouncement", annoucement);
				this.announcementId = annoucement.id;

				this.newUploadAttachments.forEach(async (attachment) => {
					console.log(attachment);
					await this.onShareAttachmentSelected(
						"share_file",
						attachment.extendedData.path
					);
				});
				emit("closeNewAnnouncement");
				this.resetForm();
			} catch (e) {
				console.error(e);
				showError(
					t(
						"announcementcenter",
						"An error occurred while posting the announcement"
					)
				);
			}
		},
	},
};
</script>

<style lang="scss" scoped>
.button-group {
	display: flex;

	.icon-upload,
	.icon-folder {
		padding-left: 44px;
		background-position: 16px center;
		flex-grow: 1;
		height: 44px;
		margin-bottom: 12px;
		text-align: left;
	}
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
		.details {
			flex-grow: 1;
			flex-shrink: 1;
			min-width: 0;
			flex-basis: 50%;
			line-height: 110%;
			padding: 2px;
		}
		.filename {
			width: 70%;
			display: flex;
			.basename {
				white-space: nowrap;
				overflow: hidden;
				text-overflow: ellipsis;
				padding-bottom: 2px;
			}
			.extension {
				opacity: 0.7;
			}
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
.avatarlist--inline {
	display: flex;
	align-items: center;
	margin-right: 3px;
	.avatarLabel {
		padding: 0;
	}
}
.announcement__form {
	max-width: 800px;
	padding: 0px 20px;
	margin: 70px auto 35px;
	font-size: 15px;
	&__user {
		width: 100%;
	}
	&__text:deep(.text-menubar) {
		top: -10px !important;
		z-index: 1;
	}
	&__subject {
		width: 100%;
		font-size: 20px;
		font-weight: bold;
	}

	&__message {
		width: 100%;
		font-size: 15px;
	}

	&__buttons {
		display: flex;
		justify-content: right;

		:deep(.button-vue) {
			margin-right: 10px;
		}
	}
}
</style>
