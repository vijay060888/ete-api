<?php

namespace App\Http\Controllers\Subscription;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\Package;
use App\Models\PackageFeature;
use App\Models\PackageFeatureMap;
use Illuminate\Http\Request;
use App\Helpers\LogActivity;

class PackageController extends Controller
{
     /**
     * Display a listing of the package.
     */
     /**
     * @OA\Get(
     *     path="/api/packages",
     *     summary="Fetch all Packages",
     *     tags={"Subscription"},
     *    @OA\Response(
     *      response=200,
     *       description="Success",
     *      @OA\MediaType(
     *           mediaType="application/json",
     *      )
     *   ),
     *   @OA\Response(
     *      response=401,
     *       description="Unauthorized "
     *   ),
     *    @OA\Response(
     *      response=404,
     *       description="Error / Data not found "
     *   ),
     *      security={{ "apiAuth": {} }}
     * )
     */
    public function index()
    {
        try{           
            $packages = Package::where('status', true)->get();
            $features = PackageFeature::where('status', true)->get();

            $packageArray = $packages->map(function ($package) use ($features) {
                
                $featureArray = $features->map(function ($feature) use ($package) {
                    return [
                        'id' =>  $feature->id,
                        'name' => $feature->name,
                        'access' => $this->getFeatureStatus($package->id, $feature->id)
                    ];
                });    

                return [
                    'id' =>  $package->id,
                    'name' => $package->name,
                    'price' => $package->price,
                    'whatsapp' => $package->whatsapp,
                    'sms' =>  $package->sms,
                    'ads' =>  $package->ads,
                    'app_notifications' =>  $package->app_notifications,
                    'features' => $featureArray
                ];
            });

            return response()->json(['status' => 'success','message' => 'Package List','result'=>$packageArray],200);
        }catch (\Exception $e){
            LogActivity::addToLog($e->getMessage(),$e->getTraceAsString());
            return response()->json(['status' => 'error','message'=>$e->getMessage()],404);
        }    
    }


    public function getFeatureStatus($package, $feature){
        return PackageFeatureMap::where('package_id', $package)->where('package_feature_id', $feature)->exists();
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Subscription $subscription)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Subscription $subscription)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Subscription $subscription)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Subscription $subscription)
    {
        //
    }
}
