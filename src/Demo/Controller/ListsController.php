<?php

declare(strict_types=1);

namespace Dartcafe\EmailValidator\Demo\Controller;

use Dartcafe\EmailValidator\Demo\Http\Request;
use Dartcafe\EmailValidator\Demo\Http\Response;
use Dartcafe\EmailValidator\Demo\Service\ArchiveService;
use Dartcafe\EmailValidator\Demo\Service\ListStore;

/**
 * Controller for /lists endpoints.
 *
 * GET /lists
 *   Get the current lists.ini and all referenced files
 *
 * POST /lists
 *   Save lists.ini and all referenced files
 *
 * GET /lists/export
 *   Export lists.ini and all referenced files as a ZIP archive
 *
 * POST /lists/import
 *   Import lists.ini and all referenced files from a ZIP archive or JSON
 */
final class ListsController
{
    /**
     * @param ListStore      $store   The ListStore service
     * @param ArchiveService $archive The ArchiveService service
     */
    public function __construct(
        private ListStore $store,
        private ArchiveService $archive,
    ) {
    }

    /**
     * Get the current lists.ini and all referenced files
     *
     * @return Response JSON response with structure:
     * {
     *   "iniPath": "config/lists.ini",
     *   "ini": "contents of lists.ini",
     *   "files": {
     *     "sectionName": {
     *       "path": "relative/path/to/file",
     *       "content": "contents of the file"
     *     },
     *     ...
     *   }
     * }
     */
    public function get(Request $r): Response
    {
        $data = $this->store->load();
        return Response::json(200, [
            'iniPath' => 'config/lists.ini',
            'ini'     => $data['ini'],
            'files'   => $data['files'],
        ]);
    }

    /**
     * Save lists.ini and all referenced files
     *
     * Expects a JSON body with structure:
     * {
     *   "ini": "contents of lists.ini",
     *   "files": {
     *     "sectionName": {
     *       "path": "relative/path/to/file",
     *       "content": "contents of the file"
     *     },
     *     ...
     *   }
     * }
     *
     * @return Response JSON response with structure:
     * {
     *   "status": "ok"
     * }
     */
    public function save(Request $r): Response
    {
        if ($r->json === null) {
            return Response::json(415, ['error' => 'Use application/json']);
        }
        $ini = (string)($r->json['ini'] ?? '');
        /** @var array<string, array{path:string,content:string}> $files */
        $files = (array)($r->json['files'] ?? []);
        $this->store->save($ini, $files);
        return Response::json(200, ['status' => 'ok']);
    }

    /**
     * Export lists.ini and all referenced files as a ZIP archive
     *
     * @return Response The ZIP file as an attachment
     */
    public function export(Request $r): Response
    {
        $pkg = $this->archive->exportZip();
        return new Response(
            200,
            $pkg['content'],
            [
                'Content-Type'        => $pkg['ctype'],
                'Content-Disposition' => 'attachment; filename="' . $pkg['filename'] . '"',
            ],
        );
    }

    /**
     * Import lists.ini and all referenced files from a ZIP archive or JSON
     *
     * Accepts multipart/form-data file "archive" (ZIP or JSON) OR application/json body
     *
     * @return Response JSON response with structure:
     * {
     *   "status": "ok",
     *   "summary": {
     *     "ini": true,      // true if lists.ini was found and written
     *     "files": 3       // number of files written
     *   }
     * }
     */
    public function import(Request $r): Response
    {
        // Accept multipart/form-data file "archive" OR application/json body
        $ct = $r->headers['content-type'] ?? '';
        if (stripos($ct, 'application/json') !== false && $r->rawBody !== null) {
            $tmp = tempnam(sys_get_temp_dir(), 'elist_json_') ?: null;
            if ($tmp === null) {
                return Response::json(500, ['error' => 'Tempfile error']);
            }
            file_put_contents($tmp, $r->rawBody);
            $summary = $this->archive->importFromUpload($tmp, 'application/json');
            @unlink($tmp);
            return Response::json(200, ['status' => 'ok', 'summary' => $summary]);
        }

        if (empty($_FILES['archive'] ?? null) || !is_uploaded_file($_FILES['archive']['tmp_name'])) {
            return Response::json(400, ['error' => 'Upload a file as "archive"']);
        }
        $tmp = $_FILES['archive']['tmp_name'];
        $ctype = (string)($_FILES['archive']['type'] ?? 'application/octet-stream');
        $summary = $this->archive->importFromUpload($tmp, $ctype);
        return Response::json(200, ['status' => 'ok', 'summary' => $summary]);
    }
}
