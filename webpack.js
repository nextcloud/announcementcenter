const path = require("path");
const webpackConfig = require("@nextcloud/webpack-vue-config");
const WindiCSSWebpackPlugin = require("windicss-webpack-plugin");
webpackConfig.entry.admin = path.join(__dirname, "src", "admin");
webpackConfig.entry.dashboard = path.join(__dirname, "src", "dashboard");
webpackConfig.plugins.push(new WindiCSSWebpackPlugin());

module.exports = webpackConfig;
