/**
 * Admin panel JS functions
 */
"use strict";
/* global toastr, site_settings, appUrl, Pickr */

$(function () {
    const location = window.location.href;
    if(location.indexOf('admin/settings') >= 0){
        Admin.settingsPageInit();
        // eslint-disable-next-line no-undef
        Admin.emailSettingsSwitch(site_settings["emails.driver"]);
        // eslint-disable-next-line no-undef
        Admin.storageSettingsSwitch(site_settings["storage.driver"]);
        Admin.socketsSettingsSwitch(site_settings["websockets.driver"]);
        Admin.videosSettingsSwitch(site_settings["transcoding.driver"]);
        Admin.initActiveTabOnSaveEvents();

        Admin.setCustomSettingsTabEvents();
        Admin.paymentsSettingsSubTabSwitch('general');
        Admin.mediaSettingsSubTabSwitch('general');

        // CTRL+S Override
        $(document).keydown(function(e) {
            var key = undefined;
            var possible = [ e.key, e.keyIdentifier, e.keyCode, e.which ];
            while (key === undefined && possible.length > 0) {
                key = possible.pop();
            }
            if (key && (key === '115' || key === '83' ) && (e.ctrlKey || e.metaKey) && !(e.altKey)) {
                e.preventDefault();
                $('.save-settings-form').submit();
                return false;
            }
            return true;
        });

        Admin.initThemeColorPickers();

    }

    // master
    var appContainer = document.querySelector('.app-container'),
        sidebar = appContainer.querySelector('.side-menu'),
        navbar = appContainer.querySelector('nav.navbar.navbar-top'),
        loader = document.getElementById('voyager-loader'),
        hamburgerMenu = document.querySelector('.hamburger'),
        sidebarTransition = sidebar.style.transition,
        navbarTransition = navbar.style.transition,
        containerTransition = appContainer.style.transition;

    sidebar.style.WebkitTransition = sidebar.style.MozTransition = sidebar.style.transition =
        appContainer.style.WebkitTransition = appContainer.style.MozTransition = appContainer.style.transition =
            navbar.style.WebkitTransition = navbar.style.MozTransition = navbar.style.transition = 'none';

    if (sidebar && sidebar.classList.contains('jf-sidebar')) {
        appContainer.classList.remove('expanded');
        sidebar.classList.remove('is-expanded', 'is-hover', 'is-revealing');
        if (hamburgerMenu) {
            hamburgerMenu.classList.remove('is-active', 'no-animation');
        }
    } else if (window.innerWidth > 768 && window.localStorage && window.localStorage['voyager.stickySidebar'] === 'true') {
        appContainer.className += ' expanded no-animation';
        if (loader) {
            loader.style.left = (sidebar.clientWidth / 2) + 'px';
        }
        if (hamburgerMenu) {
            hamburgerMenu.className += ' is-active no-animation';
        }
    }

    navbar.style.WebkitTransition = navbar.style.MozTransition = navbar.style.transition = navbarTransition;
    sidebar.style.WebkitTransition = sidebar.style.MozTransition = sidebar.style.transition = sidebarTransition;
    appContainer.style.WebkitTransition = appContainer.style.MozTransition = appContainer.style.transition = containerTransition;

    Admin.initModernSidebar(appContainer, sidebar, hamburgerMenu);
    Admin.initAdminModals();

    // login
    if(location.indexOf('admin/login') >= 0){
        var btn = document.querySelector('button[type="submit"]');
        var form = document.forms[0];
        var email = document.querySelector('[name="email"]');
        var password = document.querySelector('[name="password"]');
        btn.addEventListener('click', function(ev){
            if (form.checkValidity()) {
                btn.querySelector('.signingin').className = 'signingin';
                btn.querySelector('.signin').className = 'signin hidden';
            } else {
                ev.preventDefault();
            }
        });
        email.focus();
        document.getElementById('emailGroup').classList.add("focused");

        // Focus events for email and password fields
        email.addEventListener('focusin', function(){
            document.getElementById('emailGroup').classList.add("focused");
        });
        email.addEventListener('focusout', function(){
            document.getElementById('emailGroup').classList.remove("focused");
        });

        password.addEventListener('focusin', function(){
            document.getElementById('passwordGroup').classList.add("focused");
        });
        password.addEventListener('focusout', function(){
            document.getElementById('passwordGroup').classList.remove("focused");
        });
    }

    // // Withdrawals
    if(location.indexOf('admin/withdrawals') >= 0) {
        Admin.processWithdrawalApproval();
    }


});

var Admin = {
    approveWithdrawalId: '',
    activeSettingsTab : '',
    themeColors: {
        theme_color_code: '#cb0c9f',
        theme_gradient_from: '#7928CA',
        theme_gradient_to: '#FF0080'
    },

    /**
     * Modern admin sidebar — collapsed by default, expands on hover or hamburger pin
     */
    initModernSidebar: function(appContainer, sidebar, hamburgerMenu) {
        if (!sidebar || !sidebar.classList.contains('jf-sidebar')) {
            return;
        }

        appContainer.classList.add('jf-sidebar-ready');
        sidebar.classList.remove('sidebar-inverse', 'admin-sidebar-modern');

        if (sidebar.classList.contains('ps')) {
            sidebar.style.overflow = 'hidden';
            sidebar.scrollLeft = 0;
        }

        var psContent = sidebar.querySelector('.ps__content');
        if (psContent) {
            psContent.style.overflow = 'hidden';
            psContent.scrollLeft = 0;
        }

        appContainer.classList.remove('expanded');
        sidebar.classList.remove('is-expanded', 'is-hover', 'is-revealing');
        if (hamburgerMenu) {
            hamburgerMenu.classList.remove('is-active');
        }
        if (window.localStorage) {
            window.localStorage.setItem('voyager.stickySidebar', 'false');
        }

        sidebar.addEventListener('mouseenter', function() {
            if (!appContainer.classList.contains('expanded')) {
                sidebar.classList.add('is-hover');
            }
        });

        sidebar.addEventListener('mouseleave', function() {
            if (!appContainer.classList.contains('expanded')) {
                var userMenu = sidebar.querySelector('.jf-sidebar__user-menu');
                if (userMenu && userMenu.classList.contains('is-open')) {
                    if (typeof userMenu._scheduleUserMenuCheck === 'function') {
                        userMenu._scheduleUserMenuCheck();
                    }
                    return;
                }
                sidebar.classList.remove('is-hover');
            }
        });

        if (hamburgerMenu) {
            hamburgerMenu.addEventListener('click', function() {
                setTimeout(function() {
                    var userMenu = sidebar.querySelector('.jf-sidebar__user-menu');
                    if (userMenu && typeof userMenu._closeUserMenu === 'function') {
                        userMenu._closeUserMenu();
                    }

                    if (appContainer.classList.contains('expanded')) {
                        sidebar.classList.add('is-expanded');
                        Admin.triggerSidebarReveal(sidebar);
                    } else {
                        sidebar.classList.remove('is-expanded');
                        sidebar.classList.remove('is-revealing');
                    }
                    Admin.syncSidebarPill(sidebar);
                }, 0);
            });
        }

        if (sidebar.classList.contains('is-expanded')) {
            setTimeout(function() {
                Admin.triggerSidebarReveal(sidebar);
            }, 80);
        }

        Admin.initSidebarMicrointeractions(appContainer, sidebar);
        Admin.initSidebarUserMenu(appContainer, sidebar);
    },

    initSidebarUserMenu: function(appContainer, sidebar) {
        var menuWrap = sidebar.querySelector('.jf-sidebar__user-menu');
        if (!menuWrap) {
            return;
        }

        var btn = menuWrap.querySelector('.jf-sidebar__settings-btn');
        var dropdown = menuWrap.querySelector('.jf-sidebar__user-dropdown');
        if (!btn || !dropdown) {
            return;
        }

        var outsideClickHandler = null;
        var leaveCheckTimer = null;

        function isMenuOpen() {
            return menuWrap.classList.contains('is-open');
        }

        function pinSidebarExpanded() {
            sidebar.classList.add('is-hover', 'is-user-menu-open');
        }

        function clearLeaveCheck() {
            if (leaveCheckTimer) {
                window.clearTimeout(leaveCheckTimer);
                leaveCheckTimer = null;
            }
        }

        function isPointerOverMenu() {
            return sidebar.matches(':hover')
                || menuWrap.matches(':hover')
                || dropdown.matches(':hover');
        }

        function scheduleLeaveCheck() {
            clearLeaveCheck();
            leaveCheckTimer = window.setTimeout(function() {
                leaveCheckTimer = null;
                if (!isMenuOpen()) {
                    return;
                }
                if (isPointerOverMenu()) {
                    pinSidebarExpanded();
                    return;
                }
                closeMenu();
            }, 160);
        }

        function closeMenu() {
            if (!isMenuOpen()) {
                return;
            }

            clearLeaveCheck();

            if (outsideClickHandler) {
                document.removeEventListener('mousedown', outsideClickHandler, true);
                outsideClickHandler = null;
            }

            menuWrap.classList.remove('is-open');
            sidebar.classList.remove('is-user-menu-open');
            btn.setAttribute('aria-expanded', 'false');
            dropdown.classList.remove('is-open');
            dropdown.style.cssText = '';

            if (dropdown.parentElement !== menuWrap) {
                menuWrap.appendChild(dropdown);
            }

            if (!appContainer.classList.contains('expanded')) {
                sidebar.classList.remove('is-hover');
            }
        }

        function positionDropdown() {
            var rect = btn.getBoundingClientRect();
            var menuWidth = dropdown.offsetWidth || 210;
            var menuHeight = dropdown.offsetHeight || 180;
            var gap = 8;
            var top = rect.top - menuHeight - gap;
            var left = Math.max(8, rect.right - menuWidth);

            if (top < 8) {
                top = rect.bottom + gap;
            }

            dropdown.style.position = 'fixed';
            dropdown.style.top = Math.round(top) + 'px';
            dropdown.style.left = Math.round(left) + 'px';
            dropdown.style.right = 'auto';
            dropdown.style.bottom = 'auto';
            dropdown.style.width = Math.round(menuWidth) + 'px';
            dropdown.style.zIndex = '100060';
        }

        function openMenu() {
            menuWrap.classList.add('is-open');
            pinSidebarExpanded();
            btn.setAttribute('aria-expanded', 'true');
            dropdown.classList.add('is-open');
            dropdown.style.visibility = 'hidden';
            dropdown.style.display = 'block';

            document.body.appendChild(dropdown);
            positionDropdown();
            dropdown.style.visibility = '';

            outsideClickHandler = function(event) {
                if (menuWrap.contains(event.target) || dropdown.contains(event.target)) {
                    return;
                }
                closeMenu();
            };

            window.setTimeout(function() {
                document.addEventListener('mousedown', outsideClickHandler, true);
            }, 0);
        }

        closeMenu();

        btn.addEventListener('mousedown', function(event) {
            event.stopPropagation();
        });

        btn.addEventListener('click', function(event) {
            event.preventDefault();
            event.stopPropagation();

            if (isMenuOpen()) {
                closeMenu();
                return;
            }

            openMenu();
        });

        dropdown.addEventListener('click', function(event) {
            if (event.target.closest('a, button')) {
                closeMenu();
            }
        });

        sidebar.addEventListener('mouseleave', function() {
            if (!appContainer.classList.contains('expanded') && isMenuOpen()) {
                scheduleLeaveCheck();
            }
        });

        dropdown.addEventListener('mouseenter', function() {
            clearLeaveCheck();
            pinSidebarExpanded();
        });

        dropdown.addEventListener('mouseleave', scheduleLeaveCheck);
        menuWrap.addEventListener('mouseleave', scheduleLeaveCheck);

        window.addEventListener('resize', function() {
            if (isMenuOpen()) {
                positionDropdown();
            }
        });

        window.addEventListener('scroll', function() {
            if (isMenuOpen()) {
                closeMenu();
            }
        }, true);

        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape' && isMenuOpen()) {
                closeMenu();
            }
        });

        menuWrap._closeUserMenu = closeMenu;
        menuWrap._scheduleUserMenuCheck = scheduleLeaveCheck;
        Admin.closeSidebarUserMenu = closeMenu;
    },

    triggerSidebarReveal: function(sidebar) {
        if (!sidebar) {
            return;
        }

        sidebar.classList.add('is-revealing');
        clearTimeout(Admin._sidebarRevealTimer);
        Admin._sidebarRevealTimer = setTimeout(function() {
            sidebar.classList.remove('is-revealing');
        }, 920);
    },

    initSidebarMicrointeractions: function(appContainer, sidebar) {
        var nav = sidebar.querySelector('.jf-sidebar__nav');
        if (!nav) {
            return;
        }

        var pill = document.createElement('div');
        pill.className = 'jf-sidebar__active-pill';
        pill.setAttribute('aria-hidden', 'true');
        nav.insertBefore(pill, nav.firstChild);

        function isExpandedView() {
            return sidebar.classList.contains('is-expanded')
                || sidebar.classList.contains('is-hover')
                || appContainer.classList.contains('expanded')
                || sidebar.matches(':hover');
        }

        function triggerReveal() {
            Admin.triggerSidebarReveal(sidebar);
        }

        Admin.syncSidebarPill = function(targetSidebar) {
            var side = targetSidebar || sidebar;
            var navEl = side.querySelector('.jf-sidebar__nav');
            var pillEl = navEl && navEl.querySelector('.jf-sidebar__active-pill');
            var active = navEl && navEl.querySelector('.jf-sidebar__link.is-active');

            if (!pillEl || !navEl) {
                return;
            }

            if (!active || !isExpandedView()) {
                side.classList.remove('is-pill-visible');
                pillEl.style.opacity = '0';
                return;
            }

            var navRect = navEl.getBoundingClientRect();
            var linkRect = active.getBoundingClientRect();
            var x = linkRect.left - navRect.left;
            var y = linkRect.top - navRect.top + navEl.scrollTop;

            side.classList.add('is-pill-visible');
            pillEl.style.left = x + 'px';
            pillEl.style.width = linkRect.width + 'px';
            pillEl.style.height = linkRect.height + 'px';
            pillEl.style.transform = 'translateY(' + y + 'px)';
            pillEl.style.opacity = '1';
        };

        function bindRipple(link) {
            link.addEventListener('pointerdown', function(event) {
                if (event.button !== 0) {
                    return;
                }

                var rect = link.getBoundingClientRect();
                var size = Math.max(rect.width, rect.height) * 1.6;
                var ripple = document.createElement('span');
                ripple.className = 'jf-sidebar__ripple';
                ripple.style.width = size + 'px';
                ripple.style.height = size + 'px';
                ripple.style.left = (event.clientX - rect.left - size / 2) + 'px';
                ripple.style.top = (event.clientY - rect.top - size / 2) + 'px';
                link.appendChild(ripple);
                ripple.addEventListener('animationend', function() {
                    ripple.remove();
                });
            });
        }

        nav.querySelectorAll('.jf-sidebar__link').forEach(function(link) {
            bindRipple(link);
        });

        sidebar.addEventListener('mouseenter', function() {
            sidebar.classList.add('is-hover');
            triggerReveal();
            setTimeout(function() {
                Admin.syncSidebarPill(sidebar);
            }, 40);
        });

        sidebar.addEventListener('mouseleave', function() {
            var userMenu = sidebar.querySelector('.jf-sidebar__user-menu');
            if (userMenu && userMenu.classList.contains('is-open') && typeof userMenu._scheduleUserMenuCheck === 'function') {
                userMenu._scheduleUserMenuCheck();
                return;
            }
            sidebar.classList.remove('is-hover');
            sidebar.classList.remove('is-revealing');
            Admin.syncSidebarPill(sidebar);
        });

        nav.addEventListener('scroll', function() {
            Admin.syncSidebarPill(sidebar);
        }, { passive: true });

        window.addEventListener('resize', function() {
            Admin.syncSidebarPill(sidebar);
        });

        setTimeout(function() {
            Admin.syncSidebarPill(sidebar);
        }, 120);
    },

    syncSidebarPill: function() {},

    /**
     * Bootstrap modals must live on body — nested stacking contexts
     * (e.g. side-body isolation) otherwise render below the backdrop.
     */
    initAdminModals: function() {
        function moveModalsToBody() {
            document.querySelectorAll('.modal').forEach(function(modal) {
                if (modal.parentElement !== document.body) {
                    document.body.appendChild(modal);
                }
            });
        }

        moveModalsToBody();

        $(document).on('show.bs.modal', '.modal', function() {
            if (this.parentElement !== document.body) {
                document.body.appendChild(this);
            }
        });
    },

    initActiveTabOnSaveEvents: function(){
        $('.save-settings-form').on('submit',function(evt){
            // code
            if(Admin.activeSettingsTab === 'payments-processors' || Admin.activeSettingsTab === 'payments-general' || Admin.activeSettingsTab === 'payments-invoices' || Admin.activeSettingsTab === 'payments-withdrawals') {
                $('.setting_tab').val('Payments');
            }

            if(Admin.activeSettingsTab === 'media-general' || Admin.activeSettingsTab === 'media-videos') {
                $('.setting_tab').val('Media');
            }

            if(Admin.activeSettingsTab === 'colors'){
                evt.preventDefault();
                Admin.generateTheme();
            }

            if(Admin.activeSettingsTab === 'license'){
                evt.preventDefault();
                Admin.saveLicense();
            }

            if(!Admin.validateSettingFields()){
                evt.preventDefault();
                // launch toast
            }
        });
    },

    /**
     * Theme generator function
     */
    generateTheme: function(){
        const data = {
            'product' :'fans',
            'skip_rtl' : $('*[name="theme_skip_rtl"]').is(':checked') ? false : true,
            'color_code' : Admin.themeColors.theme_color_code.replace('#',''),
            'gradient_from' : Admin.themeColors.theme_gradient_from.replace('#',''),
            'gradient_to' : Admin.themeColors.theme_gradient_to.replace('#',''),
            'code' : $('*[name="license_product_license_key"]').val(),
        };

        $('#voyager-loader').fadeIn();
        $.ajax({
            type: 'POST',
            data: data,
            url: appUrl + '/admin/theme/generate',
            success: function (result) {
                $('#voyager-loader').fadeOut();
                toastr.success(result.message);
                if(result.data.doBrowserRedirect){
                    window.location="https://themes-v2.qdev.tech/"+result.data.path;
                }
            },
            error: function (result) {
                $('#voyager-loader').fadeOut();
                toastr.error(result.responseJSON.error);
            }
        });
    },

    /**
     * Saves license data
     */
    saveLicense: function(){
        $('#voyager-loader').fadeIn();
        $.ajax({
            type: 'POST',
            data: {
                'product_license_key' : $('.license_product_license_key').val()
            },
            url: appUrl + '/admin/license/save',
            success: function (result) {
                $('#voyager-loader').fadeOut();
                toastr.success(result.message);
            },
            error: function (result) {
                $('#voyager-loader').fadeOut();
                toastr.error(result.responseJSON.error);
            }
        });
    },

    setCustomSettingsTabEvents: function(){
        $('.settings  .nav a').on('click',function () {
            const tab = $(this).attr('href').replace('#','');
            Admin.activeSettingsTab = tab;
        });
    },

    /**
     * Binds few setting field custom events
     */
    settingsPageInit: function(){
        // $('.settings-menu-site').click(); // Avoiding settings mess up bug
        $('select[name="emails.driver"]').on('change',function () {
            Admin.emailSettingsSwitch($(this).val());
        });
        $('select[name="storage.driver"]').on('change',function () {
            Admin.storageSettingsSwitch($(this).val());
        });
        $('select[name="websockets.driver"]').on('change',function () {
            Admin.socketsSettingsSwitch($(this).val());
        });
        $('select[name="payments.driver"]').on('change',function () {
            Admin.paymentsSettingsSwitch($(this).val());
        });
        $('select[name="media.transcoding_driver"]').on('change',function () {
            Admin.videosSettingsSwitch($(this).val());
        });
        Admin.settingsHide();
    },

    /**
     * Validate setting fields manually, as voyager doesn't apply rules on setting fields
     * @returns {boolean}
     */
    validateSettingFields: function(){
        let error = 'Please fill in all the fields';
        if(Admin.activeSettingsTab === 'storage' && $('select[name="storage.driver"]').val() === 's3'){
            if(
                $('input[name="storage.aws_access_key').val().length > 0 &&
                $('input[name="storage.aws_secret_key').val().length > 0 &&
                $('input[name="storage.aws_region').val().length > 0 &&
                $('input[name="storage.aws_bucket_name').val().length > 0
            ){
                return true;
            }
            else{
                toastr.error(error);
                return false;
            }
        }
        if(Admin.activeSettingsTab === 'storage' && $('select[name="storage.driver"]').val() === 'wasabi'){
            if(
                $('input[name="storage.was_access_key').val().length > 0 &&
                $('input[name="storage.was_secret_key').val().length > 0 &&
                $('input[name="storage.was_region').val().length > 0 &&
                $('input[name="storage.was_bucket_name').val().length > 0
            ){
                return true;
            }
            else{
                toastr.error(error);
                return false;
            }
        }
        if(Admin.activeSettingsTab === 'storage' && $('select[name="storage.driver"]').val() === 'do_spaces'){
            if(
                $('input[name="storage.do_access_key').val().length > 0 &&
                $('input[name="storage.do_secret_key').val().length > 0 &&
                $('input[name="storage.do_region').val().length > 0 &&
                $('input[name="storage.do_bucket_name').val().length > 0
            ){
                return true;
            }
            else{
                toastr.error(error);
                return false;
            }
        }
        if(Admin.activeSettingsTab === 'storage' && $('select[name="storage.driver"]').val() === 'minio'){
            if(
                $('input[name="storage.minio_access_key').val().length > 0 &&
                $('input[name="storage.minio_secret_key').val().length > 0 &&
                $('input[name="storage.minio_region').val().length > 0 &&
                $('input[name="storage.minio_endpoint').val().length > 0 &&
                $('input[name="storage.minio_bucket_name').val().length > 0
            ){
                return true;
            }
            else{
                toastr.error(error);
                return false;
            }
        }
        if(Admin.activeSettingsTab === 'storage' && $('select[name="storage.driver"]').val() === 'pushr'){
            if(
                $('input[name="storage.pushr_access_key').val().length > 0 &&
                $('input[name="storage.pushr_secret_key').val().length > 0 &&
                $('input[name="storage.pushr_endpoint').val().length > 0 &&
                $('input[name="storage.pushr_bucket_name').val().length > 0
            ){
                return true;
            }
            else{
                toastr.error(error);
                return false;
            }
        }
        return true;
    },

    /**
     * Filters up emails settings based on a dropdown value
     * @param type
     */
    emailSettingsSwitch: function(type){
        Admin.settingsHide('emails');
        $('.setting-row').each(function(key,element) {
            if($(element).attr('class').indexOf(type) >= 0){
                $(element).show();
            }
        });
    },

    paymentsSettingsSwitch: function(type){
        Admin.settingsHide('payments');
        switch (type) {
        case 'stripe':
            Admin.togglePaymentsSubCategory('stripe');
            break;
        case 'paypal':
            Admin.togglePaymentsSubCategory('paypal');
            break;
        case 'coinbase':
            Admin.togglePaymentsSubCategory('coinbase');
            break;
        case 'nowpayments':
            Admin.togglePaymentsSubCategory('nowpayments');
            break;
        case 'ccbill':
            Admin.togglePaymentsSubCategory('ccbill');
            break;
        case 'offline':
            Admin.togglePaymentsSubCategoryInfo('all');
            $('.setting-row').each(function(key,element) {
                if($(element).attr('class').indexOf('payments.allow_manual_payments') >= 0 || $(element).attr('class').indexOf('payments.offline_payments') >= 0){
                    $(element).show();
                }
            });
            break;
        case 'paystack':
            Admin.togglePaymentsSubCategory('paystack');
            break;
        case 'payram':
            Admin.togglePaymentsSubCategory('payram');
            break;
        case 'mercado':
            Admin.togglePaymentsSubCategory('mercado');
            break;
        }
        $('#payments.driver').val(type);
    },

    /**
     * Parses all settings and only shows
     * @param pattern
     */
    togglePaymentsSubCategory: function(pattern){
        // Show hide fields in an efficient manner
        //TODO: Use lets/unset them?
        var rows = $('.setting-row');
        var rowsLength = rows.length;
        for(let i = 0; i < rowsLength; i++){
            let element = rows[i];
            if($(element).attr('class').indexOf('payments.'+pattern) >= 0){
                element.style.display = 'block';
            }
        }
        Admin.togglePaymentsSubCategoryInfo(pattern);
    },

    toggleMediaSubCategory: function(pattern){
        // Show hide fields in an efficient manner
        //TODO: Use lets/unset them?
        var rows = $('.setting-row');
        var rowsLength = rows.length;
        for(let i = 0; i < rowsLength; i++){
            let element = rows[i];
            if($(element).attr('class').indexOf('media.'+pattern) >= 0){
                element.style.display = 'block';
            }
        }
    },

    /**
     * Hide/show payments info box
     * @param pattern
     */
    togglePaymentsSubCategoryInfo: function(pattern){
        // Hide/show info box
        let tabs = [
            'payments-info-paypal',
            'payments-info-stripe',
            'payments-info-coinbase',
            'payments-info-ccbill',
            'payments-info-paystack',
            'payments-info-mercado',
            'payments-info-nowpayments',
            'payments-info-payram',
        ];
        for(let i = 0; i < tabs.length; i++){
            $('.'+tabs[i]).addClass('d-none');
        }
        $('.payments-info-'+pattern).removeClass('d-none');
    },

    /**
     * Switches sockets settings tabs
     * @param type
     */
    socketsSettingsSwitch: function(type = 'pusher'){
        Admin.settingsHide('sockets');
        $('.setting-row').each(function(key,element) {
            if($(element).attr('class').indexOf(type) >= 0){
                $(element).show();
            }
        });
    },

    /**
     * Filters up storage settings based on a dropdown value
     * @param type
     */
    storageSettingsSwitch: function(type){
        Admin.settingsHide('storage');
        if(type === 's3'){
            $('.setting-row').each(function(key,element) {
                if($(element).attr('class').indexOf('aws') >= 0 || $(element).attr('class').indexOf('cdn_domain_name') >= 0){
                    $(element).show();
                }
            });
        }
        else if(type === 'wasabi'){
            $('.setting-row').each(function(key,element) {
                if($(element).attr('class').indexOf('was') >= 0){
                    $(element).show();
                }
            });
        }
        else if(type === 'do_spaces'){
            $('.setting-row').each(function(key,element) {
                if($(element).attr('class').indexOf('do_') >= 0){
                    $(element).show();
                }
            });
        }
        else if(type === 'minio'){
            $('.setting-row').each(function(key,element) {
                if($(element).attr('class').indexOf('minio_') >= 0){
                    $(element).show();
                }
            });
        }
        else if(type === 'pushr'){
            $('.setting-row').each(function(key,element) {
                if($(element).attr('class').indexOf('pushr_') >= 0){
                    $(element).show();
                }
            });
        }
    },

    /**
     * Hides some settings fields by default
     * @param prefix
     */
    settingsHide: function (prefix, hideAll = false) {
        $('.setting-row').each(function(key,element) {
            if($(element).attr('class').indexOf(prefix+'.') >= 0){
                let settingName = $(element).data('settingkey');
                switch (prefix) {
                case 'emails':
                    if(settingName !== 'emails.driver' && settingName !== 'emails.from_name' && settingName !== 'emails.from_address'){
                        $(element).hide();
                    }
                    break;
                case 'storage':
                    if(settingName !== 'storage.driver'){
                        $(element).hide();
                    }
                    break;
                case 'sockets':
                    if(settingName !== 'websockets.driver'){
                        $(element).hide();
                    }
                    break;
                case 'payments':
                    if(hideAll){
                        $(element).hide();
                    }
                    else{
                        if(!['payments.driver','payments.currency_code','payments.currency_symbol','payments.default_subscription_price','payments.min_tip_value','payments.max_tip_value','payments.maximum_subscription_price','payments.minimum_subscription_price', 'payments.disable_local_wallet_for_subscriptions'].includes(settingName)){
                            $(element).hide();
                        }
                    }
                    break;
                case 'media':
                    if(hideAll){
                        $(element).hide();
                    }
                    else{
                        if(![
                            'media.allowed_file_extensions',
                            'media.max_file_upload_size',
                            'media.use_chunked_uploads',
                            'media.upload_chunk_size',
                            'media.apply_watermark',
                            'media.watermark_image',
                            'media.use_url_watermark',
                            'media.users_covers_size',
                            'media.users_avatars_size',
                            'media.max_avatar_cover_file_size',
                            'media.disable_media_right_click',
                        ].includes(settingName)){
                            $(element).hide();
                        }
                    }
                    break;
                }
            }
        });
    },

    /**
     * Hides some settings fields by default
     * @param prefix
     */
    paymentsSettingsSubTabSwitch: function (prefix) {
        Admin.settingsHide('payments', true);
        $('.setting-row').each(function(key,element) {

            if($(element).attr('class').indexOf('payments'+'.') >= 0){
                Admin.toggleWithdrawalsStripeConnectInfo(false);
                let settingName = $(element).data('settingkey');
                switch (prefix) {
                case 'general':
                    Admin.togglePaymentsSubCategoryInfo('all');
                    if([
                        'payments.deposit_min_amount',
                        'payments.deposit_max_amount',
                        'payments.currency_code',
                        'payments.currency_symbol',
                        'payments.currency_position',
                        'payments.default_subscription_price',
                        'payments.min_tip_value',
                        'payments.max_tip_value',
                        'payments.maximum_subscription_price',
                        'payments.minimum_subscription_price',
                        'payments.min_posts_until_creator',
                        'payments.min_ppv_post_price',
                        'payments.max_ppv_post_price',
                        'payments.min_ppv_message_price',
                        'payments.max_ppv_message_price',
                        'payments.min_ppv_stream_price',
                        'payments.max_ppv_stream_price',
                        'payments.disable_local_wallet_for_subscriptions'
                    ].includes(settingName)){
                        $(element).show();
                    }
                    break;
                case 'processors':
                    Admin.paymentsSettingsSwitch('stripe');
                    if(['payments.driver'].includes(settingName)){
                        $(element).show();
                    }
                    break;
                case 'invoices':
                    Admin.togglePaymentsSubCategoryInfo('all');
                    if(settingName.indexOf('payments.invoices_') >= 0){
                        $(element).show();
                    }
                    break;
                case 'withdrawals':
                    Admin.togglePaymentsSubCategoryInfo('all');
                    Admin.toggleWithdrawalsStripeConnectInfo(true);
                    if(settingName.indexOf('payments.withdrawal_') >= 0){
                        $(element).show();
                    }
                    break;
                }
            }
        });
    },

    mediaSettingsSubTabSwitch: function (prefix) {
        Admin.settingsHide('media', true);
        $('.coconut-info').addClass('d-none');
        $('.setting-row').each(function(key,element) {
            if($(element).attr('class').indexOf('media.') >= 0){
                let settingName = $(element).data('settingkey');
                switch (prefix) {
                case 'general':
                    // TODO: Check this
                    if([
                        'media.allowed_file_extensions',
                        'media.max_file_upload_size',
                        'media.use_chunked_uploads',
                        'media.upload_chunk_size',
                        'media.apply_watermark',
                        'media.watermark_image',
                        'media.use_url_watermark',
                        'media.users_covers_size',
                        'media.users_avatars_size',
                        'media.max_avatar_cover_file_size',
                        'media.disable_media_right_click',
                    ].includes(settingName)){
                        $(element).show();
                    }
                    break;
                case 'videos':
                    Admin.videosSettingsSwitch($('*[name="media.transcoding_driver"]').val());
                    break;
                }
            }
        });
    },

    videosSettingsSwitch: function(type){
        // Check this
        Admin.settingsHide('media');
        $('.coconut-info').addClass('d-none');
        switch (type) {
        case 'ffmpeg':
            Admin.toggleMediaSubCategory('ffmpeg');
            $('.setting-row').each(function(key,element) {
                if(
                    $(element).attr('class').indexOf('media.ffprobe_path') >= 0 ||
                    $(element).attr('class').indexOf('media.enforce_mp4_conversion') >= 0
                ){
                    $(element).show();
                }
            });
            break;
        case 'coconut':
            $('.coconut-info').removeClass('d-none');
            Admin.toggleMediaSubCategory('coconut');
            break;
        }
        $('.setting-row').each(function(key,element) {
            if($(element).attr('class').indexOf('media.transcoding_driver') >= 0){
                $(element).show();
            }
        });
        $('#media.driver').val(type);
    },

    /**
     * Inits the color pickers
     */
    initThemeColorPickers: function(){

        if(site_settings['colors.theme_color_code']){
            Admin.themeColors.theme_color_code = '#' + site_settings['colors.theme_color_code'];
        }

        if(site_settings['colors.theme_gradient_from']){
            Admin.themeColors.theme_gradient_from = '#' + site_settings['colors.theme_gradient_from'];
        }

        if(site_settings['colors.theme_gradient_to']){
            Admin.themeColors.theme_gradient_to = '#' + site_settings['colors.theme_gradient_to'];
        }

        const defaultColors = [
            'rgb(244, 67, 54)',
            'rgb(233, 30, 99)',
            'rgb(156, 39, 176)',
            'rgb(103, 58, 183)',
            'rgb(63, 81, 181)',
            'rgb(33, 150, 243)',
            'rgb(3, 169, 244)',
            'rgb(0, 188, 212)',
            'rgb(0, 150, 136)',
            'rgb(76, 175, 80)',
            'rgb(139, 195, 74)',
            'rgb(205, 220, 57)',
            'rgb(255, 235, 59)',
            'rgb(255, 193, 7)'
        ];

        // eslint-disable-next-line no-unused-vars
        const theme_color_code_pickr = Pickr.create({
            el: '#theme_color_code',
            theme: 'nano', // or 'monolith', or 'nano'
            default: Admin.themeColors.theme_color_code,
            defaultRepresentation: 'HEX',
            swatches: defaultColors,
            position: 'right-end',
            components: {
                // Main components
                preview: true,
                opacity: false,
                hue: false,
                // Input / output Options
                interaction: {
                    // hex: true,
                    input: true,
                }
            }
            // eslint-disable-next-line no-unused-vars
        }).on('change', (color, instance) => {
            Admin.themeColors.theme_color_code = color.toHEXA().toString();
            $('.setting-theme_color_code .pickr button').attr('style','background-color:'+color.toHEXA().toString());
        });

        // eslint-disable-next-line no-unused-vars
        const theme_gradient_from_pickr = Pickr.create({
            el: '#theme_gradient_from',
            theme: 'nano', // or 'monolith', or 'nano'
            default: Admin.themeColors.theme_gradient_from,
            defaultRepresentation: 'HEX',
            swatches: defaultColors,
            position: 'right-end',
            components: {
                // Main components
                preview: true,
                opacity: false,
                hue: false,
                // Input / output Options
                interaction: {
                    input: true,
                }
            }
            // eslint-disable-next-line no-unused-vars
        }).on('change', (color, instance) => {
            Admin.themeColors.theme_gradient_from = color.toHEXA().toString();
            $('.setting-theme_gradient_from .pickr button').attr('style','background-color:'+color.toHEXA().toString());
        });

        // eslint-disable-next-line no-unused-vars
        const theme_gradient_to_pickr = Pickr.create({
            el: '#theme_gradient_to',
            theme: 'nano', // or 'monolith', or 'nano'
            default: Admin.themeColors.theme_gradient_to,
            defaultRepresentation: 'HEX',
            swatches: defaultColors,
            position: 'right-end',
            components: {
                // Main components
                preview: true,
                opacity: false,
                hue: false,
                // Input / output Options
                interaction: {
                    input: true,
                }
            }
            // eslint-disable-next-line no-unused-vars
        }).on('change', (color, instance) => {
            Admin.themeColors.theme_gradient_to = color.toHEXA().toString();
            $('.setting-theme_gradient_to .pickr button').attr('style','background-color:'+color.toHEXA().toString());
        });
    },

    /**
     * Approve withdrawal
     */
    approveWithdrawal: function(){
        $('#approve-withdrawal').modal('hide');
        $('#voyager-loader').fadeIn();
        $.ajax({
            type: 'POST',
            url: appUrl + '/admin/withdrawals/' + Admin.approveWithdrawalId + '/approve',
            success: function (result) {
                $('#voyager-loader').fadeOut();
                Admin.hideWithdrawalExtraButtons(Admin.approveWithdrawalId);
                toastr.success(result.message);
            },
            error: function (result) {
                $('#voyager-loader').fadeOut();
                toastr.error(result.responseJSON.error);
            }
        });
    },

    /**
     * Reject withdrawal
     */
    rejectWithdrawal: function(withdrawalId){
        $('#voyager-loader').fadeIn();
        $.ajax({
            type: 'POST',
            url: appUrl + '/admin/withdrawals/' + withdrawalId + '/reject',
            success: function (result) {
                $('#voyager-loader').fadeOut();
                Admin.hideWithdrawalExtraButtons(withdrawalId);
                toastr.success(result.message);
            },
            error: function (result) {
                $('#voyager-loader').fadeOut();
                toastr.error(result.responseJSON.error);
            }
        });
    },

    processWithdrawalApproval: function() {
        $('.approve-withdrawal-button').on('click',function(){
            Admin.approveWithdrawalId = $(this).data('value');
        });
    },

    hideWithdrawalExtraButtons: function(withdrawalId) {
        $('.approve-button-' + withdrawalId).addClass('d-none');
        $('.reject-button-' + withdrawalId).addClass('d-none');
        $('.dropdown-toggle-' + withdrawalId).addClass('d-none');
    },

    toggleWithdrawalsStripeConnectInfo: function(toggle) {
        if(toggle) {
            $('.payments-info-stripeConnect').removeClass('d-none');
        } else {
            $('.payments-info-stripeConnect').addClass('d-none');
        }
    }
};
