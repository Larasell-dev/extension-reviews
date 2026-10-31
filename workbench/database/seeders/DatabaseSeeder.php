<?php

namespace Workbench\Database\Seeders;

use Illuminate\Database\Seeder;
use Larasell\Admin\Audit\SystemActor;
use Larasell\Larasell\Enums\Visibility;
use Larasell\Larasell\Models\Customer;
use Larasell\Larasell\Models\Product;
use Larasell\Larasell\Price;
use Larasell\Reviews\Actions\ApproveProductReview;
use Larasell\Reviews\Actions\RejectProductReview;
use Larasell\Reviews\Actions\SubmitProductReview;
use Larasell\Reviews\Models\ProductReview;

/**
 * Sample products, customers and reviews in every status for the playground.
 */
class DatabaseSeeder extends Seeder
{
    public function run(SubmitProductReview $submit, ApproveProductReview $approve, RejectProductReview $reject): void
    {
        if (ProductReview::query()->exists()) {
            return;
        }

        $lamp = Product::create(['slug' => 'desk-lamp', 'name' => 'Desk lamp', 'price' => Price::of(4999), 'status' => Visibility::Visible]);
        $chair = Product::create(['slug' => 'desk-chair', 'name' => 'Desk chair', 'price' => Price::of(18900), 'status' => Visibility::Visible]);
        $mug = Product::create(['slug' => 'coffee-mug', 'name' => 'Coffee mug', 'price' => Price::of(1200), 'status' => Visibility::Visible]);

        $ada = Customer::create(['first_name' => 'Ada', 'last_name' => 'Lovelace']);
        $alan = Customer::create(['first_name' => 'Alan', 'last_name' => 'Turing']);

        // Audited as the seeder rather than as an unknown actor.
        $seeder = new SystemActor('workbench-seeder');

        $approve->handle($submit->handle($lamp, rating: 5, comment: 'Bright, sturdy and the warm light is lovely in the evening.', customer: $ada), $seeder);
        $approve->handle($submit->handle($chair, rating: 4, comment: 'Comfortable for long days. Took a while to assemble.', customer: $alan), $seeder);
        $reject->handle($submit->handle($mug, rating: 1, name: 'Spam Bot', comment: 'Buy cheap watches at example.com'), $seeder);
        $submit->handle($mug, rating: 3, name: 'Grace Hopper', comment: 'Keeps coffee warm, but the handle gets hot.');
        $submit->handle($lamp, rating: 2, customer: $alan);
        $submit->handle($chair, rating: 5, name: 'Katherine Johnson');
    }
}
