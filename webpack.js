const path = require('path')
const { merge } = require('webpack-merge')
const webpackConfig = require('@nextcloud/webpack-vue-config')

webpackConfig.entry = {}
const config = {
	entry: {
		dashboard: path.join(__dirname, 'src', 'dashboard'),
	},
}

module.exports = merge(config, webpackConfig)
