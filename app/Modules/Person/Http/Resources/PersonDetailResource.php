<?php

namespace App\Modules\Person\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Request;
use App\Modules\Group\Http\Resources\MembershipResource;
use App\Modules\Person\Models\Person;

class PersonDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array
     */
    public function toArray(Request $request)
    {
        $data = parent::toArray($request);
        $user = $request->user();


       if (
            (is_null($user->id) && is_null($this->user->id)) || 
            //($user->id === $this->user->id) || 
            $user->hasAnyRole(['admin', 'super-admin'])
        ) {
        //    // Proceed with your logic when either the IDs match or the user has the required roles
            foreach (Person::$contact_details_private_fields as $field) {
              $data[$field] = $this->$field;
       }
    }

       //Current production
       //if ($user->id === $this->user->id || $user->hasAnyRole(['admin', 'super-admin'])) {
      //  foreach (Person::$contact_details_private_fields as $field) {
        //    $data[$field] = $this->$field;
        //}
        
    

    //solution we discussed today
    //if (is_null($this->user->id) || ($user->id === $this->user->id) || $user->hasAnyRole(['admin', 'super-admin'])) {
        // Your logic here
      //  foreach (Person::$contact_details_private_fields as $field) {
      //      $data[$field] = $this->$field;
   // }
//}

        $data['memberships'] = $this->whenLoaded('memberships', MembershipResource::collection($this->memberships));
        return $data;
    }
}
