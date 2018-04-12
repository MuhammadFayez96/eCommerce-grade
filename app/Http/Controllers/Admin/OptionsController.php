<?php

namespace App\Http\Controllers\Admin;

use App\Models\Language;
use App\Models\Option;
use App\Models\OptionValues;
use App\Models\OptionValuesTranslation;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

/**
 * Class OptionsController
 * @package App\Http\Controllers\Admin
 */
class OptionsController extends Controller
{
    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function getIndex()
    {
        //get all option in db
        $options = Option::all();

        // append translated option to all options
        foreach ($options as $option) {

            // get option details
            $option_translated = $option->translate();

            // add the translated option as a key => value to main option object
            // key is option_translated and the value id $option_translated
            $option->option_translated = $option_translated;

            //find optionValue by option_id
            $optionValues = $option->optionValues;

            foreach ($optionValues as $option_value) {

              $option_value->trans = $option_value->translate();

            }
            // add the translated option as a key => value to main option object
            // key is option_value_translated and the value id $option_value
            $option->option_values = $optionValues;
        }

        return view('admin.pages.options.index', compact('options'));
    }


    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function getCreateNewOption()
    {
        return view('admin.pages.options.add-option');
    }

    /**
     * @param Request $request
     * @return array
     */
    public function createNewOption(Request $request)
    {
        // validation options
        $validation_options = [
            'option_name_en' => 'required',
            'option_name_ar' => 'required',
            'option_value_en' => 'required',
            'option_value_ar' => 'required',
        ];

        $validation = validator($request->all(), $validation_options);

        // if validation failed, return false response
        if ($validation->fails()) {
            return [
                'status' => 'error',
                'title' => $validation->getMessageBag(),
                'text' => 'validation error'
            ];
        }

        // choose one language to be the default one, let's make EN is the default
        // store master option
        // store the option in en
        $en_id = Language::where('lang_code', 'en')->first()->id;

        // instantiate App\Model\Option - master
        $option = new Option;

        // check saving success
        if (!$option->save()) {
            return [
                'status' => 'error',
                'title' => 'Error',
                'text' => 'something went wrong, please try again!'
            ];
        }

        $option_en = null;
        if ($request->option_name_en) {
            // store en version
            $option_en = $option->optionTrans()->create([
                'option' => $request->option_name_en,
                'lang_id' => $en_id,
            ]);
        }

        // check saving status
        if (!$option_en) {
            return [
                'status' => 'error',
                'title' => 'Error',
                'text' => 'something went wrong while saving EN, please try again!'
            ];
        }

        $option_ar = null;
        // store ar version
        // because it is not required, we check if there is ar in request, then save it, else {no problem, not required}
        if ($request->option_name_ar) {

            $ar_id = Language::where('lang_code', 'ar')->first()->id;

            $option_ar = $option->optionTrans()->create([
                'option' => $request->option_name_ar,
                'lang_id' => $ar_id,
            ]);

            // check save status
            if (!$option_ar) {
                return [
                    'status' => 'error',
                    'title' => 'Error',
                    'text' => 'something went wrong while saving AR, please try again!'
                ];
            }
        }

        if ($option->save()) {

            //get option i
            //get option i2
            $option_id = $option->id;

            //find options by id
            $Option = Option::find($option_id);

            //check if no Option
            if (!$Option) {
                return [
                    'status' => 'error',
                    'title' => 'Error',
                    'text' => 'There is no option with such id!'
                ];
            }


            $ar_id = Language::where('lang_code', 'ar')->first()->id;

            //define $optionValues_en is null
            $option_values_en = null;

            //define $optionValues_ar is null
            $option_values_ar = null;

            //store multi value in db
            foreach ($request->option_value_en as $key => $v) {

                //store option id in database
                $option_values = OptionValues::forceCreate([
                    'option_id' => $option_id,
                ]);

                // store en version
                $option_values_en = $option_values->optionValuesTrans()->create([
                    'value' => $request->option_value_en[$key],
                    'lang_id' => $en_id,
                ]);

                $option_values_ar=$option_values->optionValuesTrans()->create([
                  'value' =>$request->option_value_ar[$key],
                  'lang_id' => $ar_id,
                ]);

            }

            // check saving status
            if (!$option_values_en) {
                return [
                    'status' => 'Error',
                    'title' => 'error',
                    'text' => 'something went wrong while saving EN, please try again!'
                ];
            }

            // check save status
            if (!$option_values_ar) {
                return [
                    'status' => 'error',
                    'title' => 'Error',
                    'text' => 'something went wrong while saving AR, please try again!'
                ];
            }
            // check saving success

            return [
                'status' => 'success',
                'title' => 'success',
                'text' => 'Data Inserted Successfully Done!',
            ];
        }
    }

    /**
     * @param $id
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function getUpdateOption($id)
    {
        //find option by id
        $option = Option::find($id);

        // $option_translated=$option->optionTrans;

        //get option details when lang en
        $option_translated = $option->translate('en');

        // add the translated option as a key => value to main option object
        // key is option_translated and the value id $option_translated
        $option->option_en_translated = $option_translated;

        //get option details when lang ar
        $option_ar_translated = $option->translate('ar');

        // add the translated option as a key => value to main option object
        // key is option_translated and the value id $option_translated
        $option->option_ar_translated = $option_ar_translated;

        //get option value details
        // $optionValue = OptionValues::where('option_id', $id)->first();
        $option_values = $option->optionValues;

        foreach ($option_values as $option_value) {

            $option_value->en = $option_value->translate('en');
            $option_value->ar = $option_value->translate('ar');
        }

        return view('admin.pages.options.edit-option', compact('option', 'option_values'));
    }

    /**
     * @param $id
     * @param Request $request
     * @return array
     */
    public function updateOption($id, Request $request)
    {

        // validation options
        $validation_options = [
            'option_name_en' => 'required',
            'option_name_ar' => 'required',
            'option_value_en' => 'required',
            'option_value_ar' => 'required',
          ];
        $validation = validator($request->all(), $validation_options);

        // if validation failed, return false response
        if ($validation->fails()) {
            return [
                'status' => 'error',
                'title' => $validation->getMessageBag(),
                'text' => 'validation error'
            ];
        }
        //search option by id
        $option = Option::find($id);

        //check if no option
        if (!$option) {
            return [
                'status' => 'error',
                'title' => 'Error',
                'text' => 'There is no option with this id!'
            ];
        }

        $en_lang_id = Language::where('lang_code', 'en')->first()->id;
        $ar_lang_id = Language::where('lang_code', 'ar')->first()->id;

        $option->optionTrans()->delete();

        $option->optionTrans()->create([
          'option' => $request->option_name_en,
          'lang_id' => $en_lang_id,
        ]);
        $option->optionTrans()->create([
          'option' => $request->option_name_ar,
          'lang_id' => $ar_lang_id,
        ]);

        //check save success
        if ($option->save()) {

          $option_values = $option->optionValues;

          $option_values_en = $request->option_value_en;
          $option_values_ar = $request->option_value_ar;

          foreach ($option_values_en as $key => $opt_val_en) {

              if ($opt_val_en[1]) {

                  $option_val = OptionValues::find($opt_val_en[1]);
                  $option_val->optionValuesTrans()->delete();
                  $option_val->optionValuesTrans()->create([
                    'value' => $opt_val_en[0],
                    'lang_id' => $en_lang_id
                  ]);

                  $option_val->optionValuesTrans()->create([
                    'value' => $option_values_ar[$key][0],
                    'lang_id' => $ar_lang_id
                  ]);

                  continue;
              }

              $option_val = OptionValues::forceCreate([
                'option_id' => $option->id
              ]);
              $option_val->optionValuesTrans()->create([
                'value' => $opt_val_en[0],
                'lang_id' => $en_lang_id
              ]);

              $option_val->optionValuesTrans()->create([
                'value' => $option_values_ar[$key][0],
                'lang_id' => $ar_lang_id
              ]);
          }

            // check save success
            return [
                'status' => 'success',
                'title' => 'success',
                'text' => 'Data updated successfully done',
            ];
        }
    }

    /**
     * @param $id
     * @return array
     */
    public function deleteOption($id)
    {
        //search option by id
        $option = Option::find($id);

        //find option Value by option_id
        $optionValue = OptionValues::where('option_id', $id)->first();

        // check if no option
        if (!$option) {
            return [
                'status' => 'error',
                'title' => 'Error',
                'text' => 'There is no option with this id!!'
            ];
        }

        //delete data from optionValuesTrans
        $optionValue->optionValuesTrans()->delete();

        //delete data from productOptionValuesDetails
        $optionValue->productOptionValuesDetails()->delete();

        //delete data from optionValues
        $option->optionValues()->delete();

        //delete data from optionTrans
        $option->optionTrans()->delete();

        //delete data from option
        $option->delete();

        //check successfully deleted data
        return [
            'status' => 'success',
            'title' => 'success',
            'text' => 'Data Deleted Successfully!'
        ];
    }


    public function deleteOptionValue($id)
    {
      //search optionValues by id
      $option_value = OptionValues::find($id);

      // check if no option
      if (!$option_value) {
          return [
              'status' => 'error',
              'title' => 'Error',
              'text' => 'There is no option with this id!!'
          ];
      }

      $option_value->optionValuesTrans()->delete();

      $option_value->delete();

      //check successfully deleted data
      return [
          'status' => 'success',
          'title' => 'success',
          'text' => 'Data Deleted Successfully!'
      ];
    }
}
