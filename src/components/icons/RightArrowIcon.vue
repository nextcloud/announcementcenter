<template>
	<span
		:aria-hidden="!title"
		:aria-label="title"
		class="material-design-icon"
		role="img"
		v-bind="$attrs"
		@click="$emit('click', $event)">
		<svg
			:fill="fillColor"
			:height="size"
			:width="size"
			xmlns="http://www.w3.org/2000/svg"
			viewBox="0 0 24 24">
			<title>chevron-right</title>
			<path
				d="M8.59,16.58L13.17,12L8.59,7.41L10,6L16,12L10,18L8.59,16.58Z" />
		</svg>
	</span>
</template>

<script>
export default {
	name: "PlusIcon",
	props: {
		title: {
			type: String,
			default: "",
		},
		fillColor: {
			type: String,
			default: "currentColor",
		},
		size: {
			type: Number,
			default: 24,
		},
	},
};
</script>
