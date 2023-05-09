<?php

namespace App\Http\Controllers;

use App\Models\ArticelPrefrences;
use Illuminate\Http\Request;

class ArticelPrefrencesController extends Controller
{
    public function index(Request $request)
{
    $user = $request->user();
    $preferences = ArticelPrefrences::where('user_id', $user->id)->first();
    return $preferences;
}


    // Update an existing article preference
    public function update(Request $request, $id)
    {
        $articlePreference = ArticelPrefrences::where('user_id', $id)->first();

        if (!$articlePreference) {
            $articlePreference = new ArticelPrefrences;
            $articlePreference->user_id = $request->user_id;
            $articlePreference->categories = json_encode($request->categories);
            $articlePreference->sources = json_encode($request->sources);
            $articlePreference->authors = json_encode($request->authors);

            $articlePreference->save();

            return response()->json([
                'message' => 'Article preference created successfully',
                'data' => $articlePreference
            ], 201);
        }

        $articlePreference->user_id = $request->user_id;
        $articlePreference->categories = $request->categories;
        $articlePreference->sources = $request->sources;
        $articlePreference->authors = $request->authors;

        $articlePreference->save();

        return response()->json([
            'message' => 'Article preference updated successfully',
            'data' => $articlePreference
        ], 200);
    }
}
