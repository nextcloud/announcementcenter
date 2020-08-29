/**
 * AnnouncementCenter
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Joas Schilling <nickvergessen@gmx.de>
 * @copyright Joas Schilling 2015
 */

function escapeHTML(text) {
	return text.toString()
		.split('&').join('&amp;')
		.split('<').join('&lt;')
		.split('>').join('&gt;')
		.split('"').join('&quot;')
		.split('\'').join('&#039;')
}

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

		init: function() {
			this.$container = $('#content');
			this.$content = $('#app-content');

			this.commentsTabView = new OCA.AnnouncementCenter.Comments.CommentsTabView();

			$('#submit_announcement').on('click', _.bind(this.postAnnouncement, this));
			$(document).bind('scroll', _.bind(this.onScroll, this));

			this.ignoreScroll = 1;
			this.isAdmin = $('#app-content').attr('data-is-admin') === '1';

			OC.Util.History.addOnPopStateHandler(_.bind(this._onPopState, this));
			var urlParams = OC.Util.History.parseUrlQuery();
			this.announcement = parseInt(urlParams.announcement, 10);
			this.loadAnnouncements();

			var self = this;
			$('#commentsTabView_close_button').on('click', function() {
				self.commentsTabView.setObjectId(0);
			});

			$('#announcement_options_button').on('click', function() {
				$('#announcement_options').toggleClass('hidden');
			});
			$('#groups').each(function(index, element) {
				self.setupGroupsSelect($(element));
			});
		},

		_onPopState: function(params) {
			params = _.extend({
				announcement: 0
			}, params);

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
			}).done(function() {
				var $announcement = $element.parents('.section').first();
				delete self.announcements[announcementId];
				self.commentsTabView.setObjectId(0);

				$announcement.slideUp();
				// Remove the hr
				$announcement.next().remove();

				setTimeout(function() {
					$announcement.remove();

					if ($('#app-content .section').length == 1) {
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
			}).done(function() {
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
				$('#app-content #emptycontent').after($html);
				$html.hide();
				setTimeout(function() {
					$html.slideDown();
					$('#emptycontent').addClass('hidden');
				}, 750);

				$('#subject').val('');
				$('#message').val('');
				$('#groups').val('').trigger('change');
			}).fail(function(response) {
				OC.msg.finishedError('#announcement_submit_msg', response.responseJSON.error);
			});
		},

		loadAnnouncements: function() {
			var self = this,
				offset = self.lastLoadedAnnouncement;

			$('#lazyload').removeClass('hidden');

			$.ajax({
				type: 'GET',
				url: OC.generateUrl('/apps/announcementcenter/announcement'),
				data: {
					offset: offset
				}
			}).done(function(response) {
				if (response.length > 0) {
					_.each(response, function(announcement) {
						self.announcements[announcement.id] = announcement;
						var $html = self.announcementToHtml(announcement);
						$('#lazyload').before($html);
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

				$('#lazyload').addClass('hidden');
			});
		},

		markdownToHtml: function(message) {
			message = message.replace(/<br \/>/g, "\n").replace(/&gt;/g, '>');
			var renderer = new window.marked.Renderer();
			renderer.link = function(href, title, text) {
				try {
					var prot = decodeURIComponent(unescape(href))
						.replace(/[^\w:]/g, '')
						.toLowerCase();
				} catch (e) {
					return '';
				}
				if (prot.indexOf('http:') === 0 || prot.indexOf('https:') === 0) {
					var out = '<a href="' + href + '" target="_blank" rel="noreferrer noopener" class="external"';
					if (title) {
						out += ' title="' + title + '"';
					}
					out += '>' + text + ' ↗</a>';
					return out;
				} else if (prot.indexOf('mailto:') === 0) {
					return '<a href="' + href + '" class="external">' + text + '</a>';
				}
				return '';
			};

			renderer.em = function(text) {
				return '<i>' + text + '</i>';
			};

			renderer.image = function(href, title, text) {
				var alt = escapeHTML(text ? text : title);
				return '<img src="' + href + '" alt="' + alt + '" />';
			};

			return DOMPurify.sanitize(
				window.marked(message.trim(), {
					breaks: true,
					gfm: true,
					highlight: false,
					pedantic: false,
					renderer: renderer,
					sanitize: true,
					smartLists: true,
					smartypants: false,
					tables: false
				}),
				{
					SAFE_FOR_JQUERY: true,
					ALLOWED_TAGS: [
						'a',
						'blockquote',
						'br',
						'code',
						'del',
						'em',
						'i',
						'img',
						'li',
						'ol',
						'p',
						'strong',
						'ul'
					]
				}
			);
		},

		announcementToHtml: function(announcement) {
			var timestamp = announcement.time * 1000;

			var object = {
				dateFormat: OC.Util.formatDate(timestamp),
				dateRelative: OC.Util.relativeModifiedDate(timestamp),
				timestamp: timestamp,
				author: announcement.author,
				authorId: announcement.author_id,
				subject: announcement.subject,
				message: this.markdownToHtml(announcement.message),
				comments: (announcement.comments !== false) ? n('announcementcenter', '%n comment', '%n comments', announcement.comments) : false,
				num_comments: (announcement.comments !== false) ? announcement.comments : false,
				hasNotifications: announcement.notifications,
				visibilityEveryone: null,
				visibilityString: null,
				announcementId: announcement.id,
				isAdmin: this.isAdmin,
				deleteTXT: t('announcementcenter', 'Delete'),
				removeNotificationTXT: t('announcementcenter', 'Remove notifications'),
				notificationsOffIMG: OC.imagePath('announcementcenter', 'notifications-off.svg'),
				deleteIMG: OC.imagePath('core', 'actions/delete.svg'),
				placesLinkIMG: OC.imagePath('core', 'places/link.svg'),
				placesContactsIMG: OC.imagePath('core', 'places/link.svg')
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

			var $html = $(OCA.AnnouncementCenter.Templates.announcement(object));
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

		onScroll: function() {
			if (this.ignoreScroll <= 0 && $(document).scrollTop() +
				window.innerHeight > this.$content.height() - 100) {
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
								query.callback({
									results: data
								});
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
					formatResult: function(group) {
						return escapeHTML(group);
					},
					formatSelection: function(group) {
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
