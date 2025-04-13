<?php

namespace App\Http\Controllers;

use App\Http\Requests\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Google_Client;
use Google_Service_Drive;
use Google_Service_Drive_DriveFile;
use Illuminate\Support\Str;

class GoogleDriveController extends Controller
{
    protected $drive;

    public function __construct()
    {
        $client = new Google_Client();
        $client->setAuthConfig(storage_path('app/google/credentials.json'));
        $client->addScope(Google_Service_Drive::DRIVE);
        $client->setAccessType('offline');
        $client->setPrompt('select_account consent');

        $accessToken = json_decode(file_get_contents(storage_path('app/google/token.json')), true);
        $client->setAccessToken($accessToken);

        if ($client->isAccessTokenExpired()) {
            if ($client->getRefreshToken()) {
                $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
                file_put_contents(storage_path('app/google/token.json'), json_encode($client->getAccessToken()));
            } else {
                abort(401, 'Google access token expired.');
            }
        }

        $this->drive = new Google_Service_Drive($client);
    }

    public function upload(FormRequest $request, $patientId)
    {
        $file = $request->file('file');
        $fileMetadata = new Google_Service_Drive_DriveFile([
            'name' => $file->getClientOriginalName(),
            'parents' => [$this->getPatientFolder($patientId)],
        ]);

        $content = file_get_contents($file);
        $createdFile = $this->drive->files->create($fileMetadata, [
            'data' => $content,
            'mimeType' => $file->getMimeType(),
            'uploadType' => 'multipart',
            'fields' => 'id, name, mimeType, createdTime',
        ]);

        return response()->json(['message' => 'File uploaded successfully.', 'file' => $createdFile]);
    }

    public function list(Request $request, $patientId)
    {
        $folderId = $this->getPatientFolder($patientId);

        $query = "'{$folderId}' in parents and trashed = false";
        $files = $this->drive->files->listFiles([
            'q' => $query,
            'fields' => 'files(id, name, mimeType, createdTime)',
        ]);

        return response()->json($files->getFiles());
    }

    public function delete($patientId, $fileId)
    {
        try {
            $this->drive->files->delete($fileId);
            return response()->json(['message' => 'File deleted successfully.']);
        } catch (\Exception $e) {
            Log::error('Google Drive delete error', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to delete file.'], 500);
        }
    }

    private function getPatientFolder($patientId)
    {
        // Optional: Cache folder IDs
        $query = "name='{$patientId}' and mimeType='application/vnd.google-apps.folder' and trashed=false";
        $results = $this->drive->files->listFiles(['q' => $query]);

        if (count($results->getFiles()) > 0) {
            return $results->getFiles()[0]->getId();
        }

        // Create folder if not exists
        $folderMetadata = new Google_Service_Drive_DriveFile([
            'name' => $patientId,
            'mimeType' => 'application/vnd.google-apps.folder',
        ]);

        $folder = $this->drive->files->create($folderMetadata, ['fields' => 'id']);
        return $folder->id;
    }
}
