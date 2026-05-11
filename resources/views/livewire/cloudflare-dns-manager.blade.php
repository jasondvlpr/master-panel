<div style="font-family: ui-sans-serif, system-ui, sans-serif;">
    {{-- Form Section --}}
    <div style="padding: 16px; background-color: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; margin-bottom: 24px;">
        <div style="display: flex; align-items: center; margin-bottom: 16px;">
            <svg style="width: 20px; height: 20px; color: #4f46e5; margin-right: 8px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <h3 style="margin: 0; font-size: 14px; font-weight: bold; color: #111827;">Add New Record</h3>
        </div>

        <div style="display: flex; flex-wrap: wrap; gap: 12px; align-items: flex-end;">
            <div style="flex: 1; min-width: 80px;">
                <label style="display: block; font-size: 11px; font-weight: bold; color: #6b7280; text-transform: uppercase; margin-bottom: 4px;">Type</label>
                <select wire:model="type" style="width: 100%; padding: 8px; font-size: 12px; border: 1px solid #d1d5db; border-radius: 6px; outline: none;">
                    <option value="A">A</option>
                    <option value="AAAA">AAAA</option>
                    <option value="CNAME">CNAME</option>
                    <option value="MX">MX</option>
                    <option value="TXT">TXT</option>
                </select>
            </div>

            <div style="flex: 2; min-width: 150px;">
                <label style="display: block; font-size: 11px; font-weight: bold; color: #6b7280; text-transform: uppercase; margin-bottom: 4px;">Name (e.g. @ or www)</label>
                <input type="text" wire:model="name" placeholder="www" style="width: 100%; padding: 8px; font-size: 12px; border: 1px solid #d1d5db; border-radius: 6px; box-sizing: border-box; outline: none;">
            </div>

            <div style="flex: 2; min-width: 150px;">
                <label style="display: block; font-size: 11px; font-weight: bold; color: #6b7280; text-transform: uppercase; margin-bottom: 4px;">IPv4 / Target</label>
                <input type="text" wire:model="content" placeholder="1.2.3.4" style="width: 100%; padding: 8px; font-size: 12px; border: 1px solid #d1d5db; border-radius: 6px; box-sizing: border-box; outline: none;">
            </div>

            <div style="display: flex; align-items: center; padding-bottom: 8px;">
                <label style="display: flex; align-items: center; cursor: pointer; margin: 0;">
                    <input type="checkbox" wire:model="proxied" style="margin: 0 6px 0 0;">
                    <span style="font-size: 12px; font-weight: 500; color: #374151;">Proxied</span>
                </label>
            </div>

            <div style="flex: 1; min-width: 100px;">
                <button wire:click="addRecord" style="width: 100%; padding: 8px 16px; background-color: #4f46e5; color: white; font-size: 12px; font-weight: bold; border: none; border-radius: 6px; cursor: pointer;">
                    Save
                </button>
            </div>
        </div>
    </div>

    {{-- Records Table --}}
    <div style="border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden; background-color: white;">
        <table style="width: 100%; text-align: left; border-collapse: collapse;">
            <thead style="background-color: #f9fafb;">
                <tr>
                    <th style="padding: 12px 16px; font-size: 11px; font-weight: bold; color: #6b7280; text-transform: uppercase; border-bottom: 1px solid #e5e7eb;">Type</th>
                    <th style="padding: 12px 16px; font-size: 11px; font-weight: bold; color: #6b7280; text-transform: uppercase; border-bottom: 1px solid #e5e7eb;">Name</th>
                    <th style="padding: 12px 16px; font-size: 11px; font-weight: bold; color: #6b7280; text-transform: uppercase; border-bottom: 1px solid #e5e7eb;">Content</th>
                    <th style="padding: 12px 16px; font-size: 11px; font-weight: bold; color: #6b7280; text-transform: uppercase; border-bottom: 1px solid #e5e7eb;">Proxy</th>
                    <th style="padding: 12px 16px; font-size: 11px; font-weight: bold; color: #6b7280; text-transform: uppercase; border-bottom: 1px solid #e5e7eb; text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($records as $dns)
                    <tr>
                        <td style="padding: 12px 16px; border-bottom: 1px solid #e5e7eb; white-space: nowrap;">
                            @php
                                $bgColor = match($dns['type']) {
                                    'A' => '#3b82f6',
                                    'AAAA' => '#6366f1',
                                    'CNAME' => '#a855f7',
                                    'MX' => '#f97316',
                                    'TXT' => '#10b981',
                                    default => '#6b7280',
                                };
                            @endphp
                            <span style="background-color: {{ $bgColor }}; color: white; font-size: 10px; font-weight: bold; padding: 2px 6px; border-radius: 4px;">
                                {{ $dns['type'] }}
                            </span>
                        </td>
                        <td style="padding: 12px 16px; border-bottom: 1px solid #e5e7eb; font-size: 12px; font-weight: bold; color: #111827;">
                            {{ $dns['name'] }}
                        </td>
                        <td style="padding: 12px 16px; border-bottom: 1px solid #e5e7eb; font-size: 12px; color: #4b5563; font-family: monospace;">
                            {{ $dns['content'] }}
                        </td>
                        <td style="padding: 12px 16px; border-bottom: 1px solid #e5e7eb; white-space: nowrap;">
                            @if($dns['proxied'])
                                <div style="display: inline-flex; align-items: center; padding: 2px 8px; border-radius: 999px; background-color: #fff7ed; border: 1px solid #ffedd5;">
                                    <span style="display: block; width: 6px; height: 6px; border-radius: 50%; background-color: #f97316; margin-right: 6px;"></span>
                                    <span style="color: #ea580c; font-size: 10px; font-weight: bold; text-transform: uppercase;">Proxied</span>
                                </div>
                            @else
                                <div style="display: inline-flex; align-items: center; padding: 2px 8px; border-radius: 999px; background-color: #f9fafb; border: 1px solid #e5e7eb;">
                                    <span style="display: block; width: 6px; height: 6px; border-radius: 50%; background-color: #9ca3af; margin-right: 6px;"></span>
                                    <span style="color: #6b7280; font-size: 10px; font-weight: bold; text-transform: uppercase;">DNS Only</span>
                                </div>
                            @endif
                        </td>
                        <td style="padding: 12px 16px; border-bottom: 1px solid #e5e7eb; text-align: right;">
                            <button wire:click="deleteRecord('{{ $dns['id'] }}')" wire:confirm="Delete this record?" style="background: none; border: none; cursor: pointer; color: #9ca3af; outline: none;">
                                <svg style="width: 16px; height: 16px; color: inherit;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="padding: 32px 16px; text-align: center; color: #6b7280; font-style: italic; font-size: 14px;">
                            No DNS records found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
