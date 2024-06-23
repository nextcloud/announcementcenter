module.exports = {
	extends: ['@nextcloud', 'prettier'],
	overrides: [
		{
			files: ['**/*.vue'],
			rules: {
				'vue/first-attribute-linebreak': 'off',
			},
		},
	],
	globals: {
		'$': true
	}
}
