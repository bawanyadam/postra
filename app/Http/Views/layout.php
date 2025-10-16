<?php
\App\Support\Session::start();
$user = $_SESSION['user'] ?? null;
$csrf = \App\Support\Csrf::token();
?><!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php $pageTitle = isset($title) && $title !== '' ? ('Postra | ' . $title) : 'Postra'; ?>
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
  </head>
  <body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
      <div class="container-fluid">
        <a class="navbar-brand" href="/app">Postra</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
          <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
          <ul class="navbar-nav me-auto mb-2 mb-lg-0">
            <?php if ($user): ?>
            <li class="nav-item"><a class="nav-link" href="/app/projects">Projects</a></li>
            <li class="nav-item"><a class="nav-link" href="/app/submissions">Submissions</a></li>
            <li class="nav-item"><a class="nav-link" href="/app/settings/email">Settings</a></li>
            <?php endif; ?>
          </ul>
          <div class="d-flex">
            <?php if ($user): ?>
              <span class="navbar-text me-2">Hi, <?= htmlspecialchars($user) ?></span>
              <form method="POST" action="/app/logout" class="d-inline">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
                <button class="btn btn-outline-light btn-sm" type="submit">Logout</button>
              </form>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </nav>

    <main class="container my-4">
      <?php if (!empty($_SESSION['flash'])): ?>
        <div class="alert alert-warning"><?= htmlspecialchars($_SESSION['flash']); unset($_SESSION['flash']); ?></div>
      <?php endif; ?>
      <?= $content ?>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
  </body>
  </html>
