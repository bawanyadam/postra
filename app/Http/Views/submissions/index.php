<?php
$csrf = \App\Support\Csrf::token();
$returnTo = '/app/submissions';
if (($page ?? 1) > 1) {
    $returnTo .= '?page=' . (int)$page;
}
?>
<form method="POST" action="/app/submissions/bulk-delete" onsubmit="return confirm('Delete selected submissions?');">
  <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
  <input type="hidden" name="return_to" value="<?= htmlspecialchars($returnTo) ?>">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Submissions</h1>
    <div class="d-flex gap-2">
      <a class="btn btn-outline-secondary" href="/app/submissions/export.csv">Export CSV</a>
      <button type="submit" class="btn btn-outline-danger" name="delete" value="1">Delete Selected</button>
    </div>
   </div>

  <div class="card shadow-sm">
    <div class="table-responsive">
      <table class="table table-striped align-middle mb-0">
        <thead>
          <tr>
            <th style="width: 1%;">
              <input class="form-check-input" type="checkbox" data-check-all>
            </th>
            <th>When</th>
            <th>Project</th>
            <th>Form</th>
            <th>IP</th>
            <th>Preview</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($submissions ?? [])): ?>
            <tr>
              <td colspan="7" class="text-center text-muted py-4">No submissions yet.</td>
            </tr>
          <?php else: ?>
          <?php foreach (($submissions ?? []) as $s): ?>
            <?php $payload = json_decode($s['payload_json'] ?? '[]', true) ?: []; ?>
            <tr>
              <td>
                <input class="form-check-input" type="checkbox" name="submission_ids[]" value="<?= (int)$s['id'] ?>" data-check-item>
              </td>
              <td style="white-space:nowrap;"><?= htmlspecialchars($s['created_at']) ?></td>
              <td><?= htmlspecialchars($s['project_name'] ?? '') ?></td>
              <td><a href="/app/forms/<?= (int)$s['form_id'] ?>"><?= htmlspecialchars($s['form_name'] ?? '') ?></a></td>
              <td><?= htmlspecialchars((string)$s['client_ip']) ?></td>
              <td>
                <?php
                  $pairs = [];
                  $i = 0;
                  foreach ($payload as $k => $v) {
                    if ($i++ >= 4) break;
                    $val = is_array($v) ? implode(', ', array_map('strval', $v)) : (string)$v;
                    $pairs[] = htmlspecialchars($k) . ': ' . htmlspecialchars($val);
                  }
                  echo implode(' · ', $pairs);
                ?>
              </td>
              <td class="text-end"><a class="btn btn-sm btn-outline-secondary" href="/app/submissions/<?= (int)$s['id'] ?>">View</a></td>
            </tr>
          <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</form>
<script>
document.addEventListener('DOMContentLoaded', function () {
  var forms = document.querySelectorAll('form[action="/app/submissions/bulk-delete"]');
  forms.forEach(function (form) {
    var master = form.querySelector('[data-check-all]');
    if (!master) return;
    var items = form.querySelectorAll('[data-check-item]');
    master.addEventListener('change', function () {
      items.forEach(function (item) { item.checked = master.checked; });
    });
  });
});
</script>

<nav class="mt-3" aria-label="Submissions pagination">
  <ul class="pagination justify-content-center">
    <?php $prevPage = max(1, (int)($page ?? 1) - 1); $nextPage = (int)($page ?? 1) + 1; ?>
    <li class="page-item <?= (($page ?? 1) <= 1) ? 'disabled' : '' ?>">
      <a class="page-link" href="/app/submissions?page=<?= $prevPage ?>" tabindex="-1">Previous</a>
    </li>
    <li class="page-item active"><span class="page-link">Page <?= (int)($page ?? 1) ?></span></li>
    <li class="page-item <?= !($hasNext ?? false) ? 'disabled' : '' ?>">
      <a class="page-link" href="/app/submissions?page=<?= $nextPage ?>">Next</a>
    </li>
  </ul>
  <p class="text-center text-muted small mb-0">Showing up to 25 per page</p>
</nav>
