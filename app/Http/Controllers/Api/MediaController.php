<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Tag(
 *     name="Media",
 *     description="API Endpoints for Media Management"
 * )
 */
class MediaController extends Controller
{
    /**
     * @OA\Get(
     *      path="/api/media",
     *      tags={"Media"},
     *      summary="List media",
     *      security={{"sanctum":{}}},
     *      @OA\Response(
     *          response=200, 
     *          description="List of media",
     *          @OA\JsonContent(
     *              @OA\Property(property="data", type="array", @OA\Items(
     *                  @OA\Property(property="id", type="integer"),
     *                  @OA\Property(property="url", type="string"),
     *                  @OA\Property(property="type", type="string")
     *              ))
     *          )
     *      )
     * )
     */
    public function index(Request $request)
    {
        $query = Media::query();

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        return response()->json($query->paginate(20));
    }

    /**
     * @OA\Post(
     *      path="/api/media",
     *      tags={"Media"},
     *      summary="Create media record (and associations)",
     *      description="Create a media record (e.g. from an existing URL) and associate it.",
     *      security={{"sanctum":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"url","type"},
     *              @OA\Property(property="url", type="string", example="https://example.com/image.jpg"),
     *              @OA\Property(property="type", type="string", example="image"),
     *              @OA\Property(property="model_id", type="integer", description="Optional parent ID"),
     *              @OA\Property(property="model_type", type="string", description="Optional parent type"),
     *              @OA\Property(property="meta", type="object", description="Optional metadata"),
     *              @OA\Property(property="associations", type="array", @OA\Items(type="object"))
     *          )
     *      ),
     *      @OA\Response(response=201, description="Created")
     * )
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'url' => 'required|string',
            'type' => 'required|string', // image, video, document
            'model_id' => 'nullable|integer',
            'model_type' => 'nullable|string',
            'meta' => 'nullable|array',
            'associations' => 'nullable|array',
        ]);

        $media = Media::create(array_merge($validated, [
            // Defaults for when created without direct parent (e.g. library upload)
            'model_id' => $validated['model_id'] ?? 0,
            'model_type' => $validated['model_type'] ?? 'library',
        ]));

        if (!empty($validated['associations'])) {
            foreach ($validated['associations'] as $assoc) {
                if (isset($assoc['type'], $assoc['id'])) {
                    \App\Models\Association::create([
                        'object_type_a' => 'media',
                        'object_id_a' => $media->id,
                        'object_type_b' => \Illuminate\Support\Str::singular($assoc['type']),
                        'object_id_b' => $assoc['id'],
                    ]);
                }
            }
        }

        return response()->json(['data' => $media], 201);
    }

    /**
     * @OA\Get(
     *      path="/api/media/{id}",
     *      tags={"Media"},
     *      summary="Get media details",
     *      security={{"sanctum":{}}},
     *      @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *      @OA\Response(response=200, description="Media details")
     * )
     */
    public function show($id)
    {
        $media = Media::findOrFail($id);
        
        // Associations (using trait)
        // Note: Traits might not be on generic Models unless we adding them dynamically or ensuring Media has it.
        // We added HasAssociations to Media in previous step.
        $contacts = $media->getAssociated(\App\Models\Contact::class)->get();
        $deals = $media->getAssociated(\App\Models\Deal::class)->get();
        $properties = $media->getAssociated(\App\Models\Property::class)->get();

        return response()->json([
            'data' => [
                'id' => $media->id,
                'attributes' => $media,
                'associations' => [
                    'contacts' => $contacts,
                    'deals' => $deals,
                    'properties' => $properties,
                ]
            ]
        ]);
    }

    /**
     * @OA\Delete(
     *      path="/api/media/{id}",
     *      tags={"Media"},
     *      summary="Delete media",
     *      security={{"sanctum":{}}},
     *      @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *      @OA\Response(response=200, description="Deleted")
     * )
     */
    public function destroy($id)
    {
        $media = Media::findOrFail($id);
        $media->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
