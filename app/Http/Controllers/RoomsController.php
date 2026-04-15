<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Session;
use Illuminate\Http\Request;
use App\Helpers\General;
use App\Models\Room;
use App\Models\Reservation;

class RoomsController extends Controller
{
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
        if (!General::permissions('Zimmer')) {
            return to_route('admin.settings');
        }

        $rooms = Room::orderBy('order')->get();

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Zimmerliste');

        // ── 5 columns matching the sketch: Rooms | Tier | Chip No. | Gender | Breed ──
        $headers = ['Zimmer', 'Tier', 'Chip Nr.', 'Geschlecht', 'Rasse'];

        $lastCol = 'E';

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType'   => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '2E7D32'],
            ],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        ];

        foreach (['A', 'B', 'C', 'D', 'E'] as $i => $c) {
            $sheet->setCellValue($c . '1', $headers[$i]);
            $sheet->getStyle($c . '1')->applyFromArray($headerStyle);
        }

        $row = 2;

        foreach ($rooms as $room) {
            $roomStartRow = $row;

            // Collect all animals for this room: active, reserved, or pending — anything except cancelled/checked-out
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
                // Empty room — one placeholder row
                $sheet->setCellValue('A' . $row, $room->number);
                $sheet->setCellValue('B' . $row, '—');
                $sheet->setCellValue('C' . $row, '—');
                $sheet->setCellValue('D' . $row, '—');
                $sheet->setCellValue('E' . $row, '—');
                $row++;
            } else {
                // ── Boarding dogs ────────────────────────────────────
                foreach ($boardingDogs as $res) {
                    $dog = $res->dog;
                    $sheet->setCellValue('B' . $row, $dog->name ?? '');
                    $sheet->setCellValue('C' . $row, $dog->chip_number ?? '');
                    $sheet->setCellValue('D' . $row, $dog->gender ?? '');
                    $sheet->setCellValue('E' . $row, $dog->compatible_breed ?? ($dog->breed ?? ''));

                    // Light blue for pension dogs
                    $sheet->getStyle('B' . $row . ':E' . $row)->getFill()
                        ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                        ->getStartColor()->setRGB('E3F2FD');

                    $row++;
                }

                // ── Breeding shelter animals ─────────────────────────
                foreach ($breedingAnimals as $animal) {
                    $sheet->setCellValue('B' . $row, $animal['name'] ?? '');
                    $sheet->setCellValue('C' . $row, $animal['chip_number'] ?? ($animal['chip_no'] ?? ''));
                    $sheet->setCellValue('D' . $row, $animal['gender'] ?? '');
                    $sheet->setCellValue('E' . $row, $animal['breed'] ?? ($animal['race'] ?? ($animal['compatible_breed'] ?? '')));

                    // Light orange for breeding animals
                    $sheet->getStyle('B' . $row . ':E' . $row)->getFill()
                        ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                        ->getStartColor()->setRGB('FFF3E0');

                    $row++;
                }
            }

            $roomEndRow = $row - 1;

            // ── Write room name once in column A, merged across all its rows ──
            $sheet->setCellValue('A' . $roomStartRow, $room->number);

            if ($roomEndRow > $roomStartRow) {
                $sheet->mergeCells('A' . $roomStartRow . ':A' . $roomEndRow);
            }

            // Room cell style: bold, centred, light grey background
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

            // Separator: thick bottom border under last row of this room
            $sheet->getStyle('A' . $roomEndRow . ':E' . $roomEndRow)->getBorders()
                ->getBottom()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM);
        }

        // ── Thin borders on all data ──────────────────────────────────
        $sheet->getStyle('A1:E' . ($row - 1))->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color'       => ['rgb' => '000000'],
                ],
            ],
        ]);

        // ── Column widths ─────────────────────────────────────────────
        $sheet->getColumnDimension('A')->setWidth(14);  // Rooms
        $sheet->getColumnDimension('B')->setWidth(20);  // Tier
        $sheet->getColumnDimension('C')->setWidth(18);  // Chip No.
        $sheet->getColumnDimension('D')->setWidth(12);  // Gender
        $sheet->getColumnDimension('E')->setWidth(22);  // Breed

        $filename = 'Zimmer_' . date('Y-m-d_His') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save('php://output');
        exit;
    }
}
