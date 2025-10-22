<div class="mb-3">
  <a href="/app/projects/<?= (int)$form['project_id'] ?>" class="text-decoration-none">← Back to Project</a>
</div>
<div class="row g-3">
  <div class="col-lg-8">
    <div class="card shadow-sm mb-3">
      <div class="card-body">
        <h1 class="h4 mb-3"><?= htmlspecialchars($form['name']) ?></h1>
        <div class="mb-2"><strong>Public ID:</strong> <code><?= htmlspecialchars($form['public_id']) ?></code></div>
        <div class="mb-2"><strong>Action URL:</strong> <code>/form/<?= htmlspecialchars($form['public_id']) ?></code></div>
        <div class="mb-2"><strong>Status:</strong> <span class="badge bg-secondary"><?= htmlspecialchars($form['status']) ?></span></div>
      </div>
    </div>

    <div class="card shadow-sm mb-3">
      <div class="card-body">
        <h2 class="h5">Settings</h2>
        <form method="POST" action="/app/forms/<?= (int)$form['id'] ?>/settings" class="mt-3">
          <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\App\Support\Csrf::token()) ?>">
          <div class="row g-3">
            <div class="col-md-6"><label class="form-label">Name</label><input class="form-control" name="name" value="<?= htmlspecialchars($form['name']) ?>" required></div>
            <div class="col-md-6"><label class="form-label">Recipient Email</label><input class="form-control" name="recipient_email" value="<?= htmlspecialchars($form['recipient_email']) ?>" required></div>
            <div class="col-md-8"><label class="form-label">Redirect URL</label><input class="form-control" name="redirect_url" value="<?= htmlspecialchars($form['redirect_url']) ?>" required></div>
            <div class="col-md-6"><label class="form-label">Allowed Domain</label><input class="form-control" name="allowed_domain" value="<?= htmlspecialchars((string)$form['allowed_domain']) ?>"></div>
            <div class="col-md-4"><label class="form-label">Status</label>
              <select class="form-select" name="status">
                <option value="active"<?= $form['status']==='active'?' selected':'' ?>>active</option>
                <option value="disabled"<?= $form['status']==='disabled'?' selected':'' ?>>disabled</option>
              </select>
            </div>
            <div class="col-12"><button class="btn btn-primary" type="submit">Save</button></div>
          </div>
        </form>
      </div>
    </div>
    <a class="btn btn-outline-secondary" href="/app/forms/<?= (int)$form['id'] ?>/submissions">View Submissions</a>
  </div>
  <div class="col-lg-4">
    <div class="card shadow-sm mb-3">
      <div class="card-body">
        <h2 class="h6">Actions</h2>
        <form method="POST" action="/app/forms/<?= (int)$form['id'] ?>/send-test" class="mt-2">
          <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\App\Support\Csrf::token()) ?>">
          <button class="btn btn-outline-primary" type="submit">Send Test Email</button>
        </form>
      </div>
    </div>
    <div class="card shadow-sm">
      <div class="card-body">
        <h2 class="h6">Embed Snippet</h2>
        <p class="text-muted">Use this in your HTML.</p>
<pre class="bg-light p-2 border rounded" style="white-space: pre-wrap; word-break: break-word;"><code>&lt;form action=&quot;<?= htmlspecialchars($_ENV['APP_URL'] ?? 'http://localhost:8000') ?>/form/<?= htmlspecialchars($form['public_id']) ?>&quot; method=&quot;POST&quot;&gt;
  &lt;!-- Honeypot: keep empty --&gt;
  &lt;input type=&quot;hidden&quot; name=&quot;_postra_hp&quot; value=&quot;&quot;&gt;
  &lt;!-- Simple timing token --&gt;
  &lt;input type=&quot;hidden&quot; name=&quot;_postra_ts&quot; value=&quot;<?= time() ?>&quot;&gt;
  &lt;input type=&quot;text&quot; name=&quot;name&quot; required&gt;
  &lt;input type=&quot;email&quot; name=&quot;email&quot; required&gt;
  &lt;textarea name=&quot;message&quot; required&gt;&lt;/textarea&gt;
  &lt;button type=&quot;submit&quot;&gt;Send&lt;/button&gt;
&lt;/form&gt;</code></pre>
      </div>
    </div>
  </div>
</div>
