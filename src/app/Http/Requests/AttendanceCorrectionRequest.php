<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AttendanceCorrectionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'clock_in'  => ['required', 'date_format:H:i'],
            'clock_out' => ['required', 'date_format:H:i'],
            'breaks.*.break_in'  => ['nullable', 'date_format:H:i'],
            'breaks.*.break_out' => ['nullable', 'date_format:H:i'],
            'description' => ['required'],
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $clockIn = $this->input('clock_in');
            $clockOut = $this->input('clock_out');

            if ($clockIn && $clockOut && strtotime($clockOut) <= strtotime($clockIn)) {
                $validator->errors()->add('clock_out', '出勤時間もしくは退勤時間が不適切な値です');
            }

            $breaks = $this->input('breaks', []);
            foreach ($breaks as $key => $break) {
                if (isset($break['break_in']) && $break['break_in']) {
                    if (strtotime($break['break_in']) < strtotime($clockIn)) {
                        $validator->errors()->add("breaks.{$key}.break_in", '休憩時間が不適切な値です');
                    }
                    if (strtotime($break['break_in']) > strtotime($clockOut)) {
                        $validator->errors()->add("breaks.{$key}.break_in", '休憩時間が不適切な値です');
                    }
                }

                if (isset($break['break_out']) && $break['break_out']) {
                    if (strtotime($break['break_out']) > strtotime($clockOut)) {
                        $validator->errors()->add("breaks.{$key}.break_out", '休憩時間もしくは退勤時間が不適切な値です');
                    }
                }
            }
        });
    }

    /**
     * Get the validation messages.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'clock_in.required' => '出勤時間を入力してください',
            'clock_in.date_format' => '出勤時間の形式が正しくありません',
            'clock_out.required' => '退勤時間を入力してください',
            'clock_out.date_format' => '退勤時間の形式が正しくありません',
            'clock_out.after' => '出勤時間もしくは退勤時間が不適切な値です',
            'breaks.*.break_in.date_format' => '休憩開始時間の形式が正しくありません',
            'breaks.*.break_in.before' => '休憩時間が不適切な値です',
            'breaks.*.break_in.after' => '休憩時間が不適切な値です',
            'breaks.*.break_out.date_format' => '休憩終了時間の形式が正しくありません',
            'breaks.*.break_out.before' => '休憩時間もしくは退勤時間が不適切な値です',
            'description.required' => '備考を記入してください',
        ];
    }
}
