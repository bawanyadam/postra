<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <a href="/app/projects" class="text-decoration-none">← Back</a>
    <h1 class="h3 mb-0 mt-2"><?= htmlspecialchars($project['name']) ?></h1>
  </div>
  <div class="d-flex gap-2">
    <a href="#projectSettings" class="btn btn-outline-secondary" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="projectSettings">Settings</a>
    <a href="#newForm" class="btn btn-primary" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="newForm">New Form</a>
  </div>
</div>

<div class="collapse" id="projectSettings">
  <div class="card shadow-sm mt-4">
    <div class="card-body">
      <h2 class="h6">Project Settings</h2>
      <form method="POST" action="/app/projects/<?= (int)$project['id'] ?>/settings" class="mt-2">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\App\Support\Csrf::token()) ?>">
        <div class="mb-3">
          <label class="form-label">Name</label>
          <input class="form-control" name="name" value="<?= htmlspecialchars($project['name']) ?>" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Description</label>
          <textarea class="form-control" name="description" rows="3"><?= htmlspecialchars((string)($project['description'] ?? '')) ?></textarea>
        </div>
        <button class="btn btn-outline-primary" type="submit">Save</button>
      </form>
    </div>
  </div>
</div>

<div class="collapse mb-4" id="newForm">
  <div class="card card-body">
    <form method="POST" action="/app/forms/new?project=<?= (int)$project['id'] ?>">
      <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\App\Support\Csrf::token()) ?>">
      <div class="row g-3">
        <div class="col-md-4"><label class="form-label">Name</label><input class="form-control" name="name" required></div>
        <div class="col-md-4"><label class="form-label">Recipient Email</label><input class="form-control" name="recipient_email" type="email" required></div>
        <div class="col-md-4"><label class="form-label">Redirect URL</label><input class="form-control" name="redirect_url" value="/app" required></div>
        <div class="col-md-4"><label class="form-label">Allowed Domain</label><input class="form-control" name="allowed_domain"></div>
        <div class="col-md-3"><label class="form-label">Status</label>
          <select class="form-select" name="status">
            <option value="active">active</option>
            <option value="disabled">disabled</option>
          </select>
        </div>
        <div class="col-12"><button class="btn btn-success" type="submit">Create Form</button></div>
      </div>
    </form>
  </div>
 </div>

<h2 class="h5">Forms</h2>
<div class="list-group">
<?php foreach (($forms ?? []) as $f): ?>
  <a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" href="/app/forms/<?= (int)$f['id'] ?>">
    <span><?= htmlspecialchars($f['name']) ?></span>
    <span class="badge bg-secondary"><?= htmlspecialchars($f['status']) ?></span>
  </a>
<?php endforeach; ?>
</div>
