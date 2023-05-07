<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\ArticelPrefrences;
use App\Services\ArticlesService;

class ArticlesController extends Controller
{
    public function index(Request $request)
    {
        $articles = [];
        if (auth()->check()) {
            $preferences = ArticelPrefrences::where('user_id', auth()->user()->id)->first();
            $categories = json_decode($preferences->categories);
            $authors = json_decode($preferences->authors);
            $sources = json_decode($preferences->sources);

            $articles = ArticlesService::fetchArticlesFromAPIs($categories, $authors, $sources);
        } else {
            $articles = ArticlesService::fetchArticlesFromAPIs();
        }

        return response()->json([
            'articles' => $articles,
        ]);
    }

    public function getCategory()
    {
        // Fetch data from NewsAPI
        $newsApiResponse = ArticlesService::newsApiSource();

        // Fetch data from GuardianAPI
        $guardianApiResponse = ArticlesService::guardianApi();

        // Fetch data from New York Times API
        $nytApiResponse = $this->nytimesApi();

        $categoryNames = [];

        // Guardian API
        foreach ($guardianApiResponse['sources'] as $source) {
            if ($source['category']) {
                $categoryNames[] = $result['category'];
            }
        }

        // Guardian API
        foreach ($guardianApiResponse['response']['results'] as $result) {
            if ($result['sectionId']) {
                $categoryNames[] = $result['sectionId'];
            }
        }

        // NYTimes API
        foreach ($nytApiResponse['response']['docs'] as $doc) {
            if ($doc['section_name']) {
                $categoryNames[] = $doc['section_name'];
            }
        }

        // Get only the unique categories
        $uniqueCategories = array_unique($categoryNames);

        return $uniqueCategories;
    }

    public function getAuthor()
    {
        // Fetch data from NewsAPI
        $newsapiResponse = ArticlesService::newsApi();

        // Fetch data from GuardianAPI
        $guardianApiResponse = ArticlesService::guardianApi();

        // Fetch data from New York Times API
        $nytApiResponse = ArticlesService::nytimesApi();

        $authorNames = [];

        // NewsAPI
        foreach ($newsapiResponse['articles'] as $article) {
            if ($article['author']) {
                $authorNames[] = $article['author'];
            }
        }

        // NYTimes API
        foreach ($nytApiResponse['response']['docs'] as $doc) {
            if ($doc['byline'] && isset($doc['byline']['person'][0]['lastname'])) {
                $authorNames[] = $doc['byline']['person'][0]['lastname'];
            }
        }

        // Guardian API
        foreach ($guardianApiResponse['response']['results'] as $result) {
            $authorNames[] = $result['fields']['byline'];
        }

        // Get only the unique authors
        $uniqueAuthors = array_unique($authorNames);
        return $uniqueAuthors;
    }
}
