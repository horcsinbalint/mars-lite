<?php

namespace App\Exports\UsersSheets;

use Generator;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Maatwebsite\Excel\Concerns\FromGenerator;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;

class CollegistsExport implements FromGenerator, WithTitle, WithHeadings, ShouldAutoSize, WithEvents
{
    protected $users;
    protected $semester;

    public function __construct( $includedUsers)
    {
        $this->users = $includedUsers->with(['roleUsers', 'workshops', 'educationalInformation', 'personalInformation', 'semesterStatuses', 'faculties', 'workshops'])->get();
        $this->semester = Semester::current();
    }

    public function generator(): Generator
    {
        foreach($this->users as $user) {
            yield [
                '=HYPERLINK("'.route('users.show', ['user' => $user]).'", "'.$user->name.'")',
                $user->educationalInformation?->neptun,
                $user->isResident() ? 'Bentlakó' : ($user->isExtern() ? 'Bejáró' : ($user->isAlumni() ? "Alumni" : ($user->isTenant() ? "Vendég" : ""))),
                $user->getStatus($this->semester)?->translatedStatus(),
                $user->email,
                $user->educationalInformation?->email,
                $user->personalInformation?->place_of_birth,
                $user->personalInformation?->date_of_birth,
                $user->personalInformation?->mothers_name,
                $user->personalInformation?->phone_number,
                $user->personalInformation?->getAddress(),
                $user->educationalInformation?->year_of_graduation,
                $user->educationalInformation?->high_school,
                $user->educationalInformation?->year_of_acceptance,
                $user->educationalInformation?->studyLines?->map(function ($studyLine) {
                    return $studyLine->getNameWithYear();
                })->implode(" \n"),
                implode(" \n", $user->faculties->pluck('name')->toArray()),
                implode(" \n", $user->workshops->pluck('name')->toArray()),
                implode(" \n", array_map(function ($exam) {
                    return implode(", ", [__('role.'.$exam->language), $exam->level, $exam->type, $exam->date->format('Y-m')]);
                }, $user->educationalInformation?->languageExamsBeforeAcceptance() ?? [])),
                implode(" \n", array_map(function ($exam) {
                    return implode(", ", [__('role.'.$exam->language), $exam->level, $exam->type, $exam->date->format('Y-m')]);
                }, $user->educationalInformation?->languageExamsAfterAcceptance() ?? [])),
                ($user->educationalInformation?->alfonso_language ?
                    __('role.'.$user->educationalInformation?->alfonso_language) . " " . $user->educationalInformation?->alfonso_desired_level
                    : ""),
                ($user->educationalInformation?->alfonsoCompleted($user->isSenior()) ?? false)   //Senior status cannot be loaded easily there
                    ? 'Igen'
                    : (($user->educationalInformation?->alfonsoCanBeCompleted() ?? true) ? "Folyamatban" : "Nem"),
                $user->room,
                ];
        }
    }

    public function collection()
    {
        return $this->users;
    }

    public function title(): string
    {
        return user()->isAdmin() ? "Felhasználók" : "Collegisták";
    }

    public function headings(): array
    {
        return [
            'Név',
            'Neptun-kód',
            'Collegista státusz',
            'Státusz ('.$this->semester->tag.')',
            'E-mail',
            'Egyetemi e-mail',
            'Születési hely',
            'Születési idő',
            'Anyja neve',
            'Telefonszám',
            'Lakhely',
            'Érettségi éve',
            'Középiskola',
            'Collegiumi felvétel éve',
            'Szak',
            'Kar',
            'Műhely',
            'Nyelvvizsgák felvétel előtt',
            'Nyelvvizsgák felvétel után',
            'Alfonsó',
            'Alfonsó teljesítve?',
            'Szobaszám',
        ];
    }


    public function registerEvents(): array
    {
        return [
            AfterSheet::class    => function (AfterSheet $event) {
                $event->sheet->getDelegate()->freezePane('C2');
            },
        ];
    }
}
