<?php

namespace Apokavkos\JEveSeAT\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Seat\Eveapi\Models\RefreshToken;

class SyncTokens extends Command
{
    protected $signature = 'jeveseat:sync-tokens';
    protected $description = 'Sync SeAT ESI refresh tokens to jEveAssets headless profile';

    public function handle()
    {
        $this->info('Starting SeAT ESI Token sync to jEveAssets...');

        $tokens = RefreshToken::with('character')->get();

        if ($tokens->isEmpty()) {
            $this->warn('No refresh tokens found in SeAT.');
            return 0;
        }

        $xml = new \SimpleXMLElement('<accounts version="1"/>');
        $account = $xml->addChild('account');
        $account->addAttribute('name', 'SeAT Sync');
        $account->addAttribute('type', 'ESI');
        
        $characters = $account->addChild('characters');

        foreach ($tokens as $token) {
            $char = $characters->addChild('character');
            $char->addAttribute('id', $token->character_id);
            $char->addAttribute('name', $token->character->name ?? 'Unknown');
            $char->addAttribute('refreshToken', $token->refresh_token);
        }

        $path = 'jeveassets/.jeveassets/accounts.xml';
        Storage::disk('local')->put($path, $xml->asXML());

        $this->info("Successfully synced " . $tokens->count() . " tokens to " . Storage::disk('local')->path($path));

        return 0;
    }
}
