/*
 * Copyright (c) 2016
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */

/* global Handlebars, escapeHTML */

(function(OC, OCA) {

	/**
	 * @memberof OCA.AnnouncementCenter.Comments
	 */
	OCA.AnnouncementCenter.Comments.CommentsTabView = {
		id: 'commentsTabView',
		className: 'tab commentsTabView',
		currentId: 0,

		_commentMaxLength: 1000,

		initialize: function() {
			this.$el = $('#commentsTabView');

			this.collection = new OCA.AnnouncementCenter.Comments.CommentCollection();
			this.collection.on('request', this._onRequest, this);
			this.collection.on('sync', this._onEndRequest, this);
			this.collection.on('add', this._onAddModel, this);

			this._avatarsEnabled = !!OC.config.enable_avatars;

			this._commentMaxThreshold = this._commentMaxLength * 0.9;

			// TODO: error handling
			_.bindAll(this, '_onTypeComment');
		},

		template: function(params) {
			var currentUser = OC.getCurrentUser();
			return OCA.AnnouncementCenter.Templates.commentsview(_.extend({
				avatarEnabled: this._avatarsEnabled,
				actorId: currentUser.uid,
				actorDisplayName: currentUser.displayName
			}, params));
		},

		editCommentTemplate: function(params) {
			var currentUser = OC.getCurrentUser();
			var el = OCA.AnnouncementCenter.Templates.edit_comment(_.extend({
				avatarEnabled: this._avatarsEnabled,
				actorId: currentUser.uid,
				actorDisplayName: currentUser.displayName,
				newMessagePlaceholder: t('comments', 'New comment …'),
				deleteTooltip: t('comments', 'Delete comment'),
				submitText: t('comments', 'Post'),
				cancelText: t('comments', 'Cancel')
			}, params));
			var $el = $(el);

			$el.find('.action.delete').on('click', _.bind(this._onClickDeleteComment, this));
			$el.find('.cancel').on('click', _.bind(this._onClickCloseComment, this));
			$el.find('.newCommentForm').on('submit', _.bind(this._onSubmitComment, this));

			return $el;
		},

		commentTemplate: function(params) {
			params = _.extend({
				avatarEnabled: this._avatarsEnabled,
				editTooltip: t('comments', 'Edit comment'),
				isUserAuthor: OC.getCurrentUser().uid === params.actorId,
				isLong: this._isLong(params.message)
			}, params);

			if (params.actorType === 'deleted_users') {
				// makes the avatar a X
				params.actorId = null;
				params.actorDisplayName = t('comments', '[Deleted user]');
			}

			return OCA.AnnouncementCenter.Templates.comment(params);
		},

		getLabel: function() {
			return t('comments', 'Comments');
		},

		setObjectId: function(announcementId) {
			if (this.currentId === announcementId) {
				return;
			}

			if (announcementId === 0) {
				$('#app-content').removeClass('with-app-sidebar');
				$('#app-sidebar').addClass('disappear');
			} else {
				$('#app-content').addClass('with-app-sidebar');
				$('#app-sidebar').removeClass('disappear');

				this.render();
				this.collection.setObjectId(announcementId);
				// reset to first page
				this.collection.reset([], {silent: true});
				this.nextPage();
			}

			this.currentId = announcementId;
		},

		render: function() {
			this.$el.html(this.template({
				emptyResultLabel: t('comments', 'No comments yet, start the conversation!'),
				moreLabel: t('comments', 'More comments …')
			}));
			this.$el.find('.comments').before(this.editCommentTemplate({}));
			this.$el.find('.has-tooltip').tooltip();
			this.$container = this.$el.find('ul.comments');
			if (this._avatarsEnabled) {
				this.$el.find('.avatar').avatar(OC.getCurrentUser().uid, 32);
			}
			this.$el.find('.message').on('keydown input change', this._onTypeComment);
			this.$el.find('.showMore').on('click', this._onClickShowMore);

			autosize(this.$el.find('.newCommentRow textarea'))
		},

		_formatItem: function(commentModel) {
			var timestamp = new Date(commentModel.get('creationDateTime')).getTime();
			var data = _.extend({
				date: OC.Util.relativeModifiedDate(timestamp),
				altDate: OC.Util.formatDate(timestamp),
				timestamp: timestamp,
				formattedMessage: this._formatMessage(commentModel.get('message'))
			}, commentModel.attributes);
			return data;
		},

		_toggleLoading: function(state) {
			this._loading = state;
			this.$el.find('.loading').toggleClass('hidden', !state);
		},

		_onRequest: function(type) {
			if (type === 'REPORT') {
				this._toggleLoading(true);
				this.$el.find('.showMore').addClass('hidden');
			}
		},

		_onEndRequest: function(type) {
			this._toggleLoading(false);
			this.$el.find('.emptycontent').toggleClass('hidden', !!this.collection.length);
			this.$el.find('.showMore').toggleClass('hidden', !this.collection.hasMoreResults());

			if (type !== 'REPORT') {
				return;
			}

			// find first unread comment
			var firstUnreadComment = this.collection.findWhere({isUnread: true});
			if (firstUnreadComment) {
				// update read marker
				this.collection.updateReadMarker();
			}
		},

		_onAddModel: function(model, collection, options) {
			var $el = $(this.commentTemplate(this._formatItem(model)));
			if (!_.isUndefined(options.at) && collection.length > 1) {
				this.$container.find('li').eq(options.at).before($el);
			} else {
				this.$container.append($el);
			}

			this._postRenderItem($el);
		},

		_postRenderItem: function($el) {
			$el.find('.has-tooltip').tooltip();

			$el.find('.action.edit').on('click', _.bind(this._onClickEditComment, this));
			$el.on('click', _.bind(this._onClickComment, this));

			if(this._avatarsEnabled) {
				$el.find('.avatar').each(function() {
					var $this = $(this);
					$this.avatar($this.attr('data-username'), 32);
				});
			}

			var username = $el.find('.avatar').data('username');
			if (username !== oc_current_user) {
				$el.find('.authorRow .avatar, .authorRow .author').contactsMenu(
					username, 0, $el.find('.authorRow'));
			}

			var message = $el.find('.message');
			message.find('.avatar').each(function() {
				var avatar = $(this);
				var strong = $(this).next();
				var appendTo = $(this).parent();

				$.merge(avatar, strong).contactsMenu(avatar.data('user'), 0, appendTo);
			});
		},

		/**
		 * Convert a message to be displayed in HTML,
		 * converts newlines to <br> tags.
		 */
		_formatMessage: function(message) {
			return escapeHTML(message).replace(/\n/g, '<br/>');
		},

		nextPage: function() {
			if (this._loading || !this.collection.hasMoreResults()) {
				return;
			}

			this.collection.fetchNext();
		},

		_onClickEditComment: function(ev) {
			ev.preventDefault();
			var $comment = $(ev.target).closest('.comment');
			var commentId = $comment.data('id');
			var commentToEdit = this.collection.get(commentId);
			var $formRow = $(this.editCommentTemplate(_.extend({
				isEditMode: true,
				submitText: t('comments', 'Save')
			}, commentToEdit.attributes)));

			$comment.addClass('hidden').removeClass('collapsed');
			// spawn form
			$comment.after($formRow);
			$formRow.data('commentEl', $comment);
			$formRow.find('textarea').on('keydown input change', this._onTypeComment);

			// copy avatar element from original to avoid flickering
			$formRow.find('.avatar').replaceWith($comment.find('.avatar').clone());
			$formRow.find('.has-tooltip').tooltip();

			// Enable autosize
			autosize($formRow.find('textarea'));

			return false;
		},

		_onTypeComment: function(ev) {
			var $field = $(ev.target);
			var len = $field.val().length;
			var $submitButton = $field.data('submitButtonEl');
			if (!$submitButton) {
				$submitButton = $field.closest('form').find('.submit');
				$field.data('submitButtonEl', $submitButton);
			}
			$field.tooltip('hide');
			if (len > this._commentMaxThreshold) {
				$field.attr('data-original-title', t('comments', 'Allowed characters {count} of {max}', {count: len, max: this._commentMaxLength}));
				$field.tooltip({trigger: 'manual'});
				$field.tooltip('show');
				$field.addClass('error');
			}

			var limitExceeded = (len > this._commentMaxLength);
			$field.toggleClass('error', limitExceeded);
			$submitButton.prop('disabled', limitExceeded);

			//submits form on ctrl+Enter or cmd+Enter
			if (ev.keyCode === 13 && (ev.ctrlKey || ev.metaKey)) {
				$submitButton.click();
			}
		},

		_onClickComment: function(ev) {
			var $row = $(ev.target);
			if (!$row.is('.comment')) {
				$row = $row.closest('.comment');
			}
			$row.removeClass('collapsed');
		},

		_onClickCloseComment: function(ev) {
			ev.preventDefault();
			var $row = $(ev.target).closest('.comment');
			$row.data('commentEl').removeClass('hidden');
			$row.remove();
			return false;
		},

		_onClickDeleteComment: function(ev) {
			ev.preventDefault();
			var self = this,
				$comment = $(ev.target).closest('.comment');
			var commentId = $comment.data('id');
			var $loading = $comment.find('.submitLoading');

			$comment.addClass('disabled');
			$loading.removeClass('hidden');
			this.collection.get(commentId).destroy({
				success: function() {
					$comment.data('commentEl').remove();
					$comment.remove();
					self._updateCommentCount(self.currentId, -1);
				},
				error: function() {
					$loading.addClass('hidden');
					$comment.removeClass('disabled');
					OC.Notification.showTemporary(t('comments', 'Error occurred while retrieving comment with ID {id}', {id: commentId}));
				}
			});


			return false;
		},

		_onClickShowMore: function(ev) {
			ev.preventDefault();
			this.nextPage();
		},

		_onSubmitComment: function(e) {
			var self = this;
			var $form = $(e.target);
			var commentId = $form.closest('.comment').data('id');
			var currentUser = OC.getCurrentUser();
			var $submit = $form.find('.submit');
			var $loading = $form.find('.submitLoading');
			var $textArea = $form.find('.message');
			var message = $textArea.val().trim();
			e.preventDefault();

			if (!message.length || message.length > this._commentMaxLength) {
				return;
			}

			$textArea.prop('disabled', true);
			$submit.addClass('hidden');
			$loading.removeClass('hidden');

			if (commentId) {
				// edit mode
				var comment = this.collection.get(commentId);
				comment.save({
					message: $textArea.val()
				}, {
					success: function(model) {
						var $row = $form.closest('.comment');
						$submit.removeClass('hidden');
						$loading.addClass('hidden');
						$row.data('commentEl')
							.removeClass('hidden')
							.find('.message')
							.html(self._formatMessage(model.get('message')));
						$row.remove();
					},
					error: function() {
						$submit.removeClass('hidden');
						$loading.addClass('hidden');
						$textArea.prop('disabled', false);

						OC.Notification.showTemporary(t('comments', 'Error occurred while updating comment with id {id}', {id: commentId}));
					}
				});
			} else {
				this.collection.create({
					actorId: currentUser.uid,
					actorDisplayName: currentUser.displayName,
					actorType: 'users',
					verb: 'comment',
					message: $textArea.val(),
					creationDateTime: (new Date()).toUTCString()
				}, {
					at: 0,
					// wait for real creation before adding
					wait: true,
					success: function() {
						$submit.removeClass('hidden');
						$loading.addClass('hidden');
						$textArea.val('').prop('disabled', false);
						self._updateCommentCount(self.currentId, 1);
					},
					error: function() {
						$submit.removeClass('hidden');
						$loading.addClass('hidden');
						$textArea.prop('disabled', false);

						OC.Notification.showTemporary(t('comments', 'Error occurred while posting comment'));
					}
				});
			}

			return false;
		},

		_updateCommentCount: function(announcement, diff) {
			var $announcement = $('.section[data-announcement-id=' + announcement + ']'),
				$details = $announcement.find('.comment-details'),
				newCount = parseInt($details.attr('data-count'), 10) + diff;

			$details.attr('data-count', newCount);
			$details.text(n('announcementcenter', '%n comment', '%n comments', newCount));
		},

		/**
		 * Returns whether the given message is long and needs
		 * collapsing
		 */
		_isLong: function(message) {
			return message.length > 250 || (message.match(/\n/g) || []).length > 1;
		}
	};
})(OC, OCA);

