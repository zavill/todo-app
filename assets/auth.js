import './styles/auth.scss';
let authForm;
let authLoginInput;
let authPassInput;
let authButton;
let toRegButton;

let regForm;
let regLoginInput;
let regPassInput;
let regRePassInput;
let regButton;
let toAuthButton;
$(function() {
    console.log('ready');

    authForm = $('#auth-form');
    authLoginInput = $('#auth-login');
    authPassInput = $('#auth-pass');
    authButton = $('#auth-button');
    toRegButton = $('#btn-to-reg');

    regForm = $('#reg-form');
    regLoginInput = $('#reg-login');
    regPassInput = $('#reg-pass');
    regRePassInput = $('#reg-rePass');
    regButton = $('#reg-button');
    toAuthButton = $('#btn-to-auth');

    bind();
});

function bind() {
    authButton.on('click', authorize);
    regButton.on('click', registration);

    toRegButton.on('click', function () {
       authForm.hide();
       regForm.show();
    });

    toAuthButton.on('click', function () {
        authForm.show();
        regForm.hide();
    });
}

function registration() {
    if (regLoginInput.val() === '') {
        sendError('Заполните поле "имя пользователя"');
        return 0;
    } else if (regPassInput.val() === '') {
        sendError('Заполните поле "пароль"');
        return 0;
    } else if (regPassInput.val() !== regRePassInput.val()) {
        sendError('Пароли не совпадают');
        return 0;
    }

    $.ajax({
        type: "POST",
        url: baseAPIURL + 'users/',
        data: {
            username: regLoginInput.val(),
            password: regPassInput.val()
        },
        dataType: 'json',
        success: function () {
            location.reload();
        },
        error: function (data) {
            sendError(data.responseJSON.error);
        }
    });
}

function authorize() {
    if (authLoginInput.val() === '') {
        sendError('Заполните поле "имя пользователя"');
        return 0;
    } else if (authPassInput.val() === '') {
        sendError('Заполните поле "пароль"');
        return 0;
    }

    $.ajax({
        type: "GET",
        url: baseAPIURL + 'users/authorize',
        data: {
            username: authLoginInput.val(),
            password: authPassInput.val()
        },
        dataType: 'json',
        success: function () {
            location.reload();
        },
        error: function (data) {
            sendError(data.responseJSON.error);
        }
    });
}

function sendError(message) {
    $.toast({
        text : message,
        bgColor : '#c0392b',              // Background color for toast
        textColor : '#bdc3c7',            // text color
        allowToastClose : true,       // Show the close button or not
        position : 'bottom-right',       // bottom-left or bottom-right or bottom-center or top-left or top-right or top-center or mid-center or an object representing the left, right, top, bottom values to position the toast on page
        icon: 'error'
    });
}