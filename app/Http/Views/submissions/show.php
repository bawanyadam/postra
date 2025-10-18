<?php
$payload = json_decode($submission['payload_json'] ?? '[]', true) ?: [];
$csrf = \App\Support\Csrf::token();
?>
<div class="mb-3">
  <a href="/app/forms/<?= (int)$submission['form_id'] ?>/submissions" class="text-decoration-none">← Back to Submissions</a>
</div>

<div class="row g-3">
  <div class="col-lg-8">
    <div class="card shadow-sm mb-3">
      <div class="card-body">
        <h1 class="h5 mb-3">Submission #<?= (int)$submission['id'] ?></h1>
        <dl class="row mb-0">
          <dt class="col-sm-3">When</dt><dd class="col-sm-9"><?= htmlspecialchars($submission['created_at']) ?></dd>
          <dt class="col-sm-3">Form</dt><dd class="col-sm-9"><?= htmlspecialchars($submission['form_name'] ?? '') ?></dd>
          <dt class="col-sm-3">IP</dt><dd class="col-sm-9"><?= htmlspecialchars((string)$submission['client_ip']) ?></dd>
          <dt class="col-sm-3">User-Agent</dt><dd class="col-sm-9"><code><?= htmlspecialchars((string)$submission['user_agent']) ?></code></dd>
        </dl>
      </div>
    </div>

    <div class="card shadow-sm">
      <div class="card-body">
        <h2 class="h6">Fields</h2>
        <div class="table-responsive">
          <table class="table table-sm">
            <tbody>
            <?php foreach ($payload as $k => $v): ?>
              <tr>
                <th style="width: 220px; white-space:nowrap;"><?= htmlspecialchars($k) ?></th>
                <td><?php if (is_array($v)) { echo htmlspecialchars(implode(', ', array_map('strval', $v))); } else { echo htmlspecialchars((string)$v); } ?></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card shadow-sm">
      <div class="card-body">
        <h2 class="h6">Actions</h2>
        <form method="POST" action="/app/submissions/<?= (int)$submission['id'] ?>/resend" class="mb-2">
          <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
          <button class="btn btn-primary w-100" type="submit">Resend Email</button>
        </form>
        <a class="btn btn-outline-secondary w-100 mb-2" href="/app/submissions/<?= (int)$submission['id'] ?>/export.json">Download JSON</a>
        <form method="POST" action="/app/submissions/<?= (int)$submission['id'] ?>/delete" onsubmit="return confirm('Delete this submission?');">
          <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
          <button class="btn btn-outline-danger w-100" type="submit">Delete Submission</button>
        </form>
      </div>
    </div>
  </div>
</div>
