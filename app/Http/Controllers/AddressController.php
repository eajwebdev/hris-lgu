<?php

namespace App\Http\Controllers;

use App\Models\Region;
use App\Models\Province;
use App\Models\City;
use App\Models\Barangay;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    public function getRegions()
    {
        $regions = Region::all();
        return response()->json($regions);
    }

    public function getProvinces($regionId)
    {
        $provinces = Province::where('region_id', $regionId)->get();
        return response()->json($provinces);
    }
    
    public function getCities($provinceId)
    {
        $cities = City::where('province_id', $provinceId)->get();
        return response()->json($cities);
    }
    
    public function getBarangays($cityId)
    {
        $barangays = Barangay::where('city_id', $cityId)->get();
        return response()->json($barangays);
    }
    
}

