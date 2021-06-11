const path = require('path')
const webpackConfig = require('@nextcloud/webpack-vue-config')

webpackConfig.entry.dashboard = path.join(__dirname, 'src', 'dashboard')

module.exports = webpackConfig
