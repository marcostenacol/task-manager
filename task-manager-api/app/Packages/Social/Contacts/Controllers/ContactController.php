<?php

namespace App\Packages\Social\Contacts\Controllers;

use App\Base\Traits\Response;
use App\Http\Controllers\Controller;
use App\Packages\Social\Contacts\Requests\StoreContactRequest;
use App\Packages\Social\Contacts\Requests\UpdateContactRequest;
use App\Packages\Social\Contacts\Resources\ContactResource;
use App\Packages\Social\Contacts\Services\CreateContactService;
use App\Packages\Social\Contacts\Services\DeleteContactService;
use App\Packages\Social\Contacts\Services\ListContactsService;
use App\Packages\Social\Contacts\Services\UpdateContactsService;
use Illuminate\Http\JsonResponse;

class ContactController extends Controller
{
    use Response;

    public function __construct(
        private ListContactsService $listContactsService,
        private CreateContactService $createContactService,
        private UpdateContactsService $updateContactsService,
        private DeleteContactService $deleteContactService,
    ) {}

    public function index(): JsonResponse
    {
        try {
            $user = userObject();
            $contacts = $this->listContactsService->execute($user->id);

            return self::successResponse(ContactResource::collection($contacts), 'Contatos recuperados com sucesso.');
        } catch (\Exception $e) {
            return self::returnError($e);
        }
    }

    public function store(StoreContactRequest $request): JsonResponse
    {
        try {
            $user = userObject();
            $data = $this->createContactService->execute($user->id, $request->validated());

            return self::successResponse($data, 'Contato criado com sucesso.');
        } catch (\Exception $e) {
            return self::returnError($e);
        }
    }

    public function update(UpdateContactRequest $request): JsonResponse
    {
        try {
            $user = userObject();
            $this->updateContactsService->execute($user->id, $request->validated('contacts'));

            return self::successResponse(null, 'Contatos atualizados com sucesso.');
        } catch (\Exception $e) {
            return self::returnError($e);
        }
    }

    public function destroy(string $id): JsonResponse
    {
        try {
            $user = userObject();
            $this->deleteContactService->execute($user->id, $id);

            return self::successResponse(null, 'Contato removido com sucesso.');
        } catch (\Exception $e) {
            return self::returnError($e);
        }
    }
}
