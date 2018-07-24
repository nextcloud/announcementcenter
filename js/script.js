/**
 * AnnouncementCenter
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Joas Schilling <nickvergessen@gmx.de>
 * @copyright Joas Schilling 2015
 */

(function() {
	if (!OCA.AnnouncementCenter) {
		/**
		 * @namespace
		 */
		OCA.AnnouncementCenter = {};
	}

	OCA.AnnouncementCenter.Comments = {};
	OCA.AnnouncementCenter.App = {
		announcements: {},
		ignoreScroll: 0,
		$container: null,
		$content: null,
		lastLoadedAnnouncement: 0,
		sevenDaysMilliseconds: 7 * 24 * 3600 * 1000,
		commentsTabView: null,

		compiledTemplate: null,
		handlebarTemplate: '<div class="section" data-announcement-id="{{{announcementId}}}">' +
				'<h2>{{{subject}}}</h2>' +
					'<span class="has-tooltip live-relative-timestamp" data-timestamp="{{timestamp}}" title="{{dateFormat}}">{{dateRelative}}</span>' +
					'<span class="avatar-name-wrapper" data-user="{{authorId}}"><div class="avatar" data-user="{{authorId}}" data-user-display-name="{{author}}"></div><strong>{{author}}</strong></span>' +
					'{{#if isAdmin}}' +
						'<span class="visibility has-tooltip" title="{{{visibilityString}}}">' +
							'{{#if visibilityEveryone}}' +
								'<img src="' + OC.imagePath('core', 'places/link') + '">' +
							'{{else}}' +
								'<img src="' + OC.imagePath('core', 'places/contacts') + '">' +
							'{{/if}}' +
						'</span>' +
					'{{/if}}' +
					'{{#if comments}}' +
						'<span class="comment-details" data-count="{{num_comments}}">{{comments}}</span>' +
					'{{/if}}' +
					'{{#if isAdmin}}' +
						'<span class="delete-link has-tooltip" title="' + t('announcementcenter', 'Delete') + '">' +
							'<a href="#" data-announcement-id="{{{announcementId}}}">' +
								'<img class="svg" src="' + OC.imagePath('core', 'actions/delete') + '" alt=""/>' +
							'</a>' +
						'</span>' +
						'{{#if hasNotifications}}' +
						'<span class="mute-link has-tooltip" title="' + t('announcementcenter', 'Remove notifications') + '">' +
							'<a href="#" data-announcement-id="{{{announcementId}}}">' +
								'<img class="svg" src="' + OC.imagePath('announcementcenter', 'notifications-off.svg') + '" alt=""/>' +
							'</a>' +
						'</span>' +
						'{{/if}}' +
					'{{/if}}' +
				'{{#if message}}' +
					'<br /><br /><p>{{{message}}}</p>' +
				'{{/if}}' +
			'</div>' +
			'<hr />',

		init: function() {
			this.$container = $('#app-content-wrapper');
			this.$content = $('#app-content');
			this.compiledTemplate = Handlebars.compile(this.handlebarTemplate);

			this.commentsTabView = OCA.AnnouncementCenter.Comments.CommentsTabView;
			this.commentsTabView.initialize();

			$('#submit_announcement').on('click', _.bind(this.postAnnouncement, this));
			this.$content.on('scroll', _.bind(this.onScroll, this));

			this.ignoreScroll = 1;
			this.isAdmin = $('#app.announcementcenter').attr('data-is-admin') === '1';

			OC.Util.History.addOnPopStateHandler(_.bind(this._onPopState, this));
			var urlParams = OC.Util.History.parseUrlQuery();
			this.announcement = parseInt(urlParams.announcement, 10);
			this.loadAnnouncements();

			var self = this;
			$('#announcement_options_button').on('click', function() {
				$('#announcement_options').toggleClass('hidden');
			});
			$('#groups').each(function (index, element) {
				self.setupGroupsSelect($(element));
			});
		},

		_onPopState: function(params) {
			params = _.extend({
				announcement: 0
			}, params);

			console.log('_onPopState' + params.announcement);
			this.highlightAnnouncement(params.announcement);
		},

		_onHighlightAnnouncement: function(event) {
			var $element = $(event.currentTarget),
				announcementId = $element.data('announcement-id');

			OC.Util.History.pushState({
				announcement: announcementId
			});

			this.highlightAnnouncement(announcementId);
		},

		highlightAnnouncement: function(announcementId) {
			if (this.announcements[announcementId]['comments'] !== false) {
				this.commentsTabView.setObjectId(announcementId);
			} else {
				this.commentsTabView.setObjectId(0);
			}

			var $appContent = $('#app-content'),
				currentOffset = $appContent.scrollTop();

			$appContent.animate({
				// Scrolling to the top of the new element
				scrollTop: currentOffset + $('div.section[data-announcement-id=' + announcementId + ']').offset().top - 50
			}, 500);
		},

		deleteAnnouncement: function(event) {
			var self = this;
			event.stopPropagation();

			var $element = $(event.currentTarget),
				announcementId = $element.data('announcement-id');
			$.ajax({
				type: 'DELETE',
				url: OC.generateUrl('/apps/announcementcenter/announcement/' + announcementId)
			}).done(function () {
				var $announcement = $element.parents('.section').first();
				delete self.announcements[announcementId];
				self.commentsTabView.setObjectId(0);

				$announcement.slideUp();
				// Remove the hr
				$announcement.next().remove();

				setTimeout(function() {
					$announcement.remove();

					if ($('#app-content-wrapper .section').length == 1) {
						$('#emptycontent').removeClass('hidden');
					}
				}, 750);

			});
		},

		removeNotifications: function(event) {
			event.stopPropagation();

			var $element = $(event.currentTarget),
				announcementId = $element.data('announcement-id');
			$.ajax({
				type: 'DELETE',
				url: OC.generateUrl('/apps/announcementcenter/announcement/' + announcementId + '/notifications')
			}).done(function () {
				var $link = $element.parents('.mute-link').first();
				$link.tooltip('hide');
				$link.remove();
			});
		},

		postAnnouncement: function() {
			var self = this;
			OC.msg.startAction('#announcement_submit_msg', t('announcementcenter', 'Announcing…'));

			$.ajax({
				type: 'POST',
				url: OC.generateUrl('/apps/announcementcenter/announcement'),
				data: {
					subject: $('#subject').val(),
					message: $('#message').val(),
					groups: $('#groups').val().split('|'),
					activities: $('#create_activities').attr('checked') === 'checked',
					notifications: $('#create_notifications').attr('checked') === 'checked',
					comments: $('#allow_comments').attr('checked') === 'checked'
				}
			}).done(function(announcement) {
				OC.msg.finishedSuccess('#announcement_submit_msg', t('announcementcenter', 'Announced!'));

				self.announcements[announcement.id] = announcement;
				var $html = self.announcementToHtml(announcement);
				$('#app-content-wrapper .section:eq(0)').after($html);
				$html.hide();
				setTimeout(function() {
					$html.slideDown();
					$('#emptycontent').addClass('hidden');
				}, 750);

				$('#subject').val('');
				$('#message').val('');
				$('#groups').val('');
			}).fail(function (response) {
				OC.msg.finishedError('#announcement_submit_msg', response.responseJSON.error);
			});
		},

		loadAnnouncements: function() {
			var self = this,
				offset = self.lastLoadedAnnouncement;
			$.ajax({
				type: 'GET',
				url: OC.generateUrl('/apps/announcementcenter/announcement'),
				data: {
					offset: offset
				}
			}).done(function (response) {
				if (response.length > 0) {
					_.each(response, function (announcement) {
						self.announcements[announcement.id] = announcement;
						var $html = self.announcementToHtml(announcement);
						$('#app-content-wrapper').append($html);
						if (announcement.id < self.lastLoadedAnnouncement || self.lastLoadedAnnouncement === 0) {
							self.lastLoadedAnnouncement = announcement.id;
						}

						if (self.announcement === announcement.id) {
							self.highlightAnnouncement(announcement.id);
						}
					});
					self.ignoreScroll = 0;
				} else if (offset === 0) {
					$('#emptycontent').removeClass('hidden');
				}
			});
		},

		announcementToHtml: function (announcement) {
			var timestamp = announcement.time * 1000;

			var object = {
				dateFormat: OC.Util.formatDate(timestamp),
				dateRelative: OC.Util.relativeModifiedDate(timestamp),
				timestamp: timestamp,
				author: announcement.author,
				authorId: announcement.author_id,
				subject: announcement.subject,
				message: announcement.message,
				comments: (announcement.comments !== false) ? n('announcementcenter', '%n comment', '%n comments', announcement.comments) : false,
				num_comments: (announcement.comments !== false) ? announcement.comments : false,
				hasNotifications: announcement.notifications,
				visibilityEveryone: null,
				visibilityString: null,
				announcementId: announcement.id,
				isAdmin: this.isAdmin
			};

			if (this.isAdmin) {
				if (announcement.groups.indexOf('everyone') > -1) {
					object.visibilityEveryone = true;
					object.visibilityString = t('announcementcenter', 'Visible for everyone');
				} else {
					object.visibilityEveryone = false;
					object.visibilityString = t('announcementcenter', 'Visible for groups: {groups}', {
						groups: announcement.groups.join(t('announcementcenter', ', '))
					});
				}
			}

			var $html = $(this.compiledTemplate(object));
			$html.find('span.delete-link a').on('click', _.bind(this.deleteAnnouncement, this));
			$html.find('span.mute-link a').on('click', _.bind(this.removeNotifications, this));
			$html.on('click', _.bind(this._onHighlightAnnouncement, this));

			$html.find('.avatar').each(function() {
				var element = $(this);
				if (element.data('user-display-name')) {
					element.avatar(element.data('user'), 21, undefined, false, undefined, element.data('user-display-name'));
				} else {
					element.avatar(element.data('user'), 21);
				}
			});

			$html.find('.avatar-name-wrapper').each(function() {
				var element = $(this),
					avatar = element.find('.avatar'),
					label = element.find('strong');

				$.merge(avatar, label).contactsMenu(element.data('user'), 0, element);
			});

			$html.find('.has-tooltip').tooltip({
				placement: 'bottom'
			});

			return $html;
		},

		onScroll: function () {
			if (this.ignoreScroll <= 0 && this.$content.scrollTop() +
				this.$content.height() > this.$container.height() - 100) {
				this.ignoreScroll = 1;
				this.loadAnnouncements();
			}
		},

		/**
		 * Setup selection box for group selection.
		 *
		 * Values need to be separated by a pipe "|" character.
		 * (mostly because a comma is more likely to be used
		 * for groups)
		 *
		 * @param $elements jQuery element (hidden input) to setup select2 on
		 */
		setupGroupsSelect: function($elements) {
			if ($elements.length > 0) {
				// note: settings are saved through a "change" event registered
				// on all input fields
				$elements.select2(_.extend({
					placeholder: t('core', 'Groups'),
					allowClear: true,
					multiple: true,
					separator: '|',
					query: _.debounce(function(query) {
						var queryData = {};
						if (query.term !== '') {
							queryData = {
								pattern: query.term
							};
						}
						$.ajax({
							url: OC.generateUrl('/apps/announcementcenter/groups'),
							data: queryData,
							dataType: 'json',
							success: function(data) {
								query.callback({results: data});
							}
						});
					}, 100, true),
					id: function(element) {
						return element;
					},
					initSelection: function(element, callback) {
						var selection = ($(element).val() || []).split('|').sort();
						callback(selection);
					},
					formatResult: function (group) {
						return escapeHTML(group);
					},
					formatSelection: function (group) {
						return escapeHTML(group);
					},
					escapeMarkup: function(m) {
						// prevent double markup escape
						return m;
					}
				}));
			}
		}
	};

})();

$(document).ready(function() {
	OCA.AnnouncementCenter.App.init();
});
