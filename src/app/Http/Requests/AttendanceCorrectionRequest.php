<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Carbon\Carbon;

class AttendanceCorrectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'clock_in'                => ['nullable', 'regex:/^([01]\d|2[0-3]):[0-5]\d$/'],
            'clock_out'               => ['nullable', 'regex:/^([01]\d|2[0-3]):[0-5]\d$/'],
            'rest_times.*.rest_start' => ['nullable', 'regex:/^([01]\d|2[0-3]):[0-5]\d$/'],
            'rest_times.*.rest_end'   => ['nullable', 'regex:/^([01]\d|2[0-3]):[0-5]\d$/'],
            'remarks'                 => ['required', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {

            $clockIn  = $this->parseTime($this->clock_in);
            $clockOut = $this->parseTime($this->clock_out);

            // 出勤・退勤の前後関係チェック（両方パース成功時のみ）
            $clockTimeInvalid = $clockIn && $clockOut && !$clockIn->lt($clockOut);
            if ($clockTimeInvalid) {
                $validator->errors()->add('clock_in',  '出勤時間もしくは退勤時間が不適切な値です');
                $validator->errors()->add('clock_out', '出勤時間もしくは退勤時間が不適切な値です');
            }

            // 休憩の前後関係チェック
            foreach ($this->rest_times ?? [] as $index => $rest) {
                $restStart = $this->parseTime($rest['rest_start'] ?? null);
                $restEnd   = $this->parseTime($rest['rest_end']   ?? null);

                // 休憩開始が出勤前 or 退勤後
                if ($restStart && $clockIn && $restStart->lt($clockIn)) {
                    $validator->errors()->add("rest_times.{$index}.rest_start", '休憩時間が不適切な値です');
                    continue;
                }
                if ($restStart && $clockOut && !$clockTimeInvalid && $restStart->gt($clockOut)) {
                    $validator->errors()->add("rest_times.{$index}.rest_start", '休憩時間が不適切な値です');
                    continue;
                }

                // 休憩終了が退勤後（出退勤が正常な場合のみチェック）
                if (!$clockTimeInvalid && $restEnd && $clockOut && $restEnd->gt($clockOut)) {
                    $validator->errors()->add("rest_times.{$index}.rest_end", '休憩時間もしくは退勤時間が不適切な値です');
                }
            }

            // 休憩同士の重複チェック
            $restPeriods = [];
            foreach ($this->rest_times ?? [] as $index => $rest) {
                $restStart = $this->parseTime($rest['rest_start'] ?? null);
                $restEnd   = $this->parseTime($rest['rest_end']   ?? null);

                if (!$restStart || !$restEnd) {
                    continue;
                }

                foreach ($restPeriods as $i => $period) {
                    if ($restStart->lt($period['end']) && $restEnd->gt($period['start'])) {
                        $validator->errors()->add("rest_times.{$index}.rest_start", '休憩時間が重複しています');
                        break;
                    }
                }

                $restPeriods[] = ['start' => $restStart, 'end' => $restEnd];
            }
        });
    }

    /**
     * 時刻文字列をCarbonに変換する（失敗時はnullを返す）
     */
    private function parseTime(?string $time): ?Carbon
    {
        if (empty($time)) {
            return null;
        }
        try {
            // H:i形式かつ00:00〜23:59の範囲チェック
            [$h, $m] = explode(':', $time);
            if ((int)$h > 23 || (int)$m > 59) {
                return null;
            }
            $parsed = Carbon::createFromFormat('H:i', $time);
            return $parsed ?: null;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function messages(): array
    {
        return [
            'clock_in.regex'                => '出勤時間もしくは退勤時間が不適切な値です',
            'clock_out.regex'               => '出勤時間もしくは退勤時間が不適切な値です',
            'rest_times.*.rest_start.regex' => '休憩時間が不適切な値です',
            'rest_times.*.rest_end.regex'   => '休憩時間が不適切な値です',
            'remarks.required'              => '備考を記入してください',
        ];
    }

    public function attributes(): array
    {
        return [
            'clock_in'                => '出勤時間',
            'clock_out'               => '退勤時間',
            'rest_times.*.rest_start' => '休憩開始時間',
            'rest_times.*.rest_end'   => '休憩終了時間',
            'remarks'                 => '備考',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            back()->withErrors($validator)->withInput()
        );
    }
}