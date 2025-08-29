<!--
  - SPDX-FileCopyrightText: 2015-2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
# Changelog
All notable changes to this project will be documented in this file.

## 7.2.1 – 2025-08-29
### Fixed
- Don't ship vendor-bin directory
- Switch to outlined icons

## 7.2.0 – 2025-08-29
### Added
- Compatibility with Nextcloud 32

### Changed
- Updated translations
- Updated dependencies

## 7.1.4 – 2025-07-14
### Changed
- Updated translations
- Updated dependencies

### Fixed
- Fix missing notifications when announcing only for a group
  [#944](https://github.com/nextcloud/announcementcenter/pull/944)

## 7.1.3 – 2025-06-01
### Changed
- Updated translations
- Updated dependencies

### Fixed
- Fix missing group list during the time an announcement is scheduled
  [#929](https://github.com/nextcloud/announcementcenter/pull/929)
- Fix background job when publishing is triggered twice
  [#930](https://github.com/nextcloud/announcementcenter/pull/930)

## 7.1.2 – 2025-05-05
### Changed
- Updated translations
- Updated dependenciesAdd commentMore actions

### Fixed
- Fix relative time in the dashboard widget
  [#917](https://github.com/nextcloud/announcementcenter/pull/917)


## 7.1.1 – 2025-04-28
### Changed
- Updated translations
- Updated dependencies

### Fixed
- Fix scheduling date replaced by relative date is confusing
  [#912](https://github.com/nextcloud/announcementcenter/pull/912)
- Fix log spam from activity about wrong object-id type
  [#903](https://github.com/nextcloud/announcementcenter/pull/903)

## 7.1.0 – 2025-01-10
### Added
- Compatibility with Nextcloud 31

### Changed
- Updated translations
- Updated dependencies
- Removed Nextcloud 28 and Nextcloud 29

### Fixed
- Fix missing admin section headline
  [#873](https://github.com/nextcloud/announcementcenter/pull/873)

## 7.0.1 – 2024-10-02
### Added
- Add CLI command to remove notifications from an announcement

### Fixed
- Fix "Invalid date" when scheduling an announcement
- Move scheduling and deletion into … menu

## 7.0.0 – 2024-09-12
### Added
- Compatibility with Nextcloud 30
- Added CLI commands to list, announce and remove announcements
- Added option to schedule announcements
- Added option to automatically delete announcements

### Changed
- Updated dependencies
- Removed Nextcloud 26 and Nextcloud 27

## 6.8.1 – 2024-03-21
### Fixed
- Fix searching for groups in the compose form
  [#774](https://github.com/nextcloud/announcementcenter/pull/774)

## 6.8.0 – 2024-03-08
### Added
- Compatibility with Nextcloud 29
- Update translations
- Update dependencies

## 6.7.0 – 2023-11-09
### Added
- Compatibility with Nextcloud 28
- Removed Nextcloud 25

### Changed
- Updated some dependencies

## 6.6.2 – 2023-08-17
### Fixed
- Don't load JS assets on all pages
  [#689](https://github.com/nextcloud/announcementcenter/pull/689)
- Migrate admin settings to Vue so they are accessible
  [#648](https://github.com/nextcloud/announcementcenter/pull/648)

## 6.6.1 – 2023-05-16
### Fixed
- Activity emails still send although the announcement is emailed
  [#655](https://github.com/nextcloud/announcementcenter/pull/655)

## 6.6.0 – 2023-05-12
### Added
- Compatibility with Nextcloud 27

### Changed
- Migrate RichText component from @nextcloud/vue-richtext to @nextcloud/vue
- Updated some dependencies

## 6.5.1 – 2023-03-01
### Changed
- Updated some dependencies

### Fixed
- Fix broken group selection with `@nextcloud/vue` update
  [#607](https://github.com/nextcloud/announcementcenter/pull/607)

## 6.5.0 – 2023-02-16
### Added
- Compatibility with Nextcloud 26

### Changed
- Updated and migrated some dependencies

### Fixed
- Don't load comment sidebar when the active announcement doesn't allow comments
  [#576](https://github.com/nextcloud/announcementcenter/pull/576)
- Log announcement creation and deletion
  [#571](https://github.com/nextcloud/announcementcenter/pull/571)
- Skip users without a valid email address
  [#551](https://github.com/nextcloud/announcementcenter/pull/551)

## 6.4.0 – 2022-10-18
### Changed
- Compatibility with Nextcloud 25
- Drop support for Nextcloud 24 and older

## 6.3.1 – 2022-06-14
### Fixed
- Don't send emails to disabled users
  [#480](https://github.com/nextcloud/announcementcenter/pull/480)

## 6.3.0 – 2022-05-16
### Added
- Add option to directly send emails for announcements (by [mejo-](https://github.com/mejo-))
  [#467](https://github.com/nextcloud/announcementcenter/pull/467)

## 6.2.0 – 2022-04-27
### Fixed
- Compatibility with Nextcloud 24

## 6.1.1 – 2021-11-09
### Fixed
- Compatibility with Nextcloud 23

## 6.0.0 – 2021-09-22
### Fixed
- Fix missing notifications when posting multiple restricted notifications in a short time
  [#395](https://github.com/nextcloud/announcementcenter/pull/395)
- Fix "Route does not exist" log spam when accessing the dashboard
  [#392](https://github.com/nextcloud/announcementcenter/pull/392)
- Compatibility with Nextcloud 22

## 5.0.1 – 2021-09-22
### Fixed
- Fix missing notifications when posting multiple restricted notifications in a short time
  [#396](https://github.com/nextcloud/announcementcenter/pull/396)
- Fix "Route does not exist" log spam when accessing the dashboard
  [#393](https://github.com/nextcloud/announcementcenter/pull/393)

## 4.0.2 – 2021-09-22
### Fixed
- Fix missing notifications when posting multiple restricted notifications in a short time
  [#397](https://github.com/nextcloud/announcementcenter/pull/397)

## 5.0.0 – 2021-02-23
### Added
- Rewrite the frontend in Vue.JS after a design review
  [#218](https://github.com/nextcloud/announcementcenter/pull/218)

### Fixed
- Make the database schema compatible with clusters by adding a primary key
  [#293](https://github.com/nextcloud/announcementcenter/pull/293)
- Compatibility with Nextcloud 21

## 4.0.1 – 2021-01-29
### Fixed
- Fix dashboard when user_status app is disabled
  [#279](https://github.com/nextcloud/announcementcenter/pull/279)

## 4.0.0 – 2020-08-31
### Added
- Add a dashboard widget
  [#209](https://github.com/nextcloud/announcementcenter/pull/209)

## 3.9.1 – 2020-08-31
### Added
- Make emails clickable
  [#206](https://github.com/nextcloud/announcementcenter/pull/206)
  
### Fixed
- Compatibility with Nextcloud 20

## 3.8.1 – 2020-06-03
### Fixed
- Don't send notifications and activities to users unable to read the announcement

## 3.8.0 – 2020-04-08
### Fixed
- Compatibility with Nextcloud 19

## 3.7.0 – 2020-01-17
### Fixed
- Compatibility with Nextcloud 18

## 3.6.1 – 2019-10-04
### Fixed
- Remove JS warnings for loading all libraries
  [#169](https://github.com/nextcloud/announcementcenter/pull/169)

## 3.6.0 – 2019-08-26
### Fixed
- Compatibility with Nextcloud 17

## 3.5.1 – 2019-05-07
### Fixed
- Fix deleting announcements
  [#150](https://github.com/nextcloud/announcementcenter/pull/150)

## 3.5.0 – 2019-04-04
### Fixed
- Compatibility with Nextcloud 16

## 3.4.1 – 2019-01-17
### Added
- Markdown support for announcements
  [#132](https://github.com/nextcloud/announcementcenter/pull/132)

### Fixed
- Correctly reset the group selection after sending an announcement
  [#137](https://github.com/nextcloud/announcementcenter/pull/137)
- Better support for dark-theme
  [#134](https://github.com/nextcloud/announcementcenter/pull/134)
  [#138](https://github.com/nextcloud/announcementcenter/pull/138)
- Prevent HTML rendering in notifications
  [#131](https://github.com/nextcloud/announcementcenter/pull/131)

## 3.4.0 – 2018-11-16
### Added
- Support mentions and clickable links in comments
  [#121](https://github.com/nextcloud/announcementcenter/pull/121)

### Fixed
- Compatibility with Nextcloud 15

## 3.3.1 – 2018-10-11
### Fixed
- Add the announcement subject to the activity emails
  [#118](https://github.com/nextcloud/announcementcenter/pull/118)

## 3.3.0 – 2018-08-06
### Fixed
- Compatibility for Nextcloud 14

## 3.2.1 – 2018-02-12
### Fixed
- Fix layout of announcements
  [#94](https://github.com/nextcloud/announcementcenter/pull/94)
