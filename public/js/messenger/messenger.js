/**
 *
 * Messages Component
 *
 */
"use strict";
/* global app, messengerVars, pusher, FileUpload,
  Lists, Pusher, PusherBatchAuthorizer, updateButtonState,
  mswpScanPage, trans, bootstrapDetectBreakpoint, incrementNotificationsCount, passesMinMaxPPVMessageLimits
  filterXSS, launchToast, initTooltips, soketi, socketsDriver, showDialog, hideDialog, noMessagesLabel,
  contactElement, noContactsLabel, messageElement, mediaSettings, bindNoLongPressEvents */

$(function () {

    if(messengerVars.bootFullMessenger){
        messenger.fetchContacts();
        messenger.boot();
        messenger.initAutoScroll();
        messenger.initMarkAsSeen();
        messenger.resetTextAreaHeight();
        if(messengerVars.lastContactID !== false && messengerVars.lastContactID !== 0){
            if (isMessengerMobile()) {
                setMessengerLayoutMode(false);
                $('.conversation-content').html(noMessagesLabel());
                $('.conversation-writeup').addClass('hidden');
                $('.conversation-header').addClass('d-none');
            } else {
                messenger.fetchConversation(messengerVars.lastContactID);
            }
        }
        else{
            $('.conversation-content').html(noMessagesLabel());
            setMessengerLayoutMode(false);
        }
        FileUpload.initDropZone('#messenger-dropzone-hook','/attachment/upload/message', mediaSettings.use_chunked_uploads);
        messenger.initSelectizeUserList();
    }
    messenger.initNewConversationUI();
    messenger.initContactsSearch();
    messenger.initListFilter();
    messenger.initMobileNavigation();
    if (isMessengerMobile()) {
        destroyConversationsScrollbar();
    }
});

/**
 * Viewport space used above the main content shell (app bar, banners, etc.)
 */
function getMessengerChromeHeight() {
    var shell = document.querySelector('.flex-fill');
    if (shell && shell.getBoundingClientRect) {
        return Math.max(0, shell.getBoundingClientRect().top);
    }

    var height = 0;
    var appBar = document.querySelector('.mobile-app-bar');
    var bottomNav = document.querySelector('.mobile-bottom-nav');

    if (appBar) {
        var appBarStyle = window.getComputedStyle(appBar);
        if (appBarStyle.display !== 'none' && appBar.offsetHeight) {
            height += appBar.offsetHeight;
        }
    }

    if (bottomNav) {
        var bottomNavStyle = window.getComputedStyle(bottomNav);
        if (bottomNavStyle.display !== 'none' && bottomNav.offsetHeight) {
            height += bottomNav.offsetHeight;
        }
    }

    return height;
}

/**
 * Fit messenger to viewport without doubling panel heights on mobile
 */
function adjustMinHeight() {
    var available = window.innerHeight - getMessengerChromeHeight();
    var isMobile = window.matchMedia('(max-width: 767px)').matches;
    var $shell = $('body > .flex-fill').first();
    if (!$shell.length) {
        $shell = $('.flex-fill').first();
    }
    var $page = $('.messenger-page');
    var $messenger = $('.container.messenger');
    var $wrappers = $('.conversations-wrapper, .conversation-wrapper');
    var $content = $('.conversation-content');

    if (isMobile) {
        $shell.css({ height: available + 'px', maxHeight: available + 'px', overflow: 'hidden' });
        $page.css({ height: '100%', maxHeight: '100%' });
        $messenger.css({ height: '100%', maxHeight: '100%' });
        $wrappers.css({ height: '', minHeight: '', maxHeight: '' });
        $content.css({ height: '', maxHeight: '', overflow: '' });
        document.documentElement.classList.add('messenger-mobile-lock');
        document.body.classList.add('messenger-mobile-lock');
    } else {
        $shell.css({ height: '', maxHeight: '', overflow: '' });
        $page.css({ height: '', maxHeight: '' });
        $messenger.css({ height: '', maxHeight: '' });
        $content.css({ height: '', maxHeight: '', overflow: '' });
        $wrappers.each(function () {
            $(this).css('height', available + 'px');
        });
        document.documentElement.classList.remove('messenger-mobile-lock');
        document.body.classList.remove('messenger-mobile-lock');
    }
}

function isMessengerMobile() {
    return window.matchMedia('(max-width: 767px)').matches;
}

function setMessengerLayoutMode(hasChat) {
    var $messenger = $('.container.messenger');
    if (!$messenger.length) {
        return;
    }
    $messenger.toggleClass('messenger--has-chat', !!hasChat);
    $messenger.toggleClass('messenger--list-only', !hasChat);
    document.body.classList.toggle('messenger-mobile-chat', isMessengerMobile() && !!hasChat);
    if (hasChat) {
        $('.conversation-writeup').removeClass('hidden');
    }
    adjustMinHeight();
}

function updateMobileChatHeader() {
    if (!isMessengerMobile()) {
        return;
    }

    const user = messenger.state.activeConversationUser;
    const $avatar = $('.conversation-header-avatar');
    const $name = $('.conversation-header-user');

    if (user && messenger.state.activeConversationUserID) {
        if ($avatar.length && user.avatar) {
            $avatar.attr('src', user.avatar);
        }
        if ($name.length && user.name) {
            $name.text(user.name);
        }
    }
}

// Adjust on page load
$(document).ready(adjustMinHeight);

// Adjust on window resize
$(window).resize(adjustMinHeight);

var messenger = {

    state : {
        contacts:[],
        conversation:[],
        activeConversationUserID:null,
        activeConversationUser:null,
        currentBreakPoint: 'lg',
        redirectedToMessage: false,
        messagePrice: 5,
        isPaidMessage: false,
        activeMessageID: null,
        receiverIDs: [],
        newConversationMode: false,
        newConversationSelectAllToggle: false,
        isSendingMessage: false,
        contactListFilter: 'all',
    },

    pusher: null,
    selectizeInstance: null,

    /**
     * Boots up the main messenger functions
     */
    boot: function(){
        const socketKey = socketsDriver === 'soketi'
            ? (typeof soketi !== 'undefined' ? soketi.key : null)
            : (typeof pusher !== 'undefined' ? pusher.key : null);

        if (!socketKey) {
            return false;
        }

        try {
            Pusher.logToConsole = typeof messengerVars.pusherDebug !== 'undefined' ? messengerVars.pusherDebug : false;
            let params = {
                authorizer: PusherBatchAuthorizer,
                authDelay: 200,
                authEndpoint: app.baseUrl + '/my/messenger/authorizeUser',
                auth: {
                    headers: {
                        'X-CSRF-Token': $('meta[name="csrf-token"]').attr('content')
                    }
                }
            };
            if(socketsDriver === 'soketi'){
                params.wsHost = soketi.host;
                params.wsPort = soketi.port;
                params.forceTLS = soketi.useTSL ? true : false;
            }
            else{
                params.cluster = messengerVars.pusherCluster;
            }
            messenger.pusher = new Pusher(socketKey, params);
            return true;
        } catch (error) {
            messenger.pusher = null;
            return false;
        }
    },

    /**
     * Instantiates pusher sockets for each conversation (batched)
     */
    initLiveSockets: function(){
        if (!messenger.pusher) {
            return;
        }
        // TODO: Optimization: When fetchContacts is call, only re-init sockets for required channels
        $.each(messenger.state.contacts, function (k,v) {
            const minID = Math.min(v.receiverID,v.senderID);
            const maxID = Math.max(v.receiverID,v.senderID);
            const keyID = ("" + minID + '-' + maxID);
            let channel = messenger.pusher.subscribe('private-chat-channel-'+keyID);
            channel.unbind('new-message');
            channel.bind('new-message', function(data) {
                const message = jQuery.parseJSON(data.message);
                if(message.sender_id === messenger.state.activeConversationUserID){
                    messenger.state.conversation.push(message);
                    messenger.reloadConversation();
                }
                messenger.updateUnreadMessagesCount(parseInt($('#unseenMessages').html()) + 1);
                messenger.addLatestMessageToConversation(message.sender_id,message);
                messenger.markConversationAsRead(message.sender_id,'unread');
                messenger.fetchContacts();
            });
        });
    },

    /**
     * Initiate chatbox scroll to bottom event
     */
    initAutoScroll: function(){
        $(".messageBoxInput").keydown(function(e){
            // Enter was pressed without shift key
            if (e.keyCode === 13)
            {
                if(!e.shiftKey){
                    e.preventDefault();
                    $('.send-message').trigger('click');
                }
            }
        });
    },

    /**
     * Fetches all messenger contacts
     */
    fetchContacts: function (callback = function(){}) {
        $.ajax({
            type: 'GET',
            url: app.baseUrl + '/my/messenger/fetchContacts',
            dataType: 'json',
            success: function (result) {
                if(result.status === 'success'){
                    messenger.state.contacts = result.data.contacts;
                    messenger.reloadContactsList();
                    messenger.initLiveSockets();
                    callback();
                }
                else{
                    $('.conversations-list').html(noContactsLabel());
                }
            },
            error: function () {
                $('.conversations-list').html(noContactsLabel());
            }
        });
    },

    /**
     * Switches between layout having horiznatal scroll for contacts or not
     */
    makeContactsHeaderResponsive: function(){
        const breakPoint = bootstrapDetectBreakpoint();
        if(breakPoint.name === 'xs'){
            destroyConversationsScrollbar();
            $('.conversations-list').css({
                'overflow-y': 'auto',
                'max-height': ''
            });
            $('.conversations-list').removeClass('border-top');
        }
        else{
            destroyConversationsScrollbar();
            $('.conversations-list').removeClass('border-top');
        }
    },

    /**
     * Fetches conversation with certain user
     * @param userID
     */
    fetchConversation: function (userID) {
        messenger.closeNewConversationUI();
        // Setting up loading and clearign up conv content
        $('.conversation-loading-box').removeClass('d-none');
        $('.conversation-header-loading-box').removeClass('d-none');
        $('.conversation-header').addClass('d-none');

        // Setting up loading and clearign up conv content
        $('.conversation-loading-box').removeClass('d-none');
        $('.conversation-content').html('');
        $.ajax({
            type: 'GET',
            url: app.baseUrl + '/my/messenger/fetchMessages/' + userID,
            dataType: 'json',
            success: function (result) {
                if(result.status === 'success'){
                    messenger.state.conversation = result.data.messages;
                    messenger.reloadConversation();
                    messenger.state.activeConversationUserID = userID;
                    messenger.setActiveContact(userID);
                    messenger.reloadConversationHeader();
                    setMessengerLayoutMode(true);
                    if(app.feedDisableRightClickOnMedia !== null){
                        messenger.disableMesagesRightClick();
                    }
                    initTooltips();
                }
                else{
                    // messenger.state.contacts = result.data
                }
            }
        });
    },

    /**
     * Sends the message
     * @returns {boolean}
     */
    sendMessage: function(forceSave = false) {

        // Checking if files are being uploaded
        if(FileUpload.isLoading === true && forceSave === false){
            $('.confirm-post-save').unbind('click');
            $('.confirm-post-save').on('click',function () {
                messenger.sendMessage(true);
            });
            $('#confirm-post-save').modal('show');
            return false;
        }

        // Check if locked message has at least one attachment
        if(messenger.state.isPaidMessage && FileUpload.attachaments.length === 0){
            $('#no-attachments-locked-post').modal('show');
            return false;
        }

        if(messenger.isSendingMessage){
            // eslint-disable-next-line no-console
            console.info(trans('Another message is being sent - please wait'));
            return false;
        }

        updateButtonState('loading',$('.send-message'));

        // Validation
        if($('.messageBoxInput').val().length === 0 && FileUpload.attachaments.length === 0){
            updateButtonState('loaded',$('.send-message'));
            return false;
        }

        messenger.isSendingMessage = true;

        $.ajax({
            type: 'POST',
            url: app.baseUrl + '/my/messenger/sendMessage',
            data: {
                'message': $('.conversation-writeup .messageBoxInput').val(),
                'attachments' : FileUpload.attachaments,
                'receiverIDs' : messenger.state.receiverIDs,
                'price': messenger.state.isPaidMessage ? messenger.state.messagePrice : 0
            },
            dataType: 'json',
            success: function (result) {
                messenger.clearMessageBox();
                messenger.clearMessagePrice();
                messenger.resetTextAreaHeight();
                messenger.clearFileUploadsState();
                if(messenger.state.receiverIDs.length === 1){
                    // Single message
                    messenger.state.conversation.push(result.data.message);
                    messenger.addLatestMessageToConversation(result.data.message.receiverID,result.data.message);
                    if(messenger.state.newConversationMode){
                        messenger.fetchContacts(function () {});
                        messenger.state.activeConversationUserID = result.data.message.receiver_id;
                        messenger.fetchConversation(result.data.message.receiver_id);
                    }
                    messenger.reloadConversation();
                    messenger.closeNewConversationUI();
                    messenger.isSendingMessage = false;

                }
                else{
                    // Mass messages
                    const latestContactId = result.data[result.data.length - 1].message.receiver_id;
                    if(messenger.state.newConversationMode){
                        messenger.fetchContacts();
                    }
                    messenger.state.activeConversationUserID = latestContactId;
                    messenger.fetchConversation(latestContactId);
                    initTooltips();
                    if(result.errors){
                        launchToast('danger',trans('Error'),result.errors);
                    }
                }
                $('#confirm-post-save').modal('hide');
                messenger.hideEmptyChatElements();
                updateButtonState('loaded', $('.send-message'));
                initTooltips();
                messenger.isSendingMessage = false;

            },
            error: function (result) {
                launchToast('danger',trans('Error'),result.responseJSON.message);
                updateButtonState('loaded',$('.send-message'));
                messenger.isSendingMessage = false;

            }
        });
    },

    /**
     * Clears up uploaded files
     */
    clearFileUploadsState: function(){
        FileUpload.attachaments = [];
        $('.dropzone-previews').html('');
    },

    /**
     * Method used for starting a conversation from the profile page
     */
    sendDMFromProfilePage: function(){
        let submitButton = $('.new-conversation-label');
        updateButtonState('loading',submitButton, trans('Send'), 'white');
        $.ajax({
            type: 'POST',
            url: app.baseUrl + '/my/messenger/sendMessage',
            data: {'receiverIDs':[$('#receiverID').val()], 'message' : $('#messageText').val()},
            success: function () {
                $("textarea[name=message]").val("");
                $('#messageModal').modal('hide');
                window.location.assign(app.baseUrl + '/my/messenger');
                updateButtonState('loaded',submitButton, trans('Save'));
            },
            error: function (result) {
                launchToast('danger',trans('Error'),result.responseJSON.message);
                updateButtonState('loaded',submitButton, trans('Save'));
            },
        });
    },

    /**
     * Marks message as seen
     */
    initMarkAsSeen:function(){
        $( ".messageBoxInput" ).on('click', function() {
            if($('#unseenValue').val() !== 0){
                $.ajax({
                    type: 'POST',
                    url: app.baseUrl + '/my/messenger/markSeen',
                    data: {userID:messenger.state.activeConversationUserID},
                    dataType: 'json',
                    success: function (result) {
                        messenger.markConversationAsRead(messenger.state.activeConversationUserID,'read');
                        messenger.updateUnreadMessagesCount(parseInt($('#unseenMessages').html()) - result.data.count);
                        incrementNotificationsCount('.menu-notification-badge.chat-menu-count', (-parseInt(result.data.count)));
                        messenger.reloadContactsList();
                    }
                });
            }
        });
    },

    /**
     * Checks if user already has a conversation with certain user
     * @param contactID
     * @returns {boolean}
     */
    isExistingContact: function(contactID){
        // Search if contact is present
        let isNewContact = false;
        $.map(messenger.state.contacts,function (contact) {
            if(contactID === contact.contactID){
                isNewContact = true;
            }
        });
        return isNewContact;
    },

    /**
     * Reloads conversation list
     */
    reloadContactsList: function () {
        let contactsHtml = '';
        $.each( messenger.state.contacts, function( key, value ) {
            contactsHtml += contactElement(value);
        });
        if(messenger.state.contacts.length > 0){
            $('.conversations-list').html('<div class="row">'+contactsHtml+'</div>');
        }
        else{
            $('.conversations-list').html(noContactsLabel());
        }
        $('.contact-'+messenger.state.activeConversationUserID).addClass('contact-active');
        messenger.applyContactListFilters();
    },

    /**
     * Reloads convesation header
     */
    reloadConversationHeader: function(){
        if(typeof messenger.state.conversation[0] !== 'undefined'){
            const contact = messenger.state.conversation[0];
            const userID = (contact.receiver_id !== messenger.state.activeConversationUserID ? contact.sender.id : contact.receiver.id);
            const username = (contact.receiver_id !== messenger.state.activeConversationUserID ? contact.sender.username : contact.receiver.username);
            const avatar = (contact.receiver_id !== messenger.state.activeConversationUserID ? contact.sender.avatar : contact.receiver.avatar);
            const name = contact.receiver_id !== messenger.state.activeConversationUserID ? `${contact.sender.name} ` : `${contact.receiver.name}`;
            const profile = contact.receiver_id !== messenger.state.activeConversationUserID ? contact.sender.profileUrl : contact.receiver.profileUrl;
            messenger.state.activeConversationUser = {
                id: userID,
                username: username,
                name: name.trim(),
                avatar: avatar,
                profile: profile
            };
            $('.conversation-header').removeClass('d-none');
            $('.conversation-header-loading-box').addClass('d-none');
            $('.conversation-header-avatar').attr('src',avatar);
            $('.conversation-header-user').html(name);
            $('.conversation-profile-link').attr('href',profile);
            updateMobileChatHeader();

            $('.details-holder .unfollow-btn').unbind('click');
            $('.details-holder .block-btn').unbind('click');
            $('.details-holder .report-btn').unbind('click');

            $('.details-holder .unfollow-btn').on('click',function () {
                Lists.showListManagementConfirmation('unfollow', userID);
            });
            $('.details-holder .block-btn').on('click',function () {
                Lists.showListManagementConfirmation('block', userID);
            });
            $('.details-holder .report-btn').on('click',function () {
                Lists.showReportBox(userID,null);
            });
            if(contact.sender.canEarnMoney === false) {
                $('.details-holder .tip-btn').addClass('hidden');
            } else {
                $('.details-holder .tip-btn').attr('data-username','@'+username);
                $('.details-holder .tip-btn').attr('data-name',name);
                $('.details-holder .tip-btn').attr('data-avatar',avatar);
                $('.details-holder .tip-btn').attr('data-recipient-id',userID);
            }

        }
    },

    /**
     * Open private custom request modal for active user
     */
    openPrivateRequestModal: function(){
        const activeUser = messenger.state.activeConversationUser;
        const modal = document.getElementById('createCustomRequestModal');
        if(!activeUser || !modal){
            return false;
        }

        const creatorUsernameInput = document.getElementById('request_creator_username');
        const creatorIdInput = document.getElementById('request_creator_id');
        const selectedIndicator = document.getElementById('creator_selected_indicator');
        const selectedName = document.getElementById('selected_creator_name');

        if(creatorUsernameInput){
            creatorUsernameInput.value = activeUser.username || '';
        }
        if(creatorIdInput){
            creatorIdInput.value = activeUser.id || '';
        }
        if(selectedIndicator && selectedName){
            selectedName.textContent = activeUser.name + (activeUser.username ? ' (@' + activeUser.username + ')' : '');
            selectedIndicator.style.display = 'block';
        }

        const privateTypeInput = document.querySelector('input[name="type"][value="private"]');
        if(privateTypeInput){
            privateTypeInput.checked = true;
            privateTypeInput.dispatchEvent(new Event('change'));
        }

        if(typeof CustomRequest !== 'undefined' && typeof CustomRequest.showCreateModal === 'function'){
            CustomRequest.showCreateModal();
        }
    },

    /**
     * Reloads conversation
     */
    reloadConversation: function () {
        let conversationHtml = '';
        let lastDayKey = null;
        $.each( messenger.state.conversation, function( key, value ) {
            if (typeof msgDayKey === 'function' && value.created_at) {
                const dayKey = msgDayKey(value.created_at);
                if (dayKey && dayKey !== lastDayKey) {
                    conversationHtml += messageDateSeparator(value.created_at);
                    lastDayKey = dayKey;
                }
            }
            conversationHtml += messageElement(value);
        });
        $('.conversation-content').html(conversationHtml);

        // Navigating to last message or last paid mesage
        let urlParams = new URLSearchParams(window.location.search);
        // Scrolling to newly unlocked message if this redirect comes from a message-unlock payment
        if(urlParams.has('token') && !messenger.state.redirectedToMessage) {
            let token = '#m-'.concat(urlParams.get('token'));
            if($('.conversation-content .message-box').length && $('.conversation-content').find(token).length){
                let offset = $('.conversation-content').find(token).offset().top - $('.conversation-content').offset().top + $('.conversation-content').scrollTop();
                $(".conversation-content").animate({scrollTop: offset}, 'slow');
            }

            $('.conversation-content').find(token).animate({
                backgroundColor: "rgba(203,12,159,.2)",
            }, 1000).delay(2000).queue(function() {
                $('.conversation-content').find(token).animate({
                    backgroundColor: "rgba(0,0,0,0)",
                }, 1000).dequeue();
            });

            messenger.state.redirectedToMessage = true;
        } else {
            // Scrolling down to last message
            if($('.conversation-content .message-box').length){
                $(".conversation-content").animate({ scrollTop: $('.conversation-content')[0].scrollHeight + 100}, 800);
            }
        }
        $('.conversation-loading-box').addClass('d-none');
        messenger.initLinks();
        messenger.initMessengerGalleries();
    },

    /**
     * Method used for auto adjusting textarea message height on resize
     * @param el
     */
    textAreaAdjust: function(el) {
        const styles = window.getComputedStyle(el);
        const minHeight = parseFloat(styles.minHeight) || 42;
        const maxHeight = parseFloat(styles.maxHeight) || 120;
        el.style.height = 'auto';
        const scrollHeight = el.scrollHeight;
        const nextHeight = Math.min(Math.max(scrollHeight, minHeight), maxHeight);
        el.style.height = nextHeight + 'px';
        if (scrollHeight > maxHeight) {
            el.classList.add('is-scrollable');
            el.style.overflowY = 'auto';
        } else {
            el.classList.remove('is-scrollable');
            el.style.overflowY = 'hidden';
        }
    },

    /**
     * Resets the send new message text area height
     */
    resetTextAreaHeight: function(){
        const $input = $(".messageBoxInput");
        $input.each(function () {
            const minHeight = parseFloat(window.getComputedStyle(this).minHeight) || 42;
            this.style.height = minHeight + 'px';
            this.style.overflowY = 'hidden';
            this.classList.remove('is-scrollable');
        });
    },

    /**
     * Set currently active contact
     * @param userID
     */
    setActiveContact: function (userID) {
        $('.messageBoxInput').focus();
        $('#receiverID').val(userID);// TODO: Not used anymore
        messenger.state.receiverIDs = [userID];
        $('.contact-box').each(function (k,el) {
            $(el).removeClass('contact-active');
        });
        $('.contact-'+messenger.state.activeConversationUserID).addClass('contact-active');
    },

    /**
     * Clears up the new message field
     */
    clearMessageBox: function(){
        $(".messageBoxInput").val('');
    },

    /**
     * Updates the unread messages count
     * @param val
     * @returns {boolean}
     */
    updateUnreadMessagesCount: function (val) {
        $("#unseenMessages").html(val);
        return true;
    },

    /**
     * Marks conversation as being read
     * @param userID
     * @param type
     */
    markConversationAsRead: function (userID, type) {
        $.map(messenger.state.contacts,function (contact,k) {
            if(userID === contact.contactID){
                let newContact = contact;
                newContact.isSeen = type === 'read' ? 1 : 0;
                messenger.state.contacts[k] = newContact;
            }
        });
        // eslint-disable-next-line no-unused-vars
        let newContactsList = messenger.state.contacts; // These kinds of stuff should be immutable
    },

    /**
     * Appends latest message to the conversation
     * @param contactID
     * @param message
     */
    addLatestMessageToConversation: function (contactID, message) {
        // add latest contact details
        let contactKey = null;
        // eslint-disable-next-line no-unused-vars
        let contactObj = null;
        let newContact = null;
        $.map(messenger.state.contacts,function (contact,k) {
            if(contactID === contact.contactID){
                newContact = contact;
                contactKey = k;
                newContact.lastMessage = message.message;
                newContact.dateAdded = message.dateAdded;
                newContact.dateAdded = message.dateAdded;
                newContact.senderID = message.sender_id;
                newContact.lastMessageSenderID = message.sender_id;
                messenger.state.contacts[k] = newContact;
            }
        });

        let newContactsList = messenger.state.contacts; // These kinds of stuff should be immutable
        if(contactKey !== null){
            newContactsList.splice(contactKey, 1);
            newContactsList.unshift(newContact);
            messenger.state.contacts = newContactsList;
        }

    },

    /**
     * Globally instantiates all href links within a conversation
     */
    initLinks: function(){
        $('.conversation-content .message-bubble').html(function(i, text) {
            var body = text.replace(
                // eslint-disable-next-line no-useless-escape
                /\bhttps:\/\/([\w\.-]+\.)+[a-z]{2,}\/.+\b/gi,
                '<a target="_blank" class="text-white" href="$&">$&</a>'
            );
            return body.replace(
                // eslint-disable-next-line no-useless-escape
                /\bhttp:\/\/([\w\.-]+\.)+[a-z]{2,}\/.+\b/gi,
                '<a target="_blank" class="text-white" href="$&">$&</a>'
            );
        });
    },

    /**
     * Globally instantiates all message attachments and groups them into individual galleries
     */
    initMessengerGalleries: function(){
        $('.message-box').each(function (index, item) {
            if($(item).find('.attachments-holder').children().length > 0){
                mswpScanPage($(item),'mswp');
            }
        });
    },

    /**
     * Replaces message's newlines with html break lines
     * @param text
     * @returns {*}
     */
    parseMessage: function(text){
        return filterXSS(text.replaceAll('\n','<br/>'));
    },

    /**
     * Loads UI elements for loaded messenger
     */
    hideEmptyChatElements: function () {
        $('.conversation-writeup').removeClass('hidden');
        $('.no-contacts').addClass('hidden');
    },

    /**
     * Instantiates & applies selectize on the new conversation modal
     */
    initSelectizeUserList: function(){
        if(typeof Selectize !== 'undefined') {
            messenger.selectizeInstance = $('#select-repo').selectize({
                valueField: 'id',
                searchField: 'label',
                options: messengerVars.availableContacts,
                create: false,
                render: {
                    option: function (item, escape) {
                        return '<div>' +
                            '<img class="searchAvatar ml-3 my-1" src="' + escape(item.avatar) + '" alt="">' +
                            '<span class="name ml-2">' + escape(item.name) + '</span>' +
                            '</div>';
                    },
                    item: function (item, escape) {
                        return '<div>' +
                            '<img class="searchAvatar ml-1" src="' + escape(item.avatar) + '" alt="">' +
                            '<span class="name ml-2">' + escape(item.name) + '</span>' +
                            '</div>';
                    }
                },
                onChange(value) {
                    messenger.state.receiverIDs = value.map(function (x) {
                        return parseInt(x, 10);
                    });
                }
            });
        }
    },

    /**
     * Shows up new conversation modal in UI
     */
    showNewMessageDialog: function () {
        $('#messageModal').modal('show');
    },

    showSetPriceDialog: function () {
        $('#message-set-price-dialog').modal('show');
    },

    clearMessagePrice: function(){
        messenger.state.messagePrice = 5;
        messenger.state.isPaidMessage = false;
        $('#message-price').val(5);
        $('.message-price-lock').removeClass('d-none');
        $('.message-price-close').addClass('d-none');
        $('#message-set-price-dialog').modal('hide');
    },

    saveMessagePrice: function(){
        messenger.state.isPaidMessage = true;
        messenger.state.messagePrice = $('#message-price').val();
        if(!passesMinMaxPPVMessageLimits(messenger.state.messagePrice)){
            $('#message-price').addClass('is-invalid');
            return false;
        }
        $('.message-price-lock').addClass('d-none');
        $('.message-price-close').removeClass('d-none');
        $('#message-set-price-dialog').modal('hide');
        $('#message-price').removeClass('is-invalid');
    },

    /**
     * Parses messenger's attachment previews
     * @param file
     * @returns {string}
     */
    parseMessageAttachment: function(file){
        let attachmentsHtml = '';
        switch (file.type) {
        case 'avi':
        case 'mp4':
        case 'wmw':
        case 'mpeg':
        case 'm4v':
        case 'moov':
        case 'mov':
            attachmentsHtml = `
                <a href="${file.path}" rel="mswp" title="" class="mr-2 mt-2 no-long-press">
                    <div class="video-wrapper">
                     <video class="video-preview" src="${file.path}" width="150" height="150" controls controlsList="nodownload" autoplay muted></video>
                    </div>
                 </a>`;
            break;
        case 'mp3':
        case 'wav':
        case 'ogg':
            attachmentsHtml = `
                <a href="${file.path}" rel="mswp" title="" class="mr-2 mt-2 d-flex align-items-center no-long-press">
                    <div class="video-wrapper">
                         <audio id="video-preview" src="${file.path}" controls controlsList="nodownload" type="audio/mpeg" muted></audio>
                    </div>
                 </a>`;
            break;
        case 'png':
        case 'jpg':
        case 'jpeg':
            attachmentsHtml = `
                    <a href="${file.path}" rel="mswp" title="" class="no-long-press">
                        <img src="${file.thumbnail}" class="mr-2 mt-2">
                    </a>`;
            break;
        default:
            attachmentsHtml = `<img src="${file.thumbnail}" class="mr-2 mt-2">`;
            break;
        }
        return attachmentsHtml;
    },

    /**
     * Shows up message delete confirmation dialog
     * @param messageID
     */
    showMessageDeleteDialog: function(messageID){
        showDialog('message-delete-dialog');
        messenger.state.activeMessageID = messageID;
    },

    /**
     * Removes own comments
     */
    deleteMessage: function () {
        $.ajax({
            type: 'DELETE',
            dataType: 'json',
            url: app.baseUrl + '/my/messenger/delete/' + messenger.state.activeMessageID,
            success: function (result) {
                let element = $('*[data-messageid="'+messenger.state.activeMessageID+'"]');
                element.remove();
                hideDialog('message-delete-dialog');
                launchToast('success',trans('Success'),trans('Message removed'));
                if(result.isLastMessage === true){
                    messenger.fetchContacts(function () {
                        if(messenger.state.contacts.length >= 1){
                            messenger.state.activeConversationUserID = messenger.state.contacts[0].contactID;
                            messenger.fetchConversation(messenger.state.activeConversationUserID);
                        }
                        else{
                            messenger.fetchContacts();
                            $('.conversation-content').html(noMessagesLabel());
                            $('.conversation-writeup').addClass('hidden');
                            $('.conversation-header').addClass('d-none');
                        }

                    });
                }
                else{
                    messenger.fetchConversation(messenger.state.activeConversationUserID);
                }
            },
            error: function (result) {
                hideDialog('message-delete-dialog');
                launchToast('danger',trans('Error'),result.responseJSON.message);
            }
        });
    },


    /**
     * Inits the new conversation UI events
     */
    initNewConversationUI: function(){
        $('.new-conversation-toggle').on('click', function () {
            if(messenger.state.newConversationMode){
                messenger.closeNewConversationUI();
            }
            else{
                messenger.openNewConversationUI();
            }
        });

        $('.new-conversation-close').on('click', function () {
            messenger.closeNewConversationUI();
        });

        $('.new-conversation-toggle-all').on('click', function () {
            messenger.toggleAllContacts();
        });



    },

    /**
     * Filters visible contacts from the search field and list filter
     */
    initContactsSearch: function () {
        $(document).on('input', '#messenger-contacts-search', function () {
            messenger.applyContactListFilters();
        });
    },

    /**
     * Inits the contacts list filter dropdown
     */
    initListFilter: function () {
        $(document).on('click', '#messenger-list-filter-btn', function (e) {
            e.preventDefault();
            e.stopPropagation();
            const $menu = $('#messenger-list-filter-menu');
            const isOpen = $menu.hasClass('is-open');
            messenger.closeListFilterMenu();
            if (!isOpen) {
                $menu.removeAttr('hidden').addClass('is-open');
                $(this).attr('aria-expanded', 'true');
            }
        });

        $(document).on('click', '.messenger-list-filter-option', function (e) {
            e.preventDefault();
            const filter = $(this).data('filter');
            messenger.state.contactListFilter = filter;
            $('.messenger-list-filter-option').removeClass('is-active').attr('aria-selected', 'false');
            $(this).addClass('is-active').attr('aria-selected', 'true');
            $('.messenger-list-filter__label').text($(this).text().trim());
            messenger.closeListFilterMenu();
            messenger.applyContactListFilters();
        });

        $(document).on('click', function () {
            messenger.closeListFilterMenu();
        });

        $(document).on('click', '.messenger-list-filter-wrap', function (e) {
            e.stopPropagation();
        });
    },

    closeListFilterMenu: function () {
        $('#messenger-list-filter-menu').attr('hidden', true).removeClass('is-open');
        $('#messenger-list-filter-btn').attr('aria-expanded', 'false');
    },

    /**
     * Mobile back navigation and header search shortcut
     */
    initMobileNavigation: function () {
        $(document).on('click', '#messenger-conversation-back', function (e) {
            e.preventDefault();
            messenger.backToList();
        });

        $(document).on('click', '#mobile-app-bar-search-open', function () {
            if (!window.location.pathname.includes('/my/messenger')) {
                return;
            }
            const $search = $('#messenger-contacts-search');
            if ($search.length) {
                $search.trigger('focus');
            }
        });
    },

    /**
     * Returns to the full-screen contact list on mobile
     */
    backToList: function () {
        messenger.state.activeConversationUserID = null;
        messenger.state.activeConversationUser = null;
        messenger.state.conversation = [];
        $('.contact-box').removeClass('contact-active active');
        $('.conversation-header').addClass('d-none');
        $('.conversation-content').html(noMessagesLabel());
        $('.conversation-writeup').addClass('hidden');
        setMessengerLayoutMode(false);
    },

    applyContactListFilters: function () {
        const query = ($('#messenger-contacts-search').val() || '').toLowerCase().trim();
        const filter = messenger.state.contactListFilter || 'all';
        let visibleCount = 0;

        $('.conversations-list .contact-box').each(function () {
            const $box = $(this);
            const $row = $box.closest('[class*="col-"]').length ? $box.closest('[class*="col-"]') : $box;
            const name = $box.find('.contact-name').text().toLowerCase();
            const message = $box.find('.contact-message').text().toLowerCase();
            const matchesSearch = !query || name.includes(query) || message.includes(query);
            const isUnread = String($box.data('contact-unread')) === '1';
            const matchesFilter = filter === 'all' || (filter === 'unread' && isUnread);
            const visible = matchesSearch && matchesFilter;
            $row.toggle(visible);
            if (visible) {
                visibleCount += 1;
            }
        });

        const $emptyFilter = $('.messenger-list-empty-filter');
        if (visibleCount === 0 && $('.conversations-list .contact-box').length > 0) {
            if (!$emptyFilter.length) {
                $('.conversations-list').append(`<p class="messenger-list-empty-filter">${trans('No conversations match your filters.')}</p>`);
            }
        } else {
            $emptyFilter.remove();
        }
    },

    /**
     * Closes the new conversation UI
     * @returns {boolean}
     */
    closeNewConversationUI: function () {
        $('.conversation-header').removeClass('d-none');
        $('.new-conversation-header').addClass('d-none');
        if(messenger.selectizeInstance !== null){
            messenger.selectizeInstance[0].selectize.clear();
        }
        if(messenger.state.contacts.length === 0 && messengerVars.lastContactID === 0){
            $('.conversation-content').html(noMessagesLabel());
            $('.conversation-writeup').addClass('hidden');
            $('.conversation-header').addClass('d-none');
        }
        else{
            messenger.reloadConversation();
        }
        messenger.state.newConversationMode = false;
        return true;
    },

    /**
     * Toggles all contacts in new create message dialog | mass message
     */
    toggleAllContacts: function(){
        if(messenger.state.newConversationSelectAllToggle === false){
            var el = messenger.selectizeInstance[0].selectize;
            var optKeys = Object.keys(el.options);
            let i = 0;
            optKeys.forEach(function (key) {
                if(i > 50){return false;}
                el.addItem(key);
                i++;
            });
            messenger.state.newConversationSelectAllToggle = true;
        }
        else{
            messenger.selectizeInstance[0].selectize.clear();
            messenger.state.newConversationSelectAllToggle = false;
        }
    },

    /**
     * Opens up the new conversation dialog
     * @returns {boolean}
     */
    openNewConversationUI: function () {
        if(messengerVars.availableContacts.length === 0) {
            return false;
        }
        messenger.hideEmptyChatElements();
        $('.conversation-header').addClass('d-none');
        $('.new-conversation-header').removeClass('d-none');
        $('.conversation-content').html('');
        messenger.state.newConversationMode = true;
        return true;
    },

    /**
     * Disabling right for posts ( if site wise setting is set to do it )
     */
    disableMesagesRightClick: function () {
        $(".attachments-holder").unbind('contextmenu');
        $(".attachments-holder").on("contextmenu",function(){
            return false;
        });
        $(".post-media, .pswp__item").unbind('contextmenu');
        $(".post-media, .pswp__item").on("contextmenu",function(){
            return false;
        });
        bindNoLongPressEvents();
    },

};

// Initialize custom scrollbar for conversations list
function initConversationsScrollbar() {
    try {
        if (typeof $.fn !== 'undefined' && typeof $.fn.mCustomScrollbar !== 'undefined') {
            $('.conversations-list').mCustomScrollbar({
                theme: "minimal-dark",
                scrollInertia: 200
            });
        } else {
            console.log("mCustomScrollbar not available for messenger, using fallback");
            $('.conversations-list').css({
                'overflow-y': 'auto',
                'max-height': 'calc(100vh - 150px)'
            });
        }
    } catch (e) {
        console.error("Error initializing conversations scrollbar:", e);
        $('.conversations-list').css({
            'overflow-y': 'auto',
            'max-height': 'calc(100vh - 150px)'
        });
    }
}

// Destroy custom scrollbar
function destroyConversationsScrollbar() {
    try {
        if (typeof $.fn !== 'undefined' && typeof $.fn.mCustomScrollbar !== 'undefined') {
            $('.conversations-list').mCustomScrollbar("destroy");
        }
    } catch (e) {
        console.error("Error destroying conversations scrollbar:", e);
    }
}
