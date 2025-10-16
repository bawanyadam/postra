<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h3 mb-0">Projects</h1>
  <a href="#create" class="btn btn-primary" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="create">New Project</a>
</div>

<div class="collapse mb-3" id="create">
  <div class="card card-body">
    <form method="POST" action="/app/projects">
      <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\App\Support\Csrf::token()) ?>">
      <div class="row g-3 align-items-end">
        <div class="col-md-4">
          <label class="form-label">Name</label>
          <input class="form-control" name="name" required>
        </div>
        <div class="col-md-6">
          <label class="form-label">Description</label>
          <input class="form-control" name="description">
        </div>
        <div class="col-md-2">
          <button class="btn btn-success w-100" type="submit">Create</button>
        </div>
      </div>
    </form>
  </div>
  </div>

<div class="row row-cols-1 row-cols-md-2 g-3">
  <?php foreach (($projects ?? []) as $p): ?>
    <div class="col">
      <a class="text-decoration-none" href="/app/projects/<?= (int)$p['id'] ?>">
        <div class="card h-100 shadow-sm">
          <div class="card-body">
            <h5 class="card-title mb-1"><?= htmlspecialchars($p['name']) ?></h5>
            <small class="text-muted">Created <?= htmlspecialchars($p['created_at']) ?></small>
          </div>
        </div>
      </a>
    </div>
  <?php endforeach; ?>
</div>

