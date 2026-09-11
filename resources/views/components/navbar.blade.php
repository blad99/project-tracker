    <nav class="navbar navbar-expand-lg bg-white border-bottom sticky-top shadow-sm py-2">
        <div class="container-fluid px-4">
            <span class="navbar-brand fw-bold text-primary fs-6"><i class="bi bi-kanban me-1"></i>
                Project Tracker
            </span>

            <div class="d-flex align-items-center gap-2 ms-3">
                <select id="filter-status" class="form-select form-select-sm" style="width:auto;min-width:130px;">
                    <option value="">Semua Status</option>
                    <option value="Draft">Draft</option>
                    <option value="In Progress">In Progress</option>
                    <option value="Done">Done</option>
                </select>
                <div class="input-group input-group-sm" style="max-width:220px;">
                    <span class="input-group-text bg-white text-muted"><i class="bi bi-search"></i></span>
                    <input id="filter-search" type="text" class="form-control border-start-0 ps-0"
                        placeholder="Cari task...">
                </div>
            </div>

            <div class="ms-auto d-flex gap-2">
                <button id="btn-add-project" class="btn btn-sm btn-outline-primary fw-semibold">
                    <i class="bi bi-folder-plus me-1"></i>
                    Add Project
                </button>
                <button id="btn-add-task" class="btn btn-sm btn-primary fw-semibold">
                    <i class="bi bi-plus-lg me-1"></i>
                    Add Task
                </button>
            </div>
        </div>
    </nav>
