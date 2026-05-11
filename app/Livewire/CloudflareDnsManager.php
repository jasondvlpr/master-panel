<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\CloudflareAccount;
use App\Services\CloudflareService;
use Filament\Notifications\Notification;

class CloudflareDnsManager extends Component
{
    public $zoneId;
    public $accountId;
    public $records = [];
    
    // Form fields
    public $type = 'A';
    public $name = '';
    public $content = '';
    public $proxied = true;
    public $ttl = 1;

    public function mount($zoneId, $accountId)
    {
        $this->zoneId = $zoneId;
        $this->accountId = $accountId;
        $this->loadRecords();
    }

    public function loadRecords()
    {
        $account = CloudflareAccount::find($this->accountId);
        if (!$account) return;

        $cf = new CloudflareService($account->api_token, $account->email, $account->api_key);
        $dnsData = $cf->getDnsRecords($this->zoneId);

        if (isset($dnsData['success']) && $dnsData['success']) {
            $this->records = $dnsData['result'];
        }
    }

    public function addRecord()
    {
        $this->validate([
            'type' => 'required',
            'name' => 'required',
            'content' => 'required',
        ]);

        $account = CloudflareAccount::find($this->accountId);
        $cf = new CloudflareService($account->api_token, $account->email, $account->api_key);

        $result = $cf->addDnsRecord($this->zoneId, $this->type, $this->name, $this->content, $this->proxied);

        if (isset($result['success']) && $result['success']) {
            Notification::make()->title('Record added successfully')->success()->send();
            $this->reset(['name', 'content']);
            $this->loadRecords();
        } else {
            $error = $result['errors'][0]['message'] ?? 'API Error';
            Notification::make()->title('Failed to add record')->danger()->body($error)->send();
        }
    }

    public function deleteRecord($recordId)
    {
        $account = CloudflareAccount::find($this->accountId);
        $cf = new CloudflareService($account->api_token, $account->email, $account->api_key);

        $result = $cf->deleteDnsRecord($this->zoneId, $recordId);

        if (isset($result['success']) && $result['success']) {
            Notification::make()->title('Record deleted')->success()->send();
            $this->loadRecords();
        } else {
            Notification::make()->title('Failed to delete')->danger()->send();
        }
    }

    public function render()
    {
        return view('livewire.cloudflare-dns-manager');
    }
}
