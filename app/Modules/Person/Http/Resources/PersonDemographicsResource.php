<?php

namespace App\Modules\Person\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
//use App\Traits\EncryptsData;

class PersonDemographicsResource extends JsonResource
{
   // use EncryptsData;

    public function toArray($request)
    {
        // Manually include fields based on user role
   //     return [
    
        $data = [
            'birth_country' => $this->birth_country,
            'birth_country_other' => $this->birth_country_other,
            'birth_country_opt_out' => $this->birth_country_opt_out,
            'uuid' => $this->uuid,
            'reside_country' => $this->reside_country,
            'reside_country_other' => $this->reside_country_other,
            'reside_country_opt_out' => $this->reside_country_opt_out,
            'reside_state' => $this->reside_state,
            'reside_state_opt_out' => $this->reside_state_opt_out,
            'ethnicities' => $this->ethnicities,
            'ethnicities_other' => $this->ethnicities_other,
            'ethnicities_opt_out' => $this->ethnicities_opt_out,
            'birth_year' => $this->birth_year,
            'birth_year_opt_out' => $this->birth_year_opt_out,
            'identities' => $this->identities,
            'identity_opt_out' => $this->identity_opt_out,
            'gender_identities' => $this->gender_identities,
            'gender_identities_other' => $this->gender_identities_other,
            'gender_identities_opt_out'=> $this->gender_identities_opt_out,
            'support' => $this->support,
            'grant_detail' => $this->grant_detail,
            'support_opt_out' => $this->support_opt_out,
            'support_other' => $this->support_other,
            'disadvantaged' => $this->disadvantaged,
            'disadvantaged_other' => $this->disadvantaged_other,
            'disadvantaged_opt_out' => $this->disadvantaged_opt_out,         
            'occupations' => $this->occupations,
            'occupations_other' => $this->occupations_other,
            'occupations_opt_out' => $this->occupations_opt_out,
            'specialty' => $this->specialty,
                         
        ];

            
        return $data;
    }
}
