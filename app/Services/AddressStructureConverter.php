<?php
namespace App\Services;

use Symfony\Component\Mime\Address;

class AddressStructureConverter
{
    public function convert($addresses)
    {
        $transformedAddresses = [];
        if (!$addresses) {
            return $addresses;
        }
        
        foreach ($addresses as $email => $name) {

            if ($this->valueIsSymfonyAddress($name)) {
                $transformedAddresses[] = $this->convertSymfonyAddress($name);
                continue;
            }
            
            $transformedAddresses[] = [
                'name' => $name,
                'address' => $email
            ];
        }

        return $transformedAddresses;
    }

    private function convertSymfonyAddress(Address $value): array
    {
        return [
            'name' => $value->getName(),
            'address' => $value->getAddress()
        ];
    }

    private function valueIsSymfonyAddress($value): bool
    {
        return is_object($value) && get_class($value) == Address::class;
    }
}
