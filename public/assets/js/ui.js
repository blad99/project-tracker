"use strict";

let offcanvas;

function initUI() {
    offcanvas = new bootstrap.Offcanvas(document.getElementById('form-offcanvas'));
}

function renderAll() {
    const $c = $('#project-container').empty();

    if (!state.projects.length) {
        $c.html(`
            <div class="text-center py-5 text-muted">
                <i class="bi bi-folder2-open fs-1"></i>
                <p class="mt-2">Belum ada project.<br>Klik <strong>Add Project</strong> untuk memulai.</p>
            </div>
        `);
        return;
    }

    state.projects.forEach(p => {
        const tree = buildClientTree(state.tasks.filter(t => t.project_id == p.id));
        const filtered = (state.filterStatus || state.filterSearch)
            ? filterClientTree(tree, state.filterStatus, state.filterSearch)
            : tree;
        $c.append(renderProject(p, filtered));
    });
}

function renderProject(p, filteredTasks) {
    const prog = parseFloat(p.completion_progress) || 0;
    const badge = statusBadge(p.status);
    const dateStr = (p.start_date && p.end_date)
        ? `<span class="text-muted" style="font-size: .8rem;"><i class="bi bi-calendar3 me-1"></i>${fmtDate(p.start_date)} - ${fmtDate(p.end_date)}</span>` : '';
    const depHtml = (p.dependencies || []).map(d =>
        `<span class="badge text-bg-primary bg-opacity-25 text-primary border border-primary-subtle fw-normal">${escHtml(d.name)}</span>`).join('');

    return `
    <div class="card mb-3 shadow-sm border-0" data-project-id="${p.id}">
      <div class="card-header bg-white border-bottom-0 py-3 project-header cursor-pointer d-flex align-items-center gap-3 transition">
        <i class="bi bi-chevron-right text-secondary chev-icon open" id="chev-${p.id}"></i>
        <div class="grow">
          <div class="d-flex align-items-center gap-2 flex-wrap">
            <span class="fw-bold fs-5 text-dark project-name" data-edit-project="${p.id}">${escHtml(p.name)}</span>
            <span class="badge rounded-pill ${badge}">${p.status}</span>
            ${depHtml}
            ${dateStr}
          </div>
        </div>
        <div class="d-flex align-items-center gap-3 ms-auto">
          <div class="d-flex align-items-center gap-2" style="min-width: 120px;">
            <div class="progress grow" style="height: 8px;">
                <div class="progress-bar bg-primary" role="progressbar" style="width:${prog}%"></div>
            </div>
            <small class="text-muted fw-semibold" style="font-size: .8rem;">${prog.toFixed(1)}%</small>
          </div>
          <button class="btn btn-sm btn-light border btn-add-task rounded-circle text-secondary" title="Add Task" data-project-id="${p.id}" style="width: 32px; height: 32px; padding: 0;">
            <i class="bi bi-plus-lg"></i>
          </button>
        </div>
      </div>
      <div class="collapse show" id="tasks-${p.id}">
        <div class="card-body p-0 border-top bg-light">
          ${renderTaskNodes(filteredTasks, false) || '<div class="p-3 text-muted text-center" style="font-size:.85rem;">Belum ada task.</div>'}
        </div>
      </div>
    </div>`;
}

function renderTaskNodes(nodes, isSubtask) {
    if (!nodes?.length) return '';
    return nodes.map(t => {
        const deps = (t.dependencies || []).map(d =>
            `<span class="badge text-bg-secondary bg-opacity-10 text-secondary border border-secondary-subtle fw-normal">${escHtml(d.name)}</span>`).join('');
        return `
        <div class="d-flex align-items-center gap-2 py-2 px-4 border-bottom bg-white task-item ${isSubtask ? 'subtask' : ''} cursor-pointer" data-edit-task="${t.id}">
          <i class="bi bi-circle-fill text-secondary" style="font-size:.4rem;"></i>
          <span class="fw-medium text-dark" style="font-size: .9rem;">${escHtml(t.name)}</span>
          ${deps ? `<div class="d-flex gap-1 ms-2">${deps}</div>` : ''}
          <div class="ms-auto d-flex align-items-center gap-2">
            <span class="badge bg-light text-secondary border" title="Bobot"><i class="bi bi-weight"></i> ${t.weight}</span>
            <span class="badge rounded-pill ${statusBadge(t.status)}">${t.status}</span>
          </div>
        </div>
        ${renderTaskNodes(t.children || [], true)}`;
    }).join('');
}

function openProjectPanel(editId) {
    state.panelMode = 'project';
    state.editId = editId;
    $('#panel-title').text(editId ? 'Edit Project' : 'Add Project');
    $('#panel-btn-delete').toggle(!!editId);
    $('#panel-form')[0].reset();
    $('#proj-dep-chips').empty();
    $('#task-fields').hide();
    $('#project-fields').show();

    if (editId) {
        $.get(`${API.projects}/${editId}`, function (res) {
            if (!res.success) return showToast(res.message, 'danger');
            const p = res.data;
            $('#proj-name').val(p.name);
            $('#proj-start-date').val(p.start_date || '');
            $('#proj-end-date').val(p.end_date || '');
            (p.dependencies || []).forEach(d => addProjDepChip(d.id, d.name));
        });
    }
    offcanvas.show();
}

function openTaskPanel(projectId, editId) {
    state.panelMode = 'task';
    state.editId = editId;
    state.editProjectId = projectId;

    $('#panel-title').text(editId ? 'Edit Task' : 'Add Task');
    $('#panel-btn-delete').toggle(!!editId);
    $('#panel-form')[0].reset();
    $('#task-dep-chips').empty();
    $('#project-fields').hide();
    $('#task-fields').show();

    const $sel = $('#task-project').empty().append('<option value="">— Pilih Project —</option>');
    state.projects.forEach(p => {
        $sel.append(`<option value="${p.id}"${p.id == projectId ? ' selected' : ''}>${escHtml(p.name)}</option>`);
    });

    populateParentDropdown(projectId, null);
    $sel.off('change').on('change', function () {
        populateParentDropdown(parseInt($(this).val()), null);
    });

    if (editId) {
        $.get(`${API.tasks}/${editId}`, function (res) {
            if (!res.success) return showToast(res.message, 'danger');
            const t = res.data;
            $('#task-project').val(t.project_id).trigger('change');
            setTimeout(() => {
                $('#task-name').val(t.name);
                $('#task-status').val(t.status);
                $('#task-weight').val(t.weight);
                $('#task-parent').val(t.parent_task_id || '');
                (t.dependencies || []).forEach(d => addDepChip(d.id, d.name));
            }, 60);
        });
    }
    offcanvas.show();
}

function populateParentDropdown(projectId, selectedId) {
    const $sel = $('#task-parent').empty().append('<option value="">— Tidak ada (root task) —</option>');
    if (!projectId) return;
    state.tasks.filter(t => t.project_id == projectId && t.id != state.editId).forEach(t => {
        $sel.append(`<option value="${t.id}"${t.id == selectedId ? ' selected' : ''}>${escHtml(t.name)}</option>`);
    });
}

function collectDepIds() {
    const ids = [];
    $('#task-dep-chips .dep-chip-rm').each(function () { ids.push(parseInt($(this).data('id'))); });
    return ids;
}

function addDepChip(id, name) {
    if ($(`#task-dep-chips .dep-chip-rm[data-id="${id}"]`).length) return;
    $('#task-dep-chips').append(
        `<span class="badge text-bg-light border d-inline-flex align-items-center gap-1 dep-chip-rm" data-id="${id}">
            ${escHtml(name)} <button type="button" class="btn-close" style="font-size: .4rem;"></button>
        </span>`
    );
}

function populateDepOptions() {
    const $list = $('#dep-option-list').empty();
    const pid = parseInt($('#task-project').val());
    const existing = collectDepIds();
    const tasks = state.tasks.filter(t => t.id != state.editId && t.project_id == pid && !existing.includes(t.id));

    if (!tasks.length) {
        $list.append('<span class="dropdown-item text-muted" style="font-size:.85rem;">Tidak ada task lain.</span>');
        return;
    }
    tasks.forEach(t => {
        $list.append(`<button type="button" class="dropdown-item dep-option d-flex justify-content-between align-items-center py-2" data-id="${t.id}" data-name="${escHtml(t.name)}">
            <span style="font-size:.85rem;">${escHtml(t.name)}</span>
            <span class="badge rounded-pill ${statusBadge(t.status)} ms-2">${t.status}</span>
        </button>`);
    });
}

function collectProjDepIds() {
    const ids = [];
    $('#proj-dep-chips .proj-dep-chip-rm').each(function () { ids.push(parseInt($(this).data('id'))); });
    return ids;
}

function addProjDepChip(id, name) {
    if ($(`#proj-dep-chips .proj-dep-chip-rm[data-id="${id}"]`).length) return;
    $('#proj-dep-chips').append(
        `<span class="badge text-bg-light border d-inline-flex align-items-center gap-1 proj-dep-chip-rm" data-id="${id}">
            ${escHtml(name)} <button type="button" class="btn-close" style="font-size: .4rem;"></button>
        </span>`
    );
}

function populateProjDepOptions() {
    const $list = $('#proj-dep-option-list').empty();
    const existing = collectProjDepIds();
    const projects = state.projects.filter(p => p.id != state.editId && !existing.includes(p.id));

    if (!projects.length) {
        $list.append('<span class="dropdown-item text-muted" style="font-size:.85rem;">Tidak ada project lain.</span>');
        return;
    }
    projects.forEach(p => {
        $list.append(`<button type="button" class="dropdown-item proj-dep-option d-flex justify-content-between align-items-center py-2" data-id="${p.id}" data-name="${escHtml(p.name)}">
            <span style="font-size:.85rem;">${escHtml(p.name)}</span>
            <span class="badge rounded-pill ${statusBadge(p.status)} ms-2">${p.status}</span>
        </button>`);
    });
}