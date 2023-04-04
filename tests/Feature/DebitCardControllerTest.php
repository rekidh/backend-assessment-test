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
        // * get api/debit-cards

        Passport::actingAs($this->user);
        $anotherUser = User::factory()->create();
        DebitCard::factory()->active()->count(5)->create([
            'user_id' => $anotherUser->id,
        ]);

        // check if user is authenticated
        $this->assertAuthenticatedAs($this->user);

        // check the user can not see debit cards for other user/s
        $this->getJson('api/debit-cards')
            ->assertOk()
            ->assertJsonCount(0);
        // get /debit-cards
    }

    public function testCustomerCanCreateADebitCard()
    {
        // post /debit-cards
        Passport::actingAs($this->user);
        $this->postJson('api/debit-cards', [
            'type' => 'creditTestEntry'
        ])
            ->assertValid(['type']) // correctly validated
            ->assertCreated() // returned status 201
            ->assertJson(
                fn ($json) => $json->where('type', 'creditTestEntry')->etc()
            )
            ->assertJsonStructure([  // check if the return json has right keys
                'id',
                'type',
                'number',
                'expiration_date',
                'is_active',
            ]);

        $this->assertDatabaseHas('debit_cards', [
            'type' => 'creditTestEntry'
        ]);
    }

    public function testCustomerCanSeeASingleDebitCardDetails()
    {
        // get api/debit-cards/{debitCard}
        $debitCards = DebitCard::factory()->active()->count(5)->create([
            'user_id' => $this->user->id,
        ]);

        Passport::actingAs($this->user);

        // check if user is authenticated
        $this->assertAuthenticatedAs($this->user);


        // check the user can not see debit cards for another user/s
        $this->getJson('api/debit-cards/' . $debitCards->first()->id)
            ->assertOk()
            ->assertJson(
                fn ($json) => $json->where(
                    'number',
                    intval($debitCards->first()->number)
                )
                    ->where('type', $debitCards->first()->type)
                    ->where('is_active', true)
                    ->where('expiration_date', date_format($debitCards->first()->expiration_date, 'Y-m-d H:i:s'))
                    ->where('id', $debitCards->first()->id)
                    ->etc()
            );
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
