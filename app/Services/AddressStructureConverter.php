<?php
namespace App\Services;

class AddressStructureConverter
{
    public function convert($addresses)
    {
        $transformedAddresses = [];
        if (!$addresses) {
            return $addresses;
        }
        
        foreach ($addresses as $email => $name) {
            if (is_numeric($email) && isset($name['address'])) {
                $transformedAddresses[] = $name;
                continue;
            }
            $transformedAddresses[] = [
                'name' => $name,
                'address' => $email
            ];
        }

        return $transformedAddresses;
    }
}
