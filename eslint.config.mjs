/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { defineConfig } from 'eslint/config'
import { recommendedJavascript } from '@nextcloud/eslint-config'

export default defineConfig([
	...recommendedJavascript,
])
