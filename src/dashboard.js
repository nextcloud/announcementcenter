/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { getRequestToken } from '@nextcloud/auth'
import { generateFilePath } from '@nextcloud/router'
import { createApp } from 'vue'
import Dashboard from './views/Dashboard.vue'

__webpack_nonce__ = btoa(getRequestToken())

// eslint-disable-next-line
__webpack_public_path__ = generateFilePath('announcementcenter', '', 'js/')

document.addEventListener('DOMContentLoaded', function() {
	OCA.Dashboard.register('announcementcenter', (el) => {
		createApp(Dashboard).mount(el)
	})
})
