"use strict";

$(function () {
    initUI();
    loadAll();

    $('#btn-add-project').on('click', () => openProjectPanel(null));
    $('#btn-add-task').on('click', () => openTaskPanel(null, null));

    $('#filter-status').on('change', function () {
        state.filterStatus = $(this).val();
        renderAll();
    });

    $('#filter-search').on('input', debounce(function () {
        state.filterSearch = $(this).val().trim();
        renderAll();
    }, 300));

    $('#panel-btn-save').on('click', function () {
        state.panelMode === 'project' ? saveProject() : saveTask();
    });
    $('#panel-btn-delete').on('click', handleDelete);

    $(document).on('click', '.dep-option', function (e) {
        e.stopPropagation(); // Keep dropdown open or handle manually
        addDepChip($(this).data('id'), $(this).data('name'));
    });
    $(document).on('click', '.dep-chip-rm .btn-close', function () {
        $(this).closest('.dep-chip-rm').remove();
    });
    $('#dep-search-input').on('input', function (e) {
        e.stopPropagation();
        const q = $(this).val().toLowerCase();
        $('#dep-option-list .dep-option').each(function () {
            $(this).toggle(!q || String($(this).data('name')).toLowerCase().includes(q));
        });
    });
    $('#dep-trigger').on('click', function () {
        populateDepOptions();
    });

    $(document).on('click', '.proj-dep-option', function (e) {
        e.stopPropagation();
        addProjDepChip($(this).data('id'), $(this).data('name'));
    });

    $(document).on('click', '.proj-dep-chip-rm .btn-close', function () {
        $(this).closest('.proj-dep-chip-rm').remove();
    });

    $('#proj-dep-trigger').on('click', function () {
        populateProjDepOptions();
    });

    $(document).on('click', '[data-edit-project]', function (e) {
        e.stopPropagation();
        openProjectPanel(parseInt($(this).data('edit-project')));
    });

    $(document).on('click', '[data-edit-task]', function (e) {
        e.stopPropagation();
        openTaskPanel(null, parseInt($(this).data('edit-task')));
    });

    $(document).on('click', '.btn-add-task', function (e) {
        e.stopPropagation();
        openTaskPanel(parseInt($(this).data('project-id')), null);
    });

    $(document).on('show.bs.collapse', '.collapse', function () {
        const id = $(this).attr('id').replace('tasks-', '');
        $(`#chev-${id}`).addClass('open');
    });

    $(document).on('hide.bs.collapse', '.collapse', function () {
        const id = $(this).attr('id').replace('tasks-', '');
        $(`#chev-${id}`).removeClass('open');
    });

    $(document).on('click', '.project-header', function (e) {
        if ($(e.target).closest('.btn-add-task, .project-name').length) return;
        const pid = $(this).closest('.project-card, .card').data('project-id');
        $(`#tasks-${pid}`).collapse('toggle');
    });
});