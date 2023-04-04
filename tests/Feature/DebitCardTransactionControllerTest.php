<?php

namespace Tests\Feature;

use App\Models\DebitCardTransaction;
use Illuminate\Testing\Fluent\AssertableJson;
use App\Models\DebitCard;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class DebitCardTransactionControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected DebitCard $debitCard;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->debitCard = DebitCard::factory()->create([
            'user_id' => $this->user->id
        ]);
        Passport::actingAs($this->user);
    }

    public function testCustomerCanSeeAListOfDebitCardTransactions()
    {
        // get /debit-card-transactions
        $debitCard = DebitCard::factory()->active()->create([
            'user_id' => $this->user->id
        ]);

        // check if user is authenticated
        $this->assertAuthenticatedAs($this->user);

        // create some transactions for the above debit card
        $debitCardTransactions =  DebitCardTransaction::factory()->count(10)->create([
            'debit_card_id' => $debitCard->id
        ]);

        $this->getJson('api/debit-card-transactions/' .  $debitCard->id)
            ->assertOk()
            ->assertJsonCount($debitCardTransactions->count())
            ->assertJson(
                fn (AssertableJson $json) =>
                $json->first(
                    fn ($json) => $json
                        ->where('amount', intval($debitCardTransactions->first()->amount))
                        ->where('currency_code', $debitCardTransactions->first()->currency_code)
                        ->etc()
                )
            );
    }

    public function testCustomerCannotSeeAListOfDebitCardTransactionsOfOtherCustomerDebitCard()
    {
        // get /debit-card-transactions
        // create another user
        $anotherUser = User::factory()->create();

        // create a debit card for the other user
        $debitCard = DebitCard::factory()->active()->create([
            'user_id' => $anotherUser->id
        ]);

        // check if user is authenticated
        $this->assertAuthenticatedAs($this->user);

        // check the user can not see debit card transactions for other user/s
        $this->getJson('api/debit-card-transactions/' .  $debitCard->id)
            ->assertForbidden();
    }

    public function testCustomerCanCreateADebitCardTransaction()
    {
        // post /debit-card-transactions
        // create a debit card for the other user
        $debitCard = DebitCard::factory()->active()->create([
            'user_id' => $this->user->id
        ]);

        // check if user is authenticated
        $this->assertAuthenticatedAs($this->user);

        $this->postJson('api/debit-card-transactions', [
            'debit_card_id' => $debitCard->id,
            'amount' => 1000,
            'currency_code' => 'EUR',
        ])
            ->assertValid(['type']) // correctly validated
            ->assertCreated() // returned status 201
            ->assertJson(
                fn ($json) => $json
                    ->where('amount', 1000)
                    ->where('currency_code', 'EUR')
                    ->etc()
            )
            ->assertJsonStructure([  // check if the return json has right keys
                'amount',
                'currency_code',
            ]);

        $this->assertDatabaseHas('debit_card_transactions', [
            'debit_card_id' => $debitCard->id,
            'amount' => 1000,
            'currency_code' => 'EUR',
        ]);
    }

    public function testCustomerCannotCreateADebitCardTransactionToOtherCustomerDebitCard()
    {
        // post /debit-card-transactions
        // create another user
        $anotherUser = User::factory()->create();

        // create a debit card for the other user
        $debitCard = DebitCard::factory()->active()->create([
            'user_id' => $anotherUser->id
        ]);

        // check if user is authenticated
        $this->assertAuthenticatedAs($this->user);

        $this->postJson('api/debit-card-transactions', [
            'debit_card_id' => $debitCard->id,
            'amount' => 1000,
            'currency_code' => 'EUR',
        ])
            ->assertValid(['type']) // correctly validated
            ->assertForbidden(); // returned status 403

        $this->assertDatabaseMissing('debit_card_transactions', [
            'debit_card_id' => $debitCard->id,
            'amount' => 1000,
            'currency_code' => 'EUR',
        ]);
    }

    public function testCustomerCanSeeADebitCardTransaction()
    {
        // get /debit-card-transactions/{debitCardTransaction}
        $debitCard = DebitCard::factory()->active()->create([
            'user_id' => $this->user->id
        ]);

        // check if user is authenticated
        $this->assertAuthenticatedAs($this->user);

        // create some transactions for the above debit card
        $debitCardTransactions =  DebitCardTransaction::factory()->count(10)->create([
            'debit_card_id' => $debitCard->id
        ]);

        $this->getJson('api/debit-card-transaction/' . $debitCardTransactions->first()->id)
            ->assertOk()
            ->assertJsonCount(2)
            ->assertJson(
                fn ($json) => $json
                    ->where('amount', intval($debitCardTransactions->first()->amount))
                    ->where('currency_code', $debitCardTransactions->first()->currency_code)
                    ->etc()
            );
    }

    public function testCustomerCannotSeeADebitCardTransactionAttachedToOtherCustomerDebitCard()
    {
        // get /debit-card-transactions/{debitCardTransaction}
        // create another user
        $anotherUser = User::factory()->create();

        $debitCard = DebitCard::factory()->active()->create([
            'user_id' => $anotherUser->id
        ]);

        // check if user is authenticated
        $this->assertAuthenticatedAs($this->user);

        // create some transactions for the above debit card
        $debitCardTransactions =  DebitCardTransaction::factory()->count(10)->create([
            'debit_card_id' => $debitCard->id
        ]);

        $this->getJson('api/debit-card-transaction/' . $debitCardTransactions->first()->id)
            ->assertForbidden();
    }

    // Extra bonus for extra tests :)
}
