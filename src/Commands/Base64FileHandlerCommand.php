<?php

namespace AwaisJameel\Base64FileHandler\Commands;

use Illuminate\Console\Command;

class Base64FileHandlerCommand extends Command
{
    public $signature = 'base64filehandler';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
