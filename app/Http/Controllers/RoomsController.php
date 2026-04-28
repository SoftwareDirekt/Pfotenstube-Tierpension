<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Session;
use Illuminate\Http\Request;
use App\Helpers\General;
use App\Models\Dog;
use App\Models\Room;
use App\Models\Reservation;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RoomsController extends Controller
{
    /** @var list<string> */
    private array $exportTempImageFiles = [];

    public function rooms(Request $request)
    {
        if(!General::permissions('Zimmer'))
        {
            return to_route('admin.settings');
        }

        if($request->ajax())
        {
            $keyword = isset($request->keyword) ? $request->keyword : "";
            $order = isset($request->order) ? $request->order : 'desc';

            $where = [];

            if(isset($request->keyword) && $request->keyword != '')
            {
                $where = function ($query) use ($request) {
                    $query->where('number', 'like', '%' . $request->keyword . '%')
                    ->orWhere('type', 'like', '%' . $request->keyword . '%');
                };
            }

            $rooms = Room::where($where)->orderBy('id', $order)->get();
            return $rooms;

        }

        $rooms = Room::orderBy("order","asc")->get();

        return view ('admin.room.index' , compact('rooms'));
    }
    public function add_rooms()
    {
        if(!General::permissions('Zimmer'))
        {
            return to_route('admin.settings');
        }

        return view('admin.room.add');
    }
    public function post_rooms(Request $request)
    {
        $request->validate([
            'room_number' => 'required',
            'type' => 'required',
            'capacity' => 'required'
        ]);

        $room = Room::orderBy('id' , 'desc')->first();
        if($room){
            $order = (int)$room->order + 1;
        }
        else{
            $order = 1;
        }

        Room::create([
            'number' => $request->room_number,
            'type' => $request->type,
            'capacity' => $request->capacity,
            'order' => $order,
        ]);

        Session::flash('success', 'Raum erfolgreich aktualisiert');
        return back();
    }
    public function edit_room($id)
    {
        if(!General::permissions('Zimmer'))
        {
            return to_route('admin.settings');
        }

        $room = Room::where('id' , $id)->first();
        return view ('admin.room.edit', compact('room'));
    }
    public function update_room(Request $request)
    {
        $request->validate([
            'room_number' => 'required',
            'type' => 'required',
            'capacity' => 'required',
            'status' => 'required'
        ]);

        Room::where('id', $request->id)->update([
            'number'=> $request->room_number,
            'type'=> $request->type,
            'capacity'=> $request->capacity,
            'status'=> $request->status
        ]);

        Session::flash('success', 'Raum erfolgreich aktualisiert');
        return to_route('admin.rooms');
    }
    public function delete_room(Request $request)
    {
        Room::where('id', $request->id)->delete();

        Session::flash('error', 'Raum erfolgreich gelöscht');
        return back();
    }

    public function update_room_order(Request $request)
    {
        $order = $request->input('order');

        foreach ($order as $index => $id) {
            Room::where('id', $id)->update(['order' => $index + 1]);
        }
        return response()->json(['success' => true]);
    }
    
    public function clean_room(Request $request)
    {
            $request->validate([
                'room_id' => 'required|exists:rooms,id',
                'cleaning_status' => 'required|integer|min:0|max:2'
            ]);

            $room = Room::findOrFail($request->room_id);

            // Toggle the cleaning status (0, 1, 2)
            if ($room->cleaning_status == 0) {
                $room->cleaning_status = 1; // In progress
            } elseif ($room->cleaning_status == 1) {
                $room->cleaning_status = 2; // Cleaned
            } else {
                $room->cleaning_status = 0; // Not cleaned
            }
            $room->save();

            // Return updated status in JSON format
            return response()->json(['room' => $room]);
    }
    
    public function resetClean()
    {
        Room::query()->update(['cleaning_status' => 0]);
        return response()->json(['message' => 'All rooms cleaning status reset to Uncleaned']);
    }

    public function resetRoomCondition()
    {
        Room::query()->update(['room_condition' => 0]);
        return response()->json(['success' => true]);
    }

    public function exportRooms()
    {
        if (! General::permissions('Zimmer')) {
            return to_route('admin.settings');
        }

        $this->exportTempImageFiles = [];

        $rooms = Room::orderBy('order')->get();

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Zimmerliste');

        $headers = [
            'Zimmer',
            'Tier',
            'Foto',
            'Geburtsdatum',
            'Chip Nr.',
            'Geschlecht',
            'Rasse',
            'Typ',
        ];

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType'   => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '2E7D32'],
            ],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        ];

        $colLetters = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];
        foreach ($colLetters as $i => $c) {
            $sheet->setCellValue($c . '1', $headers[$i]);
            $sheet->getStyle($c . '1')->applyFromArray($headerStyle);
        }

        $row = 2;
        $dataEnd = 1;

        foreach ($rooms as $room) {
            $roomStartRow = $row;

            $boardingDogs = Reservation::with('dog')
                ->where('room_id', $room->id)
                ->whereNotIn('status', [
                    Reservation::STATUS_CANCELLED,
                    Reservation::STATUS_CHECKED_OUT,
                ])
                ->whereHas('dog')
                ->get();

            $breedingAnimals = json_decode($room->breeding_shelter_animals ?? '[]', true) ?? [];

            $totalAnimals = $boardingDogs->count() + count($breedingAnimals);

            if ($totalAnimals === 0) {
                $sheet->setCellValue('A' . $row, $room->number);
                foreach (['B', 'C', 'D', 'E', 'F', 'G', 'H'] as $c) {
                    $sheet->setCellValue($c . $row, '—');
                }
                $row++;
            } else {
                foreach ($boardingDogs as $res) {
                    /** @var Dog $dog */
                    $dog = $res->dog;
                    $sheet->setCellValue('B' . $row, $dog->name ?? '');
                    $sheet->setCellValue('D' . $row, $this->exportDogBirthDisplay($dog));
                    $this->setCellValueAsText($sheet, 'E' . $row, $this->exportChipString($dog->chip_number ?? ''));
                    $sheet->setCellValue('F' . $row, $this->exportGenderDe($dog->gender));
                    $sheet->setCellValue('G' . $row, $dog->compatible_breed ?? ($dog->breed ?? ''));
                    $sheet->setCellValue('H' . $row, 'Pension');
                    $this->embedDogPicture($sheet, $row, $dog);
                    $sheet->getStyle('B' . $row . ':H' . $row)->getFill()
                        ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                        ->getStartColor()->setRGB('E3F2FD');
                    $row++;
                }

                foreach ($breedingAnimals as $animal) {
                    $sheet->setCellValue('B' . $row, $animal['name'] ?? '');
                    $sheet->setCellValue('D' . $row, $this->exportBreedingBirthDisplay($animal));
                    $this->setCellValueAsText($sheet, 'E' . $row, $this->exportChipString($animal['chip_number'] ?? ($animal['chip_no'] ?? '')));
                    $sheet->setCellValue('F' . $row, $this->exportGenderDe($animal['gender'] ?? ''));
                    $sheet->setCellValue('G' . $row, $animal['breed'] ?? ($animal['race'] ?? ($animal['compatible_breed'] ?? '')));
                    $sheet->setCellValue('H' . $row, $animal['tier_type'] ?? 'Zucht');
                    $this->embedBreedingPicture($sheet, $row, $animal);
                    $sheet->getStyle('B' . $row . ':H' . $row)->getFill()
                        ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                        ->getStartColor()->setRGB('FFF3E0');
                    $row++;
                }
            }

            $roomEndRow = $row - 1;
            $dataEnd = max($dataEnd, $roomEndRow);

            $sheet->setCellValue('A' . $roomStartRow, $room->number);
            if ($roomEndRow > $roomStartRow) {
                $sheet->mergeCells('A' . $roomStartRow . ':A' . $roomEndRow);
            }
            $sheet->getStyle('A' . $roomStartRow)->applyFromArray([
                'font'      => ['bold' => true],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical'   => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                ],
                'fill' => [
                    'fillType'   => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'F5F5F5'],
                ],
            ]);

            $sheet->getStyle('A' . $roomEndRow . ':H' . $roomEndRow)->getBorders()
                ->getBottom()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM);
        }

        if ($dataEnd >= 1) {
            $sheet->getStyle('A1:H' . $dataEnd)->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color'       => ['rgb' => '000000'],
                    ],
                ],
            ]);
        }

        $sheet->getColumnDimension('A')->setWidth(14);
        $sheet->getColumnDimension('B')->setWidth(20);
        $sheet->getColumnDimension('C')->setWidth(14);
        $sheet->getColumnDimension('D')->setWidth(14);
        $sheet->getColumnDimension('E')->setWidth(20);
        $sheet->getColumnDimension('F')->setWidth(14);
        $sheet->getColumnDimension('G')->setWidth(22);
        $sheet->getColumnDimension('H')->setWidth(16);

        $filename = 'Zimmer_' . date('Y-m-d_His') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save('php://output');
        foreach ($this->exportTempImageFiles as $p) {
            if (is_string($p) && is_file($p)) {
                @unlink($p);
            }
        }
        exit;
    }

    private function exportGenderDe(?string $gender): string
    {
        $g = strtolower(trim((string) $gender));

        return match ($g) {
            'male', 'm' => 'männlich',
            'female', 'f' => 'weiblich',
            'unknown' => 'unbekannt',
            'männlich', 'weiblich', 'unbekannt' => $g,
            default => $g !== '' ? $gender : '—',
        };
    }

    private function exportDogBirthDisplay(Dog $dog): string
    {
        if (empty($dog->age)) {
            return '—';
        }
        try {
            return Carbon::parse($dog->age)->format('d.m.Y');
        } catch (\Throwable) {
            return (string) $dog->age;
        }
    }

    private function exportBreedingBirthDisplay(array $animal): string
    {
        $raw = $animal['date_of_birth'] ?? $animal['birth'] ?? $animal['date_of_birth_formatted'] ?? null;
        if (empty($raw)) {
            return '—';
        }
        try {
            return Carbon::parse($raw)->format('d.m.Y');
        } catch (\Throwable) {
            return (string) $raw;
        }
    }

    private function exportChipString($chip): string
    {
        if ($chip === null || $chip === '') {
            return '—';
        }

        return (string) $chip;
    }

    private function setCellValueAsText(Worksheet $sheet, string $coord, string $value): void
    {
        $sheet->getCell($coord)->setValueExplicit($value, DataType::TYPE_STRING);
        $sheet->getStyle($coord)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT);
    }

    private function embedDogPicture(Worksheet $sheet, int $row, Dog $dog): void
    {
        $pic = $dog->picture;
        if (! $pic || $pic === 'no-user-picture.gif') {
            return;
        }
        $path = public_path('uploads/users/dogs/' . $pic);
        if (is_file($path)) {
            $this->embedImagePath($sheet, 'C' . $row, $path);
        }
    }

    private function embedBreedingPicture(Worksheet $sheet, int $row, array $animal): void
    {
        $rel = $animal['picture'] ?? null;
        if (empty($rel)) {
            return;
        }
        if (is_string($rel) && (str_starts_with($rel, 'http://') || str_starts_with($rel, 'https://'))) {
            $this->embedImageFromUrl($sheet, 'C' . $row, $rel);
            return;
        }
        $base = rtrim((string) (config('services.breeding_shelter.url') ?? env('BREEDING_SHELTER_URL', '')), '/');
        if ($base === '') {
            return;
        }
        $url = $base . '/' . ltrim((string) $rel, '/');
        $this->embedImageFromUrl($sheet, 'C' . $row, $url);
    }

    private function embedImagePath(Worksheet $sheet, string $coord, string $absolutePath): void
    {
        if (! is_readable($absolutePath)) {
            return;
        }
        $drawing = new Drawing();
        $drawing->setName('Foto');
        $drawing->setDescription('Tierfoto');
        $drawing->setPath($absolutePath);
        $drawing->setHeight(60);
        $drawing->setCoordinates($coord);
        $drawing->setOffsetX(2);
        $drawing->setOffsetY(2);
        $drawing->setWorksheet($sheet);
        if (preg_match('/(\d+)$/', $coord, $m)) {
            $r = (int) $m[1];
            if ($r > 0) {
                $sheet->getRowDimension($r)->setRowHeight(50);
            }
        }
    }

    private function embedImageFromUrl(Worksheet $sheet, string $coord, string $url): void
    {
        try {
            $response = Http::timeout(10)->get($url);
            if (! $response->successful()) {
                return;
            }
            $data = $response->body();
            if ($data === '' || strlen($data) < 20) {
                return;
            }
            $tmp = tempnam(sys_get_temp_dir(), 'tpxlsx');
            if ($tmp === false) {
                return;
            }
            $file = $tmp . '.img';
            if (@file_put_contents($file, $data) === false) {
                @unlink($tmp);

                return;
            }
            @unlink($tmp);
            if (is_readable($file) && getimagesize($file) !== false) {
                $this->exportTempImageFiles[] = $file;
                $this->embedImagePath($sheet, $coord, $file);
            } else {
                @unlink($file);
            }
        } catch (\Throwable) {
            // leave cell empty
        }
    }
}
