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
     * @dataProvider providesSubjects
     *
     * @param mixed $subject
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

    public function providesSubjects()
    {
        return [
            [new TestModel()],
            [new TestModelMutator()],
        ];
    }

    /**
     * @test
     * @dataProvider providesSubjects
     *
     * @param mixed $subject
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

    /**
     * @test
     * @dataProvider providesTypeSafeScenarios
     *
     * @param mixed $markingValue
     * @param mixed $expectedMarkingValue
     * @param mixed $expectedMarkingKey
     */
    public function testTypeSafeMarkings($markingValue, $expectedMarkingKey)
    {
        $store = new EloquentMarkingStore(true, 'marking');

        $subject = new TestModel();

        $subject->attributes['marking'] = $markingValue;

        $marking = $store->getMarking($subject);
        $this->assertInstanceOf(Marking::class, $marking);
        $this->assertEquals([$expectedMarkingKey => 1], $marking->getPlaces());
    }

    public function providesTypeSafeScenarios()
    {
        return [
            [0, '0'],
            ['0', '0'],
            [false, ''], // ick
            ['false', 'false'],
        ];
    }
}
