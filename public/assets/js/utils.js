"use strict";

function flattenTree(nodes, result = []) {
    for (const n of nodes) {
        const { children, ...rest } = n;
        result.push(rest);
        if (children?.length) flattenTree(children, result);
    }
    return result;
}

function buildClientTree(flat) {
    const map = {};
    const roots = [];
    for (const t of flat) map[t.id] = { ...t, children: [] };
    for (const id in map) {
        const t = map[id];
        if (t.parent_task_id && map[t.parent_task_id]) {
            map[t.parent_task_id].children.push(t);
        } else {
            roots.push(t);
        }
    }
    return roots;
}

function filterClientTree(nodes, status, search) {
    const result = [];
    for (const node of nodes) {
        const filteredChildren = filterClientTree(node.children || [], status, search);
        const selfMatch = (!status || node.status === status) &&
            (!search || node.name.toLowerCase().includes(search.toLowerCase()));
        if (selfMatch) {
            result.push({ ...node });
        } else if (filteredChildren.length) {
            result.push({ ...node, children: filteredChildren });
        }
    }
    return result;
}

function statusBadge(s) {
    return s === 'Done' ? 'text-bg-success' : s === 'In Progress' ? 'text-bg-warning' : 'text-bg-secondary';
}

function escHtml(str) {
    return String(str ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function fmtDate(d) {
    if (!d) return '';
    const [y, m, day] = d.split('-');
    return `${day}/${m}/${y}`;
}

function debounce(fn, ms) {
    let t;
    return function (...args) { clearTimeout(t); t = setTimeout(() => fn.apply(this, args), ms); };
}

function showToast(msg, type = 'primary') {
    const icon = { success: 'bi-check-circle-fill', danger: 'bi-x-circle-fill', warning: 'bi-exclamation-triangle-fill' }[type] || 'bi-info-circle-fill';
    const id = 'toast-' + Date.now();
    const html = `
    <div id="${id}" class="toast align-items-center text-bg-${type} border-0 mb-2" role="alert" aria-live="assertive" aria-atomic="true">
      <div class="d-flex">
        <div class="toast-body"><i class="bi ${icon} me-2"></i>${escHtml(msg)}</div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
      </div>
    </div>`;
    $('#toast-container').append(html);
    const toast = new bootstrap.Toast(document.getElementById(id), { delay: 3500 });
    toast.show();
    document.getElementById(id).addEventListener('hidden.bs.toast', function () {
        $(this).remove();
    });
}