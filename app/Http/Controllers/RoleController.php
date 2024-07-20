<?php

namespace App\Http\Controllers;

use App\Helpers\LogActivity;
use Illuminate\Http\Request;
use App\Models\Role;
use Auth;

class RoleController extends Controller
{
    function __construct()
    {
        // $this->middleware('role:Admin', ['only' => ['index']]);
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
  

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
     /**
     * @OA\Get(
     *     path="/api/roles",
     *     summary="Fetch all roles",
     *     tags={"Master - Roles"},
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
            $data=Role::all();
            return response()->json(['status' => 'success','message' => 'List of roles','result'=>$data],200);
        }catch (\Exception $e) {
            LogActivity::addToLog($e->getMessage(),$e->getTraceAsString());
            return response()->json(['status' => 'error','message'=>"Server Error"],404);
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    
     /**
     * @OA\Post(
     *     path="/api/roles",
     *     summary="Add new role",
     *     tags={"Master - Roles"},
     *     @OA\RequestBody(
     *      required=true,
     *      description="Enter details",
     *     @OA\JsonContent(
     *         required={"name"},
     *         @OA\Property(property="name", type="string", example="Sr Manager"),
     *         
     *      ),
     *   ),
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\MediaType(
     *           mediaType="application/json",
     *         )
     *   ),
     *   @OA\Response(
     *      response=401,
     *       description="Unauthorized "
     *   ),
     *    @OA\Response(
     *      response=404,
     *       description="Error / Data not found "
     *   ),
     *   security={{ "apiAuth": {} }}
     *)
     **/
    public function store(Request $request)
    {
         try{
            if(Role::where('name',$request->name)->exists()){
                return response()->json(['status' => 'error','message' => 'Role already exists'],404);
            }
            $input=$request->all();
            $input['guard_name']=config('auth.defaults.guard');
            $data=Role::firstOrCreate($input);
            return response()->json(['status' => 'success','message' => 'Role created successfully','result'=>$data],200);
        }catch (\Exception $e) {
            LogActivity::addToLog($e->getMessage(),$e->getTraceAsString());
            return response()->json(['status' => 'error','message'=>"Server Error"],404);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
     /**
     * @OA\Get(
     *     path="/api/roles/{id}",
     *     summary="Fetch role by id",
     *     tags={"Master - Roles"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *         )
     *     ),
     *     @OA\Response(
     *       response="400",
     *       description="Bad Request",
     *   ),
     *   @OA\Response(
     *       response="401",
     *       description="Data not found",
     *   ),
     *  security={{ "apiAuth": {} }}
     * )
     */
    public function show($id)
    {
        try{
            $data=Role::find($id);
            return response()->json(['status' => 'success','message' => 'Role details','result'=>$data],200);
        }catch (\Exception $e) {
            LogActivity::addToLog($e->getMessage(),$e->getTraceAsString());
            return response()->json(['status' => 'error','message'=>"Server Error"],404);

        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
  /**
     *   @OA\Put(
     *   path="/api/roles/{id}",
     *     summary="Update role by id",
     *     tags={"Master - Roles"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *     ),
     *     @OA\RequestBody(
     *      required=true,
     *      description="Enter details",
     *     @OA\JsonContent(
     *         required={"name","roleType"},
     *         @OA\Property(property="name", type="string", example="Sr Manager"),
     *         @OA\Property(property="roleType", type="string", example="Manager"),
     *         
     *      ),
     *   ),
     *   @OA\Response(
     *      response=200,
     *       description="Success",
     *      @OA\MediaType(
     *           mediaType="application/json",
     *      )
     *   ),
     *   @OA\Response(
     *      response=401,
     *       description="Error while updating "
     *   ),
     *    security={{ "apiAuth": {} }}
     *)
     **/
    public function update(Request $request, $id)
    {
        try{
            $input=$request->all();
            //$input['updatedBy']=Auth::user()->id;
            $role=Role::find($id);
            if($role!=null){
            $role->update($input);
            return response()->json(['status' => 'success','message' => 'Role updated successfully','result'=>$role],200);
            }else{
                return response()->json(['status' => 'success','message' => 'Role not found'],404);
            }
        }catch (\Exception $e) {
            LogActivity::addToLog($e->getMessage(),$e->getTraceAsString());
            return response()->json(['status' => 'error','message'=>"Server Error"],404);

        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
     /**
     * @OA\Delete(
     *     path="/api/roles/{id}",
     *     summary="Delete Role Type by id",
     *     tags={"Master - Roles"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *     ),
     *   @OA\Response(
     *      response=200,
     *       description="Success",
     *      @OA\MediaType(
     *           mediaType="application/json",
     *      )
     *   ),
     *   @OA\Response(
     *       response="400",
     *       description="Bad Request",
     *   ),
     *   @OA\Response(
     *       response="401",
     *       description="Data not found",
     *   ),
     *  security={{ "apiAuth": {} }}
     * )
     */
    public function destroy($id)
    {
        try{
            $role=Role::find($id);
            if($role!=null){
                Role::find($id)->delete();
                return response()->json(['status' => 'success','message' => 'Role deleted successfully'],200);
            }else{
                    return response()->json(['status' => 'success','message' => 'Role not found'],404);
                }
    
        }catch (\Exception $e) {
            LogActivity::addToLog($e->getMessage(),$e->getTraceAsString());
            return response()->json(['status' => 'error','message'=>"Server Error"],404);

        }
    }
}
