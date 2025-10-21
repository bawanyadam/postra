<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h3 mb-0">New Form</h1>
  <a href="/app/projects" class="text-decoration-none">Cancel</a>
  
</div>

<div class="card card-body">
  <form method="POST" action="/app/forms/new" id="createForm">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\App\Support\Csrf::token()) ?>">

    <div class="row g-3">
      <div class="col-12">
        <label class="form-label">Project</label>
        <div class="row g-2 align-items-end">
          <div class="col-md-6">
            <select class="form-select" name="project_id" id="projectSelect">
              <option value="">Select a project…</option>
              <?php foreach (($projects ?? []) as $p): ?>
                <option value="<?= (int)$p['id'] ?>"><?= htmlspecialchars((string)$p['name']) ?></option>
              <?php endforeach; ?>
              <?php if (empty($projects)): ?>
                <option value="" disabled>(no projects yet)</option>
              <?php endif; ?>
            </select>
          </div>
          <div class="col-md-6">
            <button type="button" id="toggleNewProject" class="btn btn-outline-secondary">Or create a new project</button>
          </div>
        </div>
      </div>

      <div class="col-12" id="newProjectFields" style="display:none;">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">New Project Name</label>
            <input class="form-control" name="new_project_name" id="newProjectName">
          </div>
          <div class="col-md-6">
            <label class="form-label">Description</label>
            <input class="form-control" name="new_project_description">
          </div>
        </div>
      </div>

      <div class="col-md-4">
        <label class="form-label">Form Name</label>
        <input class="form-control" name="name" required>
      </div>
      <div class="col-md-4">
        <label class="form-label">Recipient Email</label>
        <input class="form-control" name="recipient_email" type="email" required>
      </div>
      <div class="col-md-4">
        <label class="form-label">Redirect URL</label>
        <input class="form-control" name="redirect_url" value="/app" required>
      </div>
      <div class="col-md-4">
        <label class="form-label">Allowed Domain</label>
        <input class="form-control" name="allowed_domain">
      </div>
      <div class="col-md-3">
        <label class="form-label">Status</label>
        <select class="form-select" name="status">
          <option value="active">active</option>
          <option value="disabled">disabled</option>
        </select>
      </div>
      <div class="col-12">
        <button class="btn btn-primary" type="submit">Create Form</button>
      </div>
    </div>
  </form>
</div>

<script>
  (function(){
    var toggleBtn = document.getElementById('toggleNewProject');
    var newFields = document.getElementById('newProjectFields');
    var select = document.getElementById('projectSelect');
    var newName = document.getElementById('newProjectName');
    var creating = false;
    function updateState(){
      newFields.style.display = creating ? '' : 'none';
      select.disabled = creating;
      if (!creating) {
        newName.value = '';
      }
      toggleBtn.textContent = creating ? 'Choose an existing project' : 'Or create a new project';
    }
    toggleBtn && toggleBtn.addEventListener('click', function(){ creating = !creating; updateState(); });
    updateState();
  })();
</script>

