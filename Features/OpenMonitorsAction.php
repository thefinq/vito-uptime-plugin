<?php

namespace App\Vito\Plugins\Thefinq\VitoUptimePlugin\Features;

use App\DTOs\DynamicField;
use App\DTOs\DynamicForm;
use App\ServerFeatures\Action;
use App\Vito\Plugins\Thefinq\VitoUptimePlugin\Models\Monitor;
use Illuminate\Http\Request;

class OpenMonitorsAction extends Action
{
    public function name(): string
    {
        return 'open';
    }

    public function active(): bool
    {
        return Monitor::where('project_id', $this->server->project_id)->exists();
    }

    public function form(): ?DynamicForm
    {
        $count = Monitor::where('project_id', $this->server->project_id)->count();
        $down = Monitor::where('project_id', $this->server->project_id)->where('state', Monitor::STATE_DOWN)->count();

        return DynamicForm::make([
            DynamicField::make('summary')
                ->alert()
                ->label('Uptime monitors')
                ->description($count === 0
                    ? 'No monitors yet. Monitors belong to the project, not to a single server.'
                    : "$count monitor(s) in this project, $down down.")
                ->link('Open the Uptime page', url('/uptime')),
        ]);
    }

    public function handle(Request $request): void
    {
        session()->flash('info', 'Uptime monitors live at '.url('/uptime'));
    }
}
