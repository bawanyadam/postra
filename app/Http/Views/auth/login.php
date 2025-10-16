<div class="row justify-content-center">
  <div class="col-12 col-md-6 col-lg-4">
    <div class="card shadow-sm">
      <div class="card-body">
        <h1 class="h4 mb-3">Sign in to Postra</h1>
        <form method="POST" action="/app/login">
          <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\App\Support\Csrf::token()) ?>">
          <div class="mb-3">
            <label class="form-label">Username</label>
            <input class="form-control" name="username" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Password</label>
            <input class="form-control" name="password" type="password" required>
          </div>
          <button class="btn btn-primary w-100" type="submit">Sign In</button>
        </form>
      </div>
    </div>
  </div>
</div>

