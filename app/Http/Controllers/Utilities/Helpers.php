<?php

namespace App\Http\Controllers\Utilities;

use App\Http\Controllers\Controller;
use App\Models\Campaigns\Category;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;

class Helpers extends Controller
{ 
    private const SHORT_URL_API_KEY = '6Lfz5mMpAAAAAAAA';
    public static function convertToTitleCase($name): string
    {
        // Convert the name to lowercase
        $name = strtolower($name);

        // Use words to capitalize the first letter of each word
        return ucwords($name);
    }

    public static function getFirstName($name): string
    {
        $name = strtolower($name);
        $titleCaseName = ucwords($name);
        $name = explode(' ', $titleCaseName);

        return $name[0];
    }

    public static function uploadImageToFTP($filenametostore, $filePath): void
    {
        Storage::disk('ftp')->put($filenametostore, fopen($filePath, 'rb'));
    }

    /**
     * @param  int|null  $limit
     * @return array
     */
    public function campaignCategories($limit = null)
    {
        $limit = (int) $limit;
        $categories = Category::when($limit, function ($query) use ($limit) {
            return $query->take($limit);
        })->get();

        $categoryImages = [
            'Emergency' => 'path/to/emergency-image.jpg',
            'Evangelism' => 'path/to/evangelism-image.jpg',
            'Event' => 'path/to/event-image.jpg',
            'Family' => 'path/to/family-image.jpg',
            'Legal' => 'path/to/legal-image.jpg',
            'Medical' => 'path/to/medical-image.jpg',
            'Memorial' => 'path/to/memorial-image.jpg',
            'Mission' => 'path/to/mission-image.jpg',
            'Faith' => 'path/to/faith-image.jpg',
            'Non-Profit' => 'path/to/non-profit-image.jpg',
            'Adoption' => 'path/to/adoption-image.jpg',
            'Animal/Pets' => 'path/to/animal-pets-image.jpg',
            'Business' => 'path/to/business-image.jpg',
            'Church' => 'path/to/church-image.jpg',
            'Community' => 'path/to/community-image.jpg',
            'Competition' => 'path/to/competition-image.jpg',
            'Creative' => 'path/to/creative-image.jpg',
            'Current Events' => 'path/to/current-events-image.jpg',
            'Education' => 'path/to/education-image.jpg',
        ];

        // Combine categories and images into a single array
        $result = [];
        foreach ($categories as $category) {
            $result[] = [
                'name' => $category->name,
                'image' => $categoryImages[$category->name] ?? null,
            ];
        }

        return $result;
    }

    public static function getImageIfExist($imageUrl, $defaultImageUrl, $cacheDuration = 1440)
    {
        // Define the cache key based on the image URL
        $cacheKey = 'image_' . md5($imageUrl);
    
        // Check if the result is cached
        if (Cache::has($cacheKey)) {
            // Return the cached result
            return Cache::get($cacheKey);
        }
    
        // Initialize cURL session
        $ch = curl_init($imageUrl);
    
        // Set options to retrieve headers only
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
        // Execute cURL session
        $response = curl_exec($ch);
    
        // Get HTTP status code
        $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
        // Close cURL session
        curl_close($ch);
    
        // Check if image exists (HTTP status 200)
        if ($httpStatus == 200) {
            // Image exists, cache the result and return original URL
            Cache::put($cacheKey, $imageUrl, $cacheDuration);
            return $imageUrl;
        } else {
            // Image does not exist, cache the default image URL and return it
            Cache::put($cacheKey, $defaultImageUrl, $cacheDuration);
            return $defaultImageUrl;
        }
    }

    public static function generateShortUrl($url)
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://kdgiv.in/api/generate/shorturl',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode(
                array(
                    'url' => $url,
                    'activateAt' => '',
                    'deactivateAt' => ''
                )
            ),
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Authorization: Bearer ' . self::SHORT_URL_API_KEY
            ),
        )
        );

        $response = curl_exec($curl);
        $http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        if ($http_status != 200) {
            // Handle HTTP error
            return null;
        }

        curl_close($curl);

        $response = json_decode($response, true);

        // Check if the response is successful
        if (isset($response['response']) && $response['response'] == 'success' && isset($response['short_url'])) {
            return $response['short_url'];
        } else {
            // Handle API response error
            return null;
        }
    }
}
