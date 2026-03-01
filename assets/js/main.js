$(document).ready(function () {
    // Remove conflicting inline handlers
    $('.menu_mobile').removeAttr('onclick');
    $('.search-iph a').removeAttr('onclick');
    $('.hide_search').removeAttr('onclick');

    // Login form submit handling
    $('.form-login form').submit(function () {
        const obj = $("button[type='submit']", this);
        obj.addClass('disabled').prop("disabled", true);
        setTimeout(function () {
            obj.removeClass('disabled').prop("disabled", false);
        }, 3000);
        return true;
    });

    // UI Stub for loadTopViews
    if (document.getElementById('load-anclytic')) {
        loadTopViews('.tab_icon.one1', 1);
    }

    // Mobile Menu Toggle
    const slideMenu = $('nav.menu_top');
    $('a.menu_mobile').click(function () {
        if (slideMenu.is(':hidden')) {
            $('#off_light').css({ "display": "block" });
            $(slideMenu).css({ "display": "block" });
            $('html, body').css({ "height": "100%", "width": "100%", "overflow": "hidden" });
        } else {
            $('#off_light').css({ "display": "none" });
            $(slideMenu).css({ "display": "none" });
            $('html, body').css({ "height": "", "width": "", "overflow": "" });
        }
    });

    $('#off_light').click(function () {
        $('#off_light').css({ "display": "none" });
        $(slideMenu).css({ "display": "none" });
        $('html, body').css({ "height": "", "width": "", "overflow": "" });
    });

    // Search Toggle
    $('.search-iph').click(function (e) {
        e.preventDefault();
        $(this).hide();
        $("img.logo").removeClass('show').addClass('hide');
        $(".hide_search").removeClass('hide').addClass('show');
        $('#search-form').show();
    });

    $('.hide_search').click(function (e) {
        e.preventDefault();
        $(this).removeClass('show').addClass('hide');
        $('.search-iph').show();
        $('#search-form').hide();
        $("img.logo").removeClass('hide').addClass('show');
    });

    // Scroll to Top
    $(".croll img, .croll i").click(function () {
        $("html, body").animate({ scrollTop: 0 }, "slow");
        return false;
    });

    // Modal/Mask Close
    $('.mask').click(function () {
        $('.modal-close').fadeOut();
        $('.mask').fadeOut();
    });

    // Login Popup Validation
    $('.login-popup button').click(function (e) {
        const email = $.trim($('input[type=email]').val());
        const pass = $.trim($('input[type=password]').val());

        if (email === '') {
            e.preventDefault();
            $('input[type=email]').addClass('error').focus();
        } else if (!validateEmail(email)) {
            e.preventDefault();
            $('input[type=email]').addClass('error').focus();
        } else if (pass === '') {
            e.preventDefault();
            $('input[type=password]').addClass('error').focus();
        }

        $('input[type=email], input[type=password]').keypress(function () {
            $(this).removeClass('error');
        });
    });

    // Menu Active Highlighting
    $('.menu_top li:not(.user)').each(function () {
        const href = $(this).find('a').attr('href');
        const current_url = window.location.protocol + '//' + window.location.hostname + window.location.pathname;
        if (current_url === href) {
             $('.menu_top li').removeClass('active');
             $(this).addClass('active');
        }
    });

    // Tab Switching (.nav-tabs.ads)
    $('.nav-tabs.ads a').click(function (e) {
        e.preventDefault();
        $('.nav-tabs.ads a').removeClass('active');
        $(this).addClass('active');
        const str = $(this).attr('data-tab');
        $('.main_body_black').hide();
        $('.main_body_black.' + str).show();
    });

    // Tab Switching (.nav-tabs.intro)
    $('.nav-tabs.intro a').click(function (e) {
        e.preventDefault();
        $('.nav-tabs.intro a').removeClass('active');
        $(this).addClass('active');
        const str = $(this).attr('data-tab');
        if (str === 'recent_sub') {
            $('.datagrild_nav').show();
        } else {
            $('.datagrild_nav').hide();
        }
        $('.content_episode').hide();
        $('.content_episode.' + str).show();
    });

    // .datagrild_nav links
    $('.datagrild_nav a').click(function (e) {
        e.preventDefault();
        $('.datagrild_nav a').removeClass('active');
        $(this).addClass('active');
        const str = $(this).attr('data-tab');
        $(".content_episode.datagrild").removeClass('ver hor').addClass(str);
    });

    // Close Ads
    $('.add_ads_items_close').click(function () { $('.add_ads').hide(); });
    $('.add_ads_items_close2').click(function () { $('.add_ads2').hide(); });
    $('.add_ads_items_close3').click(function () { $('.add_ads3').hide(); });

    // Chapter Select
    $('select.chapter_select').change(function () {
        const id = $(this).val();
        window.location.href = id;
    });

    // Chat Group Toggle
    $('#chat_group_name').click(function () {
        $('#chat_group_body').slideToggle(100);
    });

    // Ads Positioning / Layout
    if ($(document).width() > 1000) {
        const offset = ($(document).width() - $("#wrapper").width()) / 2 - 170;
        $("#left-side-ads").css("left", offset);
        $("#right-side-ads").css("right", offset);
    } else {
        $("#left-side-ads").hide();
        $("#right-side-ads").hide();
    }

    // Menu Hover Effect
    $("nav.menu_top ul li a").on("mouseover", function () {
        if (!$(this).parent().hasClass('active')) {
            $(this).parent().stop(true, true).addClass('seleted');
        }
    }).on("mouseout", function () {
        if (!$(this).parent().hasClass('active')) {
            $(this).parent().stop(true, true).removeClass('seleted');
        }
    });

    // Prevent default on disabled links (if any remain)
    $('.account').click(function(e){ e.preventDefault(); });
    $('.user .account').click(function(e){ $('.nav_down_up').toggle(); });

});

// Global functions

function loadTopViews(obj, id) {
    // UI Stub only - no external fetch
    $(".tab_icon").removeClass("active");
    $(".movies_show #load_topivews").hide();

    $(".tab_icon.one" + id).addClass('active');
    $("#load_topivews.views" + id).show();
}

function validateEmail(email) {
    const emailReg = /^([\w-.]+@([\w-]+\.)+[\w-]{2,4})?$/;
    return emailReg.test(email);
}

function closePoup() {
    $('.modal-close').fadeOut();
    $('.mask').fadeOut();
    $('.modal-close').hide();
    $('.mask').hide();
}

function freload() {
    location.reload();
}

// Utility for disabled state
function disabled(obj) {
    $(obj).addClass('disabled').prop("disabled", true);
    setTimeout(function () {
        $(obj).removeClass('disabled').prop("disabled", false);
    }, 1000);
}
