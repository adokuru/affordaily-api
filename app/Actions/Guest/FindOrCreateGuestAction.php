<?php

namespace App\Actions\Guest;

use App\Actions\BaseAction;
use App\Models\Guest;

class FindOrCreateGuestAction extends BaseAction
{
    /**
     * Find existing guest by phone or create new one.
     *
     * @param string $phone
     * @param string $name
     * @param string|null $idPhotoPath
     * @param string|null $email
     * @return Guest
     */
    public function execute(string $phone, string $name, ?string $idPhotoPath = null, ?string $email = null): Guest
    {
        // First, try to find existing guest by phone
        $guest = Guest::byPhone($phone)->first();

        if ($guest) {
            // Update guest information if provided
            $updateData = [];
            if ($name && $name !== $guest->name) {
                $updateData['name'] = $name;
            }
            if ($idPhotoPath && $idPhotoPath !== $guest->id_photo_path) {
                $updateData['id_photo_path'] = $idPhotoPath;
            }
            if ($email && $email !== $guest->email) {
                $updateData['email'] = $email;
            }
            
            if (!empty($updateData)) {
                $guest->update($updateData);
            }

            return $guest;
        }

        // Create new guest
        return Guest::create([
            'name' => $name,
            'phone' => $phone,
            'email' => $email,
            'id_photo_path' => $idPhotoPath,
        ]);
    }
}
