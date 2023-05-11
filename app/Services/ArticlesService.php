<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class ArticlesService
{
    public static function newsApiSource()
    {
        $params = [
            'apiKey' => env('NEWS_API_KEY'),
        ];

        return Http::get('https://newsapi.org/v2/top-headlines/sources', $params)->json();
    }

    public static function newsApi($q = null, $author = null)
    {
        $params = [
            'apiKey' => env('NEWS_API_KEY'),
            'country' => 'us',
        ];
        if ($q !== null) {
            $params['category'] = implode('|', $q);
        }
        if ($author !== null) {
            // $params['sources'] = implode(',', $author);
        }

        return Http::get('https://newsapi.org/v2/top-headlines', $params)->json();
    }

    public static function guardianApi($q = null, $author = null)
    {
        $params = [
            'api-key' => env('GUARDIAN_API_KEY'),
            'show-fields' => 'byline,trailText,thumbnail',
        ];
        if ($q !== null) {
            $params['section'] = implode('|', $q);
        }
        if ($author !== null) {
            // $params['byline'] = implode('|', $author);
        }
        return Http::get('https://content.guardianapis.com/search', $params)->json();
    }

    public static function nytimesApi($q = null, $author = null)
    {
        $params = [
            'api-key' => env('NYT_API_KEY'),
        ];
        if ($q !== null) {
            $q = implode(' OR ', $q);
            $params['fq'] = "section_name:($q)";
        }
        if ($author !== null) {
            $author = implode(' OR ', $author);
            $params['fq'] .= " AND byline:($author)";
        }

        return Http::get('https://api.nytimes.com/svc/search/v2/articlesearch.json', $params)->json();
    }

    public static function fetchArticlesFromAPIs($categories = null, $authors = null, $sources = null)
    {
        $articles = [];

        // News API
        if ($sources && in_array('newapi', $sources)) {
            $newsApiResponse = self::newsApi($categories, $authors);
        } elseif (!$sources && !$categories && !$authors) {
            $newsApiResponse = self::newsApi();
        } 

        if (isset($newsApiResponse['articles'])) {
            foreach ($newsApiResponse['articles'] as $result) {
                $articles[] = [
                    'id' => $result['url'],
                    'title' => $result['title'],
                    'description' => $result['description'],
                    'source' => 'BBC News',
                    'category' => '',
                    'author' => $result['author'] ?? '',
                    'url' => $result['url'],
                    'urlToImage' => $result['urlToImage'] ?? '',
                    'date' => $result['publishedAt'],
                    'source' => 'newapi'
                ];
            }
        }


        // Guardian API
        if ($sources && in_array('guardian', $sources)) {
            $guardianApiResponse = self::guardianApi($categories, $authors);
        } elseif (!$sources && !$categories && !$authors) {
            $guardianApiResponse = self::guardianApi();
        } 
          
        if(isset($guardianApiResponse['response']['results'])){
            foreach ($guardianApiResponse['response']['results'] as $result) {
                $articles[] = [
                    'id' => $result['id'],
                    'title' => $result['webTitle'],
                    'description' => $result['fields']['trailText'],
                    'source' => 'The Guardian',
                    'category' => $result['sectionName'],
                    'author' => $result['fields']['byline'],
                    'url' => $result['webUrl'],
                    'urlToImage' => $result['fields']['thumbnail'],
                    'date' => $result['webPublicationDate'],
                    'source' => 'guardian'
                ];
            }
        }
    

        // NYTimes API
        if ($sources && in_array('nytimes', $sources)) {
            $nytApiResponse = self::nytimesApi($categories, $authors);
        } elseif (!$sources && !$categories && !$authors) {
            $nytApiResponse = self::nytimesApi();
        } 

        
        if (isset($nytApiResponse['response']['docs'])) {
            foreach ($nytApiResponse['response']['docs'] as $result) {
                $articles[] = [
                    'id' => $result['_id'],
                    'title' => $result['headline']['main'],
                    'description' => $result['abstract'],
                    'source' => 'The New York Times',
                    'category' => $result['section_name'] ?? '',
                    'author' => $result['byline']['original'],
                    'url' => $result['web_url'],
                    'urlToImage' => isset($result['multimedia'][0]['url']) ? 'https://www.nytimes.com/' . $result['multimedia'][0]['url'] : '',
                    'date' => $result['pub_date'],
                    'source' => 'nytime'
                ];
            }
        }

        return $articles;
    }
}
