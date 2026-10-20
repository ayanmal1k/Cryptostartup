/**
 * Side menu (slidable) component
 */
"use strict";
/* global WOW */

jQuery(document).ready(function() {
    // Sidebar
    $('.dismiss, .overlay').on('click', function() {
        $('.sidebar').removeClass('active');
        $('.overlay').removeClass('active');
    });

    // Menu toggled — use glass mobile drawer on phone
    $('.open-menu').on('click', function(e) {
        if (window.matchMedia('(max-width: 768px)').matches && window.MobileSidebar) {
            e.preventDefault();
            window.MobileSidebar.open();
            return;
        }
        e.preventDefault();
        $('.sidebar').addClass('active');
        $('.overlay').addClass('active');
        // close opened sub-menus
        $('.collapse.show').toggleClass('show');
        $('a[aria-expanded=true]').attr('aria-expanded', 'false');
    });
    /* replace the default browser scrollbar in the sidebar, in case the sidebar menu has a height that is bigger than the viewport */
    try {
        if (typeof $.fn !== 'undefined' && typeof $.fn.mCustomScrollbar !== 'undefined') {
            $('.sidebar').mCustomScrollbar({
                theme: "minimal-dark"
            });
        } else {
            // Fallback for no mCustomScrollbar
            console.log("mCustomScrollbar not available, using fallback");
            $('.sidebar').css({
                'overflow-y': 'auto',
                'max-height': 'calc(100vh - 60px)'
            });
        }
    } catch (e) {
        console.error("Error initializing scrollbar:", e);
        // Simple fallback
        $('.sidebar').css({
            'overflow-y': 'auto',
            'max-height': 'calc(100vh - 60px)'
        });
    }

    // Wow initiate
    new WOW().init();
});
