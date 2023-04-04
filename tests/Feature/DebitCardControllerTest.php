<?php

namespace Tests\Feature;

use App\Models\DebitCard;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;
use Illuminate\Testing\Fluent\AssertableJson;

class DebitCardControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        Passport::actingAs($this->user);
    }

    public function testCustomerCanSeeAListOfDebitCards()
    {
        // * get api/debit-cards

        $debitCards = DebitCard::factory()->active()->count(5)->create([
            'user_id' => $this->user->id,
        ]);

        Passport::actingAs($this->user);

        // check if user is authenticated
        $this->assertAuthenticatedAs($this->user);

        // check the user can see its own debit cards
        $this->getJson('api/debit-cards')
            ->assertOk()
            ->assertJsonCount($debitCards->count())
            ->assertJson(
                fn (AssertableJson $json) =>
                $json->first(
                    fn ($json) =>
                    $json->where('number', intval($debitCards->first()->number))
                        ->where('type', $debitCards->first()->type)
                        ->where('is_active', true)
                        ->where('expiration_date', date_format($debitCards->first()->expiration_date, 'Y-m-d H:i:s'))
                        ->where('id', $debitCards->first()->id)
                        ->etc()
                )
            ); // get /debit-cards
    }

    public function testCustomerCannotSeeAListOfDebitCardsOfOtherCustomers()
    {
        // get /debit-cards
    }

    public function testCustomerCanCreateADebitCard()
    {
        // post /debit-cards
    }

    public function testCustomerCanSeeASingleDebitCardDetails()
    {
        // get api/debit-cards/{debitCard}
    }

    public function testCustomerCannotSeeASingleDebitCardDetails()
    {
        // get api/debit-cards/{debitCard}
    }

    public function testCustomerCanActivateADebitCard()
    {
        // put api/debit-cards/{debitCard}
    }

    public function testCustomerCanDeactivateADebitCard()
    {
        // put api/debit-cards/{debitCard}
    }

    public function testCustomerCannotUpdateADebitCardWithWrongValidation()
    {
        // put api/debit-cards/{debitCard}
    }

    public function testCustomerCanDeleteADebitCard()
    {
        // delete api/debit-cards/{debitCard}
    }

    public function testCustomerCannotDeleteADebitCardWithTransaction()
    {
        // delete api/debit-cards/{debitCard}
    }

    // Extra bonus for extra tests :)
}
