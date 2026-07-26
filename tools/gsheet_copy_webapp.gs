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
 */

var SECRET = '';
var TARGET_FOLDER_NAME = 'OCMS Checksheet Copies';

function doPost(e) {
  try {
    var body = JSON.parse(e.postData.contents);

    if (SECRET && body.secret !== SECRET) {
      return jsonOut({ ok: false, error: 'unauthorized' });
    }

    if (body.action === 'upload') {
      return handleUpload(body);
    }
    if (body.action === 'read') {
      return handleRead(body);
    }
    if (body.action === 'ping') {
      return jsonOut({
        ok: true,
        ping: true,
        drive: (typeof Drive !== 'undefined'),
        driveCreate: !!(Drive && Drive.Files && Drive.Files.create),
        driveInsert: !!(Drive && Drive.Files && Drive.Files.insert)
      });
    }

    return handleCopy(body);
  } catch (err) {
    return jsonOut({ ok: false, error: String(err) });
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

function jsonOut(obj) {
  return ContentService.createTextOutput(JSON.stringify(obj))
    .setMimeType(ContentService.MimeType.JSON);
}
