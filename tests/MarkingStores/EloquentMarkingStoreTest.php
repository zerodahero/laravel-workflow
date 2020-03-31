<?php

namespace Tests\MarkingStores;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Workflow\Marking;
use Tests\Fixtures\TestModel;
use Tests\Fixtures\TestModelMutator;
use ZeroDaHero\LaravelWorkflow\MarkingStores\EloquentMarkingStore;

class EloquentMarkingStoreTest extends TestCase
{
    private $faker;

    protected function setUp(): void
    {
        $this->faker = \Faker\Factory::create();
    }

    /**
     * @test
     * @dataProvider subjectDataProvider
     */
    public function testSingleStateMarking($subject)
    {
        $store = new EloquentMarkingStore(true, 'marking');

        $subject->attributes['marking'] = $this->faker->unique()->word;

        $marking = $store->getMarking($subject);
        $this->assertInstanceOf(Marking::class, $marking);
        $this->assertEquals([$subject->attributes['marking'] => 1], $marking->getPlaces());

        $newMarking = $this->faker->unique()->word;
        $store->setMarking($subject, new Marking([$newMarking => 1]));
        $setMarking = $store->getMarking($subject);
        $this->assertInstanceOf(Marking::class, $setMarking);
        $this->assertEquals([$newMarking => 1], $setMarking->getPlaces());
    }

    public function subjectDataProvider()
    {
        return [
            [new TestModel()],
            [new TestModelMutator()]
        ];
    }

    /**
     * @test
     * @dataProvider subjectDataProvider
     */
    public function testMultiStateMarking($subject)
    {
        $store = new EloquentMarkingStore(false, 'marking');

        $subject->attributes['marking'] = array_combine($this->faker->words(3, false), [1,1,1]);

        $marking = $store->getMarking($subject);
        $this->assertInstanceOf(Marking::class, $marking);
        $this->assertEquals($subject->attributes['marking'], $marking->getPlaces());

        $newMarking = array_combine($this->faker->words(3, false), [1,1,1]);
        $store->setMarking($subject, new Marking($newMarking));
        $setMarking = $store->getMarking($subject);
        $this->assertInstanceOf(Marking::class, $setMarking);
        $this->assertEquals($newMarking, $setMarking->getPlaces());
    }
}
