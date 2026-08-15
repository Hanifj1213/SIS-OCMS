/**
 * SISI-OCMS — Web App duplikasi + upload checksheet Google Sheets.
 *
 * CARA DEPLOY / RE-DEPLOY:
 * 1. Paste seluruh file ini ke Code.gs.
 * 2. Services (+) → Drive API → Add
 *    Pilih Version: v3 (yang sekarang default).
 * 3. Deploy → Manage deployments → Edit → Version: New version → Deploy
 *    Execute as: Me | Who has access: Anyone
 * 4. Pastikan GSHEET_COPY_WEBAPP_URL di .env = URL /exec deployment itu.
 *
 * AUTHORIZE (wajib sekali setelah tambah action apply_checkboxes):
 * 5. Pilih fungsi authorizeSpreadsheetAccess → Run → Allow akses Spreadsheet
 * 6. Deploy → New version lagi (supaya web app pakai token yang sudah diizinkan)
 *
 * POST — copy template:
 *   { "template_id": "...", "name": "...", "secret": "..." }
 * POST — upload Excel → Google Sheets:
 *   { "action": "upload", "filename": "...xlsx", "subdir": "Control Valve/WA800-3",
 *     "data": "<base64>", "secret": "..." }
 * POST — ping:
 *   { "action": "ping" }
 * POST — baca spreadsheet (untuk scan INSPECTION U/R):
 *   { "action": "read", "spreadsheet_id": "...", "sheet": "inspeksi", "secret": "..." }
 *   Opsional: "sheet_keywords": ["disassy","inspection"] — urutan prioritas nama tab
 * POST — pasang checkbox kolom keputusan di master template:
 *   { "action": "apply_checkboxes", "spreadsheet_id": "...", "dry_run": true,
 *     "sheets": [{ "name": "DISASSY NO1", "cells": ["G15","H15","I15"] }], "secret": "..." }
 * POST — merge vertikal kolom keputusan per part (hanya REUSE/SALVAGE/REPLACE
 * atau U/A/U/R/R/N — kolom NO dan PART NAME tidak disentuh):
 *   { "action": "apply_decision_merges", "spreadsheet_id": "...", "dry_run": true,
 *     "sheets": [{ "name": "DISASSY", "merges": [{ "col": 21, "startRow": 55, "endRow": 64 }] }], "secret": "..." }
 *   Alternatif tanpa daftar sel: "profile": "disassembly" | "inspection" (auto-detect header)
 * POST — kotak keputusan sekaligus (merge vertikal + checkbox + center) — DIPAKAI
 * oleh tools/format_master_gsheets.php:
 *   { "action": "apply_decision_boxes", "spreadsheet_id": "...", "dry_run": false,
 *     "sheets": [{ "name": "DISASSY", "boxes": [{ "col": 21, "startRow": 61, "endRow": 64 }],
 *                  "clear_cells": ["U55:U60", "U21"] }], "secret": "..." }
 * POST — list / restore revision Drive (pulihkan master sebelum format rusak):
 *   { "action": "list_revisions", "spreadsheet_id": "...", "limit": 10, "secret": "..." }
 *   { "action": "restore_revision", "spreadsheet_id": "...", "steps": 1,
 *     "before": "2026-07-27T08:00:00+07:00", "dry_run": true, "secret": "..." }
 *     CATATAN: restore_revision TIDAK jalan untuk Google Sheets native (404).
 * POST — timpa master Google Sheet dari file .xlsx lokal (ID tetap sama):
 *   { "action": "restore_from_xlsx", "spreadsheet_id": "...", "filename": "....xlsx",
 *     "data": "<base64>", "dry_run": true, "secret": "..." }
 */

/**
 * KEAMANAN (WAJIB DIBACA SEBELUM DEPLOY):
 *
 * 1. Secret TIDAK disimpan di file ini. Simpan lewat Script Properties:
 *    Project Settings → Script Properties → Add:
 *      OCMS_SECRET = <nilai yang sama dengan GSHEET_COPY_SECRET di .env>
 *    Nilai secret tidak boleh ikut ter-commit ke repository.
 *
 * 2. Autentikasi FAIL-CLOSED: bila OCMS_SECRET belum diisi, SELURUH aksi
 *    ditolak. Deployment tanpa secret tidak dapat dipakai siapa pun.
 *
 * 3. Aksi administratif/destruktif (list_revisions, restore_revision,
 *    restore_from_xlsx, apply_checkboxes, apply_decision_merges,
 *    apply_decision_boxes) HANYA aktif bila Script Property
 *      OCMS_ADMIN_ACTIONS = enabled
 *    Deployment runtime produksi harus membiarkannya kosong sehingga hanya
 *    aksi runtime (copy, upload, read, ping) yang dapat dipanggil.
 *
 * 4. Rotasi secret: ganti nilai OCMS_SECRET di Script Properties dan
 *    GSHEET_COPY_SECRET di .env pada waktu yang sama, lalu Deploy →
 *    New version. URL /exec lama tetap berlaku; bila URL bocor, buat
 *    deployment baru dan perbarui .env.
 */

/** Ambil secret dari Script Properties — tidak pernah dari source. */
function getSecret_() {
  try {
    var value = PropertiesService.getScriptProperties().getProperty('OCMS_SECRET');
    return value == null ? '' : String(value);
  } catch (err) {
    return '';
  }
}

/** Aksi administratif hanya aktif bila di-opt-in lewat Script Properties. */
function adminActionsEnabled_() {
  try {
    var value = PropertiesService.getScriptProperties().getProperty('OCMS_ADMIN_ACTIONS');
    return String(value == null ? '' : value).toLowerCase() === 'enabled';
  } catch (err) {
    return false;
  }
}

/** Aksi runtime yang boleh dipanggil aplikasi OCMS. */
var RUNTIME_ACTIONS = ['copy', 'upload', 'read', 'ping'];

/** Aksi administratif/destruktif — default nonaktif pada deployment runtime. */
var ADMIN_ACTIONS = [
  'apply_checkboxes',
  'apply_decision_merges',
  'apply_decision_boxes',
  'list_revisions',
  'restore_revision',
  'restore_from_xlsx'
];

function inList_(list, value) {
  for (var i = 0; i < list.length; i++) {
    if (list[i] === value) {
      return true;
    }
  }
  return false;
}

/**
 * Perbandingan secret dengan waktu konstan terhadap panjang string —
 * menghindari kebocoran informasi lewat waktu respons.
 */
function secretMatches_(provided) {
  var expected = getSecret_();
  if (!expected) {
    return false; // fail-closed: secret belum dikonfigurasi
  }

  var given = provided == null ? '' : String(provided);
  if (given.length !== expected.length) {
    return false;
  }

  var diff = 0;
  for (var i = 0; i < expected.length; i++) {
    diff |= expected.charCodeAt(i) ^ given.charCodeAt(i);
  }
  return diff === 0;
}

var TARGET_FOLDER_NAME = 'OCMS Checksheet Copies';

/**
 * Jalankan sekali dari editor Apps Script (Run) → klik Allow/Izinkan.
 * Tanpa ini, apply_checkboxes via web app gagal:
 * "tidak memiliki izin untuk memanggil SpreadsheetApp.openById"
 */
function authorizeSpreadsheetAccess() {
  var id = '1iqb7_rZwRxy3BHl863jbZ7utF8S1t-OOkrvsk4deApc'; // CV PC1250-8 disassembly master
  var name = SpreadsheetApp.openById(id).getName();
  Logger.log('Authorize OK — bisa buka: ' + name);
}

/**
 * Jalankan sekali dari editor → Run → Allow (akses Drive revision).
 */
function authorizeDriveRevisionAccess() {
  var id = '1iqb7_rZwRxy3BHl863jbZ7utF8S1t-OOkrvsk4deApc';
  var revs = listDriveRevisions_(id, 3);
  Logger.log('Authorize OK — ' + revs.length + ' revision untuk ' + id);
}

function doPost(e) {
  try {
    var body = JSON.parse(e.postData.contents);

    // Tanpa action = permintaan copy template (kompatibilitas versi lama).
    var action = body.action ? String(body.action) : 'copy';

    // FAIL-CLOSED: secret kosong atau salah → seluruh aksi ditolak.
    // Tidak ada informasi konfigurasi yang dikembalikan ke pemanggil.
    if (!secretMatches_(body.secret)) {
      return jsonOut({ ok: false, error: 'unauthorized' });
    }

    var isRuntime = inList_(RUNTIME_ACTIONS, action);
    var isAdmin = inList_(ADMIN_ACTIONS, action);

    if (!isRuntime && !isAdmin) {
      return jsonOut({ ok: false, error: 'action tidak dikenal' });
    }

    if (isAdmin && !adminActionsEnabled_()) {
      return jsonOut({ ok: false, error: 'action administratif dinonaktifkan pada deployment ini' });
    }

    if (action === 'upload') {
      return handleUpload(body);
    }
    if (action === 'read') {
      return handleRead(body);
    }
    if (action === 'ping') {
      return jsonOut({
        ok: true,
        ping: true,
        drive: (typeof Drive !== 'undefined'),
        driveCreate: !!(Drive && Drive.Files && Drive.Files.create),
        driveInsert: !!(Drive && Drive.Files && Drive.Files.insert)
      });
    }
    if (action === 'apply_checkboxes') {
      return handleApplyCheckboxes(body);
    }
    if (action === 'apply_decision_merges') {
      return handleApplyDecisionMerges(body);
    }
    if (action === 'apply_decision_boxes') {
      return handleApplyDecisionBoxes(body);
    }
    if (action === 'list_revisions') {
      return handleListRevisions(body);
    }
    if (action === 'restore_revision') {
      return handleRestoreRevision(body);
    }
    if (action === 'restore_from_xlsx') {
      return handleRestoreFromXlsx(body);
    }

    return handleCopy(body);
  } catch (err) {
    // Jangan mengembalikan payload/stack mentah ke pemanggil.
    Logger.log('doPost error: ' + err);
    return jsonOut({ ok: false, error: 'internal error' });
  }
}

function handleCopy(body) {
  if (!body.template_id || !body.name) {
    return jsonOut({ ok: false, error: 'template_id dan name wajib diisi' });
  }

  var folder = getOrCreateFolder(TARGET_FOLDER_NAME);
  var copy = DriveApp.getFileById(body.template_id).makeCopy(body.name, folder);
  copy.setSharing(DriveApp.Access.ANYONE_WITH_LINK, DriveApp.Permission.EDIT);

  return jsonOut({ ok: true, id: copy.getId(), url: copy.getUrl() });
}

function handleRead(body) {
  if (!body.spreadsheet_id) {
    return jsonOut({ ok: false, error: 'spreadsheet_id wajib diisi' });
  }

  var ss = SpreadsheetApp.openById(body.spreadsheet_id);
  var matched = [];
  var usedFallback = false;

  if (body.sheet) {
    var named = ss.getSheetByName(body.sheet);
    if (!named) {
      return jsonOut({ ok: false, error: 'Sheet "' + body.sheet + '" tidak ditemukan' });
    }
    matched.push(named);
  } else {
    var sheets = ss.getSheets();
    var keywords = body.sheet_keywords || ['inspeksi', 'inspection', 'measurement', 'disassy', 'diss', 'disassembly'];

    // Satu workbook bisa punya beberapa tab keputusan (INSPEKSI NO1/NO2/NO3,
    // DISASSY LH/RH, CYL HEAD + TURBO DISASSY). Ambil SEMUA yang cocok —
    // urut sesuai posisi tab supaya nomor section stabil.
    for (var i = 0; i < sheets.length; i++) {
      var name = sheets[i].getName().toLowerCase();
      for (var k = 0; k < keywords.length; k++) {
        if (name.indexOf(String(keywords[k]).toLowerCase()) >= 0) {
          matched.push(sheets[i]);
          break;
        }
      }
    }

    if (matched.length === 0) {
      matched.push(sheets[0]);
      usedFallback = true;
    }
  }

  var out = [];
  for (var s = 0; s < matched.length; s++) {
    out.push({ name: matched[s].getName(), values: matched[s].getDataRange().getValues() });
  }

  return jsonOut({
    ok: true,
    sheet: out[0].name,        // kompatibilitas versi lama
    values: out[0].values,     // kompatibilitas versi lama
    sheets: out,
    matched: !usedFallback     // false = tidak ada tab yang cocok keyword
  });
}

function handleUpload(body) {
  if (!body.filename || !body.data) {
    return jsonOut({ ok: false, error: 'filename dan data (base64) wajib diisi' });
  }
  if (typeof Drive === 'undefined' || !Drive.Files) {
    return jsonOut({ ok: false, error: 'Drive API belum aktif. Services → Drive API → Add, lalu New version deploy.' });
  }

  var folder = getOrCreateFolder(TARGET_FOLDER_NAME);
  if (body.subdir) {
    folder = ensureSubfolders(folder, String(body.subdir));
  }

  var title = String(body.filename).replace(/\.xlsx$/i, '').replace(/\.xls$/i, '');
  var bytes = Utilities.base64Decode(body.data);
  var blob = Utilities.newBlob(
    bytes,
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    body.filename
  );

  var fileId = createGoogleSheetFromExcel(folder, title, blob);
  var driveFile = DriveApp.getFileById(fileId);
  driveFile.setSharing(DriveApp.Access.ANYONE_WITH_LINK, DriveApp.Permission.EDIT);

  return jsonOut({
    ok: true,
    id: fileId,
    url: 'https://docs.google.com/spreadsheets/d/' + fileId + '/edit'
  });
}

/**
 * Drive API v3: Files.create (name + parents string[])
 * Drive API v2: Files.insert (title + parents [{id}])
 */
function createGoogleSheetFromExcel(folder, title, blob) {
  if (Drive.Files.create) {
    // v3
    var created = Drive.Files.create(
      {
        name: title,
        mimeType: MimeType.GOOGLE_SHEETS,
        parents: [folder.getId()]
      },
      blob
    );
    return created.id;
  }

  if (Drive.Files.insert) {
    // v2
    var inserted = Drive.Files.insert(
      {
        title: title,
        mimeType: MimeType.GOOGLE_SHEETS,
        parents: [{ id: folder.getId() }]
      },
      blob,
      { convert: true }
    );
    return inserted.id;
  }

  throw new Error('Drive.Files.create/insert tidak tersedia. Cek Services → Drive API.');
}

function getOrCreateFolder(name) {
  var it = DriveApp.getFoldersByName(name);
  return it.hasNext() ? it.next() : DriveApp.createFolder(name);
}

function ensureSubfolders(root, subdir) {
  var parts = subdir.split(/[\\/]+/).filter(function (p) { return p.length > 0; });
  var folder = root;
  for (var i = 0; i < parts.length; i++) {
    var name = parts[i];
    var it = folder.getFoldersByName(name);
    folder = it.hasNext() ? it.next() : folder.createFolder(name);
  }
  return folder;
}

/**
 * Kotak keputusan per part: pecah merge lama → bersihkan → merge vertikal →
 * checkbox → alignment center, SEKALIGUS dalam satu langkah.
 *
 * Dipisah jadi dua request (merge lalu checkbox) tidak aman: langkah checkbox
 * bisa memecah merge yang baru dibuat.
 *
 * body.sheets = [{ name, boxes: [{ col, startRow, endRow }], clear_cells: ["U55", …] }]
 */
function handleApplyDecisionBoxes(body) {
  if (!body.spreadsheet_id) {
    return jsonOut({ ok: false, error: 'spreadsheet_id wajib diisi' });
  }
  if (!body.sheets || body.sheets.length === 0) {
    return jsonOut({ ok: false, error: 'sheets[] wajib diisi' });
  }

  var dryRun = !!body.dry_run;
  var ss = SpreadsheetApp.openById(body.spreadsheet_id);
  var report = {
    spreadsheet_id: body.spreadsheet_id,
    title: ss.getName(),
    dry_run: dryRun,
    sheets: [],
    total: 0,
    applied: 0,
    skipped: 0,
    cleared: 0,
    errors: []
  };

  for (var i = 0; i < body.sheets.length; i++) {
    var spec = body.sheets[i];
    var sheetName = String(spec.name || '');
    var boxes = spec.boxes || [];
    var clearCells = spec.clear_cells || [];
    var entry = {
      name: sheetName,
      boxes: boxes.length,
      applied: 0,
      skipped: 0,
      cleared: 0,
      missing_tab: false
    };

    var sheet = ss.getSheetByName(sheetName);
    if (!sheet) {
      entry.missing_tab = true;
      report.errors.push('Tab tidak ditemukan: ' + sheetName);
      report.sheets.push(entry);
      continue;
    }

    for (var x = 0; x < clearCells.length; x++) {
      var ref = String(clearCells[x] || '').toUpperCase();
      if (!ref) {
        continue;
      }
      try {
        if (!dryRun) {
          clearDecisionRange_(sheet, ref);
        }
        entry.cleared++;
        report.cleared++;
      } catch (errClear) {
        report.errors.push(sheetName + '!' + ref + ' clear: ' + errClear);
      }
    }

    var seen = {};
    for (var b = 0; b < boxes.length; b++) {
      var box = boxes[b];
      var col = Number(box.col);
      var startRow = Number(box.startRow);
      var endRow = Number(box.endRow);

      if (!col || !startRow) {
        entry.skipped++;
        report.skipped++;
        continue;
      }
      if (!endRow || endRow < startRow) {
        endRow = startRow;
      }

      var key = col + ':' + startRow + ':' + endRow;
      if (seen[key]) {
        continue;
      }
      seen[key] = true;
      report.total++;

      if (dryRun) {
        entry.applied++;
        report.applied++;
        continue;
      }

      try {
        applyDecisionBox_(sheet, col, startRow, endRow);
        entry.applied++;
        report.applied++;
      } catch (err) {
        report.errors.push(sheetName + ' col' + col + ' r' + startRow + '-' + endRow + ': ' + err);
        entry.skipped++;
        report.skipped++;
      }
    }

    report.sheets.push(entry);
  }

  return jsonOut({ ok: true, report: report });
}

/** Hapus data validation (checkbox) tanpa bergantung nama method versi runtime. */
function clearValidations_(range) {
  if (typeof range.clearDataValidations === 'function') {
    range.clearDataValidations();
  } else if (typeof range.clearDataValidation === 'function') {
    range.clearDataValidation();
  } else {
    range.setDataValidation(null);
  }
}

/**
 * Buang checkbox lama pada rentang kolom keputusan ("U55" atau "U55:U60").
 * Hanya nilai boolean yang dihapus — teks/catatan tidak disentuh.
 */
function clearDecisionRange_(sheet, a1) {
  var ref = sheet.getRange(a1);
  breakVerticalDecisionMergesCellByCell_(ref);

  var range = sheet.getRange(ref.getRow(), ref.getColumn(), ref.getNumRows(), 1);
  clearValidations_(range);

  var values = range.getValues();
  var changed = false;
  for (var i = 0; i < values.length; i++) {
    var v = values[i][0];
    if (v === true || v === false) {
      values[i][0] = '';
      changed = true;
    }
  }
  if (changed) {
    range.setValues(values);
  }
}

/**
 * Satu kotak keputusan (1 kolom × N baris) jadi: merged, checkbox, center.
 */
function applyDecisionBox_(sheet, col, startRow, endRow) {
  // getRange(row, column, numRows, numColumns) — bukan endRow/endColumn.
  var range = sheet.getRange(startRow, col, endRow - startRow + 1, 1);

  // Merge bawaan yang BERIRISAN dengan kotak memang harus diganti agar kolom
  // keputusan bisa menjadi satu merge vertikal. Range di luar kotak tidak
  // disentuh (clear_cells sekarang selalu kosong dari formatter PHP).
  breakColumnMergesCellByCell_(range);
  clearValidations_(range);
  range.clearContent();

  if (endRow > startRow) {
    range.merge();
  }

  // Setelah merge, sel anchor mewakili seluruh area merge.
  var anchor = sheet.getRange(startRow, col);
  if (typeof anchor.insertCheckboxes === 'function') {
    anchor.insertCheckboxes();
  } else {
    anchor.setDataValidation(SpreadsheetApp.newDataValidation().requireCheckbox().build());
    anchor.setValue(false);
  }

  range.setHorizontalAlignment('center');
  range.setVerticalAlignment('middle');
}

/**
 * Merge vertikal kolom keputusan per part — SATU kolom per merge (U saja, V saja, …).
 * Tidak merge kolom NO, PART NAME, CHECK POINT, atau RH/LH label.
 * body.sheets = [{ name, merges: [{ col, startRow, endRow }] }]
 */
function handleApplyDecisionMerges(body) {
  if (!body.spreadsheet_id) {
    return jsonOut({ ok: false, error: 'spreadsheet_id wajib diisi' });
  }

  var dryRun = !!body.dry_run;
  var ss = SpreadsheetApp.openById(body.spreadsheet_id);
  var report = {
    spreadsheet_id: body.spreadsheet_id,
    title: ss.getName(),
    dry_run: dryRun,
    sheets: [],
    total_merges: 0,
    applied: 0,
    skipped: 0,
    errors: []
  };

  if (!body.sheets || body.sheets.length === 0) {
    return jsonOut({ ok: false, error: 'sheets[] wajib diisi' });
  }

  for (var i = 0; i < body.sheets.length; i++) {
    var spec = body.sheets[i];
    var sheetName = String(spec.name || '');
    var merges = spec.merges || [];
    var entry = { name: sheetName, merges: merges.length, applied: 0, skipped: 0, missing_tab: false };

    var sheet = ss.getSheetByName(sheetName);
    if (!sheet) {
      entry.missing_tab = true;
      report.errors.push('Tab tidak ditemukan: ' + sheetName);
      report.sheets.push(entry);
      continue;
    }

    var seen = {};
    for (var m = 0; m < merges.length; m++) {
      var merge = merges[m];
      var col = Number(merge.col);
      var startRow = Number(merge.startRow);
      var endRow = Number(merge.endRow);
      if (!col || !startRow || !endRow || endRow <= startRow) {
        entry.skipped++;
        report.skipped++;
        continue;
      }

      var key = col + ':' + startRow + ':' + endRow;
      if (seen[key]) {
        continue;
      }
      seen[key] = true;
      report.total_merges++;

      try {
        // PENTING: getRange(row, col, numRows, numColumns) — BUKAN endRow/endCol.
        var range = sheet.getRange(startRow, col, endRow - startRow + 1, 1);
        if (dryRun) {
          entry.applied++;
          report.applied++;
          continue;
        }
        try {
          mergeVerticalDecision_(range);
          entry.applied++;
          report.applied++;
        } catch (errMerge) {
          // Jangan biarkan exception naik ke doPost (jadi HTML "Salah").
          report.errors.push(sheetName + ' col' + col + ' r' + startRow + '-' + endRow + ': ' + errMerge);
          entry.skipped++;
          report.skipped++;
        }
      } catch (err) {
        report.errors.push(sheetName + ' col' + col + ' r' + startRow + '-' + endRow + ': ' + err);
      }
    }

    report.sheets.push(entry);
  }

  return jsonOut({ ok: true, report: report });
}

/**
 * Merge vertikal satu kolom keputusan.
 * Selalu pecah merge yang menyentuh sel target dulu (per sel) — getMergedRanges
 * pada range multi-baris sering gagal jika hanya overlap sebagian.
 */
function mergeVerticalDecision_(range) {
  breakColumnMergesCellByCell_(range);
  try {
    range.merge();
  } catch (err1) {
    breakColumnMergesCellByCell_(range);
    range.merge();
  }
  try {
    range.setHorizontalAlignment('center');
    range.setVerticalAlignment('middle');
  } catch (errAlign) {
    // alignment opsional — merge tetap valid
  }
}

/**
 * Pecah semua merge yang menyentuh sel di kolom target, satu sel per satu.
 * Aman terhadap error "You must select all cells in a merged range…".
 */
function breakColumnMergesCellByCell_(range) {
  var sheet = range.getSheet();
  var col = range.getColumn();
  var startRow = range.getRow();
  var endRow = range.getLastRow();
  var broken = {};

  for (var r = startRow; r <= endRow; r++) {
    try {
      var cell = sheet.getRange(r, col);
      var merged = cell.getMergedRanges();
      for (var i = 0; i < merged.length; i++) {
        var m = merged[i];
        var key = m.getA1Notation();
        if (broken[key]) {
          continue;
        }
        broken[key] = true;
        try {
          m.breakApart();
        } catch (errBreak) {
          // ignore — coba sel berikutnya
        }
      }
    } catch (errCell) {
      // ignore
    }
  }
}

/**
 * Pecah hanya merge vertikal satu kolom pada kolom keputusan.
 *
 * Cleanup checkbox tidak boleh memecah merge horizontal bawaan template
 * seperti C274:Y274 atau U275:Y275 pada section Timing Gear.
 */
function breakVerticalDecisionMergesCellByCell_(range) {
  var sheet = range.getSheet();
  var col = range.getColumn();
  var startRow = range.getRow();
  var endRow = range.getLastRow();
  var broken = {};

  for (var r = startRow; r <= endRow; r++) {
    try {
      var cell = sheet.getRange(r, col);
      var merged = cell.getMergedRanges();
      for (var i = 0; i < merged.length; i++) {
        var m = merged[i];
        if (m.getColumn() !== col || m.getLastColumn() !== col) {
          continue;
        }
        var key = m.getA1Notation();
        if (broken[key]) {
          continue;
        }
        broken[key] = true;
        try {
          m.breakApart();
        } catch (errBreak) {
          // ignore — coba sel berikutnya
        }
      }
    } catch (errCell) {
      // ignore
    }
  }
}

/**
 * Lepas merge yang overlap dengan range target (Google Sheets wajib
 * select/break seluruh merged range — partial breakApart gagal).
 *
 * @param {boolean=} decisionColOnly  true = hanya merge yang menyentuh kolom range
 */
function breakMergedRangesOverlapping_(range, decisionColOnly) {
  // Preferensi: pecah per-sel (lebih andal untuk overlap parsial).
  if (decisionColOnly) {
    breakColumnMergesCellByCell_(range);
    return;
  }

  try {
    var merged = range.getMergedRanges();
  } catch (err) {
    breakColumnMergesCellByCell_(range);
    return;
  }

  var targetCol = range.getColumn();
  var targetLastCol = range.getLastColumn();
  var seen = {};
  for (var i = 0; i < merged.length; i++) {
    var m = merged[i];
    var key = m.getA1Notation();
    if (seen[key]) {
      continue;
    }
    seen[key] = true;
    try {
      m.breakApart();
    } catch (errBreak) {
      // ignore
    }
  }
}

/**
 * Hanya lepas merge LEBAR (multi-kolom). Merge vertikal 1 kolom keputusan
 * dibiarkan — supaya checkbox bisa hidup di dalam merge + center alignment.
 */
function breakWideMergesOverlapping_(range) {
  var merged = range.getMergedRanges();
  var targetCol = range.getColumn();
  var targetLastCol = range.getLastColumn();
  var seen = {};
  for (var i = 0; i < merged.length; i++) {
    var m = merged[i];
    if (m.getLastColumn() < targetCol || m.getColumn() > targetLastCol) {
      continue;
    }
    // Merge vertikal murni (1 kolom) di kolom target — jangan dipecah.
    if (m.getColumn() === m.getLastColumn() && m.getColumn() === targetCol) {
      continue;
    }
    var key = m.getA1Notation();
    if (seen[key]) {
      continue;
    }
    seen[key] = true;
    m.breakApart();
  }
}

/**
 * Pasang checkbox Google Sheets pada sel keputusan part.
 * Mode 1 (disarankan): body.sheets = [{ name, cells: ["G15", ...] }]
 * Mode 2 (fallback): body.profile = "disassembly" | "inspection"
 */
function handleApplyCheckboxes(body) {
  if (!body.spreadsheet_id) {
    return jsonOut({ ok: false, error: 'spreadsheet_id wajib diisi' });
  }

  var dryRun = !!body.dry_run;
  var ss = SpreadsheetApp.openById(body.spreadsheet_id);
  var report = {
    spreadsheet_id: body.spreadsheet_id,
    title: ss.getName(),
    dry_run: dryRun,
    sheets: [],
    total_cells: 0,
    applied: 0,
    skipped: 0,
    errors: []
  };

  if (body.sheets && body.sheets.length > 0) {
    applyExplicitCheckboxSheets_(ss, body.sheets, dryRun, report);
  } else if (body.profile) {
    applyAutoProfileCheckboxes_(ss, String(body.profile), dryRun, report);
  } else {
    return jsonOut({ ok: false, error: 'sheets[] atau profile wajib diisi' });
  }

  return jsonOut({ ok: true, report: report });
}

/** @param {Array<{name:string, cells:Array<string>}>} sheetSpecs */
function applyExplicitCheckboxSheets_(ss, sheetSpecs, dryRun, report) {
  for (var i = 0; i < sheetSpecs.length; i++) {
    var spec = sheetSpecs[i];
    var sheetName = String(spec.name || '');
    var cells = spec.cells || [];
    var clearCells = spec.clear_cells || [];
    var entry = { name: sheetName, cells: cells.length, cleared: 0, applied: 0, skipped: 0, missing_tab: false };

    var sheet = ss.getSheetByName(sheetName);
    if (!sheet) {
      entry.missing_tab = true;
      report.errors.push('Tab tidak ditemukan: ' + sheetName);
      report.sheets.push(entry);
      continue;
    }

    var seenClear = {};
    for (var x = 0; x < clearCells.length; x++) {
      var clearRef = String(clearCells[x] || '').toUpperCase();
      if (!clearRef || seenClear[clearRef]) {
        continue;
      }
      seenClear[clearRef] = true;
      try {
        if (!dryRun) {
          removeCheckboxAtRange_(sheet.getRange(clearRef));
        }
        entry.cleared++;
      } catch (err) {
        report.errors.push(sheetName + '!' + clearRef + ' clear: ' + err);
      }
    }

    var seen = {};
    for (var c = 0; c < cells.length; c++) {
      var ref = String(cells[c] || '').toUpperCase();
      if (!ref || seen[ref]) {
        continue;
      }
      seen[ref] = true;
      report.total_cells++;

      try {
        var range = sheet.getRange(ref);
        if (dryRun) {
          entry.applied++;
          report.applied++;
          continue;
        }
        if (applyCheckboxAtRange_(range)) {
          entry.applied++;
          report.applied++;
        } else {
          entry.skipped++;
          report.skipped++;
        }
      } catch (err) {
        report.errors.push(sheetName + '!' + ref + ': ' + err);
      }
    }

    report.sheets.push(entry);
  }
}

function applyAutoProfileCheckboxes_(ss, profile, dryRun, report) {
  var keywords = profile === 'inspection'
    ? ['inspeksi', 'inspection']
    : ['disassy', 'diss', 'disassembly', 'engine'];

  var sheets = ss.getSheets();
  for (var i = 0; i < sheets.length; i++) {
    var sheet = sheets[i];
    var low = sheet.getName().toLowerCase();
    var matched = false;
    for (var k = 0; k < keywords.length; k++) {
      if (low.indexOf(keywords[k]) >= 0) {
        matched = true;
        break;
      }
    }
    if (!matched) {
      continue;
    }

    var values = sheet.getDataRange().getValues();
    var cells = profile === 'inspection'
      ? collectInspectionDecisionCells_(values)
      : collectDisassemblyDecisionCells_(values);

    applyExplicitCheckboxSheets_(ss, [{ name: sheet.getName(), cells: cells }], dryRun, report);
  }
}

/** @param {Array<Array<*>>} values */
function collectDisassemblyDecisionCells_(values) {
  var out = [];
  var columns = null;

  for (var r = 0; r < values.length; r++) {
    var detected = detectDisassemblyHeaderColumns_(values[r]);
    if (detected) {
      columns = detected;
      continue;
    }
    if (!columns) {
      continue;
    }

    var row = values[r];
    var noRaw = row[columns.noCol];
    var nameRaw = row[columns.nameCol];
    if (!isPartRow_(noRaw) || !String(nameRaw || '').trim()) {
      continue;
    }

    pushDecisionCellRefs_(out, r + 1, columns.decisionCols);
  }

  return out;
}

/** @param {Array<Array<*>>} values */
function collectInspectionDecisionCells_(values) {
  var out = [];
  var headerRow = null;
  var nameCol = null;
  var noCol = null;
  var urCol = null;
  var rnCol = null;

  var scanLimit = Math.min(values.length, 45);
  for (var r = 0; r < scanLimit; r++) {
    var row = values[r];
    for (var c = 0; c < row.length; c++) {
      var text = String(row[c] == null ? '' : row[c]).toUpperCase().trim();
      if (!text) {
        continue;
      }
      if (/^NO\.?$/.test(text) || text === 'NO') {
        noCol = c;
      }
      if (/PARTS?\s*NAME/.test(text)) {
        nameCol = c;
        headerRow = r;
      }
    }
  }

  if (headerRow === null || nameCol === null) {
    return out;
  }
  if (noCol === null) {
    noCol = Math.max(0, nameCol - 1);
  }

  var urLoose = null;
  var rnLoose = null;
  for (var hr = headerRow; hr < Math.min(headerRow + 3, values.length); hr++) {
    var hrow = values[hr];
    for (var hc = 0; hc < hrow.length; hc++) {
      var ht = String(hrow[hc] == null ? '' : hrow[hc]).toUpperCase().trim();
      if (!ht) {
        continue;
      }
      if (ht === 'U/R' || ht === 'UR') {
        urCol = urCol == null ? hc : urCol;
      } else if (ht.indexOf('U/R') >= 0) {
        urLoose = urLoose == null ? hc : urLoose;
      }
      if (ht === 'R/N' || ht === 'RN') {
        rnCol = rnCol == null ? hc : rnCol;
      } else if (ht.indexOf('R/N') >= 0) {
        rnLoose = rnLoose == null ? hc : rnLoose;
      }
    }
  }
  urCol = urCol == null ? urLoose : urCol;
  rnCol = rnCol == null ? rnLoose : rnCol;
  if (urCol != null && urCol === rnCol) {
    rnCol = null;
  }

  if (urCol == null || rnCol == null) {
    var decisionCol = null;
    for (var dc = 0; dc < values[headerRow].length; dc++) {
      var dt = String(values[headerRow][dc] == null ? '' : values[headerRow][dc]).toUpperCase();
      if (dt.indexOf('DECISION') >= 0) {
        decisionCol = dc;
        break;
      }
    }
    if (decisionCol != null) {
      urCol = urCol == null ? decisionCol + 1 : urCol;
      rnCol = rnCol == null ? decisionCol + 2 : rnCol;
    }
  }

  var decisionCols = [];
  if (urCol != null) {
    decisionCols.push(urCol);
  }
  if (rnCol != null) {
    decisionCols.push(rnCol);
  }
  if (decisionCols.length === 0) {
    return out;
  }

  for (var dr = headerRow + 1; dr < values.length; dr++) {
    var drow = values[dr];
    if (!isPartRow_(drow[noCol]) || !String(drow[nameCol] || '').trim()) {
      continue;
    }
    pushDecisionCellRefs_(out, dr + 1, decisionCols);
  }

  return out;
}

/** @param {Array<*>} row */
function detectDisassemblyHeaderColumns_(row) {
  var noCol = null;
  var nameCol = null;
  var reuseCol = null;
  var salvageCol = null;
  var replaceCol = null;

  for (var c = 0; c < row.length; c++) {
    var text = String(row[c] == null ? '' : row[c]).toUpperCase().trim();
    if (!text) {
      continue;
    }
    if (/^NO\.?$/.test(text)) {
      noCol = c;
    }
    if (/PARTS?\s*TO\s*REMOVE|PARTS?\s*NAME|^PARTS?$/.test(text)) {
      nameCol = c;
    }
    if (text === 'REUSE' || text === 'REUSED') {
      reuseCol = c;
    }
    if (text === 'SALVAGE' || text === 'SALVG' || text === 'SALV' || text.indexOf('SALV') === 0) {
      salvageCol = c;
    }
    if (text === 'REPLACE' || text === 'REPLACE NEW') {
      replaceCol = c;
    }
  }

  if (nameCol === null) {
    return null;
  }
  if (noCol === null) {
    noCol = Math.max(0, nameCol - 1);
  }

  var decisionCols = [];
  if (reuseCol != null) {
    decisionCols.push(reuseCol);
  }
  if (salvageCol != null) {
    decisionCols.push(salvageCol);
  }
  if (replaceCol != null) {
    decisionCols.push(replaceCol);
  }

  if (decisionCols.length === 0 && salvageCol == null) {
    return null;
  }
  if (decisionCols.length === 0 && salvageCol != null) {
    decisionCols.push(salvageCol);
    if (replaceCol != null) {
      decisionCols.push(replaceCol);
    }
  }

  return { noCol: noCol, nameCol: nameCol, decisionCols: decisionCols };
}

function isPartRow_(noRaw) {
  if (noRaw === null || noRaw === '') {
    return false;
  }
  if (typeof noRaw === 'number') {
    return true;
  }
  return /^\d+(\.\d+)?$/.test(String(noRaw).trim());
}

function pushDecisionCellRefs_(out, row1Based, decisionCols) {
  for (var i = 0; i < decisionCols.length; i++) {
    out.push(columnToLetters_(decisionCols[i] + 1) + row1Based);
  }
}

function columnToLetters_(col) {
  var letters = '';
  while (col > 0) {
    var rem = (col - 1) % 26;
    letters = String.fromCharCode(65 + rem) + letters;
    col = Math.floor((col - rem - 1) / 26);
  }
  return letters;
}

function removeCheckboxAtRange_(range) {
  // Clear boleh pecah merge 1-kolom di posisi lama (baris bawah block).
  breakMergedRangesOverlapping_(range, true);
  var sheet = range.getSheet();
  var cell = sheet.getRange(range.getRow(), range.getColumn());
  clearValidations_(cell);
  var val = cell.getValue();
  if (val === true || val === false) {
    cell.clearContent();
  }
}

function applyCheckboxAtRange_(range) {
  // Jangan pecah merge vertikal 1 kolom (hasil apply_decision_merges).
  // Hanya pecah merge LEBAR yang menimpa kolom keputusan.
  breakWideMergesOverlapping_(range);
  var sheet = range.getSheet();
  var cell = sheet.getRange(range.getRow(), range.getColumn());

  // Jika sudah bagian merge vertikal, pasang checkbox pada seluruh merge.
  var merged = cell.getMergedRanges();
  var target = (merged && merged.length) ? merged[0] : cell;

  var val = target.getValue();
  if (val !== '' && val !== false && val !== true) {
    target.clearContent();
  }
  if (typeof target.insertCheckboxes === 'function') {
    target.insertCheckboxes();
  } else {
    target.setDataValidation(SpreadsheetApp.newDataValidation().requireCheckbox().build());
    if (target.getValue() === '') {
      target.setValue(false);
    }
  }
  target.setHorizontalAlignment('center');
  target.setVerticalAlignment('middle');
  return true;
}

function jsonOut(obj) {
  return ContentService.createTextOutput(JSON.stringify(obj))
    .setMimeType(ContentService.MimeType.JSON);
}

/**
 * Daftar revision Drive (newest first). Index 0 = versi terbaru.
 */
function handleListRevisions(body) {
  if (!body.spreadsheet_id) {
    return jsonOut({ ok: false, error: 'spreadsheet_id wajib diisi' });
  }

  var limit = Number(body.limit) || 15;
  var revs = listDriveRevisions_(body.spreadsheet_id, limit);
  var fileMeta = getDriveFileMeta_(body.spreadsheet_id);

  return jsonOut({
    ok: true,
    spreadsheet_id: body.spreadsheet_id,
    title: fileMeta.name,
    modified_time: fileMeta.modifiedTime,
    revisions: revs
  });
}

/**
 * Pulihkan spreadsheet ke revision lama (Drive API v3).
 * steps=1 → satu langkah sebelum versi terbaru (revision index 1).
 * before=ISO8601 → revision terbaru yang modifiedTime < before.
 */
function handleRestoreRevision(body) {
  if (!body.spreadsheet_id) {
    return jsonOut({ ok: false, error: 'spreadsheet_id wajib diisi' });
  }

  var dryRun = !!body.dry_run;
  var steps = body.steps != null ? Number(body.steps) : null;
  var beforeIso = body.before ? String(body.before) : null;

  if (steps == null && !beforeIso) {
    steps = 1;
  }

  var revs = listDriveRevisions_(body.spreadsheet_id, 100);
  if (revs.length < 2) {
    return jsonOut({
      ok: false,
      error: 'Revision tidak cukup (min 2). Coba File → Version history manual.',
      revisions: revs
    });
  }

  var target = pickRevisionTarget_(revs, steps, beforeIso);
  if (!target) {
    return jsonOut({
      ok: false,
      error: 'Tidak ada revision yang cocok dengan steps/before',
      revisions: revs.slice(0, 10)
    });
  }

  var fileMeta = getDriveFileMeta_(body.spreadsheet_id);
  var report = {
    spreadsheet_id: body.spreadsheet_id,
    title: fileMeta.name,
    dry_run: dryRun,
    current_modified: fileMeta.modifiedTime,
    target_revision_id: target.id,
    target_modified: target.modifiedTime,
    target_index: target.index,
    steps: steps,
    before: beforeIso
  };

  if (dryRun) {
    return jsonOut({ ok: true, report: report, revisions: revs.slice(0, 10) });
  }

  return jsonOut({
    ok: false,
    error: 'Google Sheets native tidak mendukung restore via Drive revision API (404). '
      + 'Pakai: php tools/restore_master_gsheets.php --apply --confirm (mode xlsx default).',
    report: report,
    revisions: revs.slice(0, 10)
  });
}

/** @param {Array<Object>} revs */
function pickRevisionTarget_(revs, steps, beforeIso) {
  if (beforeIso) {
    var beforeMs = new Date(beforeIso).getTime();
    if (isNaN(beforeMs)) {
      throw new Error('before bukan tanggal ISO valid: ' + beforeIso);
    }
    for (var i = 1; i < revs.length; i++) {
      var t = new Date(revs[i].modifiedTime).getTime();
      if (t < beforeMs) {
        return revs[i];
      }
    }
    return null;
  }

  var idx = Math.max(1, Math.min(Number(steps) || 1, revs.length - 1));
  return revs[idx];
}

function listDriveRevisions_(fileId, limit) {
  var token = ScriptApp.getOAuthToken();
  var out = [];
  var pageToken = '';

  while (out.length < limit) {
    var url = 'https://www.googleapis.com/drive/v3/files/' + encodeURIComponent(fileId)
      + '/revisions?fields=nextPageToken,revisions(id,modifiedTime,lastModifyingUser,keepForever,size)'
      + '&pageSize=' + Math.min(100, limit - out.length);
    if (pageToken) {
      url += '&pageToken=' + encodeURIComponent(pageToken);
    }

    var resp = UrlFetchApp.fetch(url, {
      headers: { Authorization: 'Bearer ' + token },
      muteHttpExceptions: true
    });
    if (resp.getResponseCode() !== 200) {
      throw new Error('list revisions gagal (' + resp.getResponseCode() + '): ' + resp.getContentText());
    }

    var data = JSON.parse(resp.getContentText());
    var batch = data.revisions || [];
    for (var i = 0; i < batch.length; i++) {
      out.push({
        index: out.length,
        id: batch[i].id,
        modifiedTime: batch[i].modifiedTime,
        keepForever: !!batch[i].keepForever,
        size: batch[i].size || null,
        user: batch[i].lastModifyingUser
          ? (batch[i].lastModifyingUser.displayName || batch[i].lastModifyingUser.emailAddress || '')
          : ''
      });
      if (out.length >= limit) {
        break;
      }
    }

    pageToken = data.nextPageToken || '';
    if (!pageToken || batch.length === 0) {
      break;
    }
  }

  return out;
}

function getDriveFileMeta_(fileId) {
  var token = ScriptApp.getOAuthToken();
  var url = 'https://www.googleapis.com/drive/v3/files/' + encodeURIComponent(fileId)
    + '?fields=id,name,mimeType,modifiedTime';
  var resp = UrlFetchApp.fetch(url, {
    headers: { Authorization: 'Bearer ' + token },
    muteHttpExceptions: true
  });
  if (resp.getResponseCode() !== 200) {
    throw new Error('get file meta gagal: ' + resp.getContentText());
  }
  return JSON.parse(resp.getContentText());
}

function restoreDriveRevision_(fileId, revisionId) {
  throw new Error(
    'Google Sheets native tidak mendukung download revision via Drive API. '
    + 'Pakai restore_from_xlsx atau File → Version history manual.'
  );
}

/**
 * Timpa isi master Google Sheet dari .xlsx — file ID & URL tetap sama.
 */
function handleRestoreFromXlsx(body) {
  if (!body.spreadsheet_id || !body.data) {
    return jsonOut({ ok: false, error: 'spreadsheet_id dan data (base64) wajib diisi' });
  }

  var dryRun = !!body.dry_run;
  var meta = getDriveFileMeta_(body.spreadsheet_id);
  var report = {
    spreadsheet_id: body.spreadsheet_id,
    title: meta.name,
    filename: String(body.filename || 'restore.xlsx'),
    dry_run: dryRun
  };

  if (dryRun) {
    return jsonOut({ ok: true, report: report });
  }

  replaceSpreadsheetFromXlsx_(body.spreadsheet_id, body.data, report.filename);
  var after = getDriveFileMeta_(body.spreadsheet_id);
  report.modified_time = after.modifiedTime;

  return jsonOut({ ok: true, report: report });
}

function replaceSpreadsheetFromXlsx_(fileId, base64Data, filename) {
  if (typeof Drive === 'undefined' || !Drive.Files) {
    throw new Error('Drive API belum aktif. Services → Drive API → Add → Deploy New version.');
  }

  var bytes = Utilities.base64Decode(base64Data);
  var blob = Utilities.newBlob(
    bytes,
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    filename
  );

  if (Drive.Files.update) {
    Drive.Files.update(
      { mimeType: MimeType.GOOGLE_SHEETS },
      fileId,
      blob,
      { convert: true }
    );
    return;
  }

  var token = ScriptApp.getOAuthToken();
  var boundary = 'sis_ocms_restore_' + Utilities.getUuid();
  var metadata = JSON.stringify({ mimeType: 'application/vnd.google-apps.spreadsheet' });
  var payload = ''
    + '--' + boundary + '\r\n'
    + 'Content-Type: application/json; charset=UTF-8\r\n\r\n'
    + metadata + '\r\n'
    + '--' + boundary + '\r\n'
    + 'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet\r\n\r\n';
  var bodyBytes = Utilities.newBlob(payload).getBytes()
    .concat(blob.getBytes())
    .concat(Utilities.newBlob('\r\n--' + boundary + '--').getBytes());

  var url = 'https://www.googleapis.com/upload/drive/v3/files/' + encodeURIComponent(fileId)
    + '?uploadType=multipart';
  var resp = UrlFetchApp.fetch(url, {
    method: 'patch',
    headers: {
      Authorization: 'Bearer ' + token,
      'Content-Type': 'multipart/related; boundary=' + boundary
    },
    payload: bodyBytes,
    muteHttpExceptions: true
  });
  if (resp.getResponseCode() !== 200) {
    throw new Error('multipart restore gagal (' + resp.getResponseCode() + '): ' + resp.getContentText());
  }
}
