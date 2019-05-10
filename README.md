# Laravel workflow [![Build Status](https://travis-ci.org/brexis/laravel-workflow.svg?branch=1.1.2)](https://travis-ci.org/zerodahero/laravel-workflow)

This is a fork from [brexis/laravel-workflow](https://github.com/brexis/laravel-workflow). My current needs for this package are a bit more bleeding-edge than seem to be maintainable by the other packages. Massive kudos to brexis for the original work and adaptation on this.

Use the Symfony Workflow component in Laravel

### Installation

    composer require zerodahero/laravel-workflow

#### Right now, I've bumped the dependencies up to active PHP version (>=7.2), so in Laravel >= 5.5, use the package auto-discovery
#### For laravel <= 5.4 (Deprecated)

Add a ServiceProvider to your providers array in `config/app.php`:

```php
<?php

'providers' => [
    ...
    ZeroDaHero\LaravelWorkflow\WorkflowServiceProvider::class,

]
```

Add the `Workflow` facade to your facades array:

```php
<?php
    ...
    'Workflow' => ZeroDaHero\LaravelWorkflow\Facades\WorkflowFacade::class,
```

### Configuration

Publish the config file

```
    php artisan vendor:publish --provider="ZeroDaHero\LaravelWorkflow\WorkflowServiceProvider"
```

Configure your workflow in `config/workflow.php`

```php
<?php

return [
    'straight'   => [
        'type'          => 'workflow', // or 'state_machine'
        'marking_store' => [
            'type'      => 'multiple_state',
            'arguments' => ['currentPlace']
        ],
        'supports'      => ['App\BlogPost'],
        'places'        => ['draft', 'review', 'rejected', 'published'],
        'transitions'   => [
            'to_review' => [
                'from' => 'draft',
                'to'   => 'review'
            ],
            'publish' => [
                'from' => 'review',
                'to'   => 'published'
            ],
            'reject' => [
                'from' => 'review',
                'to'   => 'rejected'
            ]
        ],
    ]
];
```

You may also add in metadata, similar to the Symfony implementation (note: it is not collected the same way as Symfony's implementation, but should work the same. Please open a pull request or issue if that's not the case.)

```php
<?php

return [
    'straight'   => [
        'type'          => 'workflow', // or 'state_machine'
        'metadata'      => [
            'title' => 'Blog Publishing Workflow',
        ],
        'marking_store' => [
            'type'      => 'multiple_state',
            'arguments' => ['currentPlace']
        ],
        'supports'      => ['App\BlogPost'],
        'places'        => [
            'draft', => [
                'metadata' => [
                    'max_num_of_words' => 500,
                ]
            ]
            'review',
            'rejected',
            'published'
        ],
        'transitions'   => [
            'to_review' => [
                'from' => 'draft',
                'to'   => 'review',
                'metadata' => [
                    'priority' => 0.5,
                ]
            ],
            'publish' => [
                'from' => 'review',
                'to'   => 'published'
            ],
            'reject' => [
                'from' => 'review',
                'to'   => 'rejected'
            ]
        ],
    ]
];
```

Use the `WorkflowTrait` inside supported classes

```php
<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use ZeroDaHero\LaravelWorkflow\Traits\WorkflowTrait;

class BlogPost extends Model
{
  use WorkflowTrait;

}
```
### Usage

```php
<?php

use App\BlogPost;
use Workflow;

$post = BlogPost::find(1);
$workflow = Workflow::get($post);
// if more than one workflow is defined for the BlogPost class
$workflow = Workflow::get($post, $workflowName);

$workflow->can($post, 'publish'); // False
$workflow->can($post, 'to_review'); // True
$transitions = $workflow->getEnabledTransitions($post);

// Apply a transition
$workflow->apply($post, 'to_review');
$post->save(); // Don't forget to persist the state

// Using the WorkflowTrait
$post->workflow_can('publish'); // True
$post->workflow_can('to_review'); // False

// Get the post transitions
foreach ($post->workflow_transitions() as $transition) {
    echo $transition->getName();
}

// Apply a transition
$post->workflow_apply('publish');
$post->save();
```

### Use the events
This package provides a list of events fired during a transition

```php
    ZeroDaHero\LaravelWorkflow\Events\Guard
    ZeroDaHero\LaravelWorkflow\Events\Leave
    ZeroDaHero\LaravelWorkflow\Events\Transition
    ZeroDaHero\LaravelWorkflow\Events\Enter
    ZeroDaHero\LaravelWorkflow\Events\Entered
```

You can subscribe to an event

```php
<?php

namespace App\Listeners;

use ZeroDaHero\LaravelWorkflow\Events\GuardEvent;

class BlogPostWorkflowSubscriber
{
    /**
     * Handle workflow guard events.
     */
    public function onGuard(GuardEvent $event) {
        /** Symfony\Component\Workflow\Event\GuardEvent */
        $originalEvent = $event->getOriginalEvent();

        /** @var App\BlogPost $post */
        $post = $originalEvent->getSubject();
        $title = $post->title;

        if (empty($title)) {
            // Posts with no title should not be allowed
            $originalEvent->setBlocked(true);
        }
    }

    /**
     * Handle workflow leave event.
     */
    public function onLeave($event) {}

    /**
     * Handle workflow transition event.
     */
    public function onTransition($event) {}

    /**
     * Handle workflow enter event.
     */
    public function onEnter($event) {}

    /**
     * Handle workflow entered event.
     */
    public function onEntered($event) {}

    /**
     * Register the listeners for the subscriber.
     *
     * @param  Illuminate\Events\Dispatcher  $events
     */
    public function subscribe($events)
    {
        $events->listen(
            'ZeroDaHero\LaravelWorkflow\Events\GuardEvent',
            'App\Listeners\BlogPostWorkflowSubscriber@onGuard'
        );

        $events->listen(
            'ZeroDaHero\LaravelWorkflow\Events\LeaveEvent',
            'App\Listeners\BlogPostWorkflowSubscriber@onLeave'
        );

        $events->listen(
            'ZeroDaHero\LaravelWorkflow\Events\TransitionEvent',
            'App\Listeners\BlogPostWorkflowSubscriber@onTransition'
        );

        $events->listen(
            'ZeroDaHero\LaravelWorkflow\Events\EnterEvent',
            'App\Listeners\BlogPostWorkflowSubscriber@onEnter'
        );

        $events->listen(
            'ZeroDaHero\LaravelWorkflow\Events\EnteredEvent',
            'App\Listeners\BlogPostWorkflowSubscriber@onEntered'
        );
    }

}
```

You are also welcome to use [Symfony's dot syntax style of event emission](https://symfony.com/doc/current/workflow.html#using-events). Note that the events will receive the Symfony events then, not the ones through this package.

```php
<?php

namespace App\Listeners;

use ZeroDaHero\LaravelWorkflow\Events\GuardEvent;

class BlogPostWorkflowSubscriber
{
    // ...

    /**
     * Register the listeners for the subscriber.
     *
     * @param  Illuminate\Events\Dispatcher  $events
     */
    public function subscribe($events)
    {
        // can use any of the three formats:
        // workflow.guard
        // workflow.[workflow name].guard
        // workflow.[workflow name].guard.[transition name]
        $events->listen(
            'workflow.straight.guard',
            'App\Listeners\BlogPostWorkflowSubscriber@onGuard'
        );        

        // workflow.leave
        // workflow.[workflow name].leave
        // workflow.[workflow name].leave.[place name]
        $events->listen(
            'workflow.straight.leave',
            'App\Listeners\BlogPostWorkflowSubscriber@onLeave'
        );

        // workflow.transition
        // workflow.[workflow name].transition
        // workflow.[workflow name].transition.[transition name]
        $events->listen(
            'workflow.straight.transition',
            'App\Listeners\BlogPostWorkflowSubscriber@onTransition'
        );

        // workflow.enter
        // workflow.[workflow name].enter
        // workflow.[workflow name].enter.[place name]
        $events->listen(
            'workflow.straight.enter',
            'App\Listeners\BlogPostWorkflowSubscriber@onEnter'
        );

        // workflow.entered
        // workflow.[workflow name].entered
        // workflow.[workflow name].entered.[place name]
        $events->listen(
            'workflow.straight.entered',
            'App\Listeners\BlogPostWorkflowSubscriber@onEntered'
        );

        // workflow.completed
        // workflow.[workflow name].completed
        // workflow.[workflow name].completed.[transition name]
        $events->listen(
            'workflow.straight.completed',
            'App\Listeners\BlogPostWorkflowSubscriber@onCompleted'
        );

        // workflow.announce
        // workflow.[workflow name].announce
        // workflow.[workflow name].announce.[transition name]
        $events->listen(
            'workflow.straight.announce',
            'App\Listeners\BlogPostWorkflowSubscriber@onAnnounce'
        );
    }
}
```

### Dump Workflows
Symfony workflow uses GraphvizDumper to create the workflow image. You may need to install the `dot` command of [Graphviz](http://www.graphviz.org/)

    php artisan workflow:dump workflow_name --class App\\BlogPost

You can change the image format with the `--format` option. By default the format is png.

    php artisan workflow:dump workflow_name --format=jpg

### Use in tracking mode

If you are loading workflow definitions through some dynamic means (perhaps via DB), you'll most likely want to turn on registry tracking. This will enable you to see what has been loaded, to prevent or ignore duplicate workflow definitions.

Set `track_loaded` to `true` in the `workflow_registry.php` config file.

```php
<?php

return [

    /**
     * When set to true, the registry will track the workflows that have been loaded.
     * This is useful when you're loading from a DB, or just loading outside of the
     * main config files.
     */
    'track_loaded' => false,

    /**
     * Only used when track_loaded = true
     * 
     * When set to true, a registering a duplicate workflow will be ignored (will not load the new definition)
     * When set to false, a duplicate workflow will throw a DuplicateWorkflowException
     */
    'ignore_duplicates' => false,

];
```

You can dynamically load a workflow by using the `addFromArray` method

```php
<?php

    /**
     * Load the workflow type definition into the registry
     */
    protected function loadWorkflow()
    {
        $registry = app()->make('workflow');
        $workflowName = 'straight';
        $workflowDefinition = [
            // Workflow definition here
            // (same format as config/symfony docs)
        ];

        $registry->addFromArray($workflowName, $workflowDefinition);

        // or if catching duplicates

        try {
            $registry->addFromArray($workflowName, $workflowDefinition);
        } catch (DuplicateWorkflowException $e) {
            // already loaded 
        }
    }
```

