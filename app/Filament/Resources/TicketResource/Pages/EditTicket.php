<?php

namespace App\Filament\Resources\TicketResource\Pages;

use App\Filament\Resources\TicketResource;
use App\Models\Project;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Session;

class EditTicket extends EditRecord
{
    protected static string $resource = TicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        if (!empty($data['user_id']) && !empty($data['project_id'])) {
            $project = Project::find($data['project_id']);
            $isMember = $project?->members()->where('users.id', $data['user_id'])->exists();

            if (!$isMember) {
                $data['user_id'] = null;

                $this->notify('warning', 'Selected assignee is not a member of this project. Assignee has been reset.');
            }
        }

        return parent::handleRecordUpdate($record, $data);
    }

    protected function getRedirectUrl(): string
    {
        // Ambil URL dari session
        $redirectUrl = Session::pull('redirect_url'); // Hapus setelah diambil

        // Jika ada redirect URL yang valid, gunakan itu
        if ($redirectUrl && filter_var($redirectUrl, FILTER_VALIDATE_URL)) {
            return $redirectUrl;
        }

        // Default redirect ke halaman index jika tidak ada parameter redirect
        return $this->getResource()::getUrl('index');
    }
}
