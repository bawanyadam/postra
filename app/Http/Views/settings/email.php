<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h3 mb-0">Email Settings</h1>
  <a class="btn btn-secondary" href="/app">Back</a>
</div>

<div class="row g-3">
  <div class="col-lg-6">
    <div class="card shadow-sm">
      <div class="card-body">
        <h2 class="h5">SendGrid API Key</h2>
        <p class="text-muted mb-2">Status: <?= $configured ? '<span class="badge bg-success">Configured</span>' : '<span class="badge bg-warning text-dark">Not configured</span>' ?></p>
        <form method="POST" action="/app/settings/email">
          <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\App\Support\Csrf::token()) ?>">
          <div class="mb-3">
            <label class="form-label">New API Key</label>
            <input class="form-control" type="password" name="api_key" placeholder="SG.xxxxx" required>
            <div class="form-text">Key is stored encrypted at rest.</div>
          </div>
          <button class="btn btn-primary" type="submit">Save Key</button>
        </form>
      </div>
    </div>
  </div>

  <div class="col-lg-6">
    <div class="card shadow-sm">
      <div class="card-body">
        <h2 class="h5">Send Test Email</h2>
        <form method="POST" action="/app/settings/email/test">
          <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\App\Support\Csrf::token()) ?>">
          <div class="mb-3">
            <label class="form-label">To address</label>
            <input class="form-control" type="email" name="to" placeholder="you@example.com" required>
          </div>
          <button class="btn btn-outline-primary" type="submit">Send Test</button>
        </form>
      </div>
    </div>
  </div>
</div>

