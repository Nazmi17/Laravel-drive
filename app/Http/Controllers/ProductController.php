<?php

namespace App\Http\Controllers;

use Google\Client;
use App\Models\Product;
use App\Models\Category;
use Google\Service\Drive;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Google\Service\Drive\DriveFile;
use Google\Service\Drive\Permission;
use Illuminate\Support\Facades\Validator;

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

    public function index() {
        $data = Category::all();

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    public function store($categorySlug, Request $request) {
        $validator = Validator::make($request->all(), [
           'name' => 'required|string|max:255',
           'price' => 'required|numeric',
           'description' => 'required|string',
           'stock' => 'required|integer',
           'image_url' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak valid',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $imageFile = $request->file('image_url');
            $fileName = time() . '.' . $imageFile->getClientOriginalName();

            $client = $this->initializeGoogleClient();
            $service = new Drive($client);

            $fileMetaData = new DriveFile([
                'name' => $fileName,
                'parents' => ['1kC-9BNBR02b-zNnNqWLkqnett4p0Be47?usp=drive_link'],
            ]);

            $content = file_get_contents($imageFile->getRealPath());
            $file = $service->files->create($fileMetaData, [
                'data' => $content,
                'mimeType' => $imageFile->getMimeType(),
                'uploadType' => 'multipart',
                'fields' => 'id',
                'supportsAllDrives' => true,
            ]);

            $field = $file->id;

            if (!$field) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal upload file',
                ], 500);
            }

            if (!$this->makeFilePublic($field)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal membuat file publik',
                ], 500);
            }

            $directURL = 'https://drive.google.com/uc?id=' . $field;
            $slug = Str::slug($request->name);
            $categoryId = Category::where('slug', $categorySlug)->first()->id;

            if (Product::where('slug', $slug)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Produk sudah ada',
                ], 500);
            }

            $product = Product::create([
                'name' => $request->name,
                'slug' => $slug,
                'description' => $request->description,
                'price' => $request->price,
                'stock' => $request->stock,
                'category_id' => $categoryId,
                'image_url' => $directURL,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Produk berhasil dibuat',
                'data' => $product
            ], 201);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat produk',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
