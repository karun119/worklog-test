<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminCorrectionRequest extends FormRequest
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
            'clock_in'   => ['required'],
            'clock_out'  => ['required'],
            'break_in'   => ['array'],
            'break_out'  => ['array'],
            'break_in.*'  => ['nullable'],
            'break_out.*' => ['nullable'],
            'comment'    => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'clock_in.required'  => '出勤時間を入力してください',
            'clock_out.required' => '退勤時間を入力してください',
            'comment.required'   => '備考を記入してください',
            'comment.max'    => '備考は255文字以内で入力してください',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $clockIn  = $this->input('clock_in');
            $clockOut = $this->input('clock_out');
            $breakIns = $this->input('break_in', []);
            $breakOuts = $this->input('break_out', []);
            if ($clockIn && $clockOut && strtotime($clockIn) >= strtotime($clockOut)) {
                $validator->errors()->add('clock_out', '出勤時間もしくは退勤時間が不適切な値です');
            }

            foreach ($breakIns as $i => $breakIn) {
                $breakOut = $breakOuts[$i] ?? null;
                if (($breakIn && !$breakOut) || (!$breakIn && $breakOut)) {
                    $validator->errors()->add(
                        "break_in.$i",
                        '休憩開始と休憩終了はセットで入力してください'
                    );
                }
                if (
                    ($breakIn && $clockIn && strtotime($breakIn) < strtotime($clockIn)) ||
                    ($breakIn && $clockOut && strtotime($breakIn) > strtotime($clockOut))
                ) {
                    $validator->errors()->add("break_in.$i", '休憩時間が不適切な値です');
                }
                if ($breakOut && $clockOut && strtotime($breakOut) > strtotime($clockOut)) {
                    $validator->errors()->add("break_out.$i", '休憩時間もしくは退勤時間が不適切な値です');
                }
                if ($breakOut && $clockIn && strtotime($breakOut) < strtotime($clockIn)) {
                    $validator->errors()->add(
                        "break_out.$i",
                        '休憩時間もしくは退勤時間が不適切な値です'
                    );
                }
                if ($breakIn && $breakOut && strtotime($breakOut) < strtotime($breakIn)) {
                    $validator->errors()->add("break_out.$i", '休憩時間もしくは退勤時間が不適切な値です');
                }
            }
        });
    }

    protected function prepareForValidation()
    {
        $timeFields = ['clock_in', 'clock_out', 'break_in', 'break_out'];
        foreach ($timeFields as $field) {
            if ($this->has($field)) {
                $value = $this->input($field);
                if (is_array($value)) {
                    $converted = array_map(function ($v) {
                        return $this->normalizeTime($v);
                    }, $value);
                    $this->merge([$field => $converted]);
                } else {
                    $this->merge([$field => $this->normalizeTime($value)]);
                }
            }
        }
        foreach (['break_in', 'break_out'] as $field) {
            if (!$this->has($field)) {
                $this->merge([$field => []]);
            }
        }
    }

    private function normalizeTime(?string $time): ?string
    {
        if (!$time) return null;
        $time = mb_convert_kana($time, 'a');
        if (preg_match('/^\d{1,4}$/', $time)) {
            $time = str_pad($time, 4, '0', STR_PAD_LEFT);
            $hour = substr($time, 0, 2);
            $minute = substr($time, 2, 2);
            return "{$hour}:{$minute}:00";
        }
        if (preg_match('/^(\d{1,2}):(\d{1,2})$/', $time, $matches)) {
            $hour = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
            $minute = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
            return "{$hour}:{$minute}:00";
        }

        return $time;
    }
}
