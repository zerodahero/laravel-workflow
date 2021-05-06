<?php

namespace Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use ZeroDaHero\LaravelWorkflow\Traits\WorkflowTrait;

class TestEloquentModel extends Model
{
    use WorkflowTrait;

    public $marking = 'here';
}
