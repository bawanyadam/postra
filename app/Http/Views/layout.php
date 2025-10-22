<?php
\App\Support\Session::start();
$user = $_SESSION['user'] ?? null;
$csrf = \App\Support\Csrf::token();
$sidebarProjects = [];
$sidebarError = null;
if ($user) {
    try {
        $pdo = \App\Infrastructure\Database\Connection::pdo();
        $sidebarProjects = $pdo->query('SELECT id, name FROM projects ORDER BY name ASC')->fetchAll(\PDO::FETCH_ASSOC);
    } catch (\Throwable $e) {
        $sidebarError = $e->getMessage();
    }
}
?><!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" sizes="32x32" href="/images/logo-touch.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/images/logo-touch.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/images/logo-touch.png">
    <meta property="og:image" content="https://postra.to/images/logo-touch.png">
    <meta name="twitter:image" content="https://postra.to/images/logo-touch.png">
    <?php $pageTitle = isset($title) && $title !== '' ? ('Postra | ' . $title) : 'Postra'; ?>
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <style>
      :root {
        --sidebar-width: 260px;
        --sidebar-bg-start: #7246ff;
        --sidebar-bg-end: #7246ff;
        --sidebar-text: rgba(255, 255, 255, 0.86);
        --sidebar-text-muted: rgba(255, 255, 255, 0.6);
        --sidebar-active: rgba(255, 255, 255, 0.16);
        --sidebar-border: rgba(255, 255, 255, 0.08);
        /* Brand link colors */
        --bs-link-color: #7246ff;
        --bs-link-hover-color: #6940eb;
      }

      body {
        min-height: 100vh;
        background: #f4f6fb;
      }

      .app-shell {
        display: flex;
        min-height: calc(100vh - 56px);
      }

      @media (min-width: 992px) {
        .navbar-top {
          display: none;
        }
        .app-shell {
          min-height: 100vh;
        }
      }

      .sidebar {
        flex: 0 0 var(--sidebar-width);
        max-width: var(--sidebar-width);
        width: var(--sidebar-width);
        background: linear-gradient(180deg, var(--sidebar-bg-start) 0%, var(--sidebar-bg-end) 100%);
        color: var(--sidebar-text);
        padding: 1.75rem 1.25rem;
        box-shadow: 6px 0 16px rgba(12, 35, 97, 0.24);
      }

      .sidebar a {
        color: inherit;
        text-decoration: none;
      }

      .sidebar-brand {
        font-size: 1.25rem;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        color: #fff;
        margin-bottom: 2rem;
      }

      .sidebar-section {
        margin-bottom: 1.5rem;
      }

      .sidebar-section-label {
        font-size: 0.75rem;
        text-transform: uppercase;
        font-weight: 600;
        letter-spacing: 0.08em;
        color: var(--sidebar-text-muted);
        margin-bottom: 0.75rem;
      }

      .sidebar-link {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.65rem 0.75rem;
        border-radius: 10px;
        font-weight: 500;
        font-size: 0.95rem;
        color: var(--sidebar-text);
        background: transparent;
        transition: background 0.25s ease, transform 0.25s ease;
      }

      .sidebar-link:hover {
        background: rgba(255, 255, 255, 0.12);
        transform: translateX(4px);
      }

      .sidebar-toggle {
        border: none;
        width: 100%;
        text-align: left;
      }

      .sidebar-toggle:focus-visible {
        outline: 2px solid rgba(255, 255, 255, 0.6);
        outline-offset: 2px;
      }

      .sidebar-toggle .caret {
        display: inline-flex;
        transition: transform 0.2s ease;
      }

      .sidebar-toggle.is-open .caret {
        transform: rotate(90deg);
      }

      .sidebar-collapsible {
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.3s ease;
      }

      .sidebar-collapsible.show {
        max-height: 500px;
      }

      .sidebar-collapsible .sidebar-sub-link {
        display: block;
        padding: 0.55rem 0.75rem 0.55rem 2.25rem;
        border-radius: 8px;
        font-size: 0.9rem;
        color: var(--sidebar-text-muted);
        transition: background 0.25s ease, color 0.25s ease;
      }

      .sidebar-collapsible .sidebar-sub-link:hover {
        color: #fff;
        background: rgba(255, 255, 255, 0.1);
      }

      .sidebar-divider {
        border-bottom: 1px solid var(--sidebar-border);
        margin: 1.75rem 0;
      }

      .main-content {
        flex: 1 1 auto;
        padding: 2rem 1.75rem;
      }

      @media (max-width: 991.98px) {
        .sidebar {
          display: none;
        }
        .main-content {
          padding: 1.5rem 1rem 2.5rem;
        }
      }

      /* Breadcrumb links use brand purple */
      .breadcrumb .breadcrumb-item a {
        color: var(--bs-link-color);
        text-decoration: none;
      }
      .breadcrumb .breadcrumb-item a:hover {
        color: var(--bs-link-hover-color);
        text-decoration: underline;
      }

      /* Ensure visited links remain brand purple */
      a:visited { color: var(--bs-link-color); }
      .sidebar a:visited { color: inherit; }

      .logout-button {
        width: 100%;
        margin-top: 0.5rem;
      }

      .sidebar-subtitle {
        font-size: 0.8rem;
        color: var(--sidebar-text-muted);
        margin: 0.4rem 0 0.1rem 0.75rem;
      }

      /* Primary button styling aligned with sidebar purple */
      .btn-primary {
        --bs-btn-bg: #7246ff; /* base */
        --bs-btn-border-color: #7246ff;
        --bs-btn-hover-bg: #6940eb; /* ~8% darker */
        --bs-btn-hover-border-color: #6940eb;
        --bs-btn-active-bg: #603bd6; /* ~16% darker */
        --bs-btn-active-border-color: #603bd6;
        --bs-btn-disabled-bg: #7246ff;
        --bs-btn-disabled-border-color: #7246ff;
        --bs-btn-focus-shadow-rgb: 114, 70, 255; /* focus ring */
      }
    </style>
  </head>
  <body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark navbar-top">
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

    <div class="app-shell">
      <?php if ($user): ?>
      <aside class="sidebar d-none d-lg-flex flex-column">
        <a class="sidebar-brand" href="/app">
          <img src="https://postra.to/images/postra-white.png" alt="Postra" width="120" height="auto">
        </a>
        <div class="sidebar-section">
          <div class="sidebar-section-label">Create</div>
          <a class="sidebar-link" href="/app/projects/new">
            <span>New Project</span>
            <span class="caret" aria-hidden="true">
              <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                <path d="M8 1a.75.75 0 0 1 .75.75V7.25h5.5a.75.75 0 0 1 0 1.5h-5.5v5.5a.75.75 0 0 1-1.5 0v-5.5H1.75a.75.75 0 0 1 0-1.5h5.5V1.75A.75.75 0 0 1 8 1z"/>
              </svg>
            </span>
          </a>
          <a class="sidebar-link" href="/app/forms/new">
            <span>New Form</span>
            <span class="caret" aria-hidden="true">
              <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                <path d="M8 1a.75.75 0 0 1 .75.75V7.25h5.5a.75.75 0 0 1 0 1.5h-5.5v5.5a.75.75 0 0 1-1.5 0v-5.5H1.75a.75.75 0 0 1 0-1.5h5.5V1.75A.75.75 0 0 1 8 1z"/>
              </svg>
            </span>
          </a>
        </div>

        <div class="sidebar-section">
          <div class="sidebar-section-label">Forms</div>
          <button class="sidebar-link sidebar-toggle<?= !empty($sidebarProjects) ? ' is-open' : '' ?>" type="button" data-sidebar-toggle="sidebar-projects" aria-expanded="<?= !empty($sidebarProjects) ? 'true' : 'false' ?>">
            <span>Projects</span>
            <span class="caret">
              <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                <path fill-rule="evenodd" d="M4.646 1.646a.5.5 0 0 1 .708 0l5.5 5.5a.5.5 0 0 1 0 .708l-5.5 5.5a.5.5 0 1 1-.708-.708L9.793 7.5 4.646 2.354a.5.5 0 0 1 0-.708z"/>
              </svg>
            </span>
          </button>
          <div id="sidebar-projects" class="sidebar-collapsible<?= !empty($sidebarProjects) ? ' show' : '' ?>" aria-hidden="<?= empty($sidebarProjects) ? 'true' : 'false' ?>">
            <?php if ($sidebarError): ?>
              <div class="sidebar-subtitle">Unable to load projects.</div>
            <?php elseif (empty($sidebarProjects)): ?>
              <div class="sidebar-subtitle">No projects yet.</div>
            <?php else: ?>
              <?php foreach ($sidebarProjects as $project): ?>
                <a class="sidebar-sub-link" href="/app/projects/<?= (int)$project['id'] ?>">
                  <?= htmlspecialchars((string)$project['name']) ?>
                </a>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
          <a class="sidebar-link mt-2" href="/app/submissions">Submissions</a>
        </div>
        <div class="sidebar-divider"></div>
        <div class="sidebar-section">
          <div class="sidebar-section-label">Account</div>
          <a class="sidebar-link" href="/app/settings/email">Settings</a>
          <form method="POST" action="/app/logout" class="d-flex flex-column mt-1">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
            <button class="btn btn-light btn-sm logout-button" type="submit">Logout</button>
          </form>
        </div>
      </aside>
      <?php endif; ?>
      <main class="main-content">
      <?php if (!empty($breadcrumbs) && is_array($breadcrumbs)): ?>
        <nav aria-label="breadcrumb" class="mb-3">
          <ol class="breadcrumb mb-0">
            <?php $lastIdx = count($breadcrumbs) - 1; foreach ($breadcrumbs as $i => $bc): ?>
              <?php $label = htmlspecialchars((string)($bc['label'] ?? '')); $href = (string)($bc['href'] ?? ''); ?>
              <?php if ($i === $lastIdx || $href === ''): ?>
                <li class="breadcrumb-item active" aria-current="page"><?= $label ?></li>
              <?php else: ?>
                <li class="breadcrumb-item"><a href="<?= htmlspecialchars($href) ?>"><?= $label ?></a></li>
              <?php endif; ?>
            <?php endforeach; ?>
          </ol>
        </nav>
      <?php endif; ?>
      <?php if (!empty($_SESSION['flash'])): ?>
        <div class="alert alert-warning"><?= htmlspecialchars($_SESSION['flash']); unset($_SESSION['flash']); ?></div>
      <?php endif; ?>
      <?= $content ?>
      </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
    <script>
      document.querySelectorAll('[data-sidebar-toggle]').forEach((toggle) => {
        toggle.addEventListener('click', () => {
          const targetId = toggle.getAttribute('data-sidebar-toggle');
          if (!targetId) return;
          const target = document.getElementById(targetId);
          if (!target) return;
          const willOpen = !target.classList.contains('show');
          target.classList.toggle('show');
          toggle.classList.toggle('is-open', willOpen);
          toggle.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
          target.setAttribute('aria-hidden', willOpen ? 'false' : 'true');
        });
      });
    </script>
  </body>
  </html>
