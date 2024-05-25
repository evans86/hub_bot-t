<?php

namespace App\Console\Commands;

use App\Services\Activate\RentService;
use Illuminate\Console\Command;

class RentCron extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rent:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function handle()
    {
        $rentService = new RentService();
        $rentService->cronGuzzle();
//        $rentService->cronUpdateRentStatus();
        return 0;
    }
}
