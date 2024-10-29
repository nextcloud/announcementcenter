/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import Vue from 'vue'
import Vuex, { Store } from 'vuex'
import announcementsStore from './announcementsStore.js'

Vue.use(Vuex)

const mutations = {}

export default new Store({
	modules: {
		announcementsStore,
	},

	mutations,

	strict: process.env.NODE_ENV !== 'production',
})
