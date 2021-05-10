import './styles/app.scss';
import NProgress from 'nprogress/nprogress';
import 'nprogress/nprogress.css';

let inArchive = false;

let archiveListButton;
let archiveIcon;
let listIcon;

let activeToDoContainer;
let completedToDoContainer;

let createButton;
let createToDoText;

let sortButton;

let sortASC = false;

$(function () {
    archiveListButton = $('#archive-list-btn');
    archiveIcon = $('#to-archive-icon');
    listIcon = $('#to-list-icon');

    activeToDoContainer = $('#active-todo-container');
    completedToDoContainer = $('#completed-todo-container');

    createButton = $('#create-btn');
    createToDoText = $('#create-text');

    sortButton = $('#sort-btn');

    bind();
});

function bind() {
    archiveListButton.on('click', changeContainer);
    createButton.on('click', createElem);
    sortButton.on('click', sortElements);
}

function changeContainer() {
    if (inArchive) {
        completedToDoContainer.hide();
        activeToDoContainer.show();
        listIcon.hide();
        archiveIcon.show();
    } else {
        completedToDoContainer.show();
        activeToDoContainer.hide();
        listIcon.show();
        archiveIcon.hide();
    }
    inArchive = !inArchive;
}

function createElem() {
    let elemName = createToDoText.val();

    if (elemName === '') {
        sendError('Заполните название задачи');
    }

    NProgress.start();

    $.ajax({
        type: 'POST',
        url: baseAPIURL + 'todoes/',
        timeout: 3000,
        dataType: 'json',
        data: {
            name: elemName,
        },
        success: function (data) {
            let elem = createElement(data.data.id, data.data.name);
            activeToDoContainer.append(elem);
            NProgress.done();
        },
        error: function (data) {
            NProgress.done();
            if (data.statusText === 'timeout') {
                sendSubSystemError(true);
            } else {
                if (data.responseJSON.error === 'Произошла ошибка подсистемы') {
                    sendSubSystemError(true);
                } else {
                    sendError(data.responseJSON.error);
                }
            }
        }
    });
}

function deleteElem(id) {
    NProgress.start();

    $.ajax({
        type: 'DELETE',
        url: baseAPIURL + 'todoes/' + id,
        timeout: 3000,
        dataType: 'json',
        success: function () {
            $('#todo-item-' + id).remove();
            NProgress.done();
        },
        error: function (data) {
            if (data.statusText === 'timeout') {
                sendSubSystemError(false);
                $('#todo-item-' + id).remove();
            } else {
                if (data.responseJSON.error === 'Произошла ошибка подсистемы') {
                    sendSubSystemError(false);
                    $('#todo-item-' + id).remove();
                } else {
                    sendError(data.responseJSON.error);
                }
            }
            NProgress.done();
        }
    });
}

window.deleteElem = deleteElem;

function updateElem(id, isComplete = 0) {
    NProgress.start();

    $.ajax({
        type: 'PUT',
        url: baseAPIURL + 'todoes/' + id,
        timeout: 3000,
        data: {
            name: $('#todo-item-' + id + '-name').text(),
            sort: $('#todo-item-' + id + '-sort').val(),
            isCompleted: isComplete
        },
        dataType: 'json',
        success: function () {
            if (isComplete === 1) {
                moveElemToArchive(id);
            }
        },
        error: function (data) {
            if (data.statusText === 'timeout') {
                if (isComplete === 1) {
                    moveElemToArchive(id);
                }
                sendSubSystemError(false);
            } else {
                if (data.responseJSON.error === 'Произошла ошибка подсистемы') {
                    sendSubSystemError(false);
                    if (isComplete === 1) {
                        moveElemToArchive(id);
                    }
                } else {
                    sendError(data.responseJSON.error);
                }
            }
            NProgress.done();
        }
    });
}

window.updateElem = updateElem;

function sortElements() {
    let method;
    let isCompleted;
    if (sortASC) {
        method = 'ASC';
    } else {
        method = 'DESC';
    }
    sortASC = !sortASC;

    if (inArchive) {
        isCompleted = 1;
    } else {
        isCompleted = 0;
    }

    $.ajax({
        type: 'GET',
        url: baseAPIURL + 'todoes/',
        timeout: 15000,
        dataType: 'json',
        data: {
            sortField: 'sort',
            sortMethod: method,
            isCompleted: isCompleted,
        },
        success: function (data) {
            console.log(data);
            if (isCompleted) {
                completedToDoContainer.empty();
                if (data.data.length > 0) {
                    data.data.forEach(function (element) {
                        let preparedNode = createCompleted(element.id, element.name, element.sort);
                        completedToDoContainer.append(preparedNode);
                    });
                }
            } else {
                activeToDoContainer.empty();
                if (data.data.length > 0) {
                    data.data.forEach(function (element) {
                        let preparedNode = createElement(element.id, element.name, element.sort);
                        activeToDoContainer.append(preparedNode);
                    });
                }
            }
            NProgress.done();
        },
        error: function (data) {
            if (data.statusText === 'timeout') {
                sendError('Произошла ошибка! Попробуйте через пару секунд.')
            } else {
                sendError(data.responseJSON.error);
            }
            NProgress.done();
        }
    });
}


function moveElemToArchive(id) {
    let name = $('#todo-item-' + id + '-name').text();
    let sort = $('#todo-item-' + id + '-sort').val();
    $('#todo-item-' + id).remove();
    completedToDoContainer.append(createCompleted(id, name, sort));
}

function sendError(message) {
    $.toast({
        text: message,
        bgColor: '#c0392b',              // Background color for toast
        textColor: '#bdc3c7',            // text color
        allowToastClose: true,       // Show the close button or not
        position: 'bottom-right',       // bottom-left or bottom-right or bottom-center or top-left or top-right or top-center or mid-center or an object representing the left, right, top, bottom values to position the toast on page
        icon: 'error'
    });
}

function createCompleted(id, name, sort) {
    return '<li class="todo-item" id="todo-item-' + id + '">\n' +
        '<input class="todo-item__sort todo-item__completed" type="number"\n' +
        '                           value="' + sort + '" readonly>\n' +
        '                    <section class="todo-item__label todo-item__completed">\n' +
        '                        <p>' + name + '</p>\n' +
        '                    </section>\n' +
        '                    <button class="todo-item__delete-button" onclick="deleteElem(' + id + ')">\n' +
        '                        <i class="fas fa-times"></i>\n' +
        '                    </button>\n' +
        '                </li>';
}

function createElement(id, name, sort = 1) {
    return '<li class="todo-item" id="todo-item-' + id + '">\n' +
        '                    <input class="todo-item__sort" type="number" value="' + sort + '"\n' +
        '                           id="todo-item-' + id + '-sort" onchange="updateElem(' + id + ')">\n' +
        '                    <section class="todo-item__label">\n' +
        '                        <p id="todo-item-' + id + '-name">' + name + '</p>\n' +
        '                    </section>\n' +
        '                    <button class="todo-item__complete-button" onclick="updateElem(' + id + ', 1)">\n' +
        '                        <i class="fas fa-check"></i>\n' +
        '                    </button>\n' +
        '                    <button class="todo-item__delete-button" onclick="deleteElem(' + id + ')">\n' +
        '                        <i class="fas fa-times"></i>\n' +
        '                    </button>\n' +
        '                </li>';
}

function sendSubSystemError(isReload = false) {
    sendError('Произошла ошибка подсистемы');
    if (isReload) {
        setTimeout(() => {
            location.reload();
        }, 2000);
    }
}