/**
 *
 * Messages Elements
 *
 */
"use strict";
/* global app, user, messengerVars, trans, filterXSS, messenger, getWebsiteFormattedAmount  */

/**
 * Messenger contact component
 * @param contact
 * @returns {string}
 */
// eslint-disable-next-line no-unused-vars
function contactElement(contact){
    const avatar = contact.receiverID === user.user_id ? contact.senderAvatar : contact.receiverAvatar;
    const name = contact.receiverID === user.user_id ? contact.senderName : contact.receiverName;
    const isUnread = contact.lastMessageSenderID !== user.user_id && contact.isSeen === 0;
    const youPrefix = contact.lastMessageSenderID === user.user_id ? `${trans('You')}: ` : '';
    const timeLabel = contact.created_at !== null ? contact.created_at : '';
    return `
      <div class="col-12 contact-box contact-${contact.contactID}${isUnread ? ' contact-box--unread' : ''}" data-contact-unread="${isUnread ? '1' : '0'}" onclick="messenger.fetchConversation(${contact.contactID})">
        <div class="contact-box__avatar-wrap">
            <img src="${ avatar }" class="contact-avatar rounded-circle" alt=""/>
            ${isUnread ? '<span class="contact-unread-dot" aria-hidden="true"></span>' : ''}
        </div>
        <div class="contact-box__meta">
            <div class="contact-box__row">
                <span class="contact-name">${filterXSS(name)}</span>
                ${timeLabel ? `<span class="contact-time">${filterXSS(timeLabel)}</span>` : ''}
            </div>
            <p class="contact-preview">
                <span class="contact-preview__you">${youPrefix}</span>
                <span class="contact-message">${filterXSS(contact.lastMessage)}</span>
            </p>
        </div>
      </div>
    `;
}

/**
 * Messenger message component
 * @param message
 * @returns {string}
 */
// eslint-disable-next-line no-unused-vars
function messageElement(message){
    let isSender = false;
    if(parseInt(message.sender_id) === parseInt(user.user_id)){
        isSender = true;
    }

    let attachmentsHtml = '';
    message.attachments.map(function (file) {
        attachmentsHtml += messenger.parseMessageAttachment(file);
    });

    /* Paid message preview */
    if(message.hasUserUnlockedMessage === false && message.price > 0 && !isSender){
        return `
          <div class="col-12 no-gutters pt-1 pb-1 message-box px-0" data-messageid="${message.id}" id="m-${message.id}">
                    <div class="m-0 paid-message-box message-box text-break alert ${isSender ? 'alert-primary text-white' : 'alert-default'}">
                        <div class="col-12 d-flex mb-2 ${isSender ? 'sender d-flex flex-row-reverse pr-1' : 'pl-0'}">
                            ${message.message === null ? '' : messenger.parseMessage(message.message)}
                        </div>
                        <div class="d-flex justify-content-center">
                        ${lockedMessagePreview({'id' : message.id, 'price': message.price},message.sender)}
                        </div>
                    </div>
                </div>
          </div>
        `;
    }
    else{
        /* Regular message preview */
        const hasText = message.message !== null && message.message !== '';
        return `
          <div class="col-12 no-gutters pt-1 pb-1 message-box px-0 ${isSender ? 'message-box--sender' : 'message-box--receiver'}" data-messageid="${message.id}" id="m-${message.id}">
            ${hasText ? messageBubble(isSender, message) : ''}
            ${messageAttachments(isSender, attachmentsHtml, message)}
            ${!hasText ? messageMeta(isSender, message) : ''}
          </div>
    `;
    }

}

/**
 * Message bubble component
 * @param isSender
 * @param message
 * @returns {string}
 */
function messageBubble(isSender, message) {
    const avatar = (!isSender && message.sender && message.sender.avatar)
        ? `<img class="message-avatar" src="${message.sender.avatar}" alt="" />`
        : '';

    return `
        <div class="message-bubble-group ${isSender ? 'message-bubble-group--sender' : 'message-bubble-group--receiver'}">
            <div class="message-bubble-row ${isSender ? 'message-bubble-row--sender' : 'message-bubble-row--receiver'}">
                ${avatar}
                <div class="message-bubble-col">
                    <div class="m-0 message-bubble alert ${isSender ? 'alert-primary text-white' : 'alert-default'}">${messenger.parseMessage(message.message)}</div>
                    ${messageMeta(isSender, message)}
                </div>
                ${messageActions(isSender, message)}
            </div>
        </div>
    `;
}

/**
 * Formats a message timestamp into a short local time (e.g. 9:24 AM)
 * @param ts
 * @returns {string}
 */
function formatMsgTime(ts) {
    if (!ts) {
        return '';
    }
    const d = new Date(String(ts).replace(' ', 'T'));
    if (isNaN(d.getTime())) {
        return '';
    }
    return d.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
}

/**
 * Returns a day bucket key for a timestamp (used for date separators)
 * @param ts
 * @returns {string}
 */
// eslint-disable-next-line no-unused-vars
function msgDayKey(ts) {
    if (!ts) {
        return '';
    }
    const d = new Date(String(ts).replace(' ', 'T'));
    if (isNaN(d.getTime())) {
        return '';
    }
    return d.getFullYear() + '-' + d.getMonth() + '-' + d.getDate();
}

/**
 * Human friendly day label (Today / Yesterday / date)
 * @param ts
 * @returns {string}
 */
function msgDayLabel(ts) {
    const d = new Date(String(ts).replace(' ', 'T'));
    if (isNaN(d.getTime())) {
        return '';
    }
    const today = new Date();
    const yesterday = new Date();
    yesterday.setDate(today.getDate() - 1);
    const sameDay = (a, b) => a.getFullYear() === b.getFullYear() && a.getMonth() === b.getMonth() && a.getDate() === b.getDate();
    if (sameDay(d, today)) {
        return trans('Today');
    }
    if (sameDay(d, yesterday)) {
        return trans('Yesterday');
    }
    const opts = { month: 'short', day: 'numeric' };
    if (d.getFullYear() !== today.getFullYear()) {
        opts.year = 'numeric';
    }
    return d.toLocaleDateString([], opts);
}

/**
 * Date separator chip between messages of different days
 * @param ts
 * @returns {string}
 */
// eslint-disable-next-line no-unused-vars
function messageDateSeparator(ts) {
    const label = msgDayLabel(ts);
    if (!label) {
        return '';
    }
    return `<div class="message-day-separator"><span>${label}</span></div>`;
}

/**
 * Meta row under a message (timestamp + sent indicator)
 * @param isSender
 * @param message
 * @returns {string}
 */
function messageMeta(isSender, message) {
    const time = formatMsgTime(message.created_at);
    if (!time) {
        return '';
    }
    const status = isSender
        ? `<span class="message-meta__status" aria-hidden="true"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg></span>`
        : '';
    return `<div class="message-meta ${isSender ? 'message-meta--sender' : 'message-meta--receiver'}"><span class="message-meta__time">${time}</span>${status}</div>`;
}

function messageAttachments(isSender, attachmentsHtml, message){
    return `
             <div class="col-12 d-flex  ${isSender ? 'sender d-flex flex-row-reverse pr-1' : 'pl-0'}">
                <div class="attachments-holder row no-gutters flex-row-reverse">
                    ${attachmentsHtml}
                </div>
                ${attachmentsHtml.length ? messageActions(isSender, message) : ''}
            </div>
     `;
}

function messageActions(isSender, message){
    return `
        <div class="d-flex message-actions-wrapper ${isSender ? 'mr-2' : 'ml-2'}">

            ${isSender ? `
                <div class="d-flex justify-content-center align-items-center pointer-cursor">
                    <div class="to-tooltip message-action-button d-flex justify-content-center align-items-center"  data-placement="top" title="${trans('Delete')}" onClick="messenger.showMessageDeleteDialog(${message.id})">
                        <ion-icon name="trash-outline"></ion-icon>
                    </div>
                </div>
            ` : ``}


             ${isSender === false ? `
                <div class="d-flex justify-content-center align-items-center pointer-cursor">
                    <div class="to-tooltip message-action-button d-flex justify-content-center align-items-center"  data-placement="top" title="${trans('Report')}" onClick="Lists.showReportBox(${message.sender_id}, null, ${message.id}, null);">
                        <ion-icon name="flag-outline"></ion-icon>
                    </div>
                </div>
            ` : ``}

           ${isSender && message.price > 0 ? `
            <div class="d-flex justify-content-center align-items-center">
                <div class="to-tooltip message-action-button d-flex justify-content-center align-items-center"  data-placement="top" title="${trans('Paid message')}">
                    <ion-icon name="cash-outline"></ion-icon>
                 </div>
            </div>
        ` : ``}
      </div>
    `;
}

/**
 * Locked message preview element
 * @param messageData
 * @param senderData
 * @returns {string}
 */
function lockedMessagePreview(messageData, senderData) {
    return `
            <div class="card ${app.theme === 'light' ? 'bg-gradient-faded-light-vertical' : 'bg-gradient-faded-dark-vertical'}">
              <div>
              <div class="lockedPreviewWrapper">
                  <img class="card-img" src="${messengerVars.lockedMessageSVGPath}" >
              </div>
                  <div class="card-img-overlay d-flex flex-column-reverse">
                           ${lockedMessagePaymentButton(messageData, senderData)}
                    </div>
                  </div>
              </div>
            </div>
`;
}

/**
 * Locked message payment button
 * @param messageData
 * @param senderData
 * @returns {string}
 */
function lockedMessagePaymentButton(messageData, senderData) {
    let modalData = `
                        data-toggle="modal"
                        data-target="#checkout-center"
                        data-type="message-unlock"
                        data-recipient-id="${senderData.id}"
                        data-amount="${messageData.price}"
                        data-first-name="${user.billingData.first_name}"
                        data-last-name="${user.billingData.last_name}"
                        data-billing-address="${user.billingData.billing_address}"
                        data-country="${user.billingData.country}"
                        data-city="${user.billingData.city}"
                        data-state="${user.billingData.state}"
                        data-postcode="${user.billingData.postcode}"
                        data-available-credit="${user.billingData.credit}"
                        data-username="${senderData.username}"
                        data-name="${senderData.first_name}"
                        data-avatar="${senderData.avatar}"
                        data-message-id="${messageData.id}"
    `;

    if(senderData.canEarnMoney === false) {
        modalData = `
            data-placement="top"
            title="${trans('This creator cannot earn money yet')}"
        `;
    }

    return `
                <button class="btn btn-round btn-primary btn-block d-flex align-items-center justify-content-center justify-content-lg-between mt-2 mb-0 to-tooltip" ${modalData}>
                <span class="d-none d-md-block">${trans('Locked message')}</span>  <span>${trans('Unlock for')} ${getWebsiteFormattedAmount(messageData.price)}</span>
                </button>
    `;
}


// eslint-disable-next-line no-unused-vars
function noMessagesLabel() {
    return `
        <div class="messenger-thread-empty">
            <div class="messenger-thread-empty__icon" aria-hidden="true">
                <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                </svg>
            </div>
            <p class="messenger-thread-empty__title">${trans('You got no messages yet.')}</p>
            <p class="messenger-thread-empty__text">${trans("Say 'Hi!' to someone!")}</p>
        </div>
    `;
}

// eslint-disable-next-line no-unused-vars
function noContactsLabel() {
    const bannerUrl = (typeof app !== 'undefined' && app.baseUrl ? app.baseUrl : '') + '/img/notifications-empty-banner.png';
    return `
        <div class="messenger-empty-hero" style="--msgr-empty-banner: url('${bannerUrl}')">
            <div class="messenger-empty-hero__content">
                <p class="messenger-empty-hero__eyebrow">${trans('Nothing to see here yet')}</p>
                <p class="messenger-empty-hero__title">${trans('No conversations yet!')}</p>
            </div>
            <div class="messenger-empty-hero__icon" aria-hidden="true">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                </svg>
            </div>
        </div>
    `;
}
