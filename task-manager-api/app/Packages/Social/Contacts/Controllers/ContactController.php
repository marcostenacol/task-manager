<?php

namespace App\Packages\Social\Contacts\Controllers;

use App\Http\Controllers\Controller;
use App\Base\Traits\Response;
use App\Packages\Social\Contacts\Services\CreateContactService;
use App\Packages\Social\Contacts\Models\UserContact;
use App\Packages\Social\Contacts\Resources\ContactResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ContactController extends Controller
{
    use Response;

    public function index(): JsonResponse
    {
        try {
            $user = userObject();
            $contacts = UserContact::where('user_id', $user->id)->get();
            return self::successResponse(ContactResource::collection($contacts), 'Contatos recuperados com sucesso.');
        } catch (\Exception $e) {
            return self::returnError($e);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'type' => 'required|string|max:50',
                'value' => 'required|string|max:255',
                'is_primary' => 'sometimes|boolean',
            ]);

            $user = userObject();
            $data = app(CreateContactService::class)->execute($user->id, $request->all());
            
            return self::successResponse($data, 'Contato criado com sucesso.');
        } catch (\Exception $e) {
            return self::returnError($e);
        }
    }

    public function update(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'contacts' => 'required|array',
                'contacts.*.type' => 'required|string|max:50',
                'contacts.*.value' => 'required|string|max:255',
                'contacts.*.is_primary' => 'sometimes|boolean',
            ]);

            $user = userObject();
            app(\App\Packages\Social\Contacts\Services\UpdateContactsService::class)->execute($user->id, $request->contacts);
            
            return self::successResponse(null, 'Contatos atualizados com sucesso.');
        } catch (\Exception $e) {
            return self::returnError($e);
        }
    }

    public function destroy(string $id): JsonResponse
    {
        try {
            $user = userObject();
            $contact = UserContact::where('user_id', $user->id)->findOrFail($id);
            $contact->delete();
            
            return self::successResponse(null, 'Contato removido com sucesso.');
        } catch (\Exception $e) {
            return self::returnError($e);
        }
    }
}
