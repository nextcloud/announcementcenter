/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { getRequestToken } from '@nextcloud/auth'
import { generateFilePath } from '@nextcloud/router'
import { createApp } from 'vue'
import AdminSettings from './AdminSettings.vue'
import store from './store/index.js'

// Styles
import '@nextcloud/dialogs/style.css'

__webpack_nonce__ = btoa(getRequestToken())

// eslint-disable-next-line
__webpack_public_path__ = generateFilePath('announcementcenter', '', 'js/')

createApp(AdminSettings)
	.use(store)
	.mount('#announcementcenter')
