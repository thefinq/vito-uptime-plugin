<?php

namespace App\Vito\Plugins\Thefinq\VitoUptimePlugin\Http\Controllers;

use App\Facades\Notifier;
use App\Models\Project;
use App\Vito\Plugins\Thefinq\VitoUptimePlugin\Models\Webhook;
use App\Vito\Plugins\Thefinq\VitoUptimePlugin\Notifications\WebhookEvent;
use App\Vito\Plugins\Thefinq\VitoUptimePlugin\Services\KumaPayload;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class WebhookController extends Controller
{
    public function index(Request $request): View
    {
        $project = $this->project($request);

        return view('uptime::webhooks', [
            'project' => $project,
            'webhooks' => Webhook::where('project_id', $project->id)->orderBy('name')->get(),
            'revealed' => session('revealed_webhook'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $project = $this->project($request);
        $data = $request->validate(['name' => ['required', 'string', 'max:100']]);

        $webhook = Webhook::create([
            'project_id' => $project->id,
            'name' => $data['name'],
            'source' => Webhook::SOURCE_KUMA,
            'token' => Webhook::generateToken(),
        ]);

        return redirect()->route('uptime.webhooks.index')
            ->with('status', 'Webhook created. Paste the URL below into Uptime Kuma (Settings → Notifications → Webhook).')
            ->with('revealed_webhook', $webhook->id);
    }

    public function rotate(Request $request, Webhook $webhook): RedirectResponse
    {
        $this->guard($request, $webhook);
        $webhook->rotateToken();

        return redirect()->route('uptime.webhooks.index')
            ->with('status', 'Token rotated. Update the URL in Uptime Kuma; the old one stopped working.')
            ->with('revealed_webhook', $webhook->id);
    }

    public function destroy(Request $request, Webhook $webhook): RedirectResponse
    {
        $this->guard($request, $webhook);
        $webhook->delete();

        return redirect()->route('uptime.webhooks.index')->with('status', "Webhook \"{$webhook->name}\" deleted.");
    }

    /**
     * Receives a notification from Uptime Kuma. No session, no CSRF; the token is the secret.
     */
    public function receive(Request $request, string $token): JsonResponse
    {
        $webhook = Webhook::where('token', $token)->first();
        if ($webhook === null) {
            return response()->json(['ok' => false, 'error' => 'unknown token'], 404);
        }

        $payload = $request->json()->all();
        if ($payload === []) {
            $payload = ['msg' => trim((string) $request->getContent())];
        }

        $event = KumaPayload::parse($payload);
        $webhook->recordEvent($event['text']);

        Notifier::send($webhook, new WebhookEvent($webhook->name, $event['level'], $event['text']));

        return response()->json(['ok' => true, 'level' => $event['level']]);
    }

    private function project(Request $request): Project
    {
        /** @var Project $project */
        $project = $request->user()->currentProject;

        return $project;
    }

    private function guard(Request $request, Webhook $webhook): void
    {
        abort_unless($webhook->project_id === $this->project($request)->id, 404);
    }
}
