<?php

namespace SuiteZap\LawFirm\Whatsapp\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use SuiteZap\LawFirm\SaaS\Services\MotherShipService;
use SuiteZap\LawFirm\Whatsapp\Jobs\SendWhatsappMessageJob;
use SuiteZap\LawFirm\Whatsapp\Models\WhatsappTicket;
use SuiteZap\LawFirm\Whatsapp\Services\MessengerService;

/**
 * Admin controller: serves the Messenger inbox view and handles
 * ticket actions (accept, close, send message).
 */
class WhatsappChatController extends Controller
{
    public function __construct(private MessengerService $messenger) {}

    // ── Views ─────────────────────────────────────────────────────────────────

    public function index()
    {
        $tenantId = MotherShipService::getTenantId();

        $tickets = WhatsappTicket::where('tenant_id', $tenantId)
            ->with(['contact', 'latestMessage'])
            ->orderByDesc('updated_at')
            ->cursorPaginate(30);

        return view('lawfirm::Whatsapp.messenger', compact('tickets', 'tenantId'));
    }

    // ── API (JSON) ────────────────────────────────────────────────────────────

    /** Returns paginated ticket list (AJAX refresh). */
    public function ticketList(Request $request)
    {
        $tenantId = MotherShipService::getTenantId();
        $status = $request->query('status');

        $query = WhatsappTicket::where('tenant_id', $tenantId)
            ->with(['contact', 'latestMessage'])
            ->orderByDesc('updated_at');

        if ($status) {
            $query->where('status', $status);
        }

        return response()->json($query->cursorPaginate(30));
    }

    /** Returns messages for a given ticket (AJAX, cursor-paginated). */
    public function messages(Request $request, int $ticketId)
    {
        $tenantId = MotherShipService::getTenantId();

        $ticket = WhatsappTicket::where('tenant_id', $tenantId)->findOrFail($ticketId);

        $messages = $ticket->messages()
            ->orderBy('id')
            ->cursorPaginate(50);

        return response()->json($messages);
    }

    /** Accept a pending ticket. */
    public function accept(int $ticketId)
    {
        $tenantId = MotherShipService::getTenantId();
        $ticket = WhatsappTicket::where('tenant_id', $tenantId)->findOrFail($ticketId);

        $this->messenger->acceptTicket($ticket, Auth::id());

        return response()->json(['ok' => true, 'status' => 'open']);
    }

    /** Close a ticket (optionally send farewell message). */
    public function close(int $ticketId)
    {
        $tenantId = MotherShipService::getTenantId();
        $ticket = WhatsappTicket::where('tenant_id', $tenantId)->findOrFail($ticketId);

        // Fetch configurable farewell message for this tenant
        $farewell = core()->getConfigData('lawfirm.whatsapp_templates.messages.farewell_message');

        $this->messenger->closeTicket($ticket, $tenantId, $farewell ?: null);

        return response()->json(['ok' => true, 'status' => 'closed']);
    }

    /** Dispatch outgoing message (queued job → Evolution API). */
    public function sendMessage(Request $request, int $ticketId)
    {
        $request->validate(['text' => 'required|string|max:4096']);

        $tenantId = MotherShipService::getTenantId();
        $ticket = WhatsappTicket::where('tenant_id', $tenantId)
            ->whereIn('status', ['open', 'pending'])
            ->findOrFail($ticketId);

        SendWhatsappMessageJob::dispatch($tenantId, $ticket->id, $request->input('text'));

        return response()->json(['ok' => true, 'queued' => true]);
    }

    /** Send media (image, audio, video, document) via Evolution API. */
    public function sendMedia(Request $request, int $ticketId)
    {
        $request->validate([
            'url'     => 'required|url',
            'type'    => 'required|in:image,audio,video,document',
            'caption' => 'nullable|string|max:1024',
        ]);

        $tenantId = MotherShipService::getTenantId();
        $ticket = WhatsappTicket::where('tenant_id', $tenantId)
            ->whereIn('status', ['open', 'pending'])
            ->findOrFail($ticketId);

        $msg = $this->messenger->sendMedia(
            $tenantId,
            $ticket,
            $request->input('url'),
            $request->input('type'),
            $request->input('caption')
        );

        return response()->json(['ok' => (bool) $msg, 'message' => $msg]);
    }

    /** Helper: Upload file to storage to get a public URL for Evolution API. */
    public function uploadMedia(Request $request)
    {
        $request->validate(['file' => 'required|file|max:20480']); // 20MB limit

        $path = $request->file('file')->store('whatsapp-temp', 'public');
        $url = asset('storage/'.$path);

        // Map extension to media type
        $ext = strtolower($request->file('file')->getClientOriginalExtension());
        $type = 'document';
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
            $type = 'image';
        }
        if (in_array($ext, ['mp3', 'ogg', 'wav'])) {
            $type = 'audio';
        }
        if (in_array($ext, ['mp4', 'mov', 'avi'])) {
            $type = 'video';
        }

        return response()->json(['url' => $url, 'type' => $type]);
    }

    /** Create or find a ticket by phone number to start a new chat. */
    public function startConversation(Request $request)
    {
        $request->validate(['phone' => 'required|string|min:8']);

        $tenantId = MotherShipService::getTenantId();
        $ticket = $this->messenger->getOrCreateTicket($tenantId, $request->input('phone'));

        return response()->json([
            'ok'     => true,
            'ticket' => $ticket->load('contact'),
        ]);
    }
}
