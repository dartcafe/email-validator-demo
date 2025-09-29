<?php
declare(strict_types=1);

namespace Dartcafe\EmailValidator\Demo\Controller;

use Dartcafe\EmailValidator\Demo\Http\Request;
use Dartcafe\EmailValidator\Demo\Http\Response;

/**
 * Controller for static pages.
 */
final class PagesController
{
    /**
     * Render the home page
     */
    public function home(Request $_): Response
    {
        ob_start(); ?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>Email Validator Demo</title>
  <link rel="stylesheet" href="/style.css"/>
  <script src="/app.js?v=1" defer></script>
</head>
<body>
  <main class="container">
    <header class="hero">
      <h1>Email Validator</h1>
      <p class="muted">Format validity, sendability prediction, and configurable lists — with a friendly UI.</p>
    </header>

    <section class="card">
      <h2>Validate</h2>
      <form id="form" class="row gap">
        <input id="email" name="email" type="email" placeholder="user@example.com" required/>
        <button type="submit" class="btn primary">Validate (POST)</button>
        <button id="btn-get" type="button" class="btn">Validate (GET)</button>
      </form>
      <div id="result" class="result"><div class="placeholder">Enter an email and validate to see results.</div></div>
      <details class="mt"><summary>Raw JSON</summary><pre id="out" class="out">// result will appear here</pre></details>
    </section>

    <section class="card">
      <h2>List configuration</h2>
      <p class="muted">Edit <code>config/lists.ini</code> and referenced text files. Import/Export supported.</p>

      <div class="grid-2">
        <div>
          <label for="ini">lists.ini</label>
          <textarea id="ini" spellcheck="false" class="mono" rows="18" placeholder="[deny_disposable] ..."></textarea>
        </div>
        <div>
          <div class="row between">
            <label>Referenced list files</label>
            <div class="row gap-s">
              <input id="file-input" type="file" accept=".zip,application/zip,application/json" hidden/>
              <button id="btn-import" type="button" class="btn">Import</button>
              <button id="btn-export" type="button" class="btn">Export</button>
              <button id="btn-load" type="button" class="btn">Reload</button>
              <button id="btn-save" type="button" class="btn primary">Save</button>
            </div>
          </div>
          <div id="files" class="accordion"></div>
        </div>
      </div>
    </section>
  </main>
</body>
</html>
<?php
        return new Response(200, (string)ob_get_clean(), ['Content-Type' => 'text/html; charset=utf-8']);
    }
}
