<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Carbon\Carbon;

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
            'clock_out' => ['required', 'date_format:H:i', 'after:clock_in'],
            'breaks.*.break_in'  => ['nullable', 'date_format:H:i', 'before:clock_out', 'after:clock_in'],
            'breaks.*.break_out' => ['nullable', 'date_format:H:i', 'before:clock_out'],
            'description' => ['required'],
        ];
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
            'breaks.*.break_in.after' => '休憩時間もしくは退勤時間が不適切な値です',
            'breaks.*.break_out.date_format' => '休憩終了時間の形式が正しくありません',
            'breaks.*.break_out.before' => '休憩時間もしくは退勤時間が不適切な値です',
            'description.required' => '備考を記入してください',
        ];
    }
}
