<?php

namespace App\Exports;

use App\Exports\UsersSheets\CollegistsExport;
use App\Exports\UsersSheets\SemesterEvaluationExport;
use App\Exports\UsersSheets\StatusesExport;
use App\Exports\UsersSheets\StudentsCouncilFeedback;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Maatwebsite\Excel\Concerns\WithDefaultStyles;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use PhpOffice\PhpSpreadsheet\Style\Style;

class UsersExport implements WithMultipleSheets, WithDefaultStyles
{
    private $includedUsers;

    public function __construct($includedUsers)
    {
        $this->includedUsers = $includedUsers->orderBy('name');
    }

    public function sheets(): array
    {
        $sheets = [
            //new CollegistsExport($this->includedUsers),
            //new StatusesExport($this->includedUsers),
        ];

        if(user()->can('viewSemesterEvaluation', User::class)) {
            $sheets[] = new SemesterEvaluationExport($this->includedUsers);
            /*if(user()->isStudentCouncilOfficial() ||
               user()->isStudentCouncilSecretary()) {
                $sheets[] = new StudentsCouncilFeedback($this->includedUsers);
            }*/
        }

        return $sheets;
    }

    public function defaultStyles(Style $defaultStyle)
    {
        // @phpstan-ignore-next-line
        return $defaultStyle->getAlignment()->setWrapText(true);
    }

}
