<?php
declare(strict_types=1);

namespace Rent\Application\Property\Controller;

use Common\User\Domain\User;
use Common\User\Infrastructure\DataFixtures\Factory\UserFactory;
use Rent\Domain\Property\Building;
use Rent\Infrastructure\DataFixtures\Factory\BuildingFactory;
use Rent\Infrastructure\DataFixtures\Factory\PortfolioFactory;
use Test\KernelTestUtilTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;

class BrowserTest extends KernelTestCase
{
    use HasBrowser;
    use Factories;
    use KernelTestUtilTrait;

    /**
     * @return array{0:User,1:Building,2:string}
     */
    public function testAddAttentionToProperty() : array
    {
        /** @var User $user */
        $user = UserFactory::createOne()->object();
        $portfolio = PortfolioFactory::createOne(['owner' => $user]);
        $building = BuildingFactory::createOne(['portfolio' => $portfolio])->object();

        $angryMessage = 'angry message content';

        $this
            ->browser([], ['HTTP_HOST' => 'rent.local'])
            ->actingAs($user, 'rent')
            ->visit('/add-attention/' . $building->getId())
            ->assertSuccessful()
            ->assertContains('Message')
            ->fillField('attention_form[message]', $angryMessage)
            ->clickAndIntercept('button');

        return [$user, $building, $angryMessage];
    }

    /**
     * Tests wizard to pay for transaction created by the previous test.
     *
     * @depends testAddAttentionToProperty
     *
     * @param array{0:User,1:Building,2:string} $previousTestData
     */
    public function testAttentionAppeared(array $previousTestData) : void
    {
        [$user, $building, $angryMessage] = $previousTestData;

        $this
            ->browser([], ['HTTP_HOST' => 'rent.local'])
            ->actingAs($user, 'rent')
            ->visit('/properties/' . $building->getId() . '/general')
            ->assertSuccessful()
            ->assertContains($angryMessage)
            ->visit('/properties/' . $building->getId() . '/attention-remove')
            ->assertSuccessful()
            ->visit('/properties/' . $building->getId() . '/general')
            ->assertSuccessful()
            ->assertNotContains('Dismiss attention')
            ->visit('/notes/rent_building/entity/' . $building->getId())
            ->assertSuccessful()
            ->assertSee('Attention has been successfully dismissed : ' . $angryMessage);
    }
}
