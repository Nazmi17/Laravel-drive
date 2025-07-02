<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Google\Client;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile;
use Google\Service\Drive\Permission;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    private function initializeGoogleClient() {
        $client = new Client();
        $client->setClientId(env('GOOGLE_DRIVE_CLIENT_ID'));
        $client->setClientSecret(env('GOOGLE_DRIVE_CLIENT_SECRET'));
        $client->refreshToken(env('GOOGLE_DRIVE_REFRESH_TOKEN'));
        $client->setAccessType('offline');
        $client->setScopes(Drive::DRIVE_FILE);
        $client->addScope(Drive::DRIVE);

        return $client;
    }

    private function makeFilePublic($field) {
        try{
            $client = $this->initializeGoogleClient();
            $service = new Drive($client);

            $permission = new Permission();
            $permission->setRole('reader');
            $permission->setType('anyone');

            $file = $service->files->get($field);
            $service->permissions->create($file->id, $permission);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

     public function testMakePublic(Request $request) {
        $fileId = $request->input('file_id'); 

        try {
            $client = $this->initializeGoogleClient();
            $service = new Drive($client);

            $permission = new Permission();
            $permission->setRole('reader');
            $permission->setType('anyone');

            $service->permissions->create($fileId, $permission);

            return response()->json([
                'success' => true,
                'message' => 'File berhasil dibuat publik',
                'url' => 'https://drive.google.com/uc?id=' . $fileId,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat file publik',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
