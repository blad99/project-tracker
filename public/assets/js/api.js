"use strict";

$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': window.CSRF_TOKEN || '',
        'Accept': 'application/json',
        'Content-Type': 'application/json',
    }
});

const API = {
    projects: '/api/projects',
    tasks: '/api/tasks',
};

function loadAll() {
    $.when(
        $.get(API.projects),
        $.get(API.tasks)
    ).done(function (pr, tr) {
        if (pr[0].success) state.projects = pr[0].data;
        if (tr[0].success) state.tasks = flattenTree(tr[0].data);
        renderAll();
    }).fail(function () {
        showToast('Gagal memuat data', 'danger');
    });
}

function setBtnLoading(loading) {
    $('#panel-btn-save').prop('disabled', loading).html(loading ? '<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...' : '<i class="bi bi-floppy me-1"></i> Simpan');
}

function saveProject() {
    const id = state.editId;
    const name = $('#proj-name').val().trim();
    if (!name) { showToast('Nama project tidak boleh kosong', 'warning'); return; }

    const payload = {
        name: name,
        start_date: $('#proj-start-date').val() || null,
        end_date: $('#proj-end-date').val() || null,
        dependencies: collectProjDepIds(),
    };

    setBtnLoading(true);
    const req = id
        ? $.ajax({ url: `${API.projects}/${id}`, method: 'PUT', contentType: 'application/json', data: JSON.stringify(payload) })
        : $.ajax({ url: API.projects, method: 'POST', contentType: 'application/json', data: JSON.stringify(payload) });

    req.done(function (res) {
        setBtnLoading(false);
        if (!res.success) { showToast(res.message, 'danger'); return; }
        showToast(res.message, 'success');
        offcanvas.hide();
        loadAll();
    }).fail(function (xhr) {
        setBtnLoading(false);
        showToast(xhr.responseJSON?.message || 'Terjadi kesalahan', 'danger');
    });
}

function saveTask() {
    const id = state.editId;
    const name = $('#task-name').val().trim();
    const projectId = parseInt($('#task-project').val());
    if (!name) { showToast('Nama task tidak boleh kosong', 'warning'); return; }
    if (!projectId) { showToast('Pilih project terlebih dahulu', 'warning'); return; }

    const parentVal = $('#task-parent').val();
    const payload = {
        name: name,
        project_id: projectId,
        status: $('#task-status').val(),
        weight: parseInt($('#task-weight').val()) || 1,
        parent_task_id: parentVal ? parseInt(parentVal) : null,
        dependencies: collectDepIds(),
    };

    setBtnLoading(true);
    const req = id
        ? $.ajax({ url: `${API.tasks}/${id}`, method: 'PUT', contentType: 'application/json', data: JSON.stringify(payload) })
        : $.ajax({ url: API.tasks, method: 'POST', contentType: 'application/json', data: JSON.stringify(payload) });

    req.done(function (res) {
        setBtnLoading(false);
        if (!res.success) { showToast(res.message, 'danger'); return; }
        showToast(res.message, 'success');
        offcanvas.hide();
        loadAll();
    }).fail(function (xhr) {
        setBtnLoading(false);
        showToast(xhr.responseJSON?.message || 'Terjadi kesalahan', 'danger');
    });
}

function handleDelete() {
    const id = state.editId;
    const mode = state.panelMode;
    if (!id) return;
    if (!confirm(`Hapus ${mode === 'project' ? 'project' : 'task'} ini?`)) return;

    const url = mode === 'project' ? `${API.projects}/${id}` : `${API.tasks}/${id}`;
    setBtnLoading(true);

    $.ajax({ url, method: 'DELETE' })
        .done(function (res) {
            setBtnLoading(false);
            if (!res.success) { showToast(res.message, 'danger'); return; }
            showToast(res.message, 'success');
            offcanvas.hide();
            loadAll();
        }).fail(function (xhr) {
            setBtnLoading(false);
            showToast(xhr.responseJSON?.message || 'Terjadi kesalahan', 'danger');
        });
}
