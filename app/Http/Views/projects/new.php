<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h3 mb-0">New Project</h1>
  <a href="/app/projects" class="text-decoration-none">Cancel</a>
</div>

<div class="card card-body">
  <form method="POST" action="/app/projects">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\App\Support\Csrf::token()) ?>">
    <div class="row g-3 align-items-end">
      <div class="col-md-5">
        <label class="form-label">Name</label>
        <input class="form-control" name="name" required>
      </div>
      <div class="col-md-5">
        <label class="form-label">Description</label>
        <input class="form-control" name="description">
      </div>
      <div class="col-md-2">
        <button class="btn btn-primary w-100" type="submit">Create Project</button>
      </div>
    </div>
  </form>
</div>

