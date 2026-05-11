<div>
    @livewire('cloudflare-dns-manager', [
        'zoneId' => $record->zone_id, 
        'accountId' => $record->cloudflare_account_id
    ])
</div>
