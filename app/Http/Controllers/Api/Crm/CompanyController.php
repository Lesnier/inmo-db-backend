<?php

namespace App\Http\Controllers\Api\Crm;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Tag(
 *     name="CRM Companies",
 *     description="API Endpoints for Company Management" 
 * )
 */
class CompanyController extends Controller
{
    /**
     * @OA\Get(
     *      path="/api/crm/companies",
     *      tags={"CRM Companies"},
     *      summary="List companies",
     *      security={{"sanctum":{}}},
     *      @OA\Response(
     *          response=200, 
     *          description="List of companies",
     *          @OA\JsonContent(
     *              @OA\Property(property="data", type="array", @OA\Items(
     *                  @OA\Property(property="id", type="integer", example=10),
     *                  @OA\Property(property="name", type="string", example="Acme Corp"),
     *                  @OA\Property(property="industry", type="string", example="Technology")
     *              ))
     *          )
     *      )
     * )
     */
    public function index(Request $request)
    {
        $query = Company::query();

        if ($request->has('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }

        return response()->json($query->paginate(20));
    }

    /**
     * @OA\Post(
     *      path="/api/crm/companies",
     *      tags={"CRM Companies"},
     *      summary="Create company",
     *      security={{"sanctum":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"name"},
     *              @OA\Property(property="name", type="string", example="Acme Corp"),
     *              @OA\Property(property="industry", type="string", example="Technology"),
     *              @OA\Property(property="domain", type="string", example="acme.com"),
     *              @OA\Property(property="phone", type="string", example="+1 555 0123"),
     *              @OA\Property(property="email", type="string", format="email", example="contact@acme.com"),
     *              @OA\Property(property="address", type="string", example="123 Main St"),
     *              @OA\Property(property="city", type="string", example="Metropolis"),
     *              @OA\Property(property="state", type="string", example="NY"),
     *              @OA\Property(property="country", type="string", example="USA"),
     *              @OA\Property(property="owner_id", type="integer", description="User ID of the owner"),
     *              @OA\Property(property="data", type="object", description="Additional JSON data"),
     *              @OA\Property(property="associations", type="array", @OA\Items(
     *                  @OA\Property(property="type", type="string", example="contacts"),
     *                  @OA\Property(property="id", type="integer", example=1)
     *              ))
     *          )
     *      ),
     *      @OA\Response(response=201, description="Created")
     * )
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'industry' => 'nullable|string',
            'domain' => 'nullable|string',
            'phone' => 'nullable|string',
            'email' => 'nullable|email',
            'address' => 'nullable|string',
            'city' => 'nullable|string',
            'state' => 'nullable|string',
            'country' => 'nullable|string',
            'owner_id' => 'nullable|exists:users,id',
            'data' => 'nullable|array',
            'associations' => 'nullable|array',
        ]);

        $company = Company::create(array_merge($validated, [
            // 'owner_id' => $validated['owner_id'] ?? Auth::id(), // If company has owner
        ]));

        if (!empty($validated['associations'])) {
            foreach ($validated['associations'] as $assoc) {
                if (isset($assoc['type'], $assoc['id'])) {
                    \App\Models\Association::create([
                        'object_type_a' => 'company',
                        'object_id_a' => $company->id,
                        'object_type_b' => \Illuminate\Support\Str::singular($assoc['type']),
                        'object_id_b' => $assoc['id'],
                    ]);
                }
            }
        }

        return response()->json(['data' => $company], 201);
    }

    /**
     * @OA\Get(
     *      path="/api/crm/companies/{id}",
     *      tags={"CRM Companies"},
     *      summary="Get company details",
     *      security={{"sanctum":{}}},
     *      @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *      @OA\Response(response=200, description="Company details")
     * )
     */
    public function show($id)
    {
        $cacheKey = "crm_company_{$id}_detail";

        return \Illuminate\Support\Facades\Cache::tags(["crm_company_{$id}"])->remember($cacheKey, now()->addMinutes(60), function () use ($id) {
            $company = Company::findOrFail($id);
            
            // Associations
            $contacts = $company->getAssociated(\App\Models\Contact::class)->get();
            $deals = $company->getAssociated(\App\Models\Deal::class)->get();
            $tickets = $company->getAssociated(\App\Models\Ticket::class)->get();

            return [
                'data' => [
                    'id' => $company->id,
                    'attributes' => $company,
                    'associations' => [
                        'contacts' => $contacts,
                        'deals' => $deals,
                        'tickets' => $tickets,
                    ]
                ]
            ];
        });
    }

    /**
     * @OA\Put(
     *      path="/api/crm/companies/{id}",
     *      tags={"CRM Companies"},
     *      summary="Update company",
     *      security={{"sanctum":{}}},
     *      @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *      @OA\Response(response=200, description="Updated")
     * )
     */
    public function update(Request $request, $id)
    {
        $company = Company::findOrFail($id);
        
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'industry' => 'nullable|string',
            'domain' => 'nullable|string',
            'phone' => 'nullable|string',
            // ...
        ]);

        $company->update($validated);
        return response()->json(['data' => $company]);
    }

    /**
     * @OA\Delete(
     *      path="/api/crm/companies/{id}",
     *      tags={"CRM Companies"},
     *      summary="Delete company",
     *      security={{"sanctum":{}}},
     *      @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *      @OA\Response(response=200, description="Deleted")
     * )
     */
    public function destroy($id)
    {
        $company = Company::findOrFail($id);
        $company->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
