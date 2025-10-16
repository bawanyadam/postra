<?php $payload = json_decode($submission['payload_json'] ?? '[]', true) ?: []; ?>
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
        <p class="text-muted mb-2">Future: resend email, export, delete.</p>
      </div>
    </div>
  </div>
</div>

