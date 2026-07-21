/**
 * SISI-OCMS — Web App duplikasi template checksheet Google Sheets.
 *
 * CARA DEPLOY (sekali saja, pakai akun Google pemilik template):
 * 1. Buka https://script.google.com → New project.
 * 2. Hapus isi editor, paste seluruh file ini, simpan (beri nama misal "OCMS Sheet Copier").
 * 3. Klik Deploy → New deployment → jenis "Web app".
 *    - Description : ocms-copier
 *    - Execute as  : Me (akunmu)
 *    - Who has access: Anyone
 * 4. Klik Deploy, izinkan permission yang diminta, lalu salin "Web app URL"
 *    (bentuknya https://script.google.com/macros/s/…/exec).
 * 5. Tempel URL itu di file .env aplikasi:
 *    GSHEET_COPY_WEBAPP_URL=https://script.google.com/macros/s/…/exec
 *
 * Web app menerima POST JSON: { "template_id": "...", "name": "...", "secret": "..." }
 * dan membalas JSON: { "ok": true, "url": "https://docs.google.com/..." }
 *
 * (Opsional) Ganti SECRET di bawah lalu set juga GSHEET_COPY_SECRET di .env
 * agar hanya aplikasi yang bisa memanggil.
 */

var SECRET = ''; // kosongkan jika tidak pakai secret
var TARGET_FOLDER_NAME = 'OCMS Checksheet Copies'; // folder tujuan salinan di Drive

function doPost(e) {
  try {
    var body = JSON.parse(e.postData.contents);

    if (SECRET && body.secret !== SECRET) {
      return jsonOut({ ok: false, error: 'unauthorized' });
    }
    if (!body.template_id || !body.name) {
      return jsonOut({ ok: false, error: 'template_id dan name wajib diisi' });
    }

    var folder = getOrCreateFolder(TARGET_FOLDER_NAME);
    var copy = DriveApp.getFileById(body.template_id).makeCopy(body.name, folder);

    // Supaya iframe di aplikasi langsung bisa dipakai semua user
    copy.setSharing(DriveApp.Access.ANYONE_WITH_LINK, DriveApp.Permission.EDIT);

    return jsonOut({ ok: true, id: copy.getId(), url: copy.getUrl() });
  } catch (err) {
    return jsonOut({ ok: false, error: String(err) });
  }
}

function getOrCreateFolder(name) {
  var it = DriveApp.getFoldersByName(name);
  return it.hasNext() ? it.next() : DriveApp.createFolder(name);
}

function jsonOut(obj) {
  return ContentService.createTextOutput(JSON.stringify(obj))
    .setMimeType(ContentService.MimeType.JSON);
}
