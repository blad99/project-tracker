<x-layout>
    <div class="container py-4" style="max-width: 1000px;">
        <div id="project-container">
            <div class="text-center py-5 text-muted">
                <i class="bi bi-arrow-clockwise fs-1"></i>
                <p class="mt-2">Memuat data...</p>
            </div>
        </div>
    </div>

    <div class="offcanvas offcanvas-end" tabindex="-1" id="form-offcanvas" style="width: 450px;">
        <div class="offcanvas-header border-bottom">
            <h6 class="offcanvas-title fw-bold" id="panel-title">Add Project</h6>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>

        <div class="offcanvas-body">
            <form id="panel-form" novalidate autocomplete="off">
                <div id="project-fields">
                    <div class="mb-3">
                        <label class="form-label" for="proj-name">Nama Project <span
                                class="text-danger">*</span></label>
                        <input type="text" id="proj-name" class="form-control" placeholder="Nama project...">
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col">
                            <label class="form-label" for="proj-start-date">Start Date</label>
                            <input type="date" id="proj-start-date" class="form-control">
                        </div>
                        <div class="col">
                            <label class="form-label" for="proj-end-date">End Date</label>
                            <input type="date" id="proj-end-date" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Project Dependencies</label>
                        <div class="p-2 border rounded bg-white min-vh-25">
                            <div id="proj-dep-chips" class="d-flex flex-wrap gap-1 mb-2"></div>
                            <div class="dropdown">
                                <button type="button" id="proj-dep-trigger" class="btn btn-sm btn-outline-secondary"
                                    data-bs-toggle="dropdown">
                                    <i class="bi bi-plus"></i> Tambah
                                </button>
                                <div class="dropdown-menu shadow-sm p-1"
                                    style="max-height:200px;overflow-y:auto;min-width:250px;">
                                    <div id="proj-dep-option-list"></div>
                                </div>
                            </div>
                        </div>
                        <small class="text-muted d-block mt-1" style="font-size:.75rem;">
                            Project tidak bisa In Progress/Done jika dependency belum Done.
                        </small>
                    </div>
                    <div class="alert alert-info py-2 px-3 mt-4" style="font-size:.8rem;">
                        <i class="bi bi-info-circle me-1"></i> Status & progress dihitung otomatis dari task.
                    </div>
                </div>

                <div id="task-fields" style="display:none;">
                    <div class="mb-3">
                        <label class="form-label" for="task-project">Project <span class="text-danger">*</span></label>
                        <select id="task-project" class="form-select">
                            <option value="">— Pilih Project —</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="task-name">Nama Task <span class="text-danger">*</span></label>
                        <input type="text" id="task-name" class="form-control" placeholder="Nama task...">
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col">
                            <label class="form-label" for="task-status">Status</label>
                            <select id="task-status" class="form-select">
                                <option value="Draft">Draft</option>
                                <option value="In Progress">In Progress</option>
                                <option value="Done">Done</option>
                            </select>
                        </div>
                        <div class="col">
                            <label class="form-label" for="task-weight">Bobot</label>
                            <input type="number" id="task-weight" class="form-control" value="1" min="1"
                                max="1000">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="task-parent">Parent Task (Subtask dari)</label>
                        <select id="task-parent" class="form-select">
                            <option value="">— Tidak ada (root task) —</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Task Dependencies</label>
                        <div class="p-2 border rounded bg-white min-vh-25">
                            <div id="task-dep-chips" class="d-flex flex-wrap gap-1 mb-2"></div>
                            <div class="dropdown">
                                <button type="button" id="dep-trigger" class="btn btn-sm btn-outline-secondary"
                                    data-bs-toggle="dropdown">
                                    <i class="bi bi-plus"></i> Tambah
                                </button>
                                <div class="dropdown-menu shadow-sm p-2"
                                    style="max-height:250px;overflow-y:auto;min-width:250px;">
                                    <input id="dep-search-input" type="text"
                                        class="form-control form-control-sm mb-2" placeholder="Cari task...">
                                    <div id="dep-option-list"></div>
                                </div>
                            </div>
                        </div>
                        <small class="text-muted d-block mt-1" style="font-size:.75rem;">
                            Task tidak bisa Done jika dependency belum Done.
                        </small>
                    </div>
                </div>

            </form>
        </div>

        <div class="offcanvas-header bg-light border-top d-flex justify-content-end gap-2">
            <button type="button" id="panel-btn-delete" class="btn btn-outline-danger" style="display:none;">
                <i class="bi bi-trash me-1"></i>
                Hapus
            </button>
            <button type="button" id="panel-btn-save" class="btn btn-primary">
                <i class="bi bi-floppy me-1"></i>
                Simpan
            </button>
        </div>
    </div>
</x-layout>
