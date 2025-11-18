/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import Vue from 'vue'
import { generateFilePath } from '@nextcloud/router'
import { getRequestToken } from '@nextcloud/auth'
import store from './store/index.js'
import AdminSettings from './AdminSettings.vue'
import Vuex from 'vuex'

// Styles
import '@nextcloud/dialogs/style.css'

// eslint-disable-next-line
__webpack_nonce__ = btoa(getRequestToken())

// eslint-disable-next-line
__webpack_public_path__ = generateFilePath('announcementcenter', '', 'js/')

Vue.use(Vuex)

export default new Vue({
	el: '#announcementcenter',
	store,
	render: h => h(AdminSettings),
})
