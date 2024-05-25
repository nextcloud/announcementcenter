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
	<div v-show="loaded" class="editor__wrapper">
		<div id="richEditor" ref="editor" />
	</div>
</template>

<script>
export default {
	name: 'Description',
	props: {
		value: {
			type: String,
			required: true,
		},
	},

	data() {
		return {
			message: '',
			editor: null,
			loaded: false,
		}
	},

	watch: {
		value(newValue) {
			console.debug('watch value', newValue, this.message)
			if (newValue.trim() !== this.message.trim()) {
				console.debug('change editor', this.editor, newValue)
				this.message = newValue
				if (newValue === '') {
					// No idea why this is necessary, but that's what Health does
					// https://github.com/nextcloud/health/blob/432a8ea96f70c82c27a1e15f4340aa54a1d3a403/src/shared/TextEditor.vue#L78-L81
					this.setupEditor()
				} else {
					this.editor?.setContent(newValue)
				}
			}
		},
	},

	mounted() {
		this.setupEditor()
	},
	beforeDestroy() {
		this.destroyEditor()
	},

	methods: {
		async setupEditor() {
			this.destroyEditor()
			this.editor = await window.OCA.Text.createEditor({
				el: this.$refs.editor,
				fileId: undefined,
				useSession: false,
				content: '',
				readOnly: false,
				onLoaded: () => {
					this.loaded = true
				},
				onUpdate: ({ markdown }) => {
					if (markdown !== this.message) {
						this.updateMessage(markdown)
					}
				},
			})
		},
		destroyEditor() {
			this?.editor?.destroy()
		},
		updateMessage(markdown) {
			this.message = markdown
			this.$emit('input', markdown)
		},
	},
}
</script>

<style lang="scss" scoped>
.editor__wrapper {
	border: 2px solid var(--color-border-maxcontrast);
	border-radius: var(--border-radius-large);
	padding: 0.25rem;
	margin: 0.5rem 0;

	&:hover, &:focus-within {
		border-color: 2px solid var(--color-main-text);
	}
}

#richEditor::v-deep button.entry-action__image-upload {
	display: none;
}
</style>
