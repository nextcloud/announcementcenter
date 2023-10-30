<template>
	<span
		:aria-hidden="!title"
		:aria-label="title"
		class="material-design-icon"
		role="img"
		v-bind="$attrs"
		@click="$emit('click', $event)">
		<svg
			xmlns="http://www.w3.org/2000/svg"
			:fill="fillColor"
			:height="size"
			:width="size"
			viewBox="0 0 24 24">
			<title>plus</title>
			<path d="M19,13H13V19H11V13H5V11H11V5H13V11H19V13Z" />
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
