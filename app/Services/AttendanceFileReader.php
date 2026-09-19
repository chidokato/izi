<?php

namespace App\Services;

use DateTimeImmutable;
use Illuminate\Support\Str;
use InvalidArgumentException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class AttendanceFileReader
{
    public static function weekday(string $date): string
    {
        return ['CN', 'Hai', 'Ba', 'Tư', 'Năm', 'Sáu', 'Bảy'][(int) (new DateTimeImmutable($date))->format('w')];
    }

    private function key($v): string
    {
        return preg_replace('/[^a-z0-9]/', '', strtolower(Str::ascii(trim((string) $v))));
    }

    public function read(string $path, string $format): array
    {
        $zip = new \ZipArchive();
        if ($zip->open($path) === true) {
            try {
                $size = 0;
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $size += $zip->statIndex($i)['size'];
                }
                if ($zip->numFiles > 5000 || $size > 50 * 1024 * 1024) {
                    throw new InvalidArgumentException('File Excel sau giải nén quá lớn (tối đa 50 MB).');
                }
            } finally {
                $zip->close();
            }
        }
        $reader = IOFactory::createReader(IOFactory::identify($path, ['Xlsx', 'Xls']));
        $info = $reader->listWorksheetInfo($path);
        if (count($info) > 10) {
            throw new InvalidArgumentException('Tối đa 10 sheet mỗi file.');
        }
        foreach ($info as $s) {
            if ($s['totalRows'] > 2100 || $s['totalColumns'] > 100) {
                throw new InvalidArgumentException('Mỗi sheet tối đa 2.100 dòng và 100 cột.');
            }
        }
        $book = $reader->load($path);
        try {
            $aliases = ['manvien' => 'code', 'manv' => 'code', 'manhanvien' => 'code', 'tennhanvien' => 'name', 'phongban' => 'department', 'ngay' => 'date', 'thu' => 'weekday', 'vao' => 'in', 'giovao' => 'in', 'ra' => 'out', 'giora' => 'out'];
            $matches = [];
            foreach ($book->getWorksheetIterator() as $sheet) {
                for ($line = 1; $line <= min(30, $sheet->getHighestDataRow()); $line++) {
                    $map = [];
                    foreach ($sheet->rangeToArray('A'.$line.':'.$sheet->getHighestDataColumn().$line, null, false, false)[0] as $col => $v) {
                        if (isset($aliases[$this->key($v)])) {
                            $map[$aliases[$this->key($v)]] = $col + 1;
                        }
                    }
                    if (count($map) === 7) {
                        $matches[] = [$sheet, $line, $map];
                        break;
                    }
                }
            }
            if (count($matches) !== 1) {
                throw new InvalidArgumentException('Cần đúng một sheet có đủ 7 cột: Mã N.viên, Tên nhân viên, Phòng ban, Ngày, Thứ, Vào, Ra.');
            }
            [$sheet,$header,$map] = $matches[0];
            $rows = [];
            for ($line = $header + 1; $line <= $sheet->getHighestDataRow(); $line++) {
                $v = [];
                foreach ($map as $key => $col) {
                    $cell = $sheet->getCell([$col, $line]);
                    $v[$key] = $cell->getValue();
                    if ($key === 'code' && is_numeric($v[$key])) {
                        $v[$key] = $cell->getFormattedValue();
                    }
                }
                if (! array_filter($v, fn ($x) => $x !== null && trim((string) $x) !== '')) {
                    continue;
                }
                $r = ['line' => $line, 'code' => trim((string) $v['code']), 'name' => trim((string) $v['name']), 'department' => trim((string) $v['department']), 'date' => null, 'in' => null, 'out' => null, 'errors' => [], 'warnings' => []];
                try {
                    foreach ($v as $x) {
                        if (is_string($x) && str_starts_with($x, '=')) {
                            throw new InvalidArgumentException('Không nhận công thức trong 7 cột nhập.');
                        }
                    }
                    if (! preg_match('/^[\pL\pN_.-]{1,50}$/u', $r['code'])) {
                        throw new InvalidArgumentException('Mã nhân viên không hợp lệ.');
                    }
                    if ($r['name'] === '' || $r['department'] === '' || mb_strlen($r['name']) > 255 || mb_strlen($r['department']) > 255) {
                        throw new InvalidArgumentException('Tên/phòng ban trống hoặc quá dài.');
                    }
                    $r['date'] = $this->date($v['date'], $format);
                    $r['in'] = $this->time($v['in']);
                    $r['out'] = $this->time($v['out']);
                    $n = (int) (new DateTimeImmutable($r['date']))->format('w') + 1;
                    $day = $this->key(self::weekday($r['date']));
                    if (! in_array($this->key($v['weekday']), [$day, 'thu'.$day, 'thu'.$n, (string) $n, $n === 1 ? 'chunhat' : 't'.$n], true)) {
                        throw new InvalidArgumentException('Thứ không khớp ngày: kiểm tra định dạng ngày nguồn.');
                    }
                    if (! $r['in'] && ! $r['out']) {
                        $r['warnings'][] = 'Chưa có giờ chấm';
                    } elseif (! $r['in']) {
                        $r['warnings'][] = 'Thiếu giờ vào';
                    } elseif (! $r['out']) {
                        $r['warnings'][] = 'Thiếu giờ ra';
                    } elseif ($r['out'] < $r['in']) {
                        $r['warnings'][] = 'Giờ ra trước giờ vào: kiểm tra ca qua đêm';
                    }
                } catch(InvalidArgumentException $e) {
                    $r['errors'][] = $e->getMessage();
                }
                $rows[] = $r;
            }
            if (! $rows || count($rows) > 2000) {
                throw new InvalidArgumentException('File phải có từ 1 đến 2.000 dòng dữ liệu.');
            }
            $groups = [];
            $profiles = [];
            foreach ($rows as $i => $r) {
                if ($r['date']) {
                    $groups[$r['code'].'|'.$r['date']][] = $i;
                } $profiles[$r['code']][$r['name'].'|'.$r['department']] = true;
            }
            foreach ($groups as $ids) {
                if (count($ids) > 1) {
                    foreach ($ids as $i) {
                        $rows[$i]['errors'][] = 'Trùng mã nhân viên/ngày trong file: hãy gộp thành một dòng.';
                    }
                }
            }
            foreach ($rows as &$r) {
                if (count($profiles[$r['code']]) > 1) {
                    $r['errors'][] = 'Tên/phòng ban của cùng mã nhân viên không nhất quán trong file.';
                }
            }

            return $rows;
        } finally {
            $book->disconnectWorksheets();
        }
    }

    private function date($v, string $format): string
    {
        if (is_numeric($v)) {
            if ($v < 1 || $v > 2958465 || floor((float) $v) != (float) $v) {
                throw new InvalidArgumentException('Ngày Excel không hợp lệ.');
            }

return Date::excelToDateTimeObject((float) $v)->format('Y-m-d');
        }
        $d = DateTimeImmutable::createFromFormat('!'.$format, trim((string) $v));
        $e = DateTimeImmutable::getLastErrors();
        if (! $d || ($e && ($e['warning_count'] || $e['error_count']))) {
            throw new InvalidArgumentException('Ngày không hợp lệ.');
        }

        return $d->format('Y-m-d');
    }

    private function time($v): ?string
    {
        if ($v === null || trim((string) $v) === '') {
            return null;
        }
        if (is_numeric($v) && (float) $v >= 0 && (float) $v < 1) {
            return gmdate('H:i:s', (int) round((float) $v * 86400) % 86400);
        }
        if (! preg_match('/^(\d{1,2}):([0-5]\d)(?::([0-5]\d))?$/', trim((string) $v), $p) || (int) $p[1] > 23) {
            throw new InvalidArgumentException('Giờ không hợp lệ, cần HH:mm.');
        }

        return sprintf('%02d:%s:%s', $p[1], $p[2], $p[3] ?? '00');
    }
}
