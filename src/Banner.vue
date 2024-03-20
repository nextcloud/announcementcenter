<template>
    <div class="announcement__banner" v-if="isVisible">
        <li v-for="(announcement, index) in announcements">
            <div class="announcement__banner__container">
                <div class="announcement__banner__container__text">
                    <MessageAlert :size="20"/>
                    <p class="announcement__banner__subject">{{ announcement.subject }}:</p>
                    <p class="announcement__banner__message">{{ announcement.message }}</p>
                </div>
                <NcButton
                    class="announcement__banner__close"
                    aria-label="close banner"
                    type="warning"
                    @click="announcementRead(index)">
                    <Close :size="20" />
                </NcButton>
            </div>
        </li>
    </div>
</template>

<script>
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import Close from 'vue-material-design-icons/Close'
import MessageAlert from 'vue-material-design-icons/MessageAlert'
import { showError } from '@nextcloud/dialogs'
import {
	getBanners,
	setBannerRead,
} from './services/announcementsService.js'

export default {
	name: 'Banner',

	components: {
        NcButton,
        Close,
        MessageAlert,
	},

	data() {
		return {
            announcements: [],
            isVisible: false,
        }
	},

	async mounted() {
        let success = await this.loadBanners()
		this.isVisible = success
	},

	methods: {
        async loadBanners() {
            this.announcements = []
            try {
                const promise = getBanners();
                promise.then((response) => {
                    if (response.status === 200) {
                        this.announcements = response.data.ocs.data
                    }
                });
                await promise;
            } catch (e) {
			    console.error(e)
			    showError(t('announcementcenter', 'An error occurred while trying to receive banners'))
                this.announcements = []
                return false
		    }
            return true
        },

        async announcementRead(index) {
            if (index >= 0 && index < this.announcements.length) {
                const ann = this.announcements[index]
                console.debug(ann)
                try {
                    await setBannerRead(ann.id)
                    this.announcements.splice(index, 1)
                } catch (e) {
                    console.error(e)
			        showError(t('announcementcenter', 'An error occurred while trying to mark a banner as read'))
                }
            }
        },
    },
}
</script>

<style lang="scss" scoped>
.announcement {
    &__banner {
        position: fixed;
        display: flex;
        justify-content: start;
        /*The navbar has a z-index of 1999 */
        z-index: 2000; 
        width: 100%;
        flex-direction: column;

        &__subject,
        &__message {
            color: black;
            overflow-wrap: break-word;
            text-align: center;
            display: flex;
            justify-content: center;
            flex-direction: column;
        }

        &__subject {
            font-weight: bold;
            padding-right: 12px;
        }

        &__container {
            display: flex;
            justify-content: space-between;
            border-radius: var(--border-radius-large);
            background-color: color-mix(in srgb, var(--color-warning) 50%, white);
            border: 3px solid var(--color-warning);
            height: var(--header-height);
            padding-left: 6px;

            &__text {
                display: flex;
                justify-content: start;
                flex-direction: row;
            }

            .message-alert-icon {
                /* 20px + 2*12px = 44px icon span */
                padding: 0 12px 0 12px;
                color: var(--color-warning);
            }
        }

        &__close {
            padding: 0 !important;
            /* 44 is the default button height*/
            margin-top: calc((var(--header-height) - 44px) / 2);
            margin-bottom: calc((var(--header-height) - 44px) / 2);
        }
    }

    &__banner li {
        width: 100%;
    }

}
</style>