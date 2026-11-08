<?php

namespace Tests\Feature;

use App\Modules\ExpertPanel\Actions\FundingAwardUpsert;
use App\Modules\ExpertPanel\Events\FundingAwardCreated;
use App\Modules\ExpertPanel\Events\FundingAwardUpdated;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\Funding\Models\FundingSource;
use App\Modules\Funding\Models\FundingType;
use App\Modules\Person\Models\Country;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class FundingAwardsTest extends TestCase
{
    public function test_deleting_country_clears_source_country_without_deleting_source(): void
    {
        $type = FundingType::create(['name' => 'Government']);
        $country = Country::create(['name' => 'Test country']);
        $source = FundingSource::create(['name' => 'NIH', 'funding_type_id' => $type->id, 'country_id' => $country->id]);
        $country->delete();
        $this->assertNull($source->fresh()->country_id);
    }

    public function test_award_validation_limits_contacts_and_validates_source_references(): void
    {
        $request = new \Lorisleiva\Actions\ActionRequest();
        $rules = (new FundingAwardUpsert())->rules($request);
        unset($rules['funding_source_ids'], $rules['funding_source_ids.*']);
        $validator = validator(['rep_contacts' => array_fill(0, 4, [
            'name' => 'Jane', 'publish' => 'invalid',
            'funding_sources' => [['uuid' => 'invalid', 'name' => []]],
        ])], $rules);
        $this->assertTrue($validator->fails());
        foreach (['rep_contacts', 'rep_contacts.0.publish', 'rep_contacts.0.funding_sources.0.uuid', 'rep_contacts.0.funding_sources.0.name'] as $key) {
            $this->assertTrue($validator->errors()->has($key), $key);
        }
        $this->assertFalse(validator(['rep_contacts' => [['name' => 'Legacy']]], $rules)->fails());
    }

    public function test_source_location_can_be_created_updated_cleared_and_validated(): void
    {
        $this->login(permissions: ['funding-sources-manage']);
        $type = FundingType::create(['name' => 'Government']);
        $country = Country::create(['name' => 'United States']);
        $response = $this->postJson('/api/funding-sources', [
            'name' => 'NIH', 'funding_type_id' => $type->id,
            'city' => 'Bethesda', 'country_id' => $country->id,
        ])->assertSuccessful()->assertJsonPath('city', 'Bethesda')->assertJsonPath('country.name', 'United States');
        $id = $response->json('id');
        $this->postJson('/api/funding-sources/'.$id, ['city' => null, 'country_id' => null])
            ->assertSuccessful()->assertJsonPath('city', null)->assertJsonPath('country', null);
        $this->postJson('/api/funding-sources/'.$id, ['country_id' => 999999, 'city' => str_repeat('x', 256)])
            ->assertUnprocessable()->assertJsonValidationErrors(['country_id', 'city']);
    }

    public function test_contacts_keep_only_selected_source_uuid_and_name_and_boolean_publish(): void
    {
        Event::fake([FundingAwardCreated::class, FundingAwardUpdated::class]);
        $panel = ExpertPanel::factory()->gcep()->create();
        $type = FundingType::create(['name' => 'Government']);
        $country = Country::create(['name' => 'United States']);
        $a = FundingSource::create(['name' => 'NIH', 'funding_type_id' => $type->id, 'city' => 'Bethesda', 'country_id' => $country->id]);
        $b = FundingSource::create(['name' => 'Other', 'funding_type_id' => $type->id]);
        $award = FundingAwardUpsert::run($panel, [
            'funding_source_ids' => [$a->id, $b->id],
            'rep_contacts' => [
                ['name' => 'Jane', 'publish' => true, 'funding_sources' => [
                    ['uuid' => $a->uuid, 'name' => 'Untrusted name', 'id' => $a->id],
                    ['uuid' => $b->uuid, 'name' => $b->name],
                ]],
                ['name' => 'Legacy contact', 'publish' => false],
            ],
        ]);
        $this->assertCount(2, $award->fundingSources);
        $this->assertCount(2, $award->rep_contacts[0]['funding_sources']);
        $this->assertSame(true, $award->rep_contacts[0]['publish']);
        $this->assertSame(false, $award->rep_contacts[1]['publish']);
        $this->assertSame([], $award->rep_contacts[1]['funding_sources']);
        $this->assertSame(['uuid' => $a->uuid, 'name' => 'NIH'], $award->rep_contacts[0]['funding_sources'][0]);

        // A source-only update must also prune the persisted contacts.
        $award = FundingAwardUpsert::run($panel, ['funding_source_ids' => [$a->id]], $award);
        $this->assertSame([['uuid' => $a->uuid, 'name' => 'NIH']], $award->rep_contacts[0]['funding_sources']);
        $listed = (new \App\Modules\ExpertPanel\Http\Controllers\Api\FundingAwardController())->index($panel);
        $this->assertSame($award->rep_contacts, $listed->first()->rep_contacts);
        $award = FundingAwardUpsert::run($panel, ['rep_contacts' => $award->rep_contacts], $award);
        $this->assertCount(1, $award->rep_contacts[0]['funding_sources']);
        $payload = $award->toExchangePayload();
        $this->assertSame('Bethesda', $payload['funding_sources'][0]['city']);
        $this->assertSame('United States', $payload['funding_sources'][0]['country']);
        $this->assertSame($award->rep_contacts, $payload['rep_contacts']);
        Event::assertDispatched(FundingAwardCreated::class);
        Event::assertDispatched(FundingAwardUpdated::class);
    }
}
