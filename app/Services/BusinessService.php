<?php

namespace App\Services;

use App\Models\Business;
use App\Models\BusinessPreference;
use Exception;
use Illuminate\Support\Str;

class BusinessService
{
    public function submitBusiness(string $businessId, array $data)
    {
        $business = Business::find($businessId);

        if ($business) {
            $business->update($data);
        } else {
            // Logic differs slightly from Python here. Python creates if not consistent or maybe it updates if exists, creates if not.
            // But wait, the argument business_id comes from user_data.business_id usually.
            // If user has no business_id or it's invalid, Python tries to update empty? 
            // Actually Python: await service.submit_business(data=data, business_id=user_data.business_id)
            // If user_data.business_id is null? The where clauses would fail or return null.
            // If it returns null, Python creates new Business using data.
            // But how does it link back to User? Python code in `submit_business` doesn't seem to link it back to user explicitly in that method. 
            // It just creates `Business`. Maybe relies on user calling update later? 
            // OR maybe `user_data.business_id` is populated.
            
            // Let's implement CREATE if not found functionality.
            $data['id'] = $businessId ?? (string) Str::uuid();
            $business = Business::create($data);
            
            // Create default preferences
            BusinessPreference::create([
                'business_id' => $business->id,
                'sms_id' => substr($business->business_name, 0, 10)
            ]);
            
            // If the user didn't have a business_id, we should probably update the user? 
            // The Python code doesn't show updating the user. 
            
        }

        return $business;
    }

    public function getBusinessById($businessId)
    {
        return Business::with('preferences')->find($businessId);
    }

    public function updatePreferences(string $businessId, array $data)
    {
        $preference = BusinessPreference::where('business_id', $businessId)->first();

        if ($preference) {
            $preference->update($data);
        } else {
            $data['business_id'] = $businessId;
            $preference = BusinessPreference::create($data);
        }

        return $preference;
    }
}
