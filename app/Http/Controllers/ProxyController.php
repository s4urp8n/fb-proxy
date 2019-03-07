<?php
/**
 * Created by PhpStorm.
 * User: s4urp
 * Date: 06.03.2019
 * Time: 0:30
 */

namespace App\Http\Controllers;

use App\FileSystem;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Support\Facades\Storage;

class ProxyController extends Controller
{
    public function proxy()
    {
        $accessToken = request('access_token')
                       ?? request()->query('access_token');

        if (empty($accessToken)) {
            return abort(400, 'access_token is empty');
        }

        return $this->proximateRequest($accessToken);
    }

    protected function proximateRequest($accessToken)
    {
        $url = 'https://graph.facebook.com/' . preg_replace('#^/+#', '', request()->path());
        $method = request()->getMethod();
        $params = $this->collectParams();

        $tempDir = uniqid('_', true);
        Storage::disk('local')->makeDirectory($tempDir);
        $files = $this->collectFiles($tempDir);

        $error = null;
        $response = null;

        try {
            $response = $this->sendRequest($method, $url, $params, $files);
        } catch (ClientException $e) {
            $response = $e->getResponse();
        }

        FileSystem::remove(storage_path('app') . DIRECTORY_SEPARATOR . $tempDir);

        return response($response->getBody()->getContents(), $response->getStatusCode())
            ->header('Content-Type', 'application/json; charset=UTF-8');
    }

    protected function collectParams()
    {
        return array_merge($_GET, $_POST);
    }

    protected function collectFiles($directory)
    {
        if (!empty($_FILES)) {
            return array_map(function ($fileData) use ($directory) {

                if (!empty($fileData['error'])) {
                    throw  new \Exception('File ' . $fileData['name'] . ' uploaded with error');
                }

                $savePath = storage_path('app') . DIRECTORY_SEPARATOR . $directory . DIRECTORY_SEPARATOR . $fileData['name'];
                $tempPath = $fileData['tmp_name'];

                copy($tempPath, $savePath);

                return $savePath;

            }, $_FILES);
        }
        return [];
    }

    protected function sendRequest($method, $url, $params, $files)
    {
        $method = strtolower($method);
        if ($method == 'get') {
            return $this->sendGet($url, $params);
        } elseif ($method == 'post') {
            return $this->sendPost($url, $params, $files);
        }

        throw new \Exception('Unknown request method "' . $method . '"');
    }

    protected function sendGet($url, $params)
    {
        $client = new Client();
        return $client->get($url, ['query' => $params]);
    }

    protected function sendPost($url, $params, $files)
    {
        $multipart = [];

        foreach ($params as $key => $value) {
            $multipart[] = [
                'name'     => $key,
                'contents' => $value,
            ];
        }

        foreach ($files as $key => $path) {
            $multipart[] = [
                'name'     => $key,
                'contents' => fopen($path, 'r'),
                'filename' => basename($path),
            ];
        }

        $client = new Client();

        return $client->post($url, [
            'multipart' => $multipart,
        ]);
    }

}
