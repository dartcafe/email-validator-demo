/* Email Validator Demo – app.js */
"use strict";

// ---------- small helpers ----------
const $ = (s) => document.querySelector(s);
const out = $("#out");
const form = $("#form");
const btnGet = $("#btn-get");
const filesDiv = $("#files");
const iniArea = $("#ini");
const btnLoad = $("#btn-load");
const btnSave = $("#btn-save");
const btnImport = $("#btn-import");
const btnExport = $("#btn-export");
const fileInput = $("#file-input");

const showRaw = (obj) => {
  if (!out) return;
  out.textContent = JSON.stringify(obj, null, 2);
};

const icons = {
  check:
    '<svg width="18" height="18" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M9 16.2 4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4z"/></svg>',
  x: '<svg width="18" height="18" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M18.3 5.71 12 12.01l-6.29-6.3-1.42 1.42L10.59 13.4l-6.3 6.3 1.42 1.41L12 14.82l6.29 6.29 1.42-1.41-6.3-6.3 6.3-6.29z"/></svg>',
  warn: '<svg width="18" height="18" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M1 21h22L12 2 1 21zm12-3h-2v2h2v-2zm0-8h-2v6h2V10z"/></svg>',
  copy: '<svg width="16" height="16" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M16 1H4c-1.1 0-2 .9-2 2v12h2V3h12V1zm3 4H8c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h11c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2zm0 16H8V7h11v14z"/></svg>',
};

const copyBtn = (text) => {
  const btn = document.createElement("button");
  btn.type = "button";
  btn.className = "btn ghost xs";
  btn.innerHTML = icons.copy;
  btn.title = "Copy";
  btn.addEventListener("click", async () => {
    try {
      await navigator.clipboard.writeText(text);
      btn.classList.add("okflash");
      setTimeout(() => btn.classList.remove("okflash"), 600);
    } catch {}
  });
  return btn;
};

const checklistItem = (ok, label, detail = "") => {
  const div = document.createElement("div");
  div.className = "check " + (ok ? "ok" : "bad");
  div.innerHTML = `<span class="icon">${
    ok ? icons.check : icons.x
  }</span><div class="ct"><div class="lbl">${label}</div>${
    detail ? `<div class="sub">${detail}</div>` : ""
  }</div>`;
  return div;
};

const warnItem = (label, detail = "") => {
  const div = document.createElement("div");
  div.className = "check warn";
  div.innerHTML = `<span class="icon">${
    icons.warn
  }</span><div class="ct"><div class="lbl">${label}</div>${
    detail ? `<div class="sub">${detail}</div>` : ""
  }</div>`;
  return div;
};

// ---------- pretty checklist ----------
function renderChecklist(data) {
  const wrap = document.createElement("div");
  wrap.className = "checks";

  // header: query / normalized / suggestion
  const head = document.createElement("div");
  head.className = "summary row wrap gap-s";

  const q = document.createElement("div");
  q.className = "kv";
  q.innerHTML = `<div class="k">Query</div><div class="v mono">${
    data.query ?? ""
  }</div>`;
  head.appendChild(q);

  if (data.corrections?.normalized) {
    const n = document.createElement("div");
    n.className = "kv";
    n.innerHTML = `<div class="k">Normalized</div><div class="v mono">${data.corrections.normalized}</div>`;
    n.appendChild(copyBtn(data.corrections.normalized));
    head.appendChild(n);
  }
  if (data.corrections?.suggestion) {
    const s = document.createElement("div");
    s.className = "kv";
    s.innerHTML = `<div class="k">Suggestion</div><div class="v mono">${data.corrections.suggestion}</div>`;
    s.appendChild(copyBtn(data.corrections.suggestion));
    head.appendChild(s);
  }
  wrap.appendChild(head);

  const fmtOk = !!data.simpleResults?.formatValid;
  const sndOk = !!data.simpleResults?.isSendable;
  const hasWarn = !!data.simpleResults?.hasWarnings;

  // format
  const fmtReasons = Array.isArray(data.reasons)
    ? data.reasons.filter((r) =>
        [
          "empty",
          "syntax",
          "missing_at",
          "local_too_long",
          "domain_too_long",
          "address_too_long",
          "domain_malformed",
        ].includes(r)
      )
    : [];
  const fmtDetail = fmtOk
    ? "Syntax & structure look good."
    : fmtReasons[0] ?? "Format error";
  wrap.appendChild(checklistItem(fmtOk, "Format valid", fmtDetail));

  // deliverability
  const dns = data.dns ?? {};
  const dnsDetail = !fmtOk
    ? "Format invalid"
    : dns.domainExists === false
    ? "Domain not found"
    : dns.hasMx === false
    ? "No MX records"
    : "Domain & MX OK";
  wrap.appendChild(checklistItem(sndOk, "Sendable", dnsDetail));

  // warnings
  if (hasWarn) {
    const w = Array.isArray(data.warnings) ? data.warnings : [];
    const wLine = w.length
      ? w.map((x) => `<span class="tag">${x}</span>`).join(" ")
      : "Has non-fatal warnings.";
    wrap.appendChild(warnItem("Warnings", wLine));
  } else {
    wrap.appendChild(checklistItem(true, "No warnings"));
  }

  // DNS mini checks
  const dnsRow = document.createElement("div");
  dnsRow.className = "row gap-s mt";
  const d1 = checklistItem(dns.domainExists === true, "Domain exists");
  const d2 = checklistItem(dns.hasMx === true, "MX record present");
  d1.classList.add("mini");
  d2.classList.add("mini");
  dnsRow.appendChild(d1);
  dnsRow.appendChild(d2);
  wrap.appendChild(dnsRow);

  // lists
  if (Array.isArray(data.lists)) {
    const matched = data.lists.filter((l) => l.matched);
    const listsBox = document.createElement("div");
    listsBox.className = "lists mt";
    const title = document.createElement("div");
    title.className = "lbl";
    title.textContent = "Lists";
    listsBox.appendChild(title);

    const chipbar = document.createElement("div");
    chipbar.className = "row wrap gap-s";
    if (matched.length) {
      matched.forEach((m) => {
        const t = document.createElement("span");
        t.className = "chip " + (m.typ === "deny" ? "chip-deny" : "chip-allow");
        t.textContent = m.name + (m.matchedValue ? ` (${m.matchedValue})` : "");
        chipbar.appendChild(t);
      });
    } else {
      const t = document.createElement("span");
      t.className = "muted";
      t.textContent = "No matches";
      chipbar.appendChild(t);
    }
    listsBox.appendChild(chipbar);

    const details = document.createElement("details");
    const sum = document.createElement("summary");
    sum.textContent = "Show all list outcomes";
    details.appendChild(sum);

    const table = document.createElement("table");
    table.className = "tbl";
    table.innerHTML =
      "<thead><tr><th>Name</th><th>Type</th><th>Check</th><th>Matched</th><th>Value</th></tr></thead><tbody></tbody>";
    const tbody = table.querySelector("tbody");
    data.lists.forEach((l) => {
      const tr = document.createElement("tr");
      tr.innerHTML = `
        <td>${l.humanName ?? l.name}</td>
        <td><span class="badge ${l.typ === "deny" ? "danger" : "ok"}">${
        l.typ
      }</span></td>
        <td>${l.checkType}</td>
        <td>${l.matched ? "Yes" : "No"}</td>
        <td>${l.matchedValue ?? ""}</td>`;
      tbody.appendChild(tr);
    });
    details.appendChild(table);
    listsBox.appendChild(details);

    wrap.appendChild(listsBox);
  }

  return wrap;
}

// ---------- validate form ----------
if (form) {
  form.addEventListener("submit", async (e) => {
    e.preventDefault();
    const email = $("#email").value.trim();
    if (!email) return;
    try {
      const resp = await fetch("/validate", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ email }),
      });
      const data = await resp.json();
      showRaw(data);
      const box = $("#result");
      if (box) {
        box.innerHTML = "";
        box.appendChild(renderChecklist(data));
      }
    } catch (err) {
      showRaw({ error: String(err) });
    }
  });
}

if (btnGet) {
  btnGet.addEventListener("click", async () => {
    const email = $("#email").value.trim();
    if (!email) return;
    try {
      const resp = await fetch("/validate?email=" + encodeURIComponent(email));
      const data = await resp.json();
      showRaw(data);
      const box = $("#result");
      if (box) {
        box.innerHTML = "";
        box.appendChild(renderChecklist(data));
      }
    } catch (err) {
      showRaw({ error: String(err) });
    }
  });
}

// ---------- lists editor ----------
function cleanList(text) {
  const lines = text.split("\n");
  const seen = new Map(); // key -> original
  for (let raw of lines) {
    const withoutComment = raw.replace(/#.*/, "");
    const s = withoutComment.trim();
    if (!s) continue;
    // de-duplicate case-insensitively but keep first seen original
    const key = s.toLowerCase();
    if (!seen.has(key)) seen.set(key, s);
  }
  return Array.from(seen.values())
    .sort((a, b) => a.localeCompare(b, undefined, { sensitivity: "base" }))
    .join("\n");
}

function renderFiles(files) {
  if (!filesDiv) return;
  filesDiv.innerHTML = "";
  Object.entries(files).forEach(([section, info]) => {
    const details = document.createElement("details");
    details.className = "acc";

    const summary = document.createElement("summary");
    summary.innerHTML = `<strong>${section}</strong> <span class="muted">→ ${info.path}</span> <span class="linecount"></span>`;
    summary.innerHTML = `
    <div class="summary-top">
        <strong>${section}</strong>
        <span class="linecount"></span>
    </div>
    <div class="summary-path mono muted">→ ${info.path}</div>
    `;
    const ta = document.createElement("textarea");
    ta.className = "mono";
    ta.rows = 10;
    ta.value = info.content || "";
    ta.dataset.section = section;

    const toolbar = document.createElement("div");
    toolbar.className = "row gap-s mt-s";

    const btnClean = document.createElement("button");
    btnClean.type = "button";
    btnClean.className = "btn ghost xs";
    btnClean.textContent = "Clean & sort";
    btnClean.addEventListener("click", () => {
      const cleaned = cleanList(ta.value);
      ta.value = cleaned;
      updateCount();
    });

    const btnCopyPath = document.createElement("button");
    btnCopyPath.type = "button";
    btnCopyPath.className = "btn ghost xs";
    btnCopyPath.textContent = "Copy path";
    btnCopyPath.addEventListener("click", () =>
      navigator.clipboard.writeText(info.path).catch(() => {})
    );

    toolbar.appendChild(btnClean);
    toolbar.appendChild(btnCopyPath);

    const updateCount = () => {
      const count = ta.value
        .split("\n")
        .map((l) => l.replace(/#.*/, "").trim())
        .filter(Boolean).length;
      const lc = summary.querySelector(".linecount");
      if (lc) lc.textContent = `(${count} entries)`;
    };
    ta.addEventListener("input", updateCount);

    details.appendChild(summary);
    details.appendChild(ta);
    details.appendChild(toolbar);
    filesDiv.appendChild(details);
    updateCount();
  });
}

async function loadLists() {
  const resp = await fetch("/lists");
  const data = await resp.json();
  if (iniArea) iniArea.value = data.ini || "";
  renderFiles(data.files || {});
}

async function saveLists() {
  const files = {};
  filesDiv.querySelectorAll("details.acc").forEach((acc) => {
    const ta = acc.querySelector("textarea");
    const section = ta.dataset.section;
    const pathEl = acc.querySelector("summary .summary-path");
    const path = (pathEl?.textContent || "").replace(/^→\s*/, "").trim();
    files[section] = { path, content: ta.value };
  });
  const payload = { ini: iniArea.value, files };
  const resp = await fetch("/lists", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(payload),
  });
  if (!resp.ok) throw new Error("Save failed");
}

if (btnLoad)
  btnLoad.addEventListener("click", () => loadLists().catch((e) => alert(e)));
if (btnSave)
  btnSave.addEventListener("click", () =>
    saveLists()
      .then(loadLists)
      .catch((e) => alert(e))
  );

// ---------- import/export ----------
if (btnExport) {
  btnExport.addEventListener("click", () => {
    window.location.href = "/lists/export"; // downloads ZIP or JSON fallback
  });
}

if (btnImport && fileInput) {
  btnImport.addEventListener("click", () => fileInput.click());
  fileInput.addEventListener("change", async () => {
    if (!fileInput.files || !fileInput.files[0]) return;
    const f = fileInput.files[0];
    const fd = new FormData();
    fd.append("archive", f);
    const resp = await fetch("/lists/import", { method: "POST", body: fd });
    if (!resp.ok) {
      alert("Import failed");
      return;
    }
    const data = await resp.json();
    alert("Import ok: " + JSON.stringify(data.summary));
    fileInput.value = "";
    loadLists().catch(() => {});
  });
}

// initial lists load
loadLists().catch(() => {});
