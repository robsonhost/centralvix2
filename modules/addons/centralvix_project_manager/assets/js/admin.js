(function () {
    'use strict';

    function ajax(action, fd) {
        var url = (window.CVIX_PM_AJAX || '').replace('cvix_pm_ajax=', 'cvix_pm_ajax=' + action);
        return fetch(url, { method: 'POST', body: fd, credentials: 'same-origin' }).then(function (r) { return r.json(); });
    }

    var saveTask = document.querySelector('.cvix-pm-save-task');
    if (saveTask) {
        saveTask.addEventListener('click', function () {
            var form = document.getElementById('cvix-pm-new-task');
            if (!form) return;
            var fd = new FormData(form);
            fd.append('task_action', 'save');
            ajax('task', fd).then(function (res) {
                alert(res.success ? 'Tarefa criada.' : (res.message || 'Erro'));
                if (res.success) location.reload();
            });
        });
    }

    var commentBtn = document.querySelector('.cvix-pm-send-comment');
    if (commentBtn) {
        commentBtn.addEventListener('click', function () {
            var form = document.getElementById('cvix-pm-comment-form');
            ajax('comment', new FormData(form)).then(function (res) {
                if (res.success) location.reload();
                else alert(res.message || 'Erro');
            });
        });
    }

    var uploadBtn = document.querySelector('.cvix-pm-upload');
    if (uploadBtn) {
        uploadBtn.addEventListener('click', function () {
            var form = document.getElementById('cvix-pm-upload-form');
            ajax('upload', new FormData(form)).then(function (res) {
                alert(res.success ? 'Enviado.' : (res.message || 'Erro'));
                if (res.success) location.reload();
            });
        });
    }

    var kanban = document.querySelector('.cvix-pm-kanban');
    if (!kanban) return;

    var projectId = kanban.getAttribute('data-project-id');
    var dragged = null;

    kanban.querySelectorAll('.cvix-pm-kanban-card').forEach(function (card) {
        card.addEventListener('dragstart', function (e) {
            dragged = card;
            card.classList.add('dragging');
            e.dataTransfer.effectAllowed = 'move';
        });
        card.addEventListener('dragend', function () {
            card.classList.remove('dragging');
            persistKanban();
        });
    });

    kanban.querySelectorAll('.cvix-pm-kanban-list').forEach(function (list) {
        list.addEventListener('dragover', function (e) {
            e.preventDefault();
            list.classList.add('drag-over');
        });
        list.addEventListener('dragleave', function () {
            list.classList.remove('drag-over');
        });
        list.addEventListener('drop', function (e) {
            e.preventDefault();
            list.classList.remove('drag-over');
            if (dragged) list.appendChild(dragged);
        });
    });

    function persistKanban() {
        var columns = {};
        kanban.querySelectorAll('.cvix-pm-kanban-list').forEach(function (list) {
            var status = list.getAttribute('data-drop');
            columns[status] = [];
            list.querySelectorAll('.cvix-pm-kanban-card').forEach(function (card) {
                columns[status].push(card.getAttribute('data-task-id'));
            });
        });
        var fd = new FormData();
        fd.append('csrf_token', window.CVIX_PM_CSRF || '');
        fd.append('type', 'kanban');
        fd.append('project_id', projectId);
        fd.append('columns', JSON.stringify(columns));
        ajax('status', fd);
    }
})();
