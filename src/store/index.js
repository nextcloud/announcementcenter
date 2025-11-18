/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createStore } from 'vuex'
import announcementsStore from './announcementsStore.js'

const mutations = {}

export default createStore({
	modules: {
		announcementsStore,
	},

	mutations,

	strict: process.env.NODE_ENV !== 'production',
})
