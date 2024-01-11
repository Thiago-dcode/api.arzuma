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
        try {
            $company = Company::findOrFail($id);

            $request->validate([
                'name' => 'max:255|unique:companies,name,' . $company->id,
                'color' => 'string',
                'is_active' => 'boolean',
                'logo' => 'file', // Allow updating the logo
            ]);

            // Update the company attributes
            $company->fill([
                'name' => $request->input('name', $company->name), // Use the current value if not present in the request
                'color' => $request->input('color', $company->color), // Use the current value if not present in the request
                'is_active' => $request->input('is_active', $company->is_active), // Use the current value if not present in the request
            ]);

            // Update the logo if provided
            if ($request->hasFile('logo')) {
                $logo = $request->file('logo');
                $logoPath = $logo->storeAs('companyLogo', $company->name . '.' . $logo->getClientOriginalExtension(), 'public');
                $company->logo = $logoPath;
            }

            // Save the changes
            $company->save();

            // Attach modules
            if ($request->has('modules') && is_array($request->modules)) {
               
                $modulesToAttach = collect($request->modules)->filter(function ($module) use ($company) {
                   
                    return !$company->modules->contains($module) && Module::find($module);
                });
              
                $company->modules()->attach($modulesToAttach->all());
            }

            // Return the updated company in JSON
            return response()->json(['company' => Company::findOrFail($id)], 200);
        } catch (ValidationException $e) {
            // Handle validation errors
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            // Handle other exceptions (e.g., database errors)
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
}
