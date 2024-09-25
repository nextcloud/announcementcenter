import Vue from 'vue'
import { generateFilePath } from '@nextcloud/router'
import { getRequestToken } from '@nextcloud/auth'
import { translate, translatePlural } from '@nextcloud/l10n'
import Vuex from 'vuex'
import Banner from './Banner.vue'

// Styles
import '@nextcloud/dialogs/style.css'

// eslint-disable-next-line
__webpack_nonce__ = btoa(getRequestToken())

// eslint-disable-next-line
__webpack_public_path__ = generateFilePath('announcementcenter', '', 'js/')

Vue.use(Vuex)

Vue.mixin({
	methods: {
		t: translate,
		n: translatePlural,
	},
})

/**
 * Injects a banner div into the document body in order to attach a Vue element to it
 */
function injectBanner() {
	if (document.body) {
		// Create the banner element
		const bannerDiv = document.createElement('div')
		bannerDiv.id = 'announcement__banner'

		// Append the banner to the body of the document
		document.body.appendChild(bannerDiv)
		return true
	}
	return false
}

let banner = null

// attach Vue Banner
if (injectBanner()) {
	banner = new Vue({
		el: '#announcement__banner',
		render: h => h(Banner),
	})
}

export default banner
