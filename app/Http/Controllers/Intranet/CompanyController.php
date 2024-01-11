<?php

namespace App\Http\Controllers\Intranet;
use Illuminate\Validation\ValidationException;
use App\Rules\NoSpacesOrDots;
use App\Models\Module;
use App\Models\Company;
use Illuminate\Http\Request;
use App\Traits\HttpResponses;
use App\Http\Controllers\Controller;

class CompanyController extends Controller
{


    use HttpResponses;



    public function index(Request $request)
    {
    }
    public function create(Request $request)
    {

        try {
            $fields = $request->validate([
                'name' => ['required', 'max:255', 'unique:companies', new NoSpacesOrDots],
                'color' => 'required|string',
                'is_active' => 'required|boolean',
                'logo' => 'file',
            ]);

            // Handle file upload for logo if provided
            $logoPath = '';
            if ($request->hasFile('logo')) {
                $logo = $request->file('logo');

                // Check if the uploaded file is an image (PNG or JPEG)
                if ($logo->isValid() && in_array($logo->getClientOriginalExtension(), ['png', 'jpg', 'jpeg'], true) && $logo->getClientMimeType() && in_array($logo->getClientMimeType(), ['image/png', 'image/jpeg'], true)) {
                    $extension = $logo->getClientOriginalExtension(); // Get the file extension
                    $name = $request->input('name'); // Get the 'name' field from the request

                    // Rename and store the uploaded file with the provided name and extension
                    $logoPath = $logo->storeAs('companyLogo', $name . '.' . $extension, 'public');
                } else {
                    // Handle invalid file format (not an image or unsupported format)
                    return response()->json(['error' => 'Invalid file format. Only PNG and JPEG images are allowed.'], 422);
                }
            }
            // Create a new company
            $company = Company::create([
                'name' => $fields['name'],
                'color' => $fields['color'],
                'is_active' => $fields['is_active'],
                'logo' => $logoPath, // Save the logo path in the database
            ]);

            // Retrieve the created company and return it in JSON
            $createdCompany = Company::find($company->id);

            return response()->json(['company' => $createdCompany], 201); // Return the created company in JSON with status code 201 (created)
        } catch (ValidationException $e) {
            // Validation failed; return an error response with validation messages
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            // Handle other exceptions (e.g., database errors) and return an error response
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function update($id, Request $request)
    {
        $company = Company::find($id);

        // try {
        //     $fields = $request->validate([
        //         'name'  =>  'required|max:255|unique:companies,name,' . $id,
        //         'color' => 'string',
        //         'logo' => 'string',
        //     ]);
        // } catch (ValidationException $e) {
        //     // Validation failed; return an error response with validation messages

        //     return $this->error($e->errors(), '', 422);

        // }

        // $company->update($fields);

        if (isset($request['modules'])) {
            $modulesToAttach = [];
            foreach ($request['modules'] as $key => $module) {

                if ($company->modules()->where('modules.id', $module)->exists() || !Module::find($module)) continue;
                array_push($modulesToAttach, $module);
            }

            $company->modules()->attach($modulesToAttach);
        }

        return  $this->success(Company::find($id), 'Company updated correctly');
    }
}
