<?php

namespace App\Http\Controllers;

use App\Support\Session;
use App\Support\Csrf;
use App\Support\Id;
use App\Infrastructure\Database\Connection;
use PDO;

class ProjectController
{
    private function requireAuth(): bool
    {
        Session::start();
        if (empty($_SESSION['user'])) {
            header('Location: /app/login', true, 303);
            return false;
        }
        return true;
    }

    public function index(): void
    {
        if (!$this->requireAuth()) return;
        $pdo = Connection::pdo();
        $projects = $pdo->query('SELECT id, public_id, name, created_at FROM projects ORDER BY created_at DESC')->fetchAll(PDO::FETCH_ASSOC);
        \App\Http\View::render('projects/index', ['projects' => $projects, 'title' => 'Projects']);
    }

    public function create(): void
    {
        if (!$this->requireAuth()) return;
        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            http_response_code(400);
            echo 'Invalid CSRF token';
            return;
        }
        $name = trim((string)($_POST['name'] ?? ''));
        $desc = trim((string)($_POST['description'] ?? ''));
        if ($name === '') {
            http_response_code(422);
            echo 'Name required';
            return;
        }
        $pdo = Connection::pdo();
        $stmt = $pdo->prepare('INSERT INTO projects (public_id, name, description) VALUES (?, ?, ?)');
        $stmt->execute([Id::ulid(), $name, $desc !== '' ? $desc : null]);
        header('Location: /app/projects', true, 303);
    }

    public function show(array $params): void
    {
        if (!$this->requireAuth()) return;
        $id = (int)($params[0] ?? 0);
        $pdo = Connection::pdo();
        $stmt = $pdo->prepare('SELECT id, name, public_id, description FROM projects WHERE id = ?');
        $stmt->execute([$id]);
        $project = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$project) { http_response_code(404); echo 'Project not found'; return; }
        $formsStmt = $pdo->prepare('SELECT id, public_id, name, status FROM forms WHERE project_id = ? ORDER BY created_at DESC');
        $formsStmt->execute([$id]);
        $forms = $formsStmt->fetchAll(PDO::FETCH_ASSOC);
        \App\Http\View::render('projects/show', ['project' => $project, 'forms' => $forms, 'title' => 'Project: ' . (string)$project['name']]);
    }

    public function updateSettings(array $params): void
    {
        if (!$this->requireAuth()) return;
        if (!Csrf::validate($_POST['_csrf'] ?? null)) { http_response_code(400); echo 'Invalid CSRF token'; return; }
        $id = (int)($params[0] ?? 0);
        $name = trim((string)($_POST['name'] ?? ''));
        $description = trim((string)($_POST['description'] ?? ''));
        if ($name === '') { $_SESSION['flash'] = 'Name cannot be empty.'; header('Location: /app/projects/' . $id, true, 303); return; }
        $pdo = Connection::pdo();
        try {
            $stmt = $pdo->prepare('UPDATE projects SET name = ?, description = ? WHERE id = ?');
            $stmt->execute([$name, $description !== '' ? $description : null, $id]);
            $_SESSION['flash'] = 'Project updated.';
        } catch (\PDOException $e) {
            if ($e->getCode() === '23000') {
                $_SESSION['flash'] = 'A project with that name already exists.';
            } else {
                $_SESSION['flash'] = 'Failed to update project: ' . $e->getMessage();
            }
        }
        header('Location: /app/projects/' . $id, true, 303);
    }
}
