<?php

namespace App\Console\Commands;

use App\Helpers\EncryptionHelper;
use App\Models\User;
use Illuminate\Console\Command;

class EncryptAadharNumbers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'encrypt:aadhar-numbers';
    protected $description = 'Encrypt Aadhar numbers for users that are not already encrypted';

    /**
     * The console command description.
     *
     * @var string
     */

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $users = User::all();

        foreach ($users as $user) {
            $encryptedAadhar = EncryptionHelper::encryptString($user->aadharNumber);

            $user->update([
                'aadharNumber' => $encryptedAadhar,
            ]);
        }

        $this->info('Aadhar numbers encrypted successfully.');
    }

}
