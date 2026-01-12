function out(msg, txt) {
    if( msg == undefined || msg == '' || $('.alert').length > 0 ){
        return false;
    }

    let alert = document.createElement('div');
    $(alert).addClass('alert');

    let alertContent = document.createElement('div');
    $(alertContent).addClass('alert_content').appendTo(alert);

    let appendOverlay = document.createElement('div');
    $(appendOverlay).addClass('alert_overlay').appendTo(alert);

    let alertTitle = document.createElement('div');
    $(alertTitle).addClass('alert_title').text(msg.replace(/&#39;/g, "'")).appendTo(alertContent);

    if( txt!='' ){
        let alertTxt = document.createElement('div');
        $(alertTxt).addClass('alert_message').html(txt).appendTo(alertContent);
    }

    let closeBtn = document.createElement('button');
    $(closeBtn).addClass('alert_ok').text( close_btn ).appendTo(alertContent);

    $('body').append(alert);
    $(alert).fadeIn();

    $('.alert_ok,.alert_overlay').on('click', function(){
        $('.alert').fadeOut();
        setTimeout(function(){
            $('.alert').remove();
        },350)
    });

};

function initLoader() {
    $('body').prepend('<div class="loader"></div>');
};

function removeLoader() {
    $('.loader').remove();
};

function isEmail(email) {
    if (email.length < 5) {
        return false;
    }

    var parts = email.split('@');
    if (parts.length !== 2) {
        return false;
    }

    var domain = parts[1];
    if (domain.length < 4) {
        return false;
    }


    var re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
    return re.test(email);
};