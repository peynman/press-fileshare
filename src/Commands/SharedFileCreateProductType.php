<?php

namespace Larapress\AdobeConnect\Commands;

use Illuminate\Console\Command;
use Larapress\ECommerce\Models\ProductType;

class FileShareCreateProductType extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lp:fileshare:create-pt';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create FileShare product type';

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
     */
    public function handle()
    {
        ProductType::updateOrCreate([
            'name' => config('larapress.fileshare.product_typename'),
            'author_id' => 1,
        ], [
            'flags' => 0,
            'data' => [
                "form" => [

                ],
                "title" => trans('larapress::fileshare.product_type.title'),
                "agent" => "pages.vuetify.1.0"
            ]
        ]);
        $this->info("Done.");

        return 0;
    }
}
